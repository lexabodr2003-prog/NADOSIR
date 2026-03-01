<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsHelper extends Controller {

    public function compareRemove() {
        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

            $this->load->language('octemplates/oct_deals');
        
            $json = array();
        
            if (!isset($this->session->data['compare']) || !isset($this->request->post['product_id'])) {
                $json['error'] = $this->language->get('text_empty');
            } else {
                $product_id = (int)$this->request->post['product_id'];
        
                $this->load->model('catalog/product');
        
                $product_info = $this->model_catalog_product->getProduct($product_id);
        
                if ($product_info) {
                    if (in_array($product_id, $this->session->data['compare'])) {
                        $key = array_search($product_id, $this->session->data['compare']);
                        unset($this->session->data['compare'][$key]);
                        $json['success'] = sprintf($this->language->get('compare_remove_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('product/compare'));
                        $json['total_compare'] = (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0);
                    }
                }
            }
        
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}
    }

    public function wishlistRemove() {
        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->load->language('octemplates/oct_deals');
        
            $json = array();
        
            if (isset($this->request->post['product_id'])) {
                $product_id = (int)$this->request->post['product_id'];
            } else {
                $product_id = 0;
            }
        
            $this->load->model('catalog/product');
        
            $product_info = $this->model_catalog_product->getProduct($product_id);
        
            if ($product_info) {
                if ($this->customer->isLogged()) {
                    // Edit customers wishlist
                    $this->load->model('account/wishlist');
        
                    $this->model_account_wishlist->deleteWishlist($this->request->post['product_id']);
        
                    $json['success'] = sprintf($this->language->get('wishlist_remove_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));
                    $json['total_wishlist'] = $this->model_account_wishlist->getTotalWishlist();
                } else {
                    if (isset($this->session->data['wishlist']) && (($key = array_search($this->request->post['product_id'], $this->session->data['wishlist'])) !== false)) {
                        unset($this->session->data['wishlist'][$key]);
                    }
        
                    $json['success'] = sprintf($this->language->get('wishlist_remove_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));
                    $json['total_wishlist'] = (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0);
                }
            }
        
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}
    }

    public function allCartProducts() {
        
        $this->load->model('octemplates/helper');
		$product_ids = $this->model_octemplates_helper->getOctCartProducts();

		if (!empty($product_ids)) {
			return implode(',', $product_ids);
		}

		return '';
    }

    public function octReviewReputation() {
        $json = array();

        $this->load->language('octemplates/oct_deals');

        if (isset($this->request->get['review_id'])) {

            $this->load->model('octemplates/helper');

            $check_ip = $this->model_octemplates_helper->checkOctUserIp($this->request->server['REMOTE_ADDR'], $this->request->get['review_id']);

            if ($check_ip) {
            $json['error'] = $this->language->get('error_ip_exist');
            }

            if (!isset($json['error'])) {

                $filter_data = array(
                    'review_id' => (int)$this->request->get['review_id'],
                    'ip' => $this->request->server['REMOTE_ADDR']
                );

                $this->model_octemplates_helper->addOctProductReputation($filter_data);

                $json['success'] = $this->language->get('text_ip_vote_success');

            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function octProductReviews() {

        $product_id = $this->request->post['product_id']
                   ?? $this->request->get['product_id']
                   ?? $this->request->get['oct_product_id']
                   ?? 0;

        $data['product_id'] = (int)$product_id;

        $this->load->language('product/product');
        $this->load->model('catalog/review');
        $this->load->model('octemplates/helper');

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $reviews_per_page = $this->config->get('theme_oct_deals_data_pr_reviews_limit') ? (int) $this->config->get('theme_oct_deals_data_pr_reviews_limit') : 20;
        
        $review_total = $this->model_catalog_review->getTotalReviewsByProductId($data['product_id']);
        
        $results = $this->model_catalog_review->getReviewsByProductId(
            $data['product_id'],
            ($page - 1) * $reviews_per_page,
            $reviews_per_page
        );

        $data['reviews'] = array();
        foreach ($results as $result) {
            $oct_review_data = $this->model_octemplates_helper->getOctReviewData($result['review_id']);
            $data['reviews'][] = array(
                'author'         => $result['author'],
                'positive_text'  => isset($oct_review_data['positive_text']) ? $oct_review_data['positive_text'] : '',
                'negative_text'  => isset($oct_review_data['negative_text']) ? $oct_review_data['negative_text'] : '',
                'admin_answer'   => isset($oct_review_data['admin_answer']) ? nl2br($oct_review_data['admin_answer']) : '',
                'positive_votes' => isset($oct_review_data['positive_votes']) ? $oct_review_data['positive_votes'] : 0,
                'review_id'      => $result['review_id'],
                'text'           => nl2br($result['text']),
                'rating'         => (int)$result['rating'],
                'date_added'     => $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($result['date_added'], 1))
            );
        }
        
        $data['has_more'] = (($page * $reviews_per_page) < $review_total) ? true : false;
        $data['next_page'] = $page + 1;
        
        $data['ajax'] = false;
        if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) &&
            strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $data['ajax'] = true;
        }

        $output = $this->load->view('product/review', $data);

        if ($data['ajax']) {
            $this->response->setOutput($output);
        } else {
            return $output;
        }
    }
    
    public function octMassGenerateBoughtTogether() {
        $json = array();

        $this->load->model('octemplates/module/oct_abandoned_cart');
        $ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown';

        if ($this->model_octemplates_module_oct_abandoned_cart->isRateLimited($ip, 'cron_bought_together', 5, 300)) {
            $json['error'] = 'Too many requests. Access temporarily blocked.';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $cron_pass = isset($this->request->get['cron_pass']) ? (string)$this->request->get['cron_pass'] : '';
        $valid_password = $this->config->get('theme_oct_deals_data_bought_together_cron');

        if (!$valid_password) {
            $json['error'] = 'No valid password found in config.';
        } elseif (!$cron_pass || $cron_pass !== $valid_password) {
            if (!$this->model_octemplates_module_oct_abandoned_cart->checkRateLimit($ip, 'cron_bought_together', 5, 300)) {
                $this->log->write('[SECURITY] Multiple failed Cron key attempts (Bought Together) from IP: ' . $ip);
            }
            $json['error'] = 'Invalid password.';
        } else {
            $this->load->model('octemplates/helper');
            $this->model_octemplates_helper->octMassGenerateBoughtTogether();
            $json['success'] = 'Bought Together cache has been updated successfully!';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function manualSendTtn() {
        $this->load->language('octemplates/oct_deals');
        $this->load->model('octemplates/helper');
        $this->load->model('checkout/order');

        $response = [];
        $oct_sms_settings = $this->config->get('oct_sms_settings');
        $oct_sms_settings = is_array($oct_sms_settings) ? $oct_sms_settings : [];

        if (empty($this->request->post) || !isset($this->request->post['oct_sms_token'])) {
            $response['error'] = 'Invalid request.';
            return $this->sendJsonResponse($response);
        }

        if (empty($oct_sms_settings['oct_sms_token'])) {
            $response['error'] = 'Invalid API key in module settings.';
            return $this->sendJsonResponse($response);
        }

        $this->load->model('octemplates/module/oct_abandoned_cart');
        $ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown';

        if ($this->model_octemplates_module_oct_abandoned_cart->isRateLimited($ip, 'manual_send_ttn_fail', 5, 300)) {
            $response['error'] = 'Too many requests. Access temporarily blocked.';
            return $this->sendJsonResponse($response);
        }

        $oct_sms_token = isset($this->request->post['oct_sms_token']) ? $this->sanitizeString($this->request->post['oct_sms_token']) : '';
        if (!hash_equals($oct_sms_settings['oct_sms_token'], $oct_sms_token)) {
            if (!$this->model_octemplates_module_oct_abandoned_cart->checkRateLimit($ip, 'manual_send_ttn_fail', 5, 300)) {
                $this->log->write('[SECURITY] Multiple failed oct_sms_token attempts from IP: ' . $ip);
            }

            $response['error'] = 'Invalid API key.';
            return $this->sendJsonResponse($response);
        }

        $order_id = isset($this->request->post['order_id']) ? (int)$this->request->post['order_id'] : 0;
        $order_status_id = isset($this->request->post['order_status_id']) ? (int)$this->request->post['order_status_id'] : 0;
        $email_notify = isset($this->request->post['email_notify']) && $this->request->post['email_notify'] ? true : false;

        if (!isset($this->request->post['order_sms_id']) || !is_numeric($this->request->post['order_sms_id'])) {
            $response['error'] = 'Missing or invalid required parameters.';
            return $this->sendJsonResponse($response);
        }

        $order_sms_id = (int)$this->request->post['order_sms_id'];

        if ($order_id <= 0) {
            $response['error'] = 'Invalid order ID.';
            return $this->sendJsonResponse($response);
        }

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            $response['error'] = 'Order not found or not completed.';
            return $this->sendJsonResponse($response);
        }

        if (empty($order_info['telephone'])) {
            $response['error'] = 'Customer phone number is missing.';
            return $this->sendJsonResponse($response);
        }

        $order_templates = isset($oct_sms_settings['order_templates']) ? $oct_sms_settings['order_templates'] : [];
        if (!is_array($order_templates) || !isset($order_templates[$order_sms_id])) {
            $response['error'] = 'SMS template not found.';
            return $this->sendJsonResponse($response);
        }

        $language_id = (int)$this->config->get('config_language_id');
        $template_data = $order_templates[$order_sms_id];
        $sms_message = '';

        $order_sms_edit = isset($oct_sms_settings['order_sms_edit']) ? $oct_sms_settings['order_sms_edit'] : false;
        if ($order_sms_edit && isset($this->request->post['sms_message'])) {
            $sms_message = $this->sanitizeString($this->request->post['sms_message']);
            $sms_message = mb_substr($sms_message, 0, 300);
        }

        if (!$sms_message) {
            if (!empty($template_data['lang'][$language_id]['text'])) {
                $sms_message = $template_data['lang'][$language_id]['text'];
            }
        }

        if (!trim($sms_message)) {
            $response['error'] = 'SMS template not found or empty.';
            return $this->sendJsonResponse($response);
        }

        $shipping_ttn = '';
        if (isset($this->request->post['shipping_ttn'])) {
            $shipping_ttn = $this->sanitizeString($this->request->post['shipping_ttn']);
            $shipping_ttn = mb_substr($shipping_ttn, 0, 50);
        }

        $tracking_url = '';
        if (isset($this->request->post['tracking_url'])) {
            $tracking_url = filter_var($this->request->post['tracking_url'], FILTER_SANITIZE_URL);
            if (filter_var($tracking_url, FILTER_VALIDATE_URL)) {
                $tracking_url = mb_substr($tracking_url, 0, 200);
            } else {
                $tracking_url = '';
            }
        }

        $replace = [
            '[customer_name]' => isset($order_info['firstname']) ? $this->sanitizeString($order_info['firstname']) : '',
            '[ttn]' => $shipping_ttn,
            '[tracking_url]' => $tracking_url,
            '[store]' => $this->config->get('config_name'),
            '[order_id]' => $order_id,
            '[total]' => isset($order_info['total']) ? $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']) : '',
            '[city]' => isset($order_info['shipping_city']) ? $this->sanitizeString($order_info['shipping_city']) : '',
            '[address]' => isset($order_info['shipping_address_1']) ? $this->sanitizeString($order_info['shipping_address_1']) : ''
        ];

        $replaced_message = str_replace(array_keys($replace), array_values($replace), $sms_message);

        $this->load->model('octemplates/module/oct_sms_notify');
        $this->model_octemplates_module_oct_sms_notify->sendNotification([
            'phone' => $order_info['telephone'],
            'message' => $replaced_message,
            'template_code' => 'oct_order_sms_templates',
            'access_token' => $oct_sms_settings['oct_sms_token']
        ]);

        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $replaced_message, $email_notify);

        $response['success'] = 'SMS sent successfully.';

        $this->sendJsonResponse($response);
    }
    
    private function sendJsonResponse($response) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

    private function sanitizeString($input) {
        return strip_tags(trim($input));
    }
}