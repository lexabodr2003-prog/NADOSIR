<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsMicrodata extends Controller {

    public function index(&$route, &$data) {
        $controllers = [
            'information/information',
            'octemplates/blog/oct_blogarticle',
            'octemplates/blog/oct_blogcategory',
            'octemplates/module/oct_sreview_reviews',
            'product/category',
            'product/manufacturer_list',
            'product/manufacturer_info',
            'product/product',
            'product/special'
        ];

        if (in_array($route, $controllers) || isset($data['breadcrumbs'])) {
            $oct_deals_data = $this->config->get('theme_oct_deals_data');
            
            if (!isset($data['oct_deals_data'])) {
                $data['oct_deals_data'] = [];
            }
            
            $data['oct_deals_data']['micro'] = $oct_deals_data['micro'] ?? null;

            if ($route == 'product/product' && isset($data['oct_deals_data']['micro']) && isset($data['product_id'])) {
                $product_id = (int)$data['product_id'];
                
                if ($product_id > 0) {
                    $data['oct_micro_heading_title'] = isset($data['heading_title']) ? $this->sanitizeDescription($data['heading_title']) : '';
                    $data['oct_product_categories'] = $this->getProductCategoriesName($product_id);
                    $data['oct_special_microdata'] = false;
                    $data['oct_price_currency'] = $this->session->data['currency'] ?? $this->config->get('config_currency');

                    $org_data = $this->config->get('theme_oct_deals_org_data');
                    $data['oct_shipping_cost'] = isset($org_data['shipping_cost']) ? (float)$org_data['shipping_cost'] : 0;
                    $data['oct_processing_time'] = isset($org_data['processing_time']) ? (int)$org_data['processing_time'] : 1;
                    $data['oct_delivery_time'] = isset($org_data['delivery_time']) ? (int)$org_data['delivery_time'] : 2;
                    $data['oct_return_time'] = isset($org_data['return_time']) ? (int)$org_data['return_time'] : 14;

                    $area_served = $org_data['area_served'] ?? '';
                    if (is_string($area_served) && !empty($area_served)) {
                        $data['oct_area_served'] = array_map('trim', explode(',', $area_served));
                    } else {
                        $data['oct_area_served'] = [];
                    }

                    if (!empty($data['special'])) {
                        $data['oct_special_microdata'] = $this->getNumericPrice($data['special']);
                    } elseif (!empty($data['price'])) {
                        $data['oct_price_microdata'] = $this->getNumericPrice($data['price']);
                    }

                    if (isset($data['description'])) {
                        $data['oct_description_microdata'] = $this->sanitizeDescription($data['description']);
                    }

                    $data['oct_reviews_all'] = $this->getReviewsByProductId($product_id);
                    $data['config_name'] = $this->config->get('config_name');
                }
            }
        }
    }

    private function getNumericPrice($priceString) {
        if (empty($priceString)) {
            return '0.00';
        }
        
        $price = str_replace(' ', '', $priceString);
        $price = preg_replace('/[^0-9\.,]/', '', rtrim($price, '.'));
        $price = str_replace(',', '.', $price);
        
        return $price;
    }

    private function getProductCategoriesName($product_id) {
        $product_id = (int)$product_id;
        if ($product_id <= 0) {
            return '';
        }
        
        $oct_product_categories = $this->model_catalog_product->getCategories($product_id);
        if (empty($oct_product_categories)) {
            return '';
        }
        
        $oct_cat_info = [];
        foreach ($oct_product_categories as $product_category) {
            $category = $this->model_catalog_category->getCategory((int)$product_category['category_id']);
            if ($category && !empty($category['name'])) {
                $oct_cat_info[] = $category['name'];
            }
        }

        return implode(', ', $oct_cat_info);
    }
    
    private function sanitizeDescription($description) {
        if (empty($description)) {
            return '';
        }
        
        $search = ['<br>', '<br/>', '<br />', '</p>', '</h3>', '</h2>', '</h1>'];
        $description = str_ireplace($search, "\n", $description);

        $clean = strip_tags($description);
        $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');

        $clean = str_replace(["\r", "\n"], ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        if (mb_strlen($clean) > 500) {
            $clean = mb_substr($clean, 0, 500);
            $lastDotPosition = mb_strrpos($clean, '.');
            if ($lastDotPosition !== false && $lastDotPosition > 400) {
                $clean = mb_substr($clean, 0, $lastDotPosition + 1);
            } else {
                $clean = mb_substr($clean, 0, 497) . '...';
            }
        }

        return $clean;
    }
    
    private function getReviewsByProductId($product_id) {
        $product_id = (int)$product_id;
        if ($product_id <= 0) {
            return [];
        }
        
        $oct_reviews_all = $this->model_catalog_review->getReviewsByProductId($product_id);
        
        if (empty($oct_reviews_all)) {
            return [];
        }

        return array_map(function ($result) {
            return [
                'author'     => !empty($result['author']) ? strip_tags($result['author']) : 'Анонім',
                'text'       => $this->sanitizeDescription($result['text']),
                'rating'     => max(1, min(5, (int)$result['rating'])),
                'date_added' => date('Y-m-d', strtotime($result['date_added']))
            ];
        }, $oct_reviews_all);
    }
    
}