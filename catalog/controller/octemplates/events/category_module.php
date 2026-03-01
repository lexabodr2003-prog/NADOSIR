<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsCategoryModule extends Controller {

    public function index(&$route, &$data) {
        $path = $this->request->get['path'] ?? '';
        $parts = explode('_', (string)$path);
        
        $data = $this->setCategoryData($parts, $data);
        $data['position'] = $setting['position'] ?? '';
    
        $oct_webP = $this->setWebP();
    
        $cacheKey = $this->generateCacheKey($oct_webP);
        $result_all_categories = $this->cache->get($cacheKey);
    
        if (!$result_all_categories) {
            $result_all_categories = $this->generateAllCategories($oct_webP);
            $this->cache->set($cacheKey, $result_all_categories);
        }
    
        $data['categories'] = $result_all_categories;
    }
    
    private function setCategoryData($parts, $data) {
        $keys = ['category_id', 'child_id', 'ch_id', 'child3_id'];
    
        foreach ($keys as $index => $key) {
            $data[$key] = $parts[$index] ?? 0;
        }
    
        return $data;
    }
    
    private function setWebP() {
        $webP = isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'webp');
        return ($webP ? 1 : 0) . '-' . $this->session->data['currency'];
    }
    
    private function generateCacheKey($oct_webP) {
        $configParams = ['config_language_id', 'config_store_id', 'config_customer_group_id'];
        $keyParts = array_map(function($param) {
            return (int)$this->config->get($param);
        }, $configParams);
    
        $keyParts[] = $oct_webP;
    
        return 'octemplates.module_category.' . implode('.', $keyParts);
    }
    
    private function generateAllCategories() {
        $categories = $this->model_catalog_category->getCategories(0);
        $result_all_categories = [];
    
        foreach ($categories as $category) {
            $children_data = $this->getChildrenData($category);
            $filter_data = ['filter_category_id'  => $category['category_id'], 'filter_sub_category' => true];
    
            $result_all_categories[] = [
                'category_id' => $category['category_id'],
                'name'        => $category['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                'children'    => $children_data,
                'href'        => $this->url->link('product/category', 'path=' . $category['category_id'])
            ];
        }
    
        return $result_all_categories;
    }
    
    private function getChildrenData($category) {
        $children = $this->model_catalog_category->getCategories($category['category_id']);
        $children_data = [];
    
        foreach ($children as $child) {
            $filter_data = ['filter_category_id' => $child['category_id'], 'filter_sub_category' => true];
    
            $children_data_2 = $this->getSubChildrenData($category, $child);
            $children_data[] = [
                'children'    => $children_data_2,
                'category_id' => $child['category_id'],
                'name'        => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                'href'        => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
            ];
        }
    
        return $children_data;
    }
    
    private function getSubChildrenData($category, $child) {
        $children_2 = $this->model_catalog_category->getCategories($child['category_id']);
        $children_data_2 = [];
    
        foreach ($children_2 as $child_2) {
            $filter_data2 = [
                'filter_category_id'  => $child_2['category_id'],
                'filter_sub_category' => true
            ];
    
            $children_data_3 = $this->getSubSubChildrenData($category, $child, $child_2);
    
            $children_data_2[] = [
                'children'    => $children_data_3,
                'category_id' => $child_2['category_id'],
                'name'        => $child_2['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data2) . ')' : ''),
                'href'        => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $child_2['category_id'])
            ];
        }
    
        return $children_data_2;
    }
    
    private function getSubSubChildrenData($category, $child, $child_2) {
        $children_3 = $this->model_catalog_category->getCategories($child_2['category_id']);
        $children_data_3 = [];
    
        foreach ($children_3 as $child_3) {
            $filter_data3 = [
                'filter_category_id'  => $child_3['category_id'],
                'filter_sub_category' => true
            ];
    
            $children_data_3[] = [
                'category_id' => $child_3['category_id'],
                'name'  => $child_3['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data3) . ')' : ''),
                'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $child_2['category_id'] . '_' . $child_3['category_id'])
            ];
        }
    
        return $children_data_3;
    }
    
    

}