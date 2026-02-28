<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsProductViewsHistory extends Controller {

    public function index(&$route, &$data) {
        $product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : null;

        $this->load->model('octemplates/helper');
        $this->load->model('setting/module');

        $module_id = $this->model_octemplates_helper->getModuleIdByCode($module_name = 'oct_product_views');
        $module_info = $this->model_setting_module->getModule($module_id);

        if ($product_id && $module_id && isset($module_info['status']) && $module_info['status'] == 'on') {
            $product_ids = $this->getProductViews(['oct_product_views', 'viewed']);
            $this->addProductIdToViewed($product_ids, $product_id);
        }
    }
    
    private function getProductViews($keys) {
        $product_ids = [];
    
        foreach ($keys as $key) {
            if (isset($this->request->cookie[$key])) {
                $product_ids = array_merge($product_ids, array_map('intval', explode(',', $this->request->cookie[$key])));
            } elseif (isset($this->session->data[$key])) {
                $product_ids = array_merge($product_ids, array_map('intval', (array)$this->session->data[$key]));
            }
        }
    
        return $product_ids;
    }
    
    private function addProductIdToViewed(&$product_ids, $product_id) {

        if (($key = array_search((int)$product_id, $product_ids)) !== false) {
            unset($product_ids[$key]);
        }

        array_unshift($product_ids, (int)$product_id);
        $pr_ids = array_slice($product_ids, 0, 20);
    
        $cookie_domain = '';
        if (isset($this->request->server['HTTP_HOST'])) {
            $host = $this->request->server['HTTP_HOST'];
            if (preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
                $cookie_domain = $host;
            }
        }
    
        setcookie('oct_product_views', implode(',', $pr_ids), time() + 60 * 60 * 24 * 30, '/', $cookie_domain);
    }
}