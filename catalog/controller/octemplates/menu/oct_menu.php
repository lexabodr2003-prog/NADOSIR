<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesMenuOCTMenu extends Controller {
    public function index($data = []) {
        $this->load->language('octemplates/menu/oct_menu');
        $this->load->language('octemplates/module/oct_popup_login');

        $this->load->model('tool/image');
        $this->load->model('localisation/language');

        $data['language_id'] = (int)$this->config->get('config_language_id');
        $data['oct_deals_data'] = $oct_deals_data = $this->config->get('theme_oct_deals_data');
        $data['oct_detect'] = false;
		$data['display_type'] = isset($data['display_type']) ? $data['display_type'] : 'vertical';
		$data['oct_menu_catalog'] = (isset($data['oct_deals_data']['megamenu']['dtitle'][(int)$this->config->get('config_language_id')]) && !empty($data['oct_deals_data']['megamenu']['dtitle'][(int)$this->config->get('config_language_id')])) ? $data['oct_deals_data']['megamenu']['dtitle'][(int)$this->config->get('config_language_id')] : $this->language->get('oct_menu_catalog');

		$oct_menu = $this->getOCTMegaMenu($data);
		
		$oct_categories = [];
	
		if (isset($oct_deals_data['megamenu']['status']) && $oct_deals_data['megamenu']['status']) {
			if ($data['display_type'] == 'vertical' && !empty($oct_deals_data['megamenu']['dcategories'])) {
				$oct_categories = $this->getStandartCategories($data);
			}
	
			$sortType = $oct_deals_data['megamenu']['sort'];
			$data['oct_menu'] = $this->mergeAndSortMenu($oct_menu, $oct_categories, $sortType);
	
			$view = $data['display_type'] == 'horizontal' ? 'octemplates/menu/oct_menu_horizontal' : 'octemplates/menu/oct_menu';
			return $this->load->view($view, $data);
		}
    }

	public function horizontal() {
		return $this->index(['display_type' => 'horizontal']);
	}
	
	private function mergeAndSortMenu($oct_menu, $oct_categories, $sortType) {
		$mergedMenu = $sortType == 1 ? array_merge($oct_menu, $oct_categories) : array_merge($oct_categories, $oct_menu);

		switch ($sortType) {
			case 3:
				$this->sortMenu($mergedMenu, 'sort');
				break;
			case 4:
				$this->sortMenu($mergedMenu, 'name');
				break;
		}

		return $mergedMenu;
	}

	private function sortMenu(&$menu, $sortBy) {
		$sort_order = array_column($menu, $sortBy);
		array_multisort($sort_order, SORT_ASC, $menu);
	}

    private function getOCTMegaMenu($data = []) {
		if (isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'webp')) {
			$oct_webP = 1 . '-' . $this->session->data['currency'];
		} else {
			$oct_webP = 0 . '-' . $this->session->data['currency'];
		}

		$data['display_type'] = isset($data['display_type']) ? $data['display_type'] : 'vertical';

		$menu_items = $this->cache->get('octemplates.menuItems.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . $oct_webP . '.' . $data['display_type']);
	
		if (!$menu_items) {
			$menu_items = [];

			$this->load->model('octemplates/menu/oct_menu');

			$data['types'] = [
				'category' => 1,
				'manufacturer' => 1,
				'oct_blogcategory' => 1,
				'link' => 1
			];

			$menu_items = $this->model_octemplates_menu_oct_menu->getOCTMenuItems($data);
			$this->cache->set('octemplates.menuItems.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . $oct_webP . '.' . $data['display_type'], $menu_items);
		}

		return $menu_items;
    }

    private function getStandartCategories($data = []) {
        $oct_deals_data = $this->config->get('theme_oct_deals_data');

        $categories_icon = isset($oct_deals_data['megamenu']['icon']) ? true : false;

        $this->load->model('catalog/category');

        if ($categories_icon) {
            $this->load->model('tool/image');
        }

        if ($this->config->get('config_product_count')) {
    		$this->load->model('catalog/product');
        }

        if(isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'webp')) {
			$oct_webP = 1 . '-' . $this->session->data['currency'];
		} else {
			$oct_webP = 0 . '-' . $this->session->data['currency'];
		}

        $menu_categories = $this->cache->get('octemplates.categoryItems.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . $oct_webP);

        $oct_cats_limit = $oct_deals_data['megamenu']['limit'] ? $oct_deals_data['megamenu']['limit'] : 0;

        if (!$menu_categories) {
    		$categories = $this->model_catalog_category->getOCTCategories(0, '');
            $menu_categories = [];

    		foreach ($categories as $category) {
				// Level 2
				$children_data = [];

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					$filter_data = [
						'filter_category_id'  => $child['category_id'],
						'filter_sub_category' => true
					];

                    // Level 3
        			$children_data_2 = [];
        			$children_2 = $this->model_catalog_category->getOCTCategories($child['category_id'], $oct_cats_limit);

        			foreach ($children_2 as $child_2) {
        				$filter_data2 = [
        					'filter_category_id'  => $child_2['category_id'],
        					'filter_sub_category' => true
        				];

        				$children_3 = $this->model_catalog_category->getCategories($child_2['category_id']);

        				$children_data_3 = [];

        				foreach ($children_3 as $child_3) {
        					$filter_data3 = [
        						'filter_category_id'  => $child_3['category_id'],
        						'filter_sub_category' => true
        					];

        					$children_data_3[] = [
        						'name'  => $child_3['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data3) . ')' : ''),
        						'oct_pages' => isset($oct_deals_data['megamenu']['page']) ? unserialize($child_3['page_group_links']) : [],
        						'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $child_2['category_id'] . '_' . $child_3['category_id'])
        					];
        				}

        				$children_data_2[] = [
        					'name'  => $child_2['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data2) . ')' : ''),
                            'children' => $children_data_3,
        					'oct_pages' => isset($oct_deals_data['megamenu']['page']) ? unserialize($child_2['page_group_links']) : [],
        					'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $child_2['category_id'])
        				];
        			}

					$children_data[] = [
						'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                        'children' => $children_data_2,
        				'oct_pages' => isset($oct_deals_data['megamenu']['page']) ? unserialize($child['page_group_links']) : [],
						'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
					];
				}

				// Level 1
				$menu_categories[] = [
					'name'     => $category['name'],
                    'sort'     => $category['sort_order'],
                    'type'     => 'standard',
                    'view'     => $data['oct_deals_data']['megamenu']['view'],
					'children' => $children_data,
					'column'   => 1,
                    'target'   => '',
                    'oct_image'=> $categories_icon ? $this->model_tool_image->resize($category['oct_image'], 32, 32) : false,
                    'width'    => 32,
                    'height'   => 32,
                    'oct_pages' => isset($oct_deals_data['megamenu']['page']) ? unserialize($category['page_group_links']) : false,
					'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
				];
    		}

            $this->cache->set('octemplates.categoryItems.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . $oct_webP, $menu_categories);
        }

        return $menu_categories;
    }
}
