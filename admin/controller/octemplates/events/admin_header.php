<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsAdminHeader extends Controller {

    public function headerBack(&$route, &$data) {

        $this->language->load('octemplates/oct_deals');

        if ($this->config->get('theme_oct_deals_alert_status') && isset($this->session->data['user_token'])) {
            $data['oct_alert_status'] = $this->config->get('theme_oct_deals_alert_status');

            $data['oct_alert_data'] = $oct_alert_data = $this->config->get('theme_oct_deals_alert_data');
            $oct_total_calls = $oct_total_abandoned_cart = $oct_total_found_cheaper = $oct_total_reviews = $oct_total_faqs = $oct_total_stock_notifier = $product_total = $review_total = $return_total = 0;

            if (isset($oct_alert_data['oct_modules']) && $oct_alert_data['oct_modules']) {
                if ($this->config->get('oct_popup_call_phone_status')) {
                    $this->load->model('octemplates/module/oct_popup_call_phone');

                    $filter_data = [
                        'filter_processed' => 0
                    ];

                    $data['oct_total_calls'] = $oct_total_calls = $this->model_octemplates_module_oct_popup_call_phone->getTotalCallArray($filter_data);
                    $data['oct_popup_call_phone'] = $this->url->link('octemplates/module/oct_popup_call_phone', 'user_token=' . $this->session->data['user_token'], true);
                }

                $oct_total_stock_notifier = 0;

                if ($this->config->get('oct_stock_notifier_status')) {
                    $this->load->model('octemplates/module/oct_stock_notifier');

                    $filter_data = [
                        'filter_processed' => 0
                    ];

                    $data['oct_total_stock_notifier'] = $oct_total_stock_notifier = $this->model_octemplates_module_oct_stock_notifier->getTotalCallArray($filter_data);
                    $data['oct_stock_notifier'] = $this->url->link('octemplates/module/oct_stock_notifier&tab=subscribers&filter_status=0', 'user_token=' . $this->session->data['user_token'], true);
                }

                if ($this->config->get('oct_popup_found_cheaper_status')) {
                    $this->load->model('octemplates/module/oct_popup_found_cheaper');

                    $filter_data = [
                        'filter_processed' => 0
                    ];

                    $data['oct_total_found_cheaper'] = $oct_total_found_cheaper = $this->model_octemplates_module_oct_popup_found_cheaper->getTotalCallArray($filter_data);
                    $data['oct_popup_found_cheaper'] = $this->url->link('octemplates/module/oct_popup_found_cheaper', 'user_token=' . $this->session->data['user_token'], true);
                }

                if ($this->config->get('oct_sreview_setting_status')) {
                    $this->load->model('octemplates/module/oct_sreview_reviews');

                    $filter_data = [
                        'filter_status' => 0
                    ];

                    $data['oct_total_reviews'] = $oct_total_reviews = $this->model_octemplates_module_oct_sreview_reviews->getTotalReviews($filter_data);
                    $data['oct_reviews'] = $this->url->link('octemplates/module/oct_sreview_reviews', 'user_token=' . $this->session->data['user_token'] . '&filter_status=0', true);
                }

                $abandoned_data = $this->config->get('oct_abandoned_cart');
                if (isset($abandoned_data['status']) && $abandoned_data['status']) {
                    $this->load->model('octemplates/module/oct_abandoned_cart');

                    $filter_data = [
                        'filter_status' => 'active'
                    ];

                    $data['oct_total_abandoned_cart'] = $oct_total_abandoned_cart = $this->model_octemplates_module_oct_abandoned_cart->getTotalAbandonedCarts($filter_data);
                    $data['oct_abandoned_cart'] = $this->url->link('octemplates/module/oct_abandoned_cart', 'user_token=' . $this->session->data['user_token'] . '&filter_status=0', true);
                }

                $this->load->model('octemplates/faq/oct_product_faq');

                $filter_data = [
                    'filter_status' => 0
                ];

                $data['oct_total_faqs'] = $oct_total_faqs = $this->model_octemplates_faq_oct_product_faq->getTotalFaqs($filter_data);
                $data['oct_faqs'] = $this->url->link('octemplates/faq/oct_product_faq', 'user_token=' . $this->session->data['user_token'] . '&filter_status=0', true);
            }

            if (isset($oct_alert_data['orders']) && $oct_alert_data['orders']) {
                // Orders
                $this->load->model('sale/order');

                // Processing Orders
                $data['processing_status_total'] = $this->model_sale_order->getTotalOrders(array('filter_order_status' => implode(',', $this->config->get('config_processing_status'))));
                $data['processing_status'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&filter_order_status=' . implode(',', $this->config->get('config_processing_status')), true);

                // Complete Orders
                $data['complete_status_total'] = $this->model_sale_order->getTotalOrders(array('filter_order_status' => implode(',', $this->config->get('config_complete_status'))));
                $data['complete_status'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&filter_order_status=' . implode(',', $this->config->get('config_complete_status')), true);

                // Returns
                $this->load->model('sale/return');

                $return_total = $this->model_sale_return->getTotalReturns(array('filter_return_status_id' => $this->config->get('config_return_status_id')));

                $data['return_total'] = $return_total;

                $data['return'] = $this->url->link('sale/return', 'user_token=' . $this->session->data['user_token'], true);
            }

            if (isset($oct_alert_data['products']) && $oct_alert_data['products']) {
                // Products
                $this->load->model('catalog/product');

                $product_total = $this->model_catalog_product->getTotalProducts(array('filter_quantity' => 0));

                $data['product_total'] = $product_total;

                $data['product'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&filter_quantity=0', true);

                // Reviews
                $this->load->model('catalog/review');

                $review_total = $this->model_catalog_review->getTotalReviews(array('filter_status' => 0));

                $data['review_total'] = $review_total;

                $data['review'] = $this->url->link('catalog/review', 'user_token=' . $this->session->data['user_token'] . '&filter_status=0', true);
            }

            $data['oct_alerts'] = $oct_total_calls + $oct_total_abandoned_cart + $oct_total_found_cheaper + $oct_total_stock_notifier + $oct_total_reviews + $oct_total_faqs + $product_total + $review_total + $return_total;

            $this->config->set('oct_header_data', $data);   
        } 
    }

    public function headerFront(&$route, &$data) {
       
        $admin_header_data = $this->config->get('oct_header_data');
        
        if ($admin_header_data) {
            $data = array_merge($data, $admin_header_data);
        }
    }

    public function headerAddOctData(&$route, &$data, &$output) {

        $fontawesome_free = '<link href="view/stylesheet/fontawesome-free-6.5.2-web/css/all.css" type="text/css" rel="stylesheet" />';
        $output = str_replace("<head>", "<head>\r\n". $fontawesome_free, $output);

        $search = '<ul class="nav navbar-nav navbar-right">';
        $add = $this->load->view('octemplates/events/admin_header', $data);
        $pos = strpos($output, $search);

        if ($pos !== false) {
            $output = substr_replace($output, $search. $add, $pos, strlen($search));
        }
    }
}