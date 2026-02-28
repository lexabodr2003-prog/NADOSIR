<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerExtensionTotalOctProductSet extends Controller {
    private $error = array();

    public function index() {
        
        $this->load->language('extension/total/oct_product_set');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('total_oct_product_set', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        // Шляхи
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/total/oct_product_set', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/total/oct_product_set', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true);

        // Завантаження налаштувань модуля
        if (isset($this->request->post['total_oct_product_set_status'])) {
            $data['total_oct_product_set_status'] = $this->request->post['total_oct_product_set_status'];
        } else {
            $data['total_oct_product_set_status'] = $this->config->get('total_oct_product_set_status');
        }

        if (isset($this->request->post['total_oct_product_set_sort_order'])) {
            $data['total_oct_product_set_sort_order'] = $this->request->post['total_oct_product_set_sort_order'];
        } else {
            $data['total_oct_product_set_sort_order'] = $this->config->get('total_oct_product_set_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/total/oct_product_set', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/total/oct_product_set')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
