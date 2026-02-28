<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesMenuOCTMenu extends Controller {
	public function getMenu() {
		$this->load->language('octemplates/theme/oct_deals');

		if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->load->model('octemplates/main/oct_settings');
			$this->load->model('tool/image');

			$data['menu_id'] = $menu_id = isset($this->request->get['menu_id']) ? (int)$this->request->get['menu_id'] : 0;
			$data['oct_menu'] = $this->model_octemplates_main_oct_settings->getMenuItemsByMenuId($menu_id);
            $data['config_language_id'] = (int)$this->config->get('config_language_id');

			$all_menu_items = $this->model_octemplates_main_oct_settings->getMenuItems();
			if (isset($all_menu_items[$menu_id])) {
				$data['oct_menu'] = array_merge($data['oct_menu'], $all_menu_items[$menu_id]);
			}

			$type = isset($this->request->get['type']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $this->request->get['type']) : (isset($data['oct_menu']['type']) ? $data['oct_menu']['type'] : '');

            $data['oct_menu']['type'] = $type;

			$data['thumb'] = (isset($data['oct_menu']['settings']['image']) && !empty($data['oct_menu']['settings']['image']) && file_exists(DIR_IMAGE . $data['oct_menu']['settings']['image'])) ? $this->model_tool_image->resize($data['oct_menu']['settings']['image'], 50, 50) : $this->model_tool_image->resize('no-thumb.png', 50, 50);

			$data['placeholder'] = $this->model_tool_image->resize('no-thumb.png', 50, 50);

			if (!isset($data['oct_menu']['type']) || $data['oct_menu']['type'] != $type) {
				$data['oct_menu'] = [];
			}

			if ($type) {
				$fun_name = 'getMenu' . ucfirst($type);

				if (method_exists($this, $fun_name)) {
					$data['elements'] = $this->{$fun_name}();
				}
			}

			$this->load->model('localisation/language');
			$data['languages'] = $this->model_localisation_language->getLanguages();

			if (isset($type) && $type) {
				$this->response->setOutput($this->load->view('octemplates/menu/oct_menu_' . $type, $data));
			}
		}
	}

	public function getSubLinks() {
		$this->load->language('octemplates/theme/oct_deals');

		if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$menu_id = isset($this->request->get['menu_id']) ? (int)$this->request->get['menu_id'] : 0;
            $data['config_language_id'] = (int)$this->config->get('config_language_id');
			$sub_link_id = isset($this->request->get['sub_link_id']) ? (int)$this->request->get['sub_link_id'] : time();

			$this->load->model('localisation/language');
			$data['languages'] = $this->model_localisation_language->getLanguages();

			$data['menu_id'] = $menu_id;
			$data['sub_link_id'] = $sub_link_id;

			$this->response->setOutput($this->load->view('octemplates/menu/oct_menu_sublink', $data));
		}
	}

	private function getMenuCategory() {
		$this->load->model('catalog/category');

		$categories = [];

		$filter_data = [
			'sort'  => 'name',
			'order' => 'ASC',
			'start' => 0,
			'limit' => 100000
		];

		$results = $this->model_catalog_category->getCategories($filter_data);

		foreach ($results as $result) {
			$categories[] = [
				'category_id' => (int)$result['category_id'],
				'name'        => $result['name']
			];
		}

		return $categories;
	}

	private function getMenuManufacturer() {
		$this->load->model('catalog/manufacturer');

		$manufacturers = [];

		$filter_data = [
			'sort'  => 'name',
			'order' => 'ASC',
			'start' => 0,
			'limit' => 100000
		];

		$results = $this->model_catalog_manufacturer->getManufacturers($filter_data);

		foreach ($results as $result) {
			$manufacturers[] = [
				'manufacturer_id' => (int)$result['manufacturer_id'],
				'name'            => $result['name'],
			];
		}

		return $manufacturers;
	}

	private function getMenuOCT_BlogCategory() {
		$this->load->model('octemplates/blog/oct_blogcategory');

		$blog_categories = [];

		$filter_data = [
			'sort'  => 'name',
			'order' => 'ASC',
			'start' => 0,
			'limit' => 100000
		];

		$results = $this->model_octemplates_blog_oct_blogcategory->getBlogCategories($filter_data);

		foreach ($results as $result) {
			$blog_categories[] = [
				'blogcategory_id' => (int)$result['blogcategory_id'],
				'name'            => $result['name']
			];
		}

		return $blog_categories;
	}

	private function getMenuLink() {
		return;
	}
}