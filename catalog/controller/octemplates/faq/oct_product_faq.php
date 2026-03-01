<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesFaqOCTProductFaq extends Controller {
	public function index() {
		$is_ajax = false;
		if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
			!empty($this->request->server['HTTP_X_REQUESTED_WITH']) &&
			strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$is_ajax = true;
		}
		
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
		
		$data['product_id'] = (int) (
			$this->request->post['product_id'] 
			?? $this->request->get['product_id'] 
			?? $this->request->get['oct_product_id'] 
			?? 0
		);
		
		$this->load->language('octemplates/oct_deals');
		$this->load->model('octemplates/faq/oct_product_faq');
		
		if ($this->customer->isLogged()) {
			$data['email_user'] = $this->customer->getEmail();
			$data['firstname']  = $this->customer->getFirstName();
		} else {
			$data['email_user'] = false;
			$data['firstname']  = false;
		}
		
		$faq_total = $this->model_octemplates_faq_oct_product_faq->getTotalFaqsByProductId($data['product_id']);
        $limit = $this->config->get('theme_oct_deals_data_pr_reviews_limit') ? (int) $this->config->get('theme_oct_deals_data_pr_reviews_limit') : 20;
		$offset  = ($page - 1) * $limit;
		
		$results = $this->model_octemplates_faq_oct_product_faq->getFaqsByProductId($data['product_id'], $offset, $limit);
		$data['oct_faqs'] = array();
		foreach ($results as $result) {
			$data['oct_faqs'][] = array(
				'author'        => $result['author'],
				'text'          => nl2br($result['text']),
				'answer'        => nl2br($result['answer']),
				'date_added'    => $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($result['date_added'], 1)),
				'date_modified' => $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($result['date_modified'], 1))
			);
		}
		
		$has_more = ($offset + $limit < $faq_total);
		$data['has_more']  = $has_more;
		$data['next_page'] = $page + 1;
		
		$data['oct_id_div']  = (isset($this->request->get['p_id'])) ? 'popup_product_questions' : 'product_questions';
		$data['oct_faqs_id'] = (isset($this->request->get['p_id'])) ? 'poup_oct_faqs' : 'oct_faqs';
		
		$data['is_ajax'] = $is_ajax;

        $output = $this->load->view('octemplates/faq/oct_product_faq', $data);

		if ($data['is_ajax']) {
			$this->response->setOutput($this->load->view('octemplates/faq/oct_product_faq', $data));
        } else {
            return $output;
        }
	}	

	public function write() {
		$json = [];

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->load->model('catalog/product');
			$this->load->language('octemplates/oct_deals');

			if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 20)) {
				$json['error']['name'] = $this->language->get('error_faq_name');
			}

			if (isset($this->request->post['email']) && (!empty($this->request->post['email'])) && (utf8_strlen($this->request->post['email']) > 96 || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $this->request->post['email']))) {
				$json['error']['email'] = $this->language->get('error_faq_email');
			}

			if ((utf8_strlen($this->request->post['text']) < 20) || (utf8_strlen($this->request->post['text']) > 1000)) {
				$json['error']['text'] = $this->language->get('error_faq_text');
			}

			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('oct_faq', (array)$this->config->get('config_captcha_page'))) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

				if ($captcha) {
					$json['error']['captcha'] = $captcha;
				}
			}

			$product_id = 0;
			if (isset($this->request->get['faqp_id'])) {
				$product_id = (int)$this->request->get['faqp_id'];
			} elseif (isset($this->request->get['product_id'])) {
				$product_id = (int)$this->request->get['product_id'];
			}

			if ($product_id > 0) {
				$product_info = $this->model_catalog_product->getProduct($product_id);

				if ($product_info && !isset($json['error'])) {
					$this->load->model('octemplates/faq/oct_product_faq');

					$this->model_octemplates_faq_oct_product_faq->addFaq($product_id, $this->request->post);

					$store_name = $this->config->get('config_name');

					$message = sprintf($this->language->get('text_faq_email_welcome'), html_entity_decode($store_name, ENT_QUOTES, 'UTF-8')) . "\n\n";
					$message .= sprintf($this->language->get('text_faq_email_body'), $product_info['name'], $this->url->link('product/product', 'product_id=' . $product_id)) . "\n\n";
					$message .= html_entity_decode($store_name, ENT_QUOTES, 'UTF-8');

					$mail = new Mail($this->config->get('config_mail_engine'));
					$mail->protocol = $this->config->get('config_mail_protocol');
					$mail->parameter = $this->config->get('config_mail_parameter');
					$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
					$mail->smtp_username = $this->config->get('config_mail_smtp_username');
					$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
					$mail->smtp_port = $this->config->get('config_mail_smtp_port');
					$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

					$mail->setTo($this->config->get('config_email'));
					$mail->setFrom($this->config->get('config_email'));
					$mail->setSender(html_entity_decode($store_name, ENT_QUOTES, 'UTF-8'));
					$mail->setSubject(sprintf($this->language->get('text_faq_email_subject'), html_entity_decode($store_name, ENT_QUOTES, 'UTF-8')));
					$mail->setText($message);
					$mail->send();

					$json['success'] = $this->language->get('text_faq_success');

					$oct_sms_settings = $this->config->get('oct_sms_settings');
					$template_code = 'oct_product_faq';
					$language_id = (int)$this->config->get('config_language_id');

					if (isset($oct_sms_settings['templates'][$template_code]['status']) && $oct_sms_settings['templates'][$template_code]['status']) {
						if (isset($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && !empty($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
							$sms_template = $oct_sms_settings['templates'][$template_code]['message'][$language_id];
						} else {
							$sms_template = $this->language->get('default_sms_template_oct_product_faq');
						}

						if (!empty($sms_template)) {
							$customer_name = '';
							if (isset($this->request->post['name'])) {
								$customer_name = strip_tags($this->request->post['name']);
								$customer_name = mb_substr($customer_name, 0, 50);
							}

							$product_name = strip_tags($product_info['name']);
							$product_name = mb_substr($product_name, 0, 100);

							$replace = array(
								'[customer_name]' => $customer_name,
								'[product_name]' => $product_name,
								'[product_link]' => $this->url->link('product/product', 'product_id=' . $product_id),
								'[store]' => $this->config->get('config_name')
							);

							$sms_message = str_replace(array_keys($replace), array_values($replace), $sms_template);

							if (!empty($oct_sms_settings['admin_phone']) && !empty($oct_sms_settings['oct_sms_token'])) {
								$this->load->model('octemplates/module/oct_sms_notify');

								$this->model_octemplates_module_oct_sms_notify->sendNotification(array(
									'phone' => $oct_sms_settings['admin_phone'],
									'message' => $sms_message,
									'template_code' => $template_code,
									'access_token' => $oct_sms_settings['oct_sms_token']
								));
							}
						}
					}
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
