<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctReviewReminder extends Controller {

	public function cron() {
		$ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown';

		if (!$this->config->get('oct_review_reminder_status')) {
			return;
		}

		$this->load->model('octemplates/module/oct_abandoned_cart');

		if ($this->model_octemplates_module_oct_abandoned_cart->isRateLimited($ip, 'cron_review_reminder', 5, 300)) {
			return;
		}

		$cron_secret = isset($this->request->get['cron_secret']) ? (string)$this->request->get['cron_secret'] : '';
		$config_password = $this->config->get('oct_review_reminder_cron_password');

		if (!$cron_secret || $cron_secret !== $config_password) {
			if (!$this->model_octemplates_module_oct_abandoned_cart->checkRateLimit($ip, 'cron_review_reminder', 5, 300)) {
				$this->log->write('[SECURITY] Multiple failed Cron key attempts (Review Reminder) from IP: ' . $ip);
			}
			return;
		}

		$this->load->language('octemplates/module/oct_review_reminder');
		$this->load->model('octemplates/module/oct_review_reminder');
		$this->load->model('catalog/product');
		$this->load->model('checkout/order');

		$reminders = $this->model_octemplates_module_oct_review_reminder->getRemindersToSend();

		if (empty($reminders)) {
			echo 'No reminders to send.' . PHP_EOL;
			return;
		}

		$sent_emails_count = 0;
		$sent_sms_count = 0;

		foreach ($reminders as $reminder) {
			$order_info = $this->model_checkout_order->getOrder((int)$reminder['order_id']);

			if (!$order_info) {
				$this->model_octemplates_module_oct_review_reminder->delReminderRecord((int)$reminder['order_id']);
				continue;
			}

			$phone = $order_info['telephone'];
			$customer_email = filter_var($reminder['email'], FILTER_VALIDATE_EMAIL);

			if (!$customer_email && empty($phone)) {
				$this->model_octemplates_module_oct_review_reminder->delReminderRecord((int)$reminder['order_id']);
				continue;
			}

			if ($customer_email === $this->config->get('config_email')) {
				$customer_email = false;
			}

            $order_products = $this->model_checkout_order->getOrderProducts((int)$reminder['order_id']);
            $products_info = '';
            $products_sms_info = '';
            $review_link = '';
            $first_product = true;
            $sms_length = 0;

            foreach ($order_products as $order_product) {
                $product = $this->model_catalog_product->getProduct((int)$order_product['product_id']);

                if (!$product) {
                    continue;
                }

                $product_url = $this->url->link('product/product', 'product_id=' . (int)$product['product_id'], true);
                
                if ($first_product) {
                    $review_link = $product_url;
                    $first_product = false;
                }
                
                $safe_product_name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
                $products_info .= '<p><a href="' . htmlspecialchars($product_url, ENT_QUOTES, 'UTF-8') . '">' . $safe_product_name . '</a></p>';
                
                $sms_product_name = strip_tags($product['name']);
                $sms_product_name = mb_substr($sms_product_name, 0, 50);
                $product_sms_entry = $sms_product_name . ' ' . htmlspecialchars($product_url, ENT_QUOTES, 'UTF-8');
                $entry_length = mb_strlen($product_sms_entry);
                
                if ($sms_length + $entry_length + 1 <= 200) {
                    if (!empty($products_sms_info)) {
                        $products_sms_info .= ' ';
                        $sms_length += 1;
                    }
                    $products_sms_info .= $product_sms_entry;
                    $sms_length += $entry_length;
                }
            }			
        
            $language_id = (int)$reminder['language_id'];

			if ($customer_email) {
				if ($this->config->get('oct_review_reminder_etemplates_status')) {
					$email_templates = $this->config->get('oct_review_reminder_email_template');
					$subject_template = isset($email_templates[$language_id]['subject']) && !empty($email_templates[$language_id]['subject']) ? $email_templates[$language_id]['subject'] : $this->language->get('default_email_subject');
					$message_template = isset($email_templates[$language_id]['body']) && !empty($email_templates[$language_id]['body']) ? html_entity_decode($email_templates[$language_id]['body'], ENT_QUOTES, 'UTF-8') : $this->language->get('default_email_template');
				} else {
					$subject_template = $this->language->get('default_email_subject');
					$message_template = $this->language->get('default_email_template');
					if (count($order_products) > 1) {
						$message_template = $this->language->get('default_email_template_multiple');
					}
				}

				$mail = new Mail($this->config->get('config_mail_engine'));
				$mail->protocol = $this->config->get('config_mail_protocol');
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

				$mail->setTo($customer_email);
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
				$mail->setSubject(htmlspecialchars($subject_template, ENT_QUOTES, 'UTF-8'));

				$safe_customer_name = htmlspecialchars($reminder['customer_name'], ENT_QUOTES, 'UTF-8');
				$safe_store_name = htmlspecialchars($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

				$message = str_replace(
					array('[customer_name]', '[order_id]', '[products]', '[review_link]', '[store]'),
					array($safe_customer_name, (int)$reminder['order_id'], $products_info, htmlspecialchars($review_link, ENT_QUOTES, 'UTF-8'), $safe_store_name),
					$message_template
				);

				$mail->setHtml($message);
				$mail->send();

				$sent_emails_count++;
			}

			if (!empty($phone)) {
				$oct_sms_settings = $this->config->get('oct_sms_settings');

				if (empty($oct_sms_settings['oct_sms_token'])) {
					continue;
				}

				$template_code = 'oct_review_reminder';

				if (isset($oct_sms_settings['templates'][$template_code]['status']) && $oct_sms_settings['templates'][$template_code]['status']) {
					if (isset($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && !empty($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
						$sms_template = $oct_sms_settings['templates'][$template_code]['message'][$language_id];
					} else {
						$sms_template = $this->language->get('default_sms_template');
					}

					if (!empty($sms_template)) {
						$customer_name = strip_tags($reminder['customer_name']);
						$customer_name = mb_substr($customer_name, 0, 50);

						$products_sms_info = mb_substr($products_sms_info, 0, 200);
						
						if (!empty($products_sms_info)) {
							$products_sms_info .= ' ';
						}

						$replace = array(
							'[customer_name]' => $customer_name,
							'[order_id]' => (int)$reminder['order_id'],
							'[products]' => $products_sms_info,
							'[review_link]' => $review_link,
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

						$sent_sms_count++;
					}
				}
			}

			$this->model_octemplates_module_oct_review_reminder->markReminderAsSent((int)$reminder['order_id']);
		}

		echo 'Sent ' . $sent_emails_count . ' emails and ' . $sent_sms_count . ' SMS messages.' . PHP_EOL;
	}
}