<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsHeader extends Controller {

	public function index(&$route, &$data){
		$this->loadData($data);
		$this->prepareFonts($data);
		$this->prepareWidth($data);
		$this->prepareTheme($data);
		$this->loadLogoSettings($data);
		$this->loadInfoBarSettings($data);
		$this->loadContacts($data);
		$this->loaddealsVars($data);
		$this->loadHeaderLinks($data);
		$this->loadProductViews($data);
		$this->loadPreloadSettings($data);
		$this->loadMegaMenu($data);
		$this->loadLanguageCurrency($data);
		$this->loadAnalytics($data);
		$this->loadOpenGraph($data);
		$this->loadMicrodata($data);
		$this->minifyStylesScripts($data);
		$this->isButtonInteractive($data);
		$this->loadTotals($data);
		$this->customerLoggedInfo($data);
		$this->checkFeedbackData($data);
    }

	private function loadData(&$data){
		$data['oct_deals_data'] = $this->config->get('theme_oct_deals_data');
		$data['theme_oct_deals_data_colors'] = $this->config->get('theme_oct_deals_data_colors');
		$data['oct_theme_color'] = isset($data['theme_oct_deals_data_colors']['top_background_color']) ? $data['theme_oct_deals_data_colors']['top_background_color'] : false;
		$data['oct_lang_id'] = (int)$this->config->get('config_language_id');
		$data['compare_link'] = $this->url->link('product/compare', '', true);
		$data['wishlist_link'] = $this->url->link('account/wishlist', '', true);
		$data['contact_link'] = $this->url->link('information/contact', '', true);
		$data['oct_popup_call_phone_status'] = $this->config->get('oct_popup_call_phone_status');
		$data['oct_popup_cart_status'] = $this->config->get('theme_oct_deals_popup_cart_status');
		$data['body_class'] = isset($this->request->get['product_id']) ? true : false;
		$data['isbuttoninteractive'] = isset($data['oct_deals_data']['isbuttoninteractive']) ? true : false;
		$data['oct_popup_options'] = isset($data['oct_deals_data']['oct_popup_options']) ? true : false;
		$data['main_address_html'] = isset($data['oct_deals_data']['contact_address'][$data['oct_lang_id']]) ? html_entity_decode($data['oct_deals_data']['contact_address'][$data['oct_lang_id']], ENT_QUOTES, 'UTF-8') : '';

		$logo_dark = $data['oct_deals_data']['logo_dark'] ?? '';
		$data['dark_logo'] = '';

		if (!empty($logo_dark) && is_file(DIR_IMAGE . $logo_dark)) {
			$server = $this->request->server['HTTPS'] 
				? $this->config->get('config_ssl') 
				: $this->config->get('config_url');
			
			$data['dark_logo'] = $server . 'image/' . $logo_dark;
		}
	}

	private function prepareTheme(&$data){
		$theme = $this->config->get('theme_oct_deals_data')['theme'] ?? 'light';
		
		if ($theme !== 'light' && $theme !== 'dark') {
			$theme = 'light';
		}
		
		$data['oct_theme'] = $theme;
	}

	private function prepareWidth(&$data){

		$data['oct_width'] = [
			0 => ['name' => 'small'],
			1 => ['name' => 'medium'],
			2 => ['name' => 'wide']
		];

		$main_width = (int) $data['oct_deals_data']['width'] ?? 1;

		if (isset($data['oct_width'][$main_width]['name'])) {
			$data['main_width'] = $data['oct_width'][$main_width]['name'];
		} else {
			$data['main_width'] = $data['oct_width'][1]['name'];
		}
	}

	private function prepareFonts(&$data){

		$data['oct_fonts'] = [
			0 => ['name' => 'Ubuntu', 'data-attr' => 'ubuntu'],
			1 => ['name' => 'Roboto', 'data-attr' => 'roboto'],
			2 => ['name' => 'Open Sans', 'data-attr' => 'opensans'],
			3 => ['name' => 'Montserrat', 'data-attr' => 'montserrat'],
			4 => ['name' => 'Inter', 'data-attr' => 'inter'],
			5 => ['name' => 'Fira Sans', 'data-attr' => 'firasans'],
			6 => ['name' => 'Rubik', 'data-attr' => 'rubik'],
			7 => ['name' => 'Noto Sans', 'data-attr' => 'notosans'],
			8 => ['name' => 'Raleway', 'data-attr' => 'raleway'],
			9 => ['name' => 'Nunito', 'data-attr' => 'nunito']
		];

		$main_font = (int) $data['oct_deals_data']['font'] ?? 0;
		
		if (isset($data['oct_fonts'][$main_font]['name'])) {
			$data['main_active_font']['name'] = $data['oct_fonts'][$main_font]['name'];
			$data['main_active_font']['data_attr'] = $data['oct_fonts'][$main_font]['data-attr'];
		} else {
			$data['main_active_font']['name'] = $data['oct_fonts'][1]['name'];
			$data['main_active_font']['data_attr'] = $data['oct_fonts'][1]['data-attr'];
		}
	}

	private function loadLogoSettings(&$data){
		$data['logo_width'] = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_logo_width');
		$data['logo_height'] = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_logo_height');
	}

	private function loadInfoBarSettings(&$data){
		$data['oct_info_bar_close'] = $this->language->get('oct_info_bar_close');
		$data['oct_information_bar_more'] = $this->language->get('oct_policy_more');

		$oct_information_bar_status = $this->config->get('oct_information_bar_status');
		$oct_information_bar_data = $this->config->get('oct_information_bar_data');
		$data['oct_information_bar_value'] = false;

		if (isset($oct_information_bar_data['value']) && $oct_information_bar_data['value'] && !empty($oct_information_bar_data['value']) && ($oct_information_bar_status && (!isset($this->request->cookie[$oct_information_bar_data['value']]) || !$this->request->cookie[$oct_information_bar_data['value']])) && $this->config->get('config_maintenance') == 0) {
			$data['oct_information_bar_value'] = $oct_information_bar_data['value'];
			$data['oct_information_bar_day_now'] = date("Y-m-d H:i:s");

			if (isset($oct_information_bar_data['module_text'][(int)$this->config->get('config_language_id')]) && !empty($oct_information_bar_data['module_text'][(int)$this->config->get('config_language_id')])) {
				$data['text_oct_information_bar'] = strip_tags(html_entity_decode($oct_information_bar_data['module_text'][(int)$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8'), '<img><ul><li><b><strong><a><i><u><br></br><span><div>');

				if (isset($oct_information_bar_data['indormation_id']) && $oct_information_bar_data['indormation_id']) {
					$data['text_oct_information_bar'] .= ' <a target="_blank" href="' . $this->url->link('information/information', 'information_id=' . $oct_information_bar_data['indormation_id']) . '">' . $data['oct_information_bar_more'] . '</a>';
				}

				if (isset($oct_information_bar_data['max_day']) && $oct_information_bar_data['max_day'] && !empty($oct_information_bar_data['max_day'])) {
					$data['oct_info_max_day'] = (int)$oct_information_bar_data['max_day'];
				}
			}

			$data['oct_information_bar_background']					= $oct_information_bar_data['background_bar'];
			$data['oct_information_bar_color_text']					= $oct_information_bar_data['color_text'];
			$data['oct_information_bar_color_url']					= $oct_information_bar_data['color_url'];
		}
	}	

	private function loadContacts(&$data){
		if (isset($data['oct_deals_data']['contact_open'][(int)$this->config->get('config_language_id')])) {
			$oct_contact_opens = explode(PHP_EOL, $data['oct_deals_data']['contact_open'][(int)$this->config->get('config_language_id')]);

			foreach ($oct_contact_opens as $oct_contact_open) {
				if (!empty($oct_contact_open)) {
					$data['oct_contact_opens'][] = html_entity_decode($oct_contact_open, ENT_QUOTES, 'UTF-8');
				}
			}
		}
		
		$oct_contact_telephones = explode(PHP_EOL, $data['oct_deals_data']['contact_telephone']);

		foreach ($oct_contact_telephones as $oct_contact_telephone) {
			if (!empty($oct_contact_telephone)) {
				$data['oct_contact_telephones'][] = html_entity_decode(trim($oct_contact_telephone), ENT_QUOTES, 'UTF-8');
			}
		}

		if (isset($data['oct_deals_data']['contact_email']) && strlen($data['oct_deals_data']['contact_email']) > 3) {
			$data['main_contact_email'] = $data['oct_deals_data']['contact_email'];
		}	

		if (isset($data['oct_deals_data']['contact_address'])) {
		
			foreach ($data['oct_deals_data']['contact_address'] as $oct_lang_id => $oct_adress) {
				$data['contact_address'][$oct_lang_id] = html_entity_decode($oct_adress, ENT_QUOTES, 'UTF-8');
			}
		}

		if (isset($data['oct_deals_data']['contact_map']) && !empty($data['oct_deals_data']['contact_map'])) {
			$data['contact_map'] = html_entity_decode($data['oct_deals_data']['contact_map'], ENT_QUOTES, 'UTF-8');
		}

		if (isset($data['oct_deals_data']['contact_view_locations']) && $data['oct_deals_data']['contact_view_locations'] == 'on') {
			$this->load->model('octemplates/widgets/oct_locations');

			$data['oct_locations'] = $this->model_octemplates_widgets_oct_locations->getOCTLocations();
		}
	}

	private function loaddealsVars(&$data){
		$data['oct_expand_menu'] = false;
		$data['oct_home'] = false;

		$oct_deals_vars = $this->config->get('oct_deals_vars');

		if (isset($oct_deals_vars['is_home']) && $oct_deals_vars['is_home']) {
			$data['oct_home'] = true;
			
			if (isset($data['oct_deals_data']['megamenu']['expand']) && $data['oct_deals_data']['megamenu']['expand']) {
				$data['oct_expand_menu'] = true;
			}
		}
	}

	private function loadHeaderLinks(&$data){
		$data['header_informations'] = [];

		if (isset($data['oct_deals_data']['header_links']) && !empty($data['oct_deals_data']['header_links'])) {
			foreach ($data['oct_deals_data']['header_links'] as $header_link) {
				$data['header_informations'][] = array(
					'title' => html_entity_decode($header_link[(int)$this->config->get('config_language_id')]['title'], ENT_QUOTES, 'UTF-8'),
					'href'  => $header_link[(int)$this->config->get('config_language_id')]['link']
				);
			}
		}
	}

	private function loadProductViews(&$data){
		$product_views = [];

		if (isset($this->request->cookie['oct_product_views'])) {
			$product_views = explode(',', $this->request->cookie['oct_product_views']);
		} elseif (isset($this->session->data['oct_product_views'])) {
			$product_views = $this->session->data['oct_product_views'];
		}

		if (isset($this->request->cookie['viewed'])) {
			$product_views = array_merge($product_views, explode(',', $this->request->cookie['viewed']));
		} elseif (isset($this->session->data['viewed'])) {
			$product_views = array_merge($product_views, $this->session->data['viewed']);
		}

		$data['product_views_count'] = count($product_views);
	}

	private function loadPreloadSettings(&$data){
		if (isset($data['oct_deals_data']['preload_images']) && $data['oct_deals_data']['preload_images'] && is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$server = $this->request->server['HTTPS'] ? $this->config->get('config_ssl') : $this->config->get('config_url');

			$this->document->setOCTPreload($server . 'image/' . $this->config->get('config_logo'));
		}

		$data['oct_preloads'] = $this->document->getOCTPreload();
		$data['oct_menu_catalog'] = (isset($data['oct_deals_data']['megamenu']['dtitle'][(int)$this->config->get('config_language_id')]) && !empty($data['oct_deals_data']['megamenu']['dtitle'][(int)$this->config->get('config_language_id')])) ? $data['oct_deals_data']['megamenu']['dtitle'][(int)$this->config->get('config_language_id')] : $this->language->get('oct_menu_catalog');

		$data['oct_footer_scripts'] = (isset($data['oct_deals_data']['footer_scripts']) && $data['oct_deals_data']['footer_scripts']) ? true : false;

		$data['oct_minify'] = (isset($data['oct_deals_data']['minify']) && $data['oct_deals_data']['minify']) ? true : false;

		if ($this->registry->has('oct_mobiledetect')) {
			if (!$this->oct_mobiledetect->isMobile() && !$this->oct_mobiledetect->isTablet()) {
				$data['oct_desktop'] = true;
			}
		}
	}

	private function loadMegaMenu(&$data){
		if (isset($data['oct_deals_data']['megamenu']['status']) && $data['oct_deals_data']['megamenu']['status']) {
			$data['menu'] = $this->load->controller('octemplates/menu/oct_menu');
			$data['horizontal_menu'] = $this->load->controller('octemplates/menu/oct_menu/horizontal');
		} else {
			$data['menu'] = $this->load->controller('common/menu');
			$data['horizontal_menu'] = false;
		}
	}

	private function loadLanguageCurrency(&$data){
		$data['language'] = (isset($data['oct_deals_data']['header_lang']) && $data['oct_deals_data']['header_lang']) ? $this->load->controller('common/language') : false;
		$data['currency'] = (isset($data['oct_deals_data']['header_cur']) && $data['oct_deals_data']['header_cur']) ? $this->load->controller('common/currency') : false;
	}

	private function loadAnalytics(&$data){
		if ($this->config->get('analytics_oct_analytics_google_status') && $this->config->get('analytics_oct_analytics_google_webmaster_code')) {
			$data['oct_analytics_google_webmaster_code'] = html_entity_decode($this->config->get('analytics_oct_analytics_google_webmaster_code'), ENT_QUOTES, 'UTF-8');
		}

		if ($this->config->get('analytics_oct_analytics_status') && $this->config->get('analytics_oct_analytics_googletm_code')) {
			$data['oct_analytics_googletm_code'] = html_entity_decode($this->config->get('analytics_oct_analytics_googletm_code'), ENT_QUOTES, 'UTF-8');
		}
	}

	private function loadOpenGraph(&$data){
		$data['octOpenGraphs'] = (isset($data['oct_deals_data']['open_graph']) && $data['oct_deals_data']['open_graph']) ? $this->document->getOCTOpenGraph() : [];
	}

	private function getAllowedStoreTypes() {
		return [
			'OnlineStore',
			'Store',
			'LocalBusiness',
			'AutoDealer',
			'AutoPartsStore',
			'BookStore',
			'ClothingStore',
			'ComputerStore',
			'DepartmentStore',
			'ElectronicsStore',
			'FloristShop',
			'FurnitureStore',
			'GardenStore',
			'GroceryStore',
			'HardwareStore',
			'HobbyShop',
			'HomeGoodsStore',
			'JewelryStore',
			'LiquorStore',
			'MensClothingStore',
			'MobilePhoneStore',
			'MovieRentalStore',
			'MusicStore',
			'OfficeEquipmentStore',
			'OutletStore',
			'PetStore',
			'ShoeStore',
			'SportingGoodsStore',
			'TireShop',
			'ToyStore',
			'WholesaleStore'
		];
	}

	private function getValidStoreType($type) {
		$allowed_types = $this->getAllowedStoreTypes();
		return in_array($type, $allowed_types) ? $type : 'OnlineStore';
	}

	private function formatPhoneForSchema($phone) {
		if (empty($phone) || !is_string($phone)) {
			return null;
		}
		
		$phone = trim($phone);
		
		$phone = preg_replace('/[^\d+]/', '', $phone);
		
		if (empty($phone)) {
			return null;
		}
		
		if (strpos($phone, '+') !== 0 && strlen($phone) >= 7) {
			$phone = '+' . $phone;
		}
		
		if (strlen($phone) < 8) {
			return null;
		}
		
		return $phone;
	}

	private function parseAreaServed($areas) {
		$structured_areas = [];
		
		foreach ($areas as $area) {
			if (strlen($area) === 2 && ctype_upper($area)) {
				$structured_areas[] = [
					'@type' => 'Country',
					'name' => $area
				];
			} else {
				$structured_areas[] = [
					'@type' => 'City',
					'name' => $area
				];
			}
		}
		
		return $structured_areas;
	}

	private function loadMicrodata(&$data) {
		$this->load->model('octemplates/microdata');
		
		$route = isset($this->request->get['route']) ? $this->request->get['route'] : '';
		
		if ($data['oct_home'] || $route === 'information/contact') {
			if ($data['oct_home']) {
				$data['home_microdata'] = $this->model_octemplates_microdata->generateHomeMicrodata($data);
			}
		}
	}

	private function minifyStylesScripts(&$data){
		$this->load->model('octemplates/widgets/oct_minify');

		$swiper = $this->config->get('footer_swiper') ?? NULL;

		$this->document->addOctStyle('catalog/view/theme/oct_deals/stylesheet/css/main.min.css');

		$this->addStylesheetIfItExists('oct_deals/stylesheet/oct_stickers.css');
    	$this->addStylesheetIfItExists('oct_deals/stylesheet/dynamic_stylesheet_' . (int)$this->config->get('config_store_id') . '.css');
		
		if ($swiper) {
			$this->document->addOctStyle('catalog/view/theme/oct_deals/js/swiper/swiper.min.css');
		}

		$data['styles'] = $this->model_octemplates_widgets_oct_minify->octMinifyCss($this->document->getOctStyles());

		$this->document->addOctScript('catalog/view/theme/oct_deals/js/main.min.js');
		$this->document->addOctScript('catalog/view/theme/oct_deals/js/common.js');

		$data['scripts'] = $this->model_octemplates_widgets_oct_minify->octMinifyJs($this->document->getOctScripts());
	}

	private function addStylesheetIfItExists($stylesheetPath) {
		if (file_exists(DIR_TEMPLATE . $stylesheetPath)) {
			$file_size = filesize(DIR_TEMPLATE . $stylesheetPath);
			if ($file_size) {
				$this->document->addOctStyle('catalog/view/theme/' . $stylesheetPath);
			}
		}
	}

	private function getComparedProductIds() {
		if (isset($this->session->data['compare'])) {
			return implode(',', $this->session->data['compare']);
		}
		
		return '';
	}
	
	private function getWishlistProductIds() {
		if ($this->customer->isLogged()) {
			$wishlist = $this->model_account_wishlist->getWishlist();
			$product_ids = array();
			if (is_array($wishlist)) {
				foreach ($wishlist as $item) {
					$product_ids[] = $item['product_id'];
				}
			}
			return implode(',', $product_ids);
		} else {
			if (isset($this->session->data['wishlist'])) {
				return implode(',', $this->session->data['wishlist']);
			}
		}
		return '';
	}

	private function isButtonInteractive(&$data) {
		if ($data['isbuttoninteractive']) {
			$data['wishlist_ids'] = $this->getWishlistProductIds();
			$data['compare_ids'] = $this->getComparedProductIds();
		}
	}

	private function loadTotals(&$data) {
		$data['compare_total'] = (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0);

		$data['viewed_total'] = $this->getProductViews(['oct_product_views', 'viewed']);

		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');
			$data['wishlist_total'] = $this->model_account_wishlist->getTotalWishlist();
		} else {
			$data['wishlist_total'] = (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0);
		}
	}

	private function getProductViews($keys) {
        $product_ids = [];
    
        foreach ($keys as $key) {
            if (isset($this->request->cookie[$key])) {
                $product_ids = array_merge($product_ids, explode(',', $this->request->cookie[$key]));
            } elseif (isset($this->session->data[$key])) {
                $product_ids = array_merge($product_ids, $this->session->data[$key]);
            }
        }
    
        return count($product_ids);
    }

	private function customerLoggedInfo(&$data) {
		$data['customerName'] = '';
		$data['customerLastName'] = '';
		$data['customerEmail'] = '';
		if ($this->customer->isLogged()) {
			$data['customerName'] = $this->customer->getFirstName();
			$data['customerLastName'] = $this->customer->getLastName();
			$data['customerEmail'] = $this->customer->getEmail();
		}
	}

    private function checkFeedbackData(&$data) {
        if ($this->config->get('theme_oct_deals_feedback_status')) {
            $data['oct_feedback_data'] = $this->config->get('theme_oct_deals_feedback_data');
        }
    }
}