<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctAbandonedCart extends Controller {
	private $cookieName = 'oct_abandoned_cart_token';

	public function setOrUpdateCookie() {
		if (!$this->checkModuleStatus()) {
			return;
		}

		$this->load->model('octemplates/module/oct_abandoned_cart');
		$ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown';

		if ($this->model_octemplates_module_oct_abandoned_cart->isRateLimited($ip, 'csrf_token_fail', 10, 300)) {
			exit('Access denied');
		}

		if ($this->request->server['REQUEST_METHOD'] === 'POST') {
			$token = $this->request->post['smart_csrf_token'] ?? '';
			$session_token = $this->session->data['smart_csrf_token'] ?? '';

			if (empty($token) || empty($session_token) || !hash_equals($session_token, $token)) {
				if (!$this->model_octemplates_module_oct_abandoned_cart->checkRateLimit($ip, 'csrf_token_fail', 10, 300)) {
					$this->log->write('[SECURITY] Multiple failed CSRF token attempts in setOrUpdateCookie from IP: ' . $ip);
				}
				return;
			}
		}

		$module_settings = $this->config->get('oct_abandoned_cart') ?: [];

		if (empty($module_settings['api_key']) || !preg_match('/^[a-f0-9]{32,}$/i', $module_settings['api_key'])) {
			return;
		}

		$cart_products = $this->cart->getProducts();
		$filtered_cart_data = [];

		foreach ($cart_products as $product) {
			$filtered_options = [];
			foreach ($product['option'] as $option) {
				$filtered_options[] = [
					'product_option_id'       => (int)$option['product_option_id'],
					'product_option_value_id' => (int)$option['product_option_value_id'],
					'option_id'               => (int)$option['option_id'],
					'option_value_id'         => (int)$option['option_value_id'],
					'name'                    => $this->sanitizeString($option['name']),
					'value'                   => $this->sanitizeString($option['value'])
				];
			}

			$filtered_cart_data[] = [
				'product_id' => (int)$product['product_id'],
				'quantity'   => (int)$product['quantity'],
				'option'     => $filtered_options
			];
		}

		$data = [
			'cart_data'   => $filtered_cart_data,
			'customer_id' => $this->customer->isLogged() ? (int)$this->customer->getId() : 0,
			'firstname'   => isset($this->request->post['firstname']) ? $this->sanitizeString($this->request->post['firstname']) : ($this->customer->getFirstName() ? $this->sanitizeString($this->customer->getFirstName()) : ''),
			'lastname'    => isset($this->request->post['lastname']) ? $this->sanitizeString($this->request->post['lastname']) : ($this->customer->getLastName() ? $this->sanitizeString($this->customer->getLastName()) : ''),
			'email'       => isset($this->request->post['email']) ? filter_var($this->request->post['email'], FILTER_SANITIZE_EMAIL) : ($this->customer->getEmail() ? filter_var($this->customer->getEmail(), FILTER_SANITIZE_EMAIL) : ''),
			'phone'       => isset($this->request->post['telephone']) ? $this->sanitizeString($this->request->post['telephone'], 32, true) : ($this->customer->getTelephone() ? $this->sanitizeString($this->customer->getTelephone(), 32, true) : ''),
			'store_id'    => (int)$this->config->get('config_store_id'),
			'store_name'  => $this->sanitizeString($this->config->get('config_name')),
			'language_id' => (int)$this->config->get('config_language_id')
		];

		$this->load->model('octemplates/module/oct_abandoned_cart');

		if ($this->customer->isLogged()) {
			$customer_id = (int)$this->customer->getId();
			$existing_cart = $this->model_octemplates_module_oct_abandoned_cart->getAbandonedCartByCustomerId($customer_id);

			if ($existing_cart) {
				$cookie_lifetime_seconds = (int)$module_settings['cookie_lifetime'] * 24 * 60 * 60;
				$cookie_expiry_time = strtotime($existing_cart['date_added']) + $cookie_lifetime_seconds;

				if ($cookie_expiry_time > time()) {
					$data['cookie_token'] = $existing_cart['cookie_token'];
					$data['cookie_signature'] = $existing_cart['cookie_signature'];

					$this->setCookie(
						$existing_cart['cookie_token'],
						$existing_cart['cookie_signature'],
						$cookie_expiry_time
					);

					$data = $this->prepareAbandonedData($data);
					$this->model_octemplates_module_oct_abandoned_cart->saveAbandonedCartData($data);
					return;
				}
			}
		}

		if (empty($this->request->cookie[$this->cookieName])) {
			try {
				$raw_bytes = random_bytes(16);
				$raw_token = rtrim(strtr(base64_encode($raw_bytes), '+/', '-_'), '=');
			} catch (Exception $e) {
				$this->log->write('Error generating token: ' . $e->getMessage());
				return;
			}

			$api_key = $module_settings['api_key'] ?? '';
			$signature = $this->makeSignature($raw_token, $api_key);

			$lifetime = time() + (int)$module_settings['cookie_lifetime'] * 24 * 60 * 60;
			$this->setCookie($raw_token, $signature, $lifetime);

			$data['cookie_token'] = $raw_token;
			$data['cookie_signature'] = $signature;
		} else {
			if (strpos($this->request->cookie[$this->cookieName], '|') !== false) {
				list($raw_token, $signature) = explode('|', $this->request->cookie[$this->cookieName], 2);

				if (!$this->customer->isLogged()) {
					$existing_guest_cart = $this->model_octemplates_module_oct_abandoned_cart->getAbandonedCartGuests($raw_token, $signature);
					if (!$existing_guest_cart) {
						$this->deleteCookie();
						try {
							$raw_bytes = random_bytes(16);
							$raw_token = rtrim(strtr(base64_encode($raw_bytes), '+/', '-_'), '=');
						} catch (Exception $e) {
							$this->log->write('Error generating token: ' . $e->getMessage());
							return;
						}
						$api_key = $module_settings['api_key'] ?? '';
						$signature = $this->makeSignature($raw_token, $api_key);
						$lifetime = time() + (int)$module_settings['cookie_lifetime'] * 24 * 60 * 60;
						$this->setCookie($raw_token, $signature, $lifetime);
					}
				}
				$data['cookie_token'] = $raw_token;
				$data['cookie_signature'] = $signature;
			} else {
				$this->deleteCookie();
				return;
			}
		}

		$data = $this->prepareAbandonedData($data);
		$this->model_octemplates_module_oct_abandoned_cart->saveAbandonedCartData($data);
	}

	public function cron() {
		if (!$this->checkModuleStatus()) {
			return;
		}

		$this->load->language('octemplates/module/oct_abandoned_cart');
		$this->load->model('octemplates/module/oct_abandoned_cart');

		$module_settings = $this->config->get('oct_abandoned_cart') ?: [];

		$ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown';

		if ($this->model_octemplates_module_oct_abandoned_cart->isRateLimited($ip, 'cron_abandoned_cart', 5, 300)) {
			exit('Access denied');
		}

		$cron_pass_req = isset($this->request->get['cron_pass']) ? $this->request->get['cron_pass'] : '';
		if (($module_settings['cron_password'] ?? '') !== $cron_pass_req) {
			if (!$this->model_octemplates_module_oct_abandoned_cart->checkRateLimit($ip, 'cron_abandoned_cart', 5, 300)) {
				$this->log->write('[SECURITY] Multiple failed Cron key attempts (Abandoned Cart) from IP: ' . $ip);
			}
			exit($this->language->get('text_invalid_cron_pass'));
		}

		$this->model_octemplates_module_oct_abandoned_cart->sendAbandonedReminders();

		$this->model_octemplates_module_oct_abandoned_cart->deleteConvertedCarts();

		$this->model_octemplates_module_oct_abandoned_cart->deleteExpiredCarts();

		$this->model_octemplates_module_oct_abandoned_cart->deleteExpiredShortLinks();

		exit($this->language->get('text_cron_done'));
	}

	public function manualSend() {
		if (!$this->checkModuleStatus()) {
			return;
		}

		$module_settings = $this->config->get('oct_abandoned_cart') ?: [];

		if (empty($module_settings['api_key']) || !preg_match('/^[a-f0-9]{32,}$/i', $module_settings['api_key'])) {
			$this->log->write('Invalid API key in module settings.');
			exit($this->language->get('text_error_invalid_settings'));
		}

		$this->load->model('octemplates/module/oct_abandoned_cart');
		$ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown';

		if ($this->model_octemplates_module_oct_abandoned_cart->isRateLimited($ip, 'manual_send_fail', 5, 300)) {
			exit('Access denied');
		}

		$api_key_req = isset($this->request->post['api_key']) ? $this->request->post['api_key'] : '';
		if ($api_key_req !== $module_settings['api_key']) {
			if (!$this->model_octemplates_module_oct_abandoned_cart->checkRateLimit($ip, 'manual_send_fail', 5, 300)) {
				$this->log->write('[SECURITY] Multiple failed API key attempts from IP: ' . $ip);
			}
			exit($this->language->get('text_error_invalid_api_key'));
		}

		$cart_id = isset($this->request->post['cart_id']) ? (int)$this->request->post['cart_id'] : 0;
		if ($cart_id <= 0) {
			exit($this->language->get('text_manual_send_nothing'));
		}

		$res = $this->model_octemplates_module_oct_abandoned_cart->sendOneAbandoned($cart_id);

		$this->load->language('octemplates/module/oct_abandoned_cart');

		$response = [];

		if ($res) {
			$response['success'] = true;
			$response['abandoned_cart_id'] = $res['abandoned_cart_id'];
			$response['reminder_count'] = $res['reminder_count'];
			$response['last_reminder'] = $res['last_reminder'];
		} else {
			$response['success'] = false;
			$response['message'] = $this->language->get('text_manual_send_nothing');
		}

		header('Content-Type: application/json');
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function restoreCart() {
		if (!$this->checkModuleStatus()) {
			$this->response->redirect($this->url->link('common/home'));
			return;
		}

		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('checkout/checkout', '', true));
			return;
		}

		$this->load->language('octemplates/module/oct_abandoned_cart');
		$this->load->model('octemplates/module/oct_abandoned_cart');

		$ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown';

		if ($this->model_octemplates_module_oct_abandoned_cart->isRateLimited($ip, 'restore_cart_fail', 20, 600)) {
			$this->session->data['error_warning'] = $this->language->get('text_error_too_many_requests');
			$this->response->redirect($this->url->link('common/home'));
			return;
		}

		$short_id = isset($this->request->get['r']) ? $this->request->get['r'] : '';
		if (!$short_id) {
			$this->response->redirect($this->url->link('common/home'));
			return;
		}

		if (!preg_match('/^[a-zA-Z0-9]{6,8}$/', $short_id)) {
			$this->response->redirect($this->url->link('common/home'));
			return;
		}

		$link_data = $this->model_octemplates_module_oct_abandoned_cart->getRestoreDataByShortId($short_id);

		if (!$link_data) {
			if (!$this->model_octemplates_module_oct_abandoned_cart->checkRateLimit($ip, 'restore_cart_fail', 20, 600)) {
				$this->log->write('[SECURITY] Rate limit exceeded for restoreCart from IP: ' . $ip);
			}

			$this->session->data['error_warning'] = $this->language->get('text_error_no_cart');
			$this->response->redirect($this->url->link('common/home'));
			return;
		}

		$raw_token = $link_data['raw_token'];
		$signature = $link_data['signature'];

		$module_settings = $this->config->get('oct_abandoned_cart') ?: [];
		$lifetime = time() + (int)$module_settings['cookie_lifetime'] * 24 * 60 * 60;
		$api_key = $module_settings['api_key'] ?? '';
		$can_login_by_token = $module_settings['can_login_by_token'] ?? false;

		if (!$this->checkSignature($raw_token, $signature, $api_key)) {
			$this->response->redirect($this->url->link('common/home'));
			return;
		}

		$this->model_octemplates_module_oct_abandoned_cart->incrementClickCount($short_id);

		$restored = $this->model_octemplates_module_oct_abandoned_cart->getAbandonedCartData($raw_token, $signature);

		if ($restored && !empty($restored['cart_data'])) {
			if (!empty($restored['coupon_code'])) {
				$is_active = $this->model_octemplates_module_oct_abandoned_cart->getPromocodeByCode($restored['coupon_code']);
				if ($is_active) {
					$this->session->data['coupon'] = $restored['coupon_code'];
				}
			}

			if (!$this->customer->isLogged()) {
				if (!empty($restored['firstname'])) {
					$this->session->data['oct_form_data']['firstname'] = filter_var($restored['firstname'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
				}
				if (!empty($restored['lastname'])) {
					$this->session->data['oct_form_data']['lastname'] = filter_var($restored['lastname'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
				}
				if (!empty($restored['email'])) {
					$email = filter_var($restored['email'], FILTER_VALIDATE_EMAIL);
					if ($email) {
						$this->session->data['oct_form_data']['email'] = $email;
					}
				}
				if (!empty($restored['phone'])) {
					$this->session->data['oct_form_data']['telephone'] = filter_var($restored['phone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
				}
			}

			if ($restored['customer_id'] && !$this->customer->isLogged()) {
				if ($can_login_by_token) {
					$customer_email = $this->model_octemplates_module_oct_abandoned_cart->getEmailByCustomerId($restored['customer_id']);
					$this->customer->login($customer_email, '', true);
					$this->response->redirect($this->url->link('checkout/checkout', '', true));
					return;
				} else {
					$this->response->redirect($this->url->link('account/login', '', true));
					return;
				}
			}

			$this->load->model('catalog/product');

			$cart_data = json_decode($restored['cart_data'], true);
			if (!is_array($cart_data)) {
				$this->session->data['error_warning'] = $this->language->get('text_error_no_cart');
				$this->response->redirect($this->url->link('common/home'));
				return;
			}

			$existing_products = [];
			foreach ($this->cart->getProducts() as $cart_product) {
				$existing_products[] = (int)$cart_product['product_id'];
			}

			foreach ($cart_data as $product) {
				$product_id = (int)$product['product_id'];

				if (in_array($product_id, $existing_products)) {
					continue;
				}

				$product_info = $this->model_catalog_product->getProduct($product_id);

				if ($product_info && $product_info['quantity'] > 0) {
					$options = [];
					$specific_option_available = true;

					if (!empty($product['option'])) {
						$product_options = $this->model_catalog_product->getProductOptions($product_id);
						foreach ($product['option'] as $option) {
							$option_found = false;
							foreach ($product_options as $product_option) {
								if ($product_option['product_option_id'] == (int)$option['product_option_id']) {
									foreach ($product_option['product_option_value'] as $product_option_value) {
										if ($product_option_value['product_option_value_id'] == (int)$option['product_option_value_id'] && $product_option_value['quantity'] > 0) {
											$options[$option['product_option_id']] = $option['product_option_value_id'];
											$option_found = true;
											break;
										}
									}
								}
								if ($option_found) {
									break;
								}
							}
							if (!$option_found) {
								$specific_option_available = false;
								break;
							}
						}
					}

					if ($specific_option_available) {
						$this->cart->add($product_id, (int)$product['quantity'], $options);
					}
				}
			}

			$this->setCookie($raw_token, $signature, $lifetime);

			$this->session->data['success'] = $this->language->get('text_success_restore');
			$this->response->redirect($this->url->link('checkout/checkout', '', true));
			return;
		} else {
			$this->session->data['error_warning'] = $this->language->get('text_error_no_cart');
			$this->response->redirect($this->url->link('common/home'));
			return;
		}
	}

    private function sanitizeString($input, $max_length = 255, $allow_phone_chars = false) {
        if (!is_string($input)) return '';
        
        $input = trim($input);
        $input = strip_tags($input);
        
        $pattern = $allow_phone_chars 
            ? '/[^\p{L}\p{N}\s\-_\.@\+\(\)]+/u'
            : '/[^\p{L}\p{N}\s\-_\.@]+/u';
        
        $input = preg_replace([
            $pattern,
            '/\s+/u'
        ], ['', ' '], $input);
        
        return mb_substr($input, 0, $max_length, 'UTF-8');
    }

	private function checkModuleStatus() {
		$module_settings = $this->config->get('oct_abandoned_cart') ?: [];
		return !empty($module_settings['status']);
	}

	private function prepareAbandonedData($data) {
		if (!isset($data['customer_id'])) {
			$data['customer_id'] = $this->customer->isLogged() ? (int)$this->customer->getId() : 0;
		}
		if (!isset($data['firstname']) || !isset($data['lastname']) || !isset($data['email']) || !isset($data['phone'])) {
			if ($this->customer->isLogged()) {
				$data['firstname'] = $this->sanitizeString($this->customer->getFirstName());
				$data['lastname']  = $this->sanitizeString($this->customer->getLastName());
				$data['email']     = filter_var($this->customer->getEmail(), FILTER_SANITIZE_EMAIL);
				$data['phone']     = $this->sanitizeString($this->customer->getTelephone(), 32, true);
			} elseif (!empty($this->session->data['guest'])) {
				$g = $this->session->data['guest'];
				$data['firstname'] = isset($g['firstname']) ? $this->sanitizeString($g['firstname']) : '';
				$data['lastname']  = isset($g['lastname']) ? $this->sanitizeString($g['lastname']) : '';
				$data['email']     = isset($g['email']) ? filter_var($g['email'], FILTER_SANITIZE_EMAIL) : '';
				$data['phone']     = isset($g['telephone']) ? $this->sanitizeString($g['telephone'], 32, true) : '';
			} else {
				$data['firstname'] = $data['firstname'] ?? '';
				$data['lastname']  = $data['lastname'] ?? '';
				$data['email']     = $data['email'] ?? '';
				$data['phone']     = $data['phone'] ?? '';
			}
		}

		if (!empty($data['email']) && !$this->validateEmail($data['email'])) {
			$data['email'] = '';
		}
		if (!empty($data['phone']) && !$this->validatePhone($data['phone'])) {
			$data['phone'] = '';
		}

		if (!isset($data['store_id'])) {
			$data['store_id'] = (int)$this->config->get('config_store_id');
		}

		if (!isset($data['store_name'])) {
			$data['store_name'] = $this->sanitizeString($this->config->get('config_name')) ?: 'Default Store';
		}

		if (!isset($data['language_id'])) {
			$data['language_id'] = (int)$this->config->get('config_language_id');
		}

		if (!isset($data['cart_data'])) {
			$data['cart_data'] = $this->makeCartData();
		}
		return $data;
	}

	private function validateEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	private function validatePhone($phone) {
		return preg_match('/^[0-9\-\(\)\/\+\s]*$/', $phone);
	}

	private function makeSignature($raw_token, $api_key) {
		$hash_bytes = hash_hmac('sha256', $raw_token, $api_key, true);
		$truncated = substr($hash_bytes, 0, 16);
		return rtrim(strtr(base64_encode($truncated), '+/', '-_'), '=');
	}

	private function checkSignature($raw_token, $signature, $api_key) {
		$expected_signature = $this->makeSignature($raw_token, $api_key);
		return hash_equals($expected_signature, $signature);
	}

	private function setCookieUnified($name, $value, $expires, $path = '/', $domain = '', $secure = false, $httponly = true, $samesite = 'Strict') {
		if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
			$options = [
				'expires'  => $expires,
				'path'     => $path,
				'domain'   => $domain,
				'secure'   => $secure,
				'httponly' => $httponly,
				'samesite' => $samesite,
			];
			setcookie($name, $value, $options);
		} else {
			$path_with_samesite = $path . '; samesite=' . $samesite;
			setcookie($name, $value, $expires, $path_with_samesite, $domain, $secure, $httponly);
		}
	}

	private function setCookie($raw_token, $signature, $lifetime) {
		$value   = $raw_token . '|' . $signature;
		$expires = time() + $lifetime;
		$domain  = $this->request->server['HTTP_HOST'];
		$secure  = isset($this->request->server['HTTPS']) &&
				   ($this->request->server['HTTPS'] === 'on' || $this->request->server['HTTPS'] == 1);

		$this->setCookieUnified($this->cookieName, $value, $expires, '/', $domain, $secure, true, 'Strict');
	}

	private function deleteCookie() {
		$domain = $this->request->server['HTTP_HOST'];
		$secure = isset($this->request->server['HTTPS']) &&
				  ($this->request->server['HTTPS'] === 'on' || $this->request->server['HTTPS'] == 1);

		$this->setCookieUnified($this->cookieName, '', time() - 3600, '/', $domain, $secure, true, 'Strict');
	}

	private function makeCartData() {
		$products = $this->cart->getProducts();
		$result = [];
		foreach ($products as $p) {
			$product_options = [];
			if (isset($p['option']) && is_array($p['option'])) {
				foreach ($p['option'] as $option) {
					$product_options[] = [
						'product_option_id'       => (int)$option['product_option_id'],
						'product_option_value_id' => (int)$option['product_option_value_id'],
						'option_id'               => (int)$option['option_id'],
						'option_value_id'         => (int)$option['option_value_id'],
						'name'                    => $this->sanitizeString($option['name']),
						'value'                   => $this->sanitizeString($option['value']),
						'type'                    => $this->sanitizeString($option['type'])
					];
				}
			}

			$result[] = [
				'product_id' => (int)$p['product_id'],
				'quantity'   => (int)$p['quantity'],
				'name'       => $this->sanitizeString($p['name']),
				'price'      => $p['price'],
				'total'      => $p['total'],
				'option'     => $product_options,
			];
		}
		return $result;
	}
}