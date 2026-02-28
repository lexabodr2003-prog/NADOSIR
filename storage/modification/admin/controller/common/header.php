<?php
class ControllerCommonHeader extends Controller {
	public function index() {
		$data['title'] = $this->document->getTitle();

		if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}

		if ($this->request->server['HTTPS']) {
            $server = HTTPS_CATALOG;
        } else {
            $server = HTTP_CATALOG;
        }

        if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
        }

		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts();
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$this->load->language('common/header');

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->user->getUserName());

		if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
			$data['logged'] = '';

			$data['home'] = $this->url->link('common/login', '', true);
		} else {
			$data['logged'] = true;

			$data['home'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
			$data['logout'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token'], true);
			$data['profile'] = $this->url->link('common/profile', 'user_token=' . $this->session->data['user_token'], true);

			$this->load->model('user/user');

			$this->load->model('tool/image');


		// CSV Price Pro import/export
		$data['module_csvprice_pro_status'] = $this->config->get('module_csvprice_pro_status');

		$this->language->load('extension/csvprice_pro/app_header');
		$data['csvprice_pro_menu'] = array();

		$data['csvprice_pro_menu'][] = array(
			'url' => $this->url->link('extension/csvprice_pro/app_general', 'user_token=' . $this->session->data['user_token'], true),
			'title' => $this->language->get('text_menu_general')
		);
		$data['csvprice_pro_menu'][] = array(
			'url' => $this->url->link('extension/csvprice_pro/app_product', 'user_token=' . $this->session->data['user_token'], true),
			'title' => $this->language->get('text_menu_product')
		);
		$data['csvprice_pro_menu'][] = array(
			'url' => $this->url->link('extension/csvprice_pro/app_category', 'user_token=' . $this->session->data['user_token'], true),
			'title' => $this->language->get('text_menu_category')
		);
		$data['csvprice_pro_menu'][] = array(
			'url' => $this->url->link('extension/csvprice_pro/app_manufacturer', 'user_token=' . $this->session->data['user_token'], true),
			'title' => $this->language->get('text_menu_manufacturer')
		);
		$data['csvprice_pro_menu'][] = array(
			'url' => $this->url->link('extension/csvprice_pro/app_customer', 'user_token=' . $this->session->data['user_token'], true),
			'title' => $this->language->get('text_menu_customer')
		);
		$data['csvprice_pro_menu'][] = array(
			'url' => $this->url->link('extension/csvprice_pro/app_order', 'user_token=' . $this->session->data['user_token'], true),
			'title' => $this->language->get('text_menu_order')
		);
		$data['csvprice_pro_menu'][] = array(
			'url' => $this->url->link('extension/csvprice_pro/app_crontab', 'user_token=' . $this->session->data['user_token'], true),
			'title' => $this->language->get('text_menu_crontab')
		);
		$data['csvprice_pro_menu'][] = array(
			'url' => $this->url->link('extension/csvprice_pro/app_log', 'user_token=' . $this->session->data['user_token'], true),
			'title' => $this->language->get('text_menu_log')
		);

		$data['csvprice_pro_menu'][] = array(
			'url' => $this->url->link('extension/csvprice_pro/app_about', 'user_token=' . $this->session->data['user_token'], true),
			'title' => $this->language->get('text_menu_about')
		);
		$data['csvprice_pro_menu'][] = array(
			'url' => 'http://ocmod.costaslabs.com',
			'title' => 'ocmod.costaslabs.com',
			'target' => 1
		);
			
			$user_info = $this->model_user_user->getUser($this->user->getId());

			if ($user_info) {
				$data['firstname'] = $user_info['firstname'];
				$data['lastname'] = $user_info['lastname'];
				$data['username']  = $user_info['username'];
				$data['user_group'] = $user_info['user_group'];

				if (is_file(DIR_IMAGE . $user_info['image'])) {
					$data['image'] = $this->model_tool_image->resize($user_info['image'], 45, 45);
				} else {
					$data['image'] = $this->model_tool_image->resize('profile.png', 45, 45);
				}
			} else {
				$data['firstname'] = '';
				$data['lastname'] = '';
				$data['user_group'] = '';
				$data['image'] = '';
			}

			// Online Stores
			$data['stores'] = array();

			$data['stores'][] = array(
				'name' => $this->config->get('config_name'),
				'href' => HTTP_CATALOG
			);

			$this->load->model('setting/store');

			$results = $this->model_setting_store->getStores();

			foreach ($results as $result) {
				$data['stores'][] = array(
					'name' => $result['name'],
					'href' => $result['url']
				);
			}
		}

		return $this->load->view('common/header', $data);
	}
}
