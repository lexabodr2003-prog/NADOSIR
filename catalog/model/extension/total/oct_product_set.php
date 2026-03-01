<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelExtensionTotalOctProductSet extends Model {

    public function getTotal($total_data) {
        $this->load->language('extension/total/oct_product_set');

        $total = &$total_data['total'];
        $taxes = &$total_data['taxes'];
        $totals = &$total_data['totals'];

        $this->calculateSetDiscounts($totals, $total, $taxes);
    }

    private function calculateSetDiscounts(&$totals, &$total, &$taxes) {
        $total_discount = 0;
        $cart_products = $this->cart->getProducts();
        $cart_product_ids = array_column($cart_products, 'quantity', 'product_id');

        if ($this->isCartEmpty($cart_products)) {
            return;
        }

        $product_sets = $this->getProductSetsByProductIds(array_keys($cart_product_ids));
        $set_ids = array_keys($product_sets);

        if ($this->isProductSetsEmpty($product_sets)) {
            return;
        }

        $set_products = $this->getProductsBySetIds($set_ids);

        foreach ($set_ids as $set_id) {
            if ($this->isCompleteSet($set_products[$set_id], $cart_product_ids)) {
                $this->processSetDiscount($set_products[$set_id], $cart_products, $total_discount, $taxes, $set_id);
            }
        }

        if ($total_discount > 0) {
            $this->applyTotalDiscount($total, $totals, $total_discount);
        }
    }

    private function isCompleteSet($set_product_ids_quantities, $cart_product_ids) {
        foreach ($set_product_ids_quantities as $item) {
            $product_id = $item['product_id'];
            $required_quantity = $item['quantity'];

            if (!isset($cart_product_ids[$product_id]) || $cart_product_ids[$product_id] < $required_quantity) {
                return false;
            }
        }
        return true;
    }

    private function applyTotalDiscount(&$total, &$totals, $total_discount) {
        $total -= (float)$total_discount;
        $totals[] = array(
            'code'       => 'oct_product_set',
            'title'      => $this->language->get('text_oct_product_set_discount'),
            'value'      => -$total_discount,
            'sort_order' => $this->config->get('total_oct_product_set_sort_order')
        );
    }

    private function processSetDiscount($set_products, $cart_products, &$total_discount, &$taxes, $set_id) {
        foreach ($set_products as $set_product_id) {
            $cart_product_info = $this->getProductFromCart($set_product_id, $cart_products);
            if ($cart_product_info) {
                $relation_data = $this->getProductSetRelationData($set_product_id, $set_id);
                if ($relation_data) {
                    $discount_per_unit = $this->calculateProductDiscount($cart_product_info, $relation_data);
                    if ($discount_per_unit > 0) {
                        $this->applyDiscount($total_discount, $cart_product_info, $discount_per_unit, $taxes);
                    }
                }
            }
        }
    }

    private function applyDiscount(&$total_discount, $cart_product_info, $discount_per_unit, &$taxes) {
        $total_discount += $discount_per_unit * $cart_product_info['quantity'];
        if ($cart_product_info['tax_class_id']) {
            $tax_amount = $this->tax->getTax($total_discount, $cart_product_info['tax_class_id']);
            if ($tax_amount) {
                $taxes[$cart_product_info['tax_class_id']] = (isset($taxes[$cart_product_info['tax_class_id']])) ? $taxes[$cart_product_info['tax_class_id']] - $tax_amount : $tax_amount;
            }
        }
    }
    
    private function getProductFromCart($product_id, $cart_products) {
        foreach ($cart_products as $product) {
            if ($product['product_id'] == $product_id['product_id']) {
                return $product;
            }
        }
        return null;
    }

    private function getProductSetsByProductIds($product_ids) {
        $product_set_data = array();

        if (!empty($product_ids)) {


        $product_ids = array_map('intval', $product_ids);
        $product_ids_string = implode(',', $product_ids);

        $query = $this->db->query("SELECT ps.*, psr.product_id FROM " . DB_PREFIX . "oct_product_set ps
            INNER JOIN " . DB_PREFIX . "oct_product_set_relation psr ON ps.product_set_id = psr.product_set_id
            WHERE ps.status = 1 AND psr.product_id IN (" . $product_ids_string . ")
            AND ps.date_start <= NOW() AND (ps.date_end = '0000-00-00' OR ps.date_end >= NOW())");

        foreach ($query->rows as $result) {
            $product_set_data[$result['product_set_id']]['products'][] = $result['product_id'];
            $product_set_data[$result['product_set_id']]['details'] = [
                'product_set_id' => $result['product_set_id'],
                'name'           => $result['name'],
                'sort_order'     => $result['sort_order'],
                'status'         => $result['status'],
                'date_added'     => $result['date_added'],
                'date_start'     => $result['date_start'],
                'date_end'       => $result['date_end']
            ];
        }
    }   

        return $product_set_data;
    }

    private function getProductSetRelationData($product_id, $set_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "oct_product_set_relation WHERE product_set_id = '" . (int)$set_id . "' AND product_id = '" . (int)$product_id['product_id'] . "'");
        return $query->row;
    }

    private function calculateProductDiscount($product, $relation_data) {
        $discount_per_unit = 0.0;
    
        if ($relation_data['discount_type'] == 'fixed') {
            $discount_per_unit = (float)$relation_data['discount_value'];
        } elseif ($relation_data['discount_type'] == 'percentage') {
            $discount_per_unit = ($product['price'] * (float)$relation_data['discount_value']) / 100.0;
        }
    
        if ($discount_per_unit > $product['price']) {
            $discount_per_unit = $product['price'];
        }
    
        return $discount_per_unit;
    }    

    private function getProductsBySetIds($set_ids) {
        $product_data = array();
        $set_ids = array_map('intval', $set_ids);
        $set_ids_string = implode(',', $set_ids);

        $query = $this->db->query("SELECT product_id, product_set_id, product_quantity FROM " . DB_PREFIX . "oct_product_set_relation 
            WHERE product_set_id IN (" . $set_ids_string . ")");

        foreach ($query->rows as $result) {
            $product_data[$result['product_set_id']][] = [
                'product_id' => $result['product_id'],
                'quantity'   => $result['product_quantity']
            ];
        }

        return $product_data;
    }

    private function isCartEmpty($cart_products) {
        return empty($cart_products);
    }
    
    private function isProductSetsEmpty($product_sets) {
        return empty($product_sets);
    }
}