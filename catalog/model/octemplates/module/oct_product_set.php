<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOCTemplatesModuleOctProductSet extends Model {

    public function getProductSet($filters = array()) {

        $store_id = $this->config->get('config_store_id');
        $user_group_id = $this->customer->isLogged() ? $this->customer->getGroupId() : $this->config->get('config_customer_group_id');

        $sql = "
            SELECT 
            ps.product_set_id AS product_set_id,
            ps.name AS set_name,
            ps.sort_order AS set_sort_order,
            psr.product_id AS product_id,
            psr.product_quantity AS product_quantity,
            psr.discount_type AS discount_type,
            psr.discount_value AS discount_value,
            psr.sort_order AS product_sort_order,
            pd.name AS name,
            p.*,
            COALESCE(ps3.price, 0) AS special_price
            FROM " . DB_PREFIX . "oct_product_set_relation psr
            JOIN " . DB_PREFIX . "oct_product_set ps ON psr.product_set_id = ps.product_set_id
            JOIN " . DB_PREFIX . "product p ON psr.product_id = p.product_id
            JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id
            JOIN " . DB_PREFIX . "oct_product_set_to_store ps2s ON ps.product_set_id = ps2s.product_set_id
            JOIN " . DB_PREFIX . "oct_product_set_to_user_group ps2ug ON ps.product_set_id = ps2ug.product_set_id
            LEFT JOIN " . DB_PREFIX . "product_special ps3 ON p.product_id = ps3.product_id 
                AND ps3.customer_group_id = '" . (int)$user_group_id . "' 
                AND (ps3.date_start = '0000-00-00' OR ps3.date_start <= NOW()) 
                AND (ps3.date_end = '0000-00-00' OR ps3.date_end >= NOW())
            WHERE psr.product_set_id IN (
                SELECT product_set_id 
                FROM " . DB_PREFIX . "oct_product_set_to_product
        ";

        if (!empty($filters['product_id'])) {
            $sql .= " WHERE product_id = '" . (int)$filters['product_id'] . "'";
        }

        $sql .= ") AND ps2s.store_id = '" . (int)$store_id . "'
                AND ps2ug.user_group_id = '" . (int)$user_group_id . "'
                AND ps.status = '1'
                AND ps.date_start <= NOW()
                AND (ps.date_end = '0000-00-00 00:00:00' OR ps.date_end >= NOW())
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";

        if (!empty($filters['category_id'])) {
            $sql .= " AND EXISTS (
                SELECT * FROM " . DB_PREFIX . "oct_product_set_to_category ps2c
                WHERE ps.product_set_id = ps2c.product_set_id
                AND ps2c.category_id = '" . (int)$filters['category_id'] . "'
            )";
        }

        if (!empty($filters['manufacturer_id'])) {
            $sql .= " AND EXISTS (
                SELECT * FROM " . DB_PREFIX . "oct_product_set_to_manufacturer ps2m
                WHERE ps.product_set_id = ps2m.product_set_id
                AND ps2m.manufacturer_id = '" . (int)$filters['manufacturer_id'] . "'
            )";
        }

        $sql .= "ORDER BY ";

        if (!empty($filters['product_id'])) {
            $sql .= " CASE WHEN psr.product_id = '" . (int)$filters['product_id'] . "' THEN 0 ELSE 1 END, ";
        }

        $sql .= "ps.sort_order, 
        psr.sort_order";

        $query = $this->db->query($sql);

        $productSets = [];
        foreach ($query->rows as $row) {
            $productSets[$row['product_set_id']]['products'][] = $row;
            $productSets[$row['product_set_id']]['set_details'] = [
                'product_set_id' => $row['product_set_id'],
                'set_name' => $row['set_name'],
                'set_sort_order' => $row['set_sort_order']
            ];
        }
    
        foreach ($productSets as $setId => $set) {
            foreach ($set['products'] as $product) {
                if ($product['quantity'] <= 0) {
                    unset($productSets[$setId]);
                    break;
                }
            }
        }
    
        $filteredSets = [];
        foreach ($productSets as $set) {
            $filteredSets = array_merge($filteredSets, $set['products']);
        }
    
        return $filteredSets;
    }    
}