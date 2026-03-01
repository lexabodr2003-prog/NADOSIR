<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesModuleOctPopupCart extends Controller {
	public function index() {
		if ($this->config->get('theme_oct_deals_popup_cart_status') && isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$data = [];

			$oct_deals_data = $this->config->get('theme_oct_deals_data');

			$this->load->language('octemplates/module/oct_popup_cart');

			if (isset($this->request->request['remove'])) {
				$this->cart->remove((string)$this->request->request['remove']);
				unset($this->session->data['vouchers'][(string)$this->request->request['remove']]);
			}

			if (isset($this->request->request['update'])) {
				$this->cart->update((string)$this->request->request['update'], (int)$this->request->request['quantity']);
			}

			if (isset($this->request->request['add'])) {
				$this->cart->add((string)$this->request->request['add'], (int)$this->request->request['quantity']);
			}

			$minimum_order = $this->config->get('theme_oct_deals_popup_minimum_order_amount') ?? 0;
			$data['use_minimum_step'] = isset($oct_deals_data['use_minimum_step']) ? 1 : 0;

			if ($minimum_order > 0 && $this->cart->getTotal() < $minimum_order) {
				$data['error_minimum'] = sprintf($this->language->get('error_minimum_order'), $this->currency->format($minimum_order, $this->session->data['currency']));
			} else {
				$data['error_minimum'] = '';
			}

			if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
				$data['error_warning'] = $this->language->get('error_stock');
			} elseif (isset($this->session->data['error'])) {
				$data['error_warning'] = $this->session->data['error'];
				unset($this->session->data['error']);
			} else {
				$data['error_warning'] = '';
			}

			if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
				$data['attention'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));
			} else {
				$data['attention'] = '';
			}

			if (isset($this->session->data['success'])) {
				$data['success'] = $this->session->data['success'];
				unset($this->session->data['success']);
			} else {
				$data['success'] = '';
			}

			$this->load->model('tool/image');
			$this->load->model('tool/upload');
			$this->load->model('catalog/product');

			$data['isPopup'] = (isset($this->request->get['isPopup']) && (bool)$this->request->get['isPopup']) ? true : false;

			$data['products'] = [];

			$products = $this->cart->getProducts();

			if (($this->config->get('config_checkout_guest') && $this->config->get('oct_popup_purchase_byoneclick_status')) && $products) {
				$oct_byoneclick_data = $this->config->get('oct_popup_purchase_byoneclick_data');
				$oct_data = [
					'isPopup' => $data['isPopup'],
					'oct_byoneclick_status' => isset($oct_byoneclick_data['popup_cart']) ? 1 : 0,
					'oct_byoneclick_mask' => $oct_byoneclick_data['mask'] ?? '',
					'oct_byoneclick_product_id' => 1,
					'oct_cart_in' => 1,
					'oct_popup_cart' => 1,
					'oct_byoneclick_page' => '_popup_cart'
				];
				$data['oct_byoneclick'] = $this->load->controller('octemplates/module/oct_popup_purchase/byoneclick', $oct_data);
			}

			$free_shipping_from = $this->config->get('theme_oct_deals_popup_free_shipping_from') ?? 0;

			foreach ($products as $product) {
				$product_total = 0;

				foreach ($products as $product_2) {
					if ($product_2['product_id'] == $product['product_id']) {
						$product_total += $product_2['quantity'];
					}
				}

				if ($product['minimum'] > $product_total) {
					$data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
				}

				if ($product['image']) {
					$image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
				}

				$option_data = [];
				$options_arr = [];

				foreach ($product['option'] as $value_opt) {
					$options_arr[] = $value_opt['product_option_value_id'];
				}

				if ($options_arr) {
					$opt_array = [];

					foreach ($options_arr as $value) {
						if (is_array($value)) {
							foreach ($value as $val) {
								if ($val) {
									$opt_array[] = $this->model_catalog_product->getProductOptionValueId($product['product_id'], $val);
								}
							}
						} else {
							if ($value) {
								$opt_array[] = $this->model_catalog_product->getProductOptionValueId($product['product_id'], $value);
							}
						}
					}

					$results_opts = $this->model_catalog_product->getProductImagesByOptionValueId($product['product_id'], $opt_array);

					if (isset($results_opts[0]['image']) && $results_opts[0]['image']) {
						$image = $this->model_tool_image->resize($results_opts[0]['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
					}
				}

				foreach ($product['option'] as $option) {
					if ($option['type'] != 'file') {
						$value = $option['value'];
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$value = $upload_info['name'];
						} else {
							$value = '';
						}
					}

					$option_data[] = [
						'name' => $option['name'],
						'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
					];
				}

				if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
					$p_price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$p_price = false;
				}

				if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
					$p_total = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']);
				} else {
					$p_total = false;
				}

				$recurring = '';

				if ($product['recurring']) {
					$frequencies = [
						'day' => $this->language->get('text_day'),
						'week' => $this->language->get('text_week'),
						'semi_month' => $this->language->get('text_semi_month'),
						'month' => $this->language->get('text_month'),
						'year' => $this->language->get('text_year')
					];

					if ($product['recurring']['trial']) {
						$recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
					}

					if ($product['recurring']['duration']) {
						$recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
					} else {
						$recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
					}
				}

				$product_info = $this->model_catalog_product->getProduct($product['product_id']);

				$data['products'][] = [
					'key' => $product['cart_id'],
					'product_id' => $product['product_id'],
					'thumb' => $image,
					'width' => 100,
					'height' => 100,
					'name' => $product['name'],
					'model' => $product['model'],
					'option' => $option_data,
					'recurring' => $recurring,
					'quantity' => $product['quantity'],
					'quantity_product' => $product_info['quantity'],
					'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
					'reward' => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
					'price' => $p_price,
					'total' => $p_total,
					'minimum' => $product['minimum'],
					'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'], true)
				];
			}

			$data['vouchers'] = [];

			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $key => $voucher) {
					$data['vouchers'][] = [
						'key' => $key,
						'description' => $voucher['description'],
						'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency']),
						'remove' => $this->url->link('checkout/cart', 'remove=' . $key, true)
					];
				}
			}

			$this->load->model('setting/extension');

			$totals = [];
			$taxes = $this->cart->getTaxes();
			$total = 0;

			$total_data = [
				'totals' => &$totals,
				'taxes' => &$taxes,
				'total' => &$total
			];

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$sort_order = [];

				$results = $this->model_setting_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}

				$sort_order = [];

				foreach ($totals as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

				array_multisort($sort_order, SORT_ASC, $totals);
			}

			$data['totals'] = [];
			$subtotal_without_tax = 0;

			foreach ($totals as $total_value) {
				if ($total_value['code'] !== 'shipping' && $total_value['code'] !== 'total') {
					$subtotal_without_tax += $total_value['value'];
				}

				if ($total_value['code'] == 'oct_product_set') {
					$data['totals'][] = [
						'title' => $total_value['title'],
						'text' => $this->currency->format($total_value['value'], $this->session->data['currency'])
					];
				}
			}

			if ($subtotal_without_tax > 0 && $free_shipping_from > 0) {
				if ($subtotal_without_tax < $free_shipping_from) {
					$shipping_free = $free_shipping_from - $subtotal_without_tax;
					$data['total_percentage'] = ($subtotal_without_tax / $free_shipping_from) * 100;
					$data['free_shipping_from_text'] = sprintf($this->language->get('free_shipping_from'), $this->currency->format($shipping_free, $this->session->data['currency']));
				} else {
					$data['free_shipping_from_text'] = $this->language->get('free_shipping_get');
					$data['total_percentage'] = 100;
				}
			}

			$data['checkout_link'] = $this->url->link('checkout/checkout', '', true);
			$data['cart_link'] = $this->url->link('checkout/cart', '', true);
			$data['heading_title'] = $this->language->get('cart_title');
			$data['text_cart_items'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));

			$data['free_shipping_html'] = $this->generateFreeShippingHtml($data);

			$data['product_stock_checkout'] = $this->config->get('config_stock_checkout');

			$theme_oct_deals_popup_cart_recommend_products = $this->config->get('theme_oct_deals_popup_cart_recommend_status');

			if ($theme_oct_deals_popup_cart_recommend_products) {
				$this->load->model('catalog/product');
				$this->load->model('octemplates/helper');

				$manual_recommendations = [];
				$automatic_recommendations = [];

				$data['recommended'] = $theme_oct_deals_popup_cart_recommend_products;

				if ($this->config->get('theme_oct_deals_popup_cart_autorelated_status')) {
					$manual_recommendations = $this->model_octemplates_helper->getManualRecommendations($products) ?? [];
				}

				if ($this->config->get('theme_oct_deals_popup_cart_relatedbysales_status')) {
					$automatic_recommendations = $this->model_octemplates_helper->getAutomaticRecommendations($products) ?? [];
				}

				$data['manual_automatic_products'] = array_merge($manual_recommendations, $automatic_recommendations);
				$data['recommended_products'] = $this->getRecommendedProducts($data);
			} else {
				$data['recommended_products'] = '';
			}

			$this->response->setOutput($this->load->view('octemplates/module/oct_popup_cart', $data));
		} else {
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}
	}

	public function status_cart() {
		if ($this->config->get('theme_oct_deals_popup_cart_status') && isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$json = [];

			$free_shipping_from = $this->config->get('theme_oct_deals_popup_free_shipping_from') ?? 0;

			$this->load->language('octemplates/module/oct_popup_cart');

			$this->load->model('setting/extension');

			$totals = [];
			$taxes = $this->cart->getTaxes();
			$total = 0;

			$total_data = [
				'totals' => &$totals,
				'taxes' => &$taxes,
				'total' => &$total
			];

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$sort_order = [];

				$results = $this->model_setting_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}

				$sort_order = [];

				foreach ($totals as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

				array_multisort($sort_order, SORT_ASC, $totals);
			}

			$data = [];
			$data['totals'] = [];
			$subtotal_without_tax = 0;

			foreach ($totals as $total_value) {
				if ($total_value['code'] !== 'shipping' && $total_value['code'] !== 'total') {
					$subtotal_without_tax += $total_value['value'];
				}

				if ($total_value['code'] == 'oct_product_set') {
					$data['totals'][] = [
						'title' => $total_value['title'],
						'text' => $this->currency->format($total_value['value'], $this->session->data['currency'])
					];
				}
			}

			if ($subtotal_without_tax > 0 && $free_shipping_from > 0) {
				if ($subtotal_without_tax < $free_shipping_from) {
					$shipping_free = $free_shipping_from - $subtotal_without_tax;
					$data['total_percentage'] = ($subtotal_without_tax / $free_shipping_from) * 100;
					$data['free_shipping_from_text'] = sprintf($this->language->get('free_shipping_from'), $this->currency->format($shipping_free, $this->session->data['currency']));
				} else {
					$data['free_shipping_from_text'] = $this->language->get('free_shipping_get');
					$data['total_percentage'] = 100;
				}
			}

			$theme_oct_deals_popup_cart_recommend_products = $this->config->get('theme_oct_deals_popup_cart_recommend_products');

			if ($theme_oct_deals_popup_cart_recommend_products) {
				$this->load->model('catalog/product');
				$this->load->model('octemplates/helper');

				$manual_recommendations = [];
				$automatic_recommendations = [];

				$products = $this->cart->getProducts();

				$data['recommended'] = $theme_oct_deals_popup_cart_recommend_products;

				if ($this->config->get('theme_oct_deals_popup_cart_autorelated_status')) {
					$manual_recommendations = $this->model_octemplates_helper->getManualRecommendations($products) ?? [];
				}

				if ($this->config->get('theme_oct_deals_popup_cart_relatedbysales_status')) {
					$automatic_recommendations = $this->model_octemplates_helper->getAutomaticRecommendations($products) ?? [];
				}

				$data['manual_automatic_products'] = array_merge($manual_recommendations, $automatic_recommendations);
				$json['recommended_products'] = $this->getRecommendedProducts($data);
			} else {
				$json['recommended_products'] = '';
			}

			$minimum_order = $this->config->get('theme_oct_deals_popup_minimum_order_amount') ?? 0;

			if ($minimum_order > 0 && $this->cart->getTotal() < $minimum_order) {
				if ($this->cart->getProducts()) {
					$json['error_minimum'] = sprintf($this->language->get('error_minimum_order'), $this->currency->format($minimum_order, $this->session->data['currency']));
				} else {
					$json['error_minimum'] = '';
				}
			}

			$json['free_shipping_html'] = $this->generateFreeShippingHtml($data);
			$json['total'] = sprintf($this->language->get('text_cart_items'), $this->currency->format($total, $this->session->data['currency']), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0));
			$json['text_items'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
			$json['text_cart_items'] = sprintf($this->language->get('text_cart_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
			$json['total_products'] = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);
			$json['total_amount'] = $this->currency->format($total, $this->session->data['currency']);
			$json['oct_cart_ids'] = $this->load->controller('octemplates/events/helper/allCartProducts');
			$json['totals'] = $data['totals'];

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		} else {
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}
	}

	private function getRecommendedProducts(array $data = []) {
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->load->language('octemplates/module/oct_smartcheckout');

		$width = $this->config->get('theme_oct_deals_popup_cart_recommend_width') ? $this->config->get('theme_oct_deals_popup_cart_recommend_width') : 98;
		$height = $this->config->get('theme_oct_deals_popup_cart_recommend_height') ? $this->config->get('theme_oct_deals_popup_cart_recommend_height') : 98;

		$popup_cart_data = $this->config->get('theme_oct_deals_popup_cart_data');

		$data['recommended_poducts'] = [];

		if ($this->config->get('theme_oct_deals_popup_cart_recommend_status')) {
			$product_ids = [];

			if ($this->config->get('theme_oct_deals_popup_cart_recommend_products')) {
				$product_ids = $this->config->get('theme_oct_deals_popup_cart_recommend_products');
			}

			$products = $this->cart->getProducts();
			$product_ids = array_merge($data['manual_automatic_products'], $product_ids);
			$product_ids = array_unique($product_ids);

			$exclude_ids = array_map(function($product) {
				return $product['product_id'];
			}, $products);

			$data['title'] = isset($popup_cart_data['recommended_poducts']['title'][$this->config->get('config_language_id')]) ? $popup_cart_data['recommended_poducts']['title'][$this->config->get('config_language_id')] : $this->language->get('text_recommended_products');

			$results = [];
			foreach ($product_ids as $product_id) {

				if (in_array($product_id, $exclude_ids)) {
					continue;
				}

				$product_info = $this->model_catalog_product->getProduct((int)$product_id);

				if ($product_info) {

					if ($product_info['image']) {
						$image = $this->model_tool_image->resize($product_info['image'], $width, $height);
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $width, $height);
					}

					if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
						$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$price = false;
					}

					if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
						$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						$tax_price = (float)$product_info['special'];
					} else {
						$special = false;
						$tax_price = (float)$product_info['price'];
					}

					if ($this->config->get('config_tax')) {
						$tax = $this->currency->format($tax_price, $this->session->data['currency']);
					} else {
						$tax = false;
					}

					if ($this->config->get('config_review_status')) {
						$rating = $product_info['rating'];
					} else {
						$rating = false;
					}

					if ($product_info['quantity'] > 0) {
						$data['recommended_poducts'][] = [
							'product_id' => $product_info['product_id'],
							'thumb' => $image,
							'name' => $product_info['name'],
							'description' => utf8_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
							'price' => $price,
							'special' => $special,
							'tax' => $tax,
							'rating' => $rating,
							'width' => $width,
							'height' => $height,
							'href' => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
						];
					}
				}
			}

			if ($data['recommended_poducts']) {
				return $this->load->view('octemplates/module/oct_popup_cart_shipping_recommended', $data);
			}
		}

		return '';
	}

	private function generateFreeShippingHtml(array $data): string {
		return $this->load->view('octemplates/module/oct_popup_cart_shipping', $data);
	}
}