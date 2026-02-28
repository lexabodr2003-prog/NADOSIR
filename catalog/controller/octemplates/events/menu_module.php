<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsMenuModule extends Controller {

    public function index(&$route, &$data) {
        $data['categories'] = [];
        $data['language_id'] = (int)$this->config->get('config_language_id');
        $data['oct_detect'] = $this->isMobileDevice();
        $oct_deals_data = $this->config->get('theme_oct_deals_data');
        $categories_icon = isset($oct_deals_data['megamenu']['icon']) ? true : false;
        $oct_webP = $this->defineWebP($data);
        $cacheKey = $this->generateCacheKey($oct_webP);

        $result_all_categories = $this->cache->get($cacheKey);
        $oct_cats_limit = $this->getCategoriesLimit();

        if (!$result_all_categories) {
            $result_all_categories = $this->fetchCategories($categories_icon, $oct_deals_data);
            $this->cache->set($cacheKey, $result_all_categories);
        }

        if ($this->reachedCategoryLimit($result_all_categories, $oct_cats_limit)) {
            $data['oct_all_categories'] = $this->url->link('octemplates/main/oct_functions/octallcategories', '', true);
        }

        $data['categories'] = $result_all_categories;

        if ($this->isAjax() && isset($this->request->post['mobile']) && $this->request->post['mobile']) {
            return $result_all_categories;
        }
    }

    private function isMobileDevice() {
        return $this->registry->has('oct_mobiledetect') && ($this->oct_mobiledetect->isMobile() || $this->oct_mobiledetect->isTablet());
    }

    private function defineWebP($data) {
        $isWebP = isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'webp');
        $oct_webP = ($isWebP ? 1 : 0) . '-' . $this->session->data['currency'];
        $isMobile = isset($data['mobile']) && $data['mobile'];

        return $isMobile ? $oct_webP . "mobile" : $oct_webP;
    }

    private function generateCacheKey($oct_webP) {
        return 'octemplates.category_in_menu.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . $oct_webP;
    }

    private function getCategoriesLimit()
    {
        return $this->config->get('oct_megamenu_categories_limit') ?: 0;
    }

    private function fetchCategories() {
        $categories_icon = isset($this->config->get('theme_oct_deals_data')['megamenu']['icon']) ? true : false;

        $categories = $this->model_catalog_category->getCategories(0);
        $result_all_categories = [];

        foreach ($categories as $category) {
            if ($category['top']) {
                $children_data = $this->fetchChildren($category);

                if ($categories_icon) {
                    $this->load->model('tool/image');
                    $oct_image = $this->model_tool_image->resize($category['oct_image'], 30, 30);
                } else {
                    $oct_image = false;
                }

                $result_all_categories[] = array(
                    'name'     => $category['name'],
                    'children' => $children_data,
                    'column'   => 1,
                    'oct_pages' => isset($this->config->get('theme_oct_deals_data')['megamenu']['page']) ? unserialize($category['page_group_links']) : [],
                    'oct_image'     => $oct_image,
                    'width'     => 32,
                    'height'     => 32,
                    'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
                );
            }
        }

        return $result_all_categories;
    }

    private function fetchChildren($parent_category) {
        $children = $this->model_catalog_category->getCategories($parent_category['category_id']);

        $children_data = [];

        foreach ($children as $child) {
            $filter_data = array(
                'filter_category_id'  => $child['category_id'],
                'filter_sub_category' => true
            );

            $children_data_level2 = $this->fetchChildrenLevel2($child, $parent_category);

            $children_data[] = array(
                'children' => $children_data_level2,
                'oct_pages' => isset($this->config->get('theme_oct_deals_data')['megamenu']['page']) ? unserialize($child['page_group_links']) : [],
                'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                'href'  => $this->url->link('product/category', 'path=' . $parent_category['category_id'] . '_' . $child['category_id'])
            );
        }

        return $children_data;
    }

    private function fetchChildrenLevel2($parent_category, $root_category) {
        $children_level2 = $this->model_catalog_category->getCategories($parent_category['category_id']);

        $children_data = [];

        foreach ($children_level2 as $child_level2) {
            $filter_data = array(
                'filter_category_id'  => $child_level2['category_id'],
                'filter_sub_category' => true
            );

            $children_data_level3 = $this->fetchChildrenLevel3($child_level2, $parent_category, $root_category);

            $children_data[] = array(
                'children' => $children_data_level3,
                'category_id' => $child_level2['category_id'],
                'name'  => $child_level2['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                'href'  => $this->url->link('product/category', 'path=' . $root_category['category_id'] . '_' . $parent_category['category_id'] . '_' . $child_level2['category_id'])
            );
        }

        return $children_data;
    }

    private function fetchChildrenLevel3($parent_category, $root_category, $root2_category) {
        $children_level3 = $this->model_catalog_category->getCategories($parent_category['category_id']);

        $children_data = [];

        foreach ($children_level3 as $child_level3) {
            $filter_data = array(
                'filter_category_id'  => $child_level3['category_id'],
                'filter_sub_category' => true
            );

            $children_data_level4 = $this->fetchChildrenLevel4($child_level3, $parent_category, $root_category, $root2_category);

            $children_data[] = array(
                'children' => $children_data_level4,
                'category_id' => $child_level3['category_id'],
                'name'  => $child_level3['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                'href'  => $this->url->link('product/category', 'path=' . $root2_category['category_id'] . '_' . $root_category['category_id'] . '_' . $parent_category['category_id'] . '_' . $child_level3['category_id'])
            );
        }

        return $children_data;
    }

    private function fetchChildrenLevel4($parent_category, $root_category, $root2_category, $root3_category) {
        $children_level4 = $this->model_catalog_category->getCategories($parent_category['category_id']);

        $children_data = [];

        foreach ($children_level4 as $child_level4) {
            $filter_data = array(
                'filter_category_id'  => $child_level4['category_id'],
                'filter_sub_category' => true
            );

            $children_data[] = array(
                'category_id' => $child_level4['category_id'],
                'name'  => $child_level4['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                'href'  => $this->url->link('product/category', 'path=' . $root3_category['category_id'] . '_' . $root2_category['category_id'] . '_' . $root_category['category_id'] . '_' . $parent_category['category_id'] . '_' . $child_level4['category_id'])
            );
        }

        return $children_data;
    }

    private function reachedCategoryLimit($result_all_categories, $oct_cats_limit) {
        return (count($result_all_categories) == $oct_cats_limit) && $oct_cats_limit;
    }

    private function isAjax() {
        return isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
}