<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsSmsNotify extends Controller {

    public function order(&$route, &$data, &$output) {
    
        $oct_sms_settings = $this->config->get('oct_sms_settings');
        
        if (!$oct_sms_settings) {
            return;
        }
    
        $oct_order_info = $data[0] ?? null;

        if (!$oct_order_info) {
            return;
        }

        $oct_order_info['order_id'] = $output;
    
        $this->load->language('octemplates/oct_deals');
    
        $this->sendSmsNotification(
            $oct_sms_settings, 
            'oct_order_admin', 
            $oct_sms_settings['admin_phone'], 
            $oct_order_info, 
            'admin_order_sms_template'
        );
    
        $this->sendSmsNotification(
            $oct_sms_settings, 
            'oct_order_customer', 
            $oct_order_info['telephone'], 
            $oct_order_info, 
            'customer_order_sms_template'
        );
    }

    public function orderStatusNotify(&$route, &$data, &$output) {
        $oct_sms_settings = $this->config->get('oct_sms_settings');

        if (!$oct_sms_settings || empty($oct_sms_settings['status']) || empty($oct_sms_settings['order_status_items'])) {
            return;
        }

        $order_id = (int)$data[0];
        $order_status_id = (int)$data[1];

        if (!$order_id || !$order_status_id) {
            return;
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if (!$order_info || empty($order_info['telephone'])) {
            return;
        }

        $this->load->model('localisation/order_status');
        $order_status_info = $this->model_localisation_order_status->getOrderStatus($order_status_id);

        if (!$order_status_info) {
            return;
        }

        foreach ($oct_sms_settings['order_status_items'] as $status_item) {
            if (empty($status_item['order_status_ids']) || !is_array($status_item['order_status_ids'])) {
                continue;
            }

            if (in_array((string)$order_status_id, $status_item['order_status_ids'])) {
                $customer_name = strip_tags(trim($order_info['firstname'] . ' ' . $order_info['lastname']));
                $customer_name = mb_substr($customer_name, 0, 100);

                $template_data = array(
                    'order_id' => $order_id,
                    'store' => $this->config->get('config_name'),
                    'customer_name' => $customer_name,
                    'email' => strip_tags($order_info['email']),
                    'telephone' => strip_tags($order_info['telephone']),
                    'order_status' => strip_tags($order_status_info['name']),
                    'total' => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'])
                );

                $language_id = (int)$this->config->get('config_language_id');
                $sms_text = isset($status_item['lang'][$language_id]['text']) ? $status_item['lang'][$language_id]['text'] : '';

                if (!empty($sms_text)) {
                    foreach ($template_data as $key => $value) {
                        $sms_text = str_replace('[' . $key . ']', $value, $sms_text);
                    }

                    $this->load->model('octemplates/module/oct_sms_notify');
                    $this->model_octemplates_module_oct_sms_notify->sendNotification(array(
                        'phone' => $order_info['telephone'],
                        'message' => $sms_text,
                        'template_code' => 'oct_order_sms_templates',
                        'access_token' => $oct_sms_settings['oct_sms_token']
                    ));
                }

                break;
            }
        }
    }

    private function sendSmsNotification($oct_sms_settings, $template_code, $phone, $oct_order_info, $default_template_key) {
        if (!isset($oct_sms_settings['templates'][$template_code]['status']) || !$oct_sms_settings['templates'][$template_code]['status'] || empty($phone)) {
            return;
        }

        $language_id = (int)$oct_order_info['language_id'];
        $sms_template = $this->getSmsTemplate($oct_sms_settings, $template_code, $language_id, $default_template_key);

        if (!empty($sms_template)) {
            $formatted_total = $this->currency->format($oct_order_info['total'], $oct_order_info['currency_code'], $oct_order_info['currency_value']);

            $comment = strip_tags(isset($oct_order_info['comment']) ? $oct_order_info['comment'] : '');
            $comment = mb_substr($comment, 0, 300);

            $replace = array(
                '[customer_name]' => strip_tags($oct_order_info['firstname']),
                '[customer_lastname]' => strip_tags($oct_order_info['lastname']),
                '[email]' => strip_tags($oct_order_info['email']),
                '[telephone]' => strip_tags($oct_order_info['telephone']),
                '[comment]' => $comment,
                '[shipping_method]' => strip_tags($oct_order_info['shipping_method']),
                '[payment_method]' => strip_tags($oct_order_info['payment_method']),
                '[order_id]' => (int)$oct_order_info['order_id'],
                '[order_total]' => $formatted_total,
                '[store]' => $this->config->get('config_name')
            );

            $sms_message = str_replace(array_keys($replace), array_values($replace), $sms_template);

            $this->load->model('octemplates/module/oct_sms_notify');
            $this->model_octemplates_module_oct_sms_notify->sendNotification(array(
                'phone' => $phone,
                'message' => $sms_message,
                'template_code' => $template_code,
                'access_token' => $oct_sms_settings['oct_sms_token']
            ));
        }
    }

    private function getSmsTemplate($oct_sms_settings, $template_code, $language_id, $default_template_key) {
        if (isset($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && !empty($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
            return $oct_sms_settings['templates'][$template_code]['message'][$language_id];
        } else {
            return $this->language->get($default_template_key);
        }
    }
}