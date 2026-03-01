<?php

class ControllerExtensionPaymentTBank extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/tbank');
        // Онлайн-оплата через T-Bank API отключена по решению владельца.
        // Клиент выбирает этот способ, оформляет заказ, затем менеджер
        // присылает ссылку на оплату вручную.
        return $this->load->view('extension/payment/tbank');
    }

    public function confirm()
    {
        $json = [];

        if (isset($this->session->data['payment_method']['code'])
            && $this->session->data['payment_method']['code'] == 'tbank'
        ) {
            $this->load->model('checkout/order');

            // Подтверждаем заказ со статусом «В ожидании» (id=1)
            // Менеджер получает заказ и вручную присылает ссылку на оплату
            $status_id = (int)$this->config->get('payment_tbank_order_status_id');
            if (!$status_id) {
                $status_id = 1; // Pending — fallback
            }

            $this->model_checkout_order->addOrderHistory(
                $this->session->data['order_id'],
                $status_id
            );

            $json['redirect'] = $this->url->link('checkout/success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function callback()
    {
        // API-процессинг отключён
        die('DISABLED');
    }

    public function failure()
    {
        $this->load->language('extension/payment/tbank');
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        return $this->response->setOutput($this->load->view('extension/payment/tbank_failure', $data));
    }

    public function success()
    {
        $this->load->language('extension/payment/tbank');
        $this->cart->clear();
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        return $this->response->setOutput($this->load->view('extension/payment/tbank_success', $data));
    }
}
