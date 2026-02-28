<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesModuleOctProductMainImageOption extends Controller {
    private $error = [];

    public function index() {

        $this->load->language('octemplates/module/oct_product_main_image_option');
        
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
		$this->document->addScript('view/javascript/octemplates/oct_main.js');
        $this->document->addStyle('view/stylesheet/oct_deals.css');
        
        $this->load->model('setting/setting');
        
        $oct_product_main_image_option_info = $this->model_setting_setting->getSetting('oct_product_main_image_option');
        
        if (!$oct_product_main_image_option_info) {
            $this->response->redirect($this->url->link('octemplates/module/oct_product_main_image_option/install', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            if (!isset($this->request->post['oct_product_main_image_option_status'])) {
                $this->request->post['oct_product_main_image_option_status'] = 0;
            }

            $this->model_setting_setting->editSetting('oct_product_main_image_option', $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('octemplates/module/oct_product_main_image_option', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('octemplates/module/oct_product_main_image_option', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['action'] = $this->url->link('octemplates/module/oct_product_main_image_option', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $this->load->model('catalog/option');

        $data['options'] = [];
        
        foreach ($this->model_catalog_option->getOptions() as $product_option) {
            $data['options'][] = [
                'option_id' => $product_option['option_id'],
                'name' => $product_option['name']
            ];
        }
        
        if (isset($this->request->post['oct_product_main_image_option_status'])) {
			$data['oct_product_main_image_option_status'] = $this->request->post['oct_product_main_image_option_status'];
		} else {
			$data['oct_product_main_image_option_status'] = $this->config->get('oct_product_main_image_option_status');
		}
        
        if (isset($this->request->post['oct_product_main_image_option_data'])) {
            $data['oct_product_main_image_option_data'] = $this->request->post['oct_product_main_image_option_data'];
        } else {
            $data['oct_product_main_image_option_data'] = $this->config->get('oct_product_main_image_option_data');
        }
        
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('octemplates/module/oct_product_main_image_option', $data));
    }
    
    public function install() {
        $this->load->language('octemplates/module/oct_product_main_image_option');

        if (!$this->validate()) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }
        
        $this->load->model('octemplates/module/oct_product_main_image_option');
        $this->load->model('setting/setting');
        $this->load->model('user/user_group');

        $this->model_octemplates_module_oct_product_main_image_option->makeDB();

        $this->model_setting_setting->editSetting('oct_product_main_image_option', [
	        'oct_product_main_image_option_status' => '0'
        ]);

        $this->response->redirect($this->url->link('octemplates/module/oct_product_main_image_option', 'user_token=' . $this->session->data['user_token'], true));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_product_main_image_option')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}