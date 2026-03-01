<?php
class ControllerProductProduct extends Controller {
	private $error = array();

	
			public function getPImages() {
            	$json = array();
				$this->load->model('catalog/product');
				$this->load->model('tool/image');
				
				if (isset($this->request->get['product_id'])) {
					$product_id = (int)$this->request->get['product_id'];
				} else {
					$product_id = 0;
				}
				
				$product_info = $this->model_catalog_product->getProduct($product_id);

				if (isset($this->request->post['option'])) {
					$opt_array = array();
					
					foreach ($this->request->post['option'] as $value) {
						if (is_array($value)) {
							foreach ($value as $val) {
								if ($val) {
									$opt_array[] = $this->model_catalog_product->getProductOptionValueId($this->request->get['product_id'], $val);
								}
							}
						} else {
							if ($value) {
								$opt_array[] = $this->model_catalog_product->getProductOptionValueId($this->request->get['product_id'], $value);
							}
						}
					}

					$results = $this->model_catalog_product->getProductImagesByOptionValueId($this->request->get['product_id'], $opt_array);

					foreach ($results as $result) {
						$json['images'][] = array(
							'popup' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
							'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height')),
							'thumb_slider' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height')),
							'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'),
							'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'),
							'thumb_height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height'),
							'thumb_width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width')
						);
					}

				} else {
					$results = false;
				}

				if (!$results) {
					$results = $this->model_catalog_product->getProductImages( $this->request->get['product_id'] );

					$json['images'][] = array(
		                'popup' => $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
		                'thumb' => $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height')),
		                'thumb_slider' => $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height')),
						'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'),
						'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'),
						'thumb_height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height'),
						'thumb_width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width')
					);
					
					foreach ($results as $result) {
						$json['images'][] = array(
							'popup' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
		                    'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height')),
		                    'thumb_slider' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height')),
							'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'),
							'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'),
							'thumb_height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height'),
							'thumb_width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width')
						);
					}
				}

	            $this->response->addHeader('Content-Type: application/json');
	            $this->response->setOutput(json_encode($json));
			}
			public function index() {
			$data['oct_product_main_image_option_status'] = $oct_product_main_image_option_status = $this->config->get('oct_product_main_image_option_status');
	        
			$data['oct_allow_autoselect_option'] = false;

			$oct_product_main_image_option_data = $this->config->get('oct_product_main_image_option_data');

		    if (isset($oct_product_main_image_option_status) && $oct_product_main_image_option_status && isset($oct_product_main_image_option_data['allow_autoselect_option']) && $oct_product_main_image_option_data['allow_autoselect_option']) {

				$data['oct_allow_autoselect_option'] = $oct_product_main_image_option_data['allow_autoselect_option'];

				$allowed_options_ids = [];

				if (isset($oct_product_main_image_option_data['allowed_options']) && !empty($oct_product_main_image_option_data['allowed_options'])) {

					foreach ($oct_product_main_image_option_data['allowed_options'] as $allowed_option) {
						array_push($allowed_options_ids, $allowed_option);
					}
				}

				$data['allowed_options_ids'] = $allowed_options_ids;

	        }
			
			$data['oct_deals_data'] = $oct_deals_data = $this->config->get('theme_oct_deals_data');
			$this->load->model('tool/image');
			
		$this->load->language('product/product');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$this->load->model('catalog/category');

		if (isset($this->request->get['path'])) {
			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = $path_id;
				} else {
					$path .= '_' . $path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path)
					);
				}
			}

			// Set the last category breadcrumb
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$url = '';

				if (isset($this->request->get['sort'])) {
					$url .= '&sort=' . $this->request->get['sort'];
				}

				if (isset($this->request->get['order'])) {
					$url .= '&order=' . $this->request->get['order'];
				}

				if (isset($this->request->get['page'])) {
					$url .= '&page=' . $this->request->get['page'];
				}

				if (isset($this->request->get['limit'])) {
					$url .= '&limit=' . $this->request->get['limit'];
				}

				$data['breadcrumbs'][] = array(
					'text' => $category_info['name'],
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url)
				);
			}
		}

		$this->load->model('catalog/manufacturer');

		if (isset($this->request->get['manufacturer_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_brand'),
				'href' => $this->url->link('product/manufacturer')
			);

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($this->request->get['manufacturer_id']);

			if ($manufacturer_info) {
				$data['breadcrumbs'][] = array(
					'text' => $manufacturer_info['name'],
					'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . $url)
				);
			}
		}

		if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode(trim($this->request->get['tag']), ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_search'),
				'href' => $this->url->link('product/search', $url)
			);
		}

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		//check product page open from cateory page
			if (!isset($oct_deals_data['category_subcat_products'])) {
			
		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
						
			if(empty($this->model_catalog_product->checkProductCategory($product_id, $parts))) {
				$product_info = array();
			}
		}

		
			}
			//check product page open from manufacturer page
		if (isset($this->request->get['manufacturer_id']) && !empty($product_info)) {
			if($product_info['manufacturer_id'] !=  $this->request->get['manufacturer_id']) {
				$product_info = array();
			}
		}

		if ($product_info) {
			$data['oct_product_stickers'] = [];
			$data['you_save'] = $product_info['you_save'];
			$data['you_save_price'] = $this->currency->format($this->tax->calculate($product_info['you_save_price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

			if ($this->config->get('oct_stickers_status')) {
				$oct_stickers = $this->config->get('oct_stickers_data');

				$data['oct_sticker_you_save'] = false;

				if ($oct_stickers) {
					$data['oct_sticker_you_save'] = isset($oct_stickers['stickers']['special']['persent']) ? true : false;
				}

				$this->load->model('octemplates/stickers/oct_stickers');

				$oct_stickers_data = $this->model_octemplates_stickers_oct_stickers->getOCTStickers($product_info);

				if ($oct_stickers_data) {
					$data['oct_product_stickers'] = $oct_stickers_data['stickers'];
				}
			}
			
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode(trim($this->request->get['tag']), ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $product_info['name'],
				'href' => $this->url->link('product/product', $url . '&product_id=' . $this->request->get['product_id'])
			);

			$this->document->setTitle($product_info['meta_title']);
			$this->document->setDescription($product_info['meta_description']);
			$this->document->setKeywords($product_info['meta_keyword']);
			$this->document->addLink($this->url->link('product/product', 'product_id=' . $this->request->get['product_id']), 'canonical');
			
			//$this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
			
			
			//$this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');
			
			
			//$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
			
			
			//$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
			
			
			//$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
			
			
			//$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
			

			$data['heading_title'] = $product_info['name'];

			
			if ($this->config->get('theme_oct_deals_seo_title_status')) {
				$oct_seo_title_data = $this->config->get('theme_oct_deals_seo_title_data');

				$oct_price = ($this->customer->isLogged() || !$this->config->get('config_customer_price')) ? $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : '';
				$oct_special = ((float)$product_info['special']) ? $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : '';

				if ((isset($oct_seo_title_data['product']['title_status']) && $oct_seo_title_data['product']['title_status']) && (isset($oct_seo_title_data['product']['title'][$this->config->get('config_language_id')]) && !empty($oct_seo_title_data['product']['title'][$this->config->get('config_language_id')]))) {
					$oct_address = (isset($oct_deals_data['contact_address'][$this->config->get('config_language_id')]) && !empty($oct_deals_data['contact_address'][$this->config->get('config_language_id')])) ? str_replace(PHP_EOL, ', ', $oct_deals_data['contact_address'][$this->config->get('config_language_id')]) : '';
					$oct_phone = (isset($oct_deals_data['contact_telephone']) && !empty($oct_deals_data['contact_telephone'])) ? str_replace(PHP_EOL, ', ',  $oct_deals_data['contact_telephone']) : '';
					$oct_time = (isset($oct_deals_data['contact_open'][$this->config->get('config_language_id')]) && !empty($oct_deals_data['contact_open'][$this->config->get('config_language_id')])) ? str_replace(PHP_EOL, ', ', $oct_deals_data['contact_open'][$this->config->get('config_language_id')]) : '';

					$oct_replace = [
						'[name]' => strip_tags(html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8')),
						'[price]' => $oct_price ? $oct_special ? strip_tags($oct_special) : strip_tags($oct_price) : '',
						'[model]' => !empty($product_info['model']) ? strip_tags(html_entity_decode($product_info['model'], ENT_QUOTES, 'UTF-8')) : '',
						'[sku]' => !empty($product_info['sku']) ? strip_tags(html_entity_decode($product_info['sku'], ENT_QUOTES, 'UTF-8')) : '',
						'[category]' => (isset($category_info) && $category_info) ? strip_tags(html_entity_decode($category_info['name'], ENT_QUOTES, 'UTF-8')) : '',
						'[manufacturer]' => !empty($product_info['manufacturer']) ? strip_tags(html_entity_decode($product_info['manufacturer'], ENT_QUOTES, 'UTF-8')) : '',
						'[address]' => $oct_address,
						'[phone]' => $oct_phone,
						'[time]' => $oct_time,
						'[store]' => $this->config->get('config_name')
					];

					$oct_seo_title = str_replace(array_keys($oct_replace), array_values($oct_replace), $oct_seo_title_data['product']['title'][$this->config->get('config_language_id')]);

					if ((isset($oct_seo_title_data['product']['title_empty']) && $oct_seo_title_data['product']['title_empty']) && empty($product_info['meta_title'])) {
						$og_seo_title = true;

						$this->document->setTitle(htmlspecialchars($oct_seo_title));
					} elseif (!isset($oct_seo_title_data['product']['title_empty'])) {
						$og_seo_title = true;

						$this->document->setTitle(htmlspecialchars($oct_seo_title));
					}
				}

				if ((isset($oct_seo_title_data['product']['description_status']) && $oct_seo_title_data['product']['description_status']) && (isset($oct_seo_title_data['product']['description'][$this->config->get('config_language_id')]) && !empty($oct_seo_title_data['product']['description'][$this->config->get('config_language_id')]))) {
					$oct_address = (isset($oct_deals_data['contact_address'][$this->config->get('config_language_id')]) && !empty($oct_deals_data['contact_address'][$this->config->get('config_language_id')])) ? str_replace(PHP_EOL, ', ', $oct_deals_data['contact_address'][$this->config->get('config_language_id')]) : '';
					$oct_phone = (isset($oct_deals_data['contact_telephone']) && !empty($oct_deals_data['contact_telephone'])) ? str_replace(PHP_EOL, ', ',  $oct_deals_data['contact_telephone']) : '';
					$oct_time = (isset($oct_deals_data['contact_open'][$this->config->get('config_language_id')]) && !empty($oct_deals_data['contact_open'][$this->config->get('config_language_id')])) ? str_replace(PHP_EOL, ', ', $oct_deals_data['contact_open'][$this->config->get('config_language_id')]) : '';

					$oct_replace = [
						'[name]' => strip_tags(html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8')),
						'[price]' => $oct_price ? $oct_special ? strip_tags($oct_special) : strip_tags($oct_price) : '',
						'[model]' => !empty($product_info['model']) ? strip_tags(html_entity_decode($product_info['model'], ENT_QUOTES, 'UTF-8')) : '',
						'[sku]' => !empty($product_info['sku']) ? strip_tags(html_entity_decode($product_info['sku'], ENT_QUOTES, 'UTF-8')) : '',
						'[category]' => (isset($category_info) && $category_info) ? strip_tags(html_entity_decode($category_info['name'], ENT_QUOTES, 'UTF-8')) : '',
						'[manufacturer]' => !empty($product_info['manufacturer']) ? strip_tags(html_entity_decode($product_info['manufacturer'], ENT_QUOTES, 'UTF-8')) : '',
						'[address]' => $oct_address,
						'[phone]' => $oct_phone,
						'[time]' => $oct_time,
						'[store]' => $this->config->get('config_name')
					];

					$oct_seo_description = str_replace(array_keys($oct_replace), array_values($oct_replace), $oct_seo_title_data['product']['description'][$this->config->get('config_language_id')]);

					if ((isset($oct_seo_title_data['product']['description_empty']) && $oct_seo_title_data['product']['description_empty']) && empty($product_info['meta_description'])) {
						$og_seo_description = true;
						$this->document->setDescription(htmlspecialchars($oct_seo_description));
					} elseif (!isset($oct_seo_title_data['product']['description_empty'])) {
						$og_seo_description = true;
						$this->document->setDescription(htmlspecialchars($oct_seo_description));
					}
				}
			}
			$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));

			$this->load->model('catalog/review');

			$data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);

			$data['product_id'] = (int)$this->request->get['product_id'];
			$data['manufacturer'] = $product_info['manufacturer'];
		
			$data['manufacturer_id'] = (int)$product_info['manufacturer_id'];
			$data['manufacturers'] = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product_info['manufacturer_id']);
			$data['model'] = $product_info['model'];
			$data['reward'] = $product_info['reward'];
			$data['points'] = $product_info['points'];
			$data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');
			if (isset($oct_deals_data['preload_images']) && $oct_deals_data['preload_images'] && $product_info['image'] && is_file(DIR_IMAGE . $product_info['image'])) {
				$this->document->setOCTPreload($this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height')));
			}
			if (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')) {
				$data['max_quantity'] = $product_info['quantity'];
			}
			

			
			$data['text_oct_popup_found_cheaper'] = $this->language->get('oct_product_cheaper');
       		$data['out_of_stock'] = false;

			if ($product_info['quantity'] <= 0) {
				$data['out_of_stock'] = true;
			
				$data['stock'] = $product_info['stock_status'];
			} elseif ($this->config->get('config_stock_display')) {
				$data['stock'] = $product_info['quantity'];
			} else {
				$data['stock'] = $this->language->get('text_instock');
			}

			$this->load->model('tool/image');

			if ($product_info['image']) {
				$data['popup'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
			} else {
				
			$data['popup'] = $this->model_tool_image->resize('no-thumb.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
			
			}

			if ($product_info['image']) {
				$data['thumb'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
			} else {
				
			$data['thumb'] = $this->model_tool_image->resize('no-thumb.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
			
			}

			
			$data['popup_width'] = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width');
			$data['popup_height'] = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height');
			$data['thumb_width'] = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width');
			$data['thumb_height'] = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height');
			$data['images'] = array();

			$results = $this->model_catalog_product->getProductImages($this->request->get['product_id']);
			if ($data['popup'] && $data['thumb'] && !empty($results)) {
				$data['images'][0] = array(
					'popup' => $data['thumb'],
					'popup_fancy' => $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
					'thumb' => $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height')),
					'images_width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'),
					'images_height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height'),
				);
			}
			

			foreach ($results as $result) {
				$data['images'][] = array(
					
			'popup' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height')),
			'popup_fancy' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
			'images_width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'),
			'images_height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height'),
			
					'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height'))
				);
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['price'] = false;
			}

			if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
				$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$tax_price = (float)$product_info['special'];
			} else {
				$data['special'] = false;
				$tax_price = (float)$product_info['price'];
			}

			if ($this->config->get('config_tax')) {
				$data['tax'] = $this->currency->format($tax_price, $this->session->data['currency']);
			} else {
				$data['tax'] = false;
			}

			
			if ($product_info['quantity'] <= 0) {
				$data['is_stock'] = $product_info['stock_status'];
			} else {
				$data['is_stock'] = false;
			}

			$data['can_buy'] = true;

			if ($product_info['quantity'] <= 0 && !$this->config->get('config_stock_checkout')) {
				$data['can_buy'] = false;
			} elseif ($product_info['quantity'] <= 0 && $this->config->get('config_stock_checkout')) {
				$data['can_buy'] = true;
			}
			$discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id']);

			$data['discounts'] = array();

			foreach ($discounts as $discount) {
				$data['discounts'][] = array(
					'quantity' => $discount['quantity'],
					'price'    => $this->currency->format($this->tax->calculate($discount['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
				);
			}

			$data['options'] = array();

			foreach ($this->model_catalog_product->getProductOptions($this->request->get['product_id']) as $option) {
				$product_option_value_data = array();

				foreach ($option['product_option_value'] as $option_value) {
					if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
						} else {
							$price = false;
						}

						$product_option_value_data[] = array(
							'product_option_value_id' => $option_value['product_option_value_id'],
							'option_value_id'         => $option_value['option_value_id'],
							'name'                    => $option_value['name'],
							'image'                   => $this->model_tool_image->resize($option_value['image'], 50, 50),
							'price'                   => $price,
							'price_prefix'            => $option_value['price_prefix']
						);
					}
				}

				$data['options'][] = array(
					'product_option_id'    => $option['product_option_id'],
					'product_option_value' => $product_option_value_data,
					'option_id'            => $option['option_id'],
					'name'                 => $option['name'],
					'type'                 => $option['type'],
					'value'                => $option['value'],
					'required'             => $option['required']
				);
			}

			if ($product_info['minimum']) {
				$data['minimum'] = $product_info['minimum'];
			} else {
				$data['minimum'] = 1;
			}

			$data['review_status'] = $this->config->get('config_review_status');
			$data['oct_reviews_list'] = $data['review_status'] ? $this->review() : '';
			

			if ($this->config->get('config_review_guest') || $this->customer->isLogged()) {
				$data['review_guest'] = true;
			} else {
				$data['review_guest'] = false;
			}

			if ($this->customer->isLogged()) {
				$data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
			} else {
				$data['customer_name'] = '';
			}

			$data['reviews'] = sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);
			$data['rating'] = (int)$product_info['rating'];

			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$data['captcha'] = '';
			}

			$data['share'] = $this->url->link('product/product', 'product_id=' . (int)$this->request->get['product_id']);

			

			$this->config->set('footer_swiper', true);

			$this->document->addScript('catalog/view/theme/oct_deals/js/fancybox/jquery.fancybox.min.js');
			$this->document->addStyle('catalog/view/theme/oct_deals/js/fancybox/jquery.fancybox.min.css');

			if (isset($oct_deals_data['product_zoom']) && $oct_deals_data['product_zoom']) {
	            $this->document->addScript('catalog/view/theme/oct_deals/js/zoom/jquery.zoom.js');
        	}

        	$data['sku'] = $product_info['sku'];
			$data['upc'] = $product_info['upc'];
			$data['ean'] = $product_info['ean'];
			$data['jan'] = $product_info['jan'];
			$data['isbn'] = $product_info['isbn'];
			$data['mpn'] = $product_info['mpn'];
			$data['oct_p_reviews'] = $this->load->controller('octemplates/events/helper/octProductReviews');

			$data['oct_product_sku_show'] = $oct_deals_data['product_sku_show'] ?? '';
			$data['oct_product_upc_show'] = $oct_deals_data['product_upc_show'] ?? '';
			$data['oct_product_ean_show'] = $oct_deals_data['product_ean_show'] ?? '';
			$data['oct_product_jan_show'] = $oct_deals_data['product_jan_show'] ?? '';
			$data['oct_product_isbn_show'] = $oct_deals_data['product_isbn_show'] ?? '';
			$data['oct_product_mpn_show'] = $oct_deals_data['product_mpn_show'] ?? '';

			$data['total_reviews'] = (int)$product_info['reviews'];

			$oct_review = $this->model_catalog_review->getOCTReviewsByProductId($product_id);

			$data['oct_rating'] = isset($oct_review['sum']) ? round((float)$oct_review['sum'] / $data['total_reviews'], 1) : 0;

			$data['oct_raiting_stats'][5] = [
				'raiting' => isset($oct_review['rating'][5]) ? round(count($oct_review['rating'][5])/$data['total_reviews']*100) : 0,
				'sum' => isset($oct_review['rating'][5]) ? (int)count($oct_review['rating'][5]) : 0
			];

			$data['oct_raiting_stats'][4] = [
				'raiting' => isset($oct_review['rating'][4]) ? round(count($oct_review['rating'][4])/$data['total_reviews']*100) : 0,
				'sum' => isset($oct_review['rating'][4]) ? (int)count($oct_review['rating'][4]) : 0
			];

			$data['oct_raiting_stats'][3] = [
				'raiting' => isset($oct_review['rating'][3]) ? round(count($oct_review['rating'][3])/$data['total_reviews']*100) : 0,
				'sum' => isset($oct_review['rating'][3]) ? (int)count($oct_review['rating'][3]) : 0
			];

			$data['oct_raiting_stats'][2] = [
				'raiting' => isset($oct_review['rating'][2]) ? round(count($oct_review['rating'][2])/$data['total_reviews']*100) : 0,
				'sum' => isset($oct_review['rating'][2]) ? (int)count($oct_review['rating'][2]) : 0
			];

			$data['oct_raiting_stats'][1] = [
				'raiting' => isset($oct_review['rating'][1]) ? round(count($oct_review['rating'][1])/$data['total_reviews']*100) : 0,
				'sum' => isset($oct_review['rating'][1]) ? (int)count($oct_review['rating'][1]) : 0
			];
			$data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);

			$data['products'] = array();

			$results = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);
			$oct_product_stickers = [];

			if ($this->config->get('oct_stickers_status')) {
				$oct_stickers = $this->config->get('oct_stickers_data');

				$data['oct_sticker_you_save'] = false;

				if ($oct_stickers) {
					$data['oct_sticker_you_save'] = isset($oct_stickers['stickers']['special']['persent']) ? true : false;
				}

				$this->load->model('octemplates/stickers/oct_stickers');
			}
			

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if (!is_null($result['special']) && (float)$result['special'] >= 0) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$tax_price = (float)$result['special'];
				} else {
					$special = false;
					$tax_price = (float)$result['price'];
				}
	
				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format($tax_price, $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

				
			if ($result['quantity'] <= 0) {
				$stock = $result['stock_status'];
			} else {
				$stock = false;
			}

			$can_buy = true;

			if ($result['quantity'] <= 0 && !$this->config->get('config_stock_checkout')) {
				$can_buy = false;
			} elseif ($result['quantity'] <= 0 && $this->config->get('config_stock_checkout')) {
				$can_buy = true;
			}

			$oct_grayscale = ($this->config->get('theme_oct_deals_no_quantity_grayscale') && !$can_buy) ? true : false;
			$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
			'stock'     => $stock,
			'can_buy'   => $can_buy,
			'oct_grayscale'  => $oct_grayscale,
			
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $rating,
			'reviews'	  => $result['reviews'],
			'oct_model'	  => $this->config->get('theme_oct_deals_data_model') ? $result['model'] : '',
			'quantity'	  => $result['quantity'] <= 0 ? 0 : $result['quantity'],
			'width'		  => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'),
			'height'	  => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'),
			
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			
            $data['products'] = $this->load->controller('octemplates/module/oct_products_modules', $data);
			$data['tags'] = array();

			if ($product_info['tag']) {
				$tags = explode(',', $product_info['tag']);

				foreach ($tags as $tag) {
					$data['tags'][] = array(
						'tag'  => trim($tag),
						'href' => $this->url->link('product/search', 'tag=' . urlencode(html_entity_decode(trim($tag), ENT_QUOTES, 'UTF-8')))
					);
				}
			}

			$data['recurrings'] = $this->model_catalog_product->getProfiles($this->request->get['product_id']);

			
            if (isset($oct_deals_data['open_graph']) && $oct_deals_data['open_graph']) {
                $site_link = $this->request->server['HTTPS'] ? HTTPS_SERVER : HTTP_SERVER;

				$config_logo = file_exists(DIR_IMAGE . $this->config->get('config_logo')) ? $this->config->get('config_logo') : 'catalog/opencart-logo.png';

                $oct_ogimage = $product_info['image'] ? $product_info['image'] : $config_logo;
                $product_image = $site_link . 'image/' . $oct_ogimage;

				$image_info = getimagesize(DIR_IMAGE . $oct_ogimage);

				if ($image_info) {
					$image_width  = $image_info[0];
					$image_height = $image_info[1];
				} else {
					$image_width  = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_logo_width') ? $this->config->get('theme_' . $this->config->get('config_theme') . '_image_logo_width') : 140;
					$image_height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_logo_height') ? $this->config->get('theme_' . $this->config->get('config_theme') . '_image_logo_height') : 65;
				}

				$mime_type = isset($image_info['mime']) ? $image_info['mime'] : 'image/svg+xml';

                $this->document->setOCTOpenGraph('og:title', htmlspecialchars(strip_tags(str_replace("\r", " ", str_replace("\n", " ", str_replace("\\", "/", str_replace("\"", "", (isset($oct_seo_title) && $oct_seo_title) ? $oct_seo_title : $product_info['meta_title'])))))));
                $this->document->setOCTOpenGraph('og:description', htmlspecialchars(strip_tags(str_replace("\r", " ", str_replace("\n", " ", str_replace("\\", "/", str_replace("\"", "", (isset($oct_seo_description) && $oct_seo_description && empty($product_info['meta_description'])) ? $oct_seo_description : $product_info['meta_description'])))))));
                $this->document->setOCTOpenGraph('og:site_name', htmlspecialchars(strip_tags(str_replace("\r", " ", str_replace("\n", " ", str_replace("\\", "/", str_replace("\"", "", $this->config->get('config_name'))))))));
                $this->document->setOCTOpenGraph('og:url', $this->url->link('product/product', 'product_id=' . $product_info['product_id']));
                $this->document->setOCTOpenGraph('og:image', str_replace(" ", "%20", $product_image));

				if (isset($mime_type) && $mime_type) {
                	$this->document->setOCTOpenGraph('og:image:type', $mime_type);
				}

				if (isset($image_width) && $image_width) {
                	$this->document->setOCTOpenGraph('og:image:width', $image_width);
				}

				if (isset($image_height) && $image_height) {
					$this->document->setOCTOpenGraph('og:image:height', $image_height);
				}

                $this->document->setOCTOpenGraph('og:image:alt', htmlspecialchars(strip_tags(str_replace("\r", " ", str_replace("\n", " ", str_replace("\\", "/", str_replace("\"", "", $data['heading_title'])))))));
                $this->document->setOCTOpenGraph('og:type', 'product');
            }
			$this->model_catalog_product->updateViewed($this->request->get['product_id']);
			
			
      // OCFilter Start
      if ($this->registry->get('ocfilter') && $this->ocfilter->startup()) {
        $this->ocfilter->api->setProductItemControllerData($data);
      }
      // OCFilter End
      $data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('product/product', $data));
		} else {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode(trim($this->request->get['tag']), ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/product', $url . '&product_id=' . $product_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			
      // OCFilter Start
      if ($this->registry->get('ocfilter') && $this->ocfilter->startup()) {
        $this->ocfilter->api->setProductItemControllerData($data);
      }
      // OCFilter End
      $data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function review() {
			if (isset($this->request->post['product_id']) && !empty($this->request->post['product_id'])) {
				$this->request->get['product_id'] = $this->request->post['product_id'];
			}
			
		$this->load->language('product/product');

		$this->load->model('catalog/review');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['reviews'] = array();
			$this->load->model('octemplates/helper');
			

		$review_total = $this->model_catalog_review->getTotalReviewsByProductId($this->request->get['product_id']);

		$results = $this->model_catalog_review->getReviewsByProductId($this->request->get['product_id'], ($page - 1) * 5, 5);

		foreach ($results as $result) {
			
			$oct_review_data = $this->model_octemplates_helper->getOctReviewData($result['review_id']);
			$data['reviews'][] = array(
				'author'     => $result['author'],
			'positive_text' => isset($oct_review_data['positive_text']) ? $oct_review_data['positive_text'] : '',
            'negative_text' => isset($oct_review_data['negative_text']) ? $oct_review_data['negative_text'] : '',
            'admin_answer'  => isset($oct_review_data['admin_answer']) ? nl2br($oct_review_data['admin_answer']) : '',
			'positive_votes' => isset($oct_review_data['positive_votes']) ? $oct_review_data['positive_votes'] : 0,
			'review_id' => $result['review_id'],
			
				'text'       => nl2br($result['text']),
				'rating'     => (int)$result['rating'],
				'date_added' => $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($result['date_added'], 1))
			);
		}

		$pagination = new Pagination();
		$pagination->total = $review_total;
		$pagination->page = $page;
		$pagination->limit = 5;
		$pagination->url = $this->url->link('product/product/review', 'product_id=' . $this->request->get['product_id'] . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

		$this->response->setOutput($this->load->view('product/review', $data));
	}

	public function write() {
		$this->load->language('product/product');

		$json = array();

		if (isset($this->request->get['product_id']) && $this->request->get['product_id']) {
			if ($this->request->server['REQUEST_METHOD'] == 'POST') {
				if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 25)) {
					$json['error']['name'] = $this->language->get('error_name');
				}

				if ((utf8_strlen($this->request->post['text']) < 25) || (utf8_strlen($this->request->post['text']) > 1000)) {
					$json['error']['text'] = $this->language->get('error_text');
				}
			
				if (empty($this->request->post['rating']) || $this->request->post['rating'] < 0 || $this->request->post['rating'] > 5) {
					$json['error']['rating'] = $this->language->get('error_rating');
				}

				// Captcha
				if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
					$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

					if ($captcha) {
						$json['error']['captcha'] = $captcha;
					}
				}

				if (!isset($json['error'])) {
					$this->load->model('catalog/review');

					$this->model_catalog_review->addReview($this->request->get['product_id'], $this->request->post);

					$json['success'] = $this->language->get('text_success');
				}
			}
		} else {
			$json['error'] = $this->language->get('error_product');
		} 

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getRecurringDescription() {
		$this->load->language('product/product');
		$this->load->model('catalog/product');

		if (isset($this->request->post['product_id'])) {
			$product_id = $this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		if (isset($this->request->post['recurring_id'])) {
			$recurring_id = $this->request->post['recurring_id'];
		} else {
			$recurring_id = 0;
		}

		if (isset($this->request->post['quantity'])) {
			$quantity = $this->request->post['quantity'];
		} else {
			$quantity = 1;
		}

		$product_info = $this->model_catalog_product->getProduct($product_id);
		
		$recurring_info = $this->model_catalog_product->getProfile($product_id, $recurring_id);

		$json = array();

		if ($product_info && $recurring_info) {
			if (!$json) {
				$frequencies = array(
					'day'        => $this->language->get('text_day'),
					'week'       => $this->language->get('text_week'),
					'semi_month' => $this->language->get('text_semi_month'),
					'month'      => $this->language->get('text_month'),
					'year'       => $this->language->get('text_year'),
				);

				if ($recurring_info['trial_status'] == 1) {
					$price = $this->currency->format($this->tax->calculate($recurring_info['trial_price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$trial_text = sprintf($this->language->get('text_trial_description'), $price, $recurring_info['trial_cycle'], $frequencies[$recurring_info['trial_frequency']], $recurring_info['trial_duration']) . ' ';
				} else {
					$trial_text = '';
				}

				$price = $this->currency->format($this->tax->calculate($recurring_info['price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

				if ($recurring_info['duration']) {
					$text = $trial_text . sprintf($this->language->get('text_payment_description'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				} else {
					$text = $trial_text . sprintf($this->language->get('text_payment_cancel'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				}

				$json['success'] = $text;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
