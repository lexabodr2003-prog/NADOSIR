<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerExtensionModuleOctProductSet extends Controller {
    public function index($setting) {
        static $module = 0;

        $this->config->set('footer_swiper', true);

        $this->load->language('extension/module/oct_product_set');

        $data['heading_title'] = $this->language->get('heading_title');
        $data['module'] = $module++;

        // Product Sets
        if (isset($setting['status']) && $setting['status'] == "on") {

            if (isset($this->request->get['path'])) {
                $parts = explode('_', (string)$this->request->get['path']);
                $data['category_id'] = (int)array_pop($parts);
            } else {
                $data['category_id'] = 0;
            }
    
            $data['manufacturer_id'] = isset($this->request->get['manufacturer_id']) ? (int)$this->request->get['manufacturer_id'] : 0;

            $data['product_set'] = $this->load->controller('octemplates/module/oct_product_set/setsInPages', $data);
            $data['product_sets'] = array();

            return $this->load->view('octemplates/module/oct_product_set', $data);
        }
    }
}