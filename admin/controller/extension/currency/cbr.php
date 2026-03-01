<?php
class ControllerExtensionCurrencyCbr extends Controller {

	private $error = array();

	public function index() {
		$this->load->language('extension/currency/cbr');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('currency_cbr', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=currency', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['ip'])) {
			$data['error_ip'] = $this->error['ip'];
		} else {
			$data['error_ip'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=currency', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/currency/cbr', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/currency/cbr', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=currency', true);
		$data['refresh'] = $this->url->link('localisation/currency', 'user_token=' . $this->session->data['user_token'], true);

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_edit'] = str_replace('%1',$this->url->link('localisation/currency', 'user_token=' . $this->session->data['user_token'], true), $data['text_edit']);
		$data['text_edit'] = str_replace('%2',$this->url->link('setting/store', 'user_token=' . $this->session->data['user_token'], true), $data['text_edit']);

		$data['currency_cbr_cron'] = 'curl -s &quot;' . HTTPS_CATALOG . 'index.php?route=extension/currency/cbr/refresh&quot;';

		if (isset($this->request->post['currency_cbr_ip'])) {
			$data['currency_cbr_ip'] = $this->request->post['currency_cbr_ip'];
		} else {
			$data['currency_cbr_ip'] = (string)$this->config->get('currency_cbr_ip');
		}

		if (isset($this->request->post['currency_cbr_status'])) {
			$data['currency_cbr_status'] = $this->request->post['currency_cbr_status'];
		} else {
			$data['currency_cbr_status'] = $this->config->get('currency_cbr_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/currency/cbr', $data));
	}


	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/currency/cbr')) {
			$this->error['warning'] = $this->language->get('error_permission');
		} else {
			if (!empty($this->request->post['currency_cbr_status'])) { 
				$this->load->model('localisation/currency');
				$euro_currency = $this->model_localisation_currency->getCurrencyByCode('RUB');
				if (empty($euro_currency)) {
					$this->error['warning'] = $this->language->get('error_euro');
				}
			}
			if (!empty($this->request->post['currency_cbr_ip'])) {
				if (!filter_var($this->request->post['currency_cbr_ip'],FILTER_VALIDATE_IP)) {
					$this->error['ip'] = $this->language->get('error_ip');
				}
			}
		}
		return !$this->error;
	}


	public function install() {
	}


	public function uninstall() {
	}


	public function currency() {
		$this->load->model('extension/currency/cbr');
		$this->model_extension_currency_cbr->refresh();
		return null;
	}
}
