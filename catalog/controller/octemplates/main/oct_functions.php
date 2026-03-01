<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesMainOCTFunctions extends Controller {
    public function octBreadcrumbs($data) {
        $data['oct_deals_data'] = $this->config->get('theme_oct_deals_data');

        return $this->load->view('octemplates/module/oct_breadcrumbs', $data);
    }

    public function getOctPolicy() {
        if(isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $this->config->get('config_maintenance') == 0) {
            $data = [];

            $this->load->language('octemplates/oct_deals');

            $data['oct_policy_accept'] = $this->language->get('oct_policy_accept');
            $data['oct_policy_more'] = $this->language->get('oct_policy_more');

            $data['text_oct_policy'] = false;
            $data['oct_max_day'] = 365;
            $data['oct_policy_value'] = 'oct_policy';
            $data['oct_policy_day_now'] = date("Y-m-d H:i:s");

            $oct_policy_status = $this->config->get('oct_policy_status');
            $oct_policy_data = $this->config->get('oct_policy_data');

            if (isset($oct_policy_data['value']) && $oct_policy_data['value'] && !empty($oct_policy_data['value'])) {
                $data['oct_policy_value'] = $oct_policy_value = $oct_policy_data['value'];
            }

            if ($oct_policy_status && (!isset($this->request->cookie[$oct_policy_value]) || !$this->request->cookie[$oct_policy_value])) {
                if (isset($oct_policy_data['module_text'][(int)$this->config->get('config_language_id')]) && !empty($oct_policy_data['module_text'][(int)$this->config->get('config_language_id')])) {
                    $data['text_oct_policy'] = strip_tags(html_entity_decode($oct_policy_data['module_text'][(int)$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8'));

                    if (isset($oct_policy_data['indormation_id']) && $oct_policy_data['indormation_id']) {
                        $data['text_oct_policy'] .= ' <a target="_blank" href="'. $this->url->link('information/information', 'information_id=' . $oct_policy_data['indormation_id']) . '">' . $data['oct_policy_more'] . '</a>';
                    }

                    if (isset($oct_policy_data['max_day']) && $oct_policy_data['max_day'] && !empty($oct_policy_data['max_day'])) {
                        $data['oct_max_day'] = (int)$oct_policy_data['max_day'];
                    }
                }
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($data));
        } else {
            $this->response->redirect($this->url->link('error/not_found', '', true));
        }
    }

    public function updatePrices() {
        if ((isset($this->request->post['product_id']) && isset($this->request->post['quantity'])) && isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $json = [];

            if ($this->request->post['product_id'] && $this->request->post['quantity']) {
                $this->load->model('catalog/product');
                $this->load->language('octemplates/oct_deals');

                $json['special'] = false;
                $json['you_save'] = false;
                $json['you_save_price'] = false;

                $option_price = 0;

                $product_id = (int)$this->request->post['product_id'];
                $quantity = (int)$this->request->post['quantity'];
                $operation = isset($this->request->post['operation']) ? $this->request->post['operation'] : 'manual';
                $current_quantity = isset($this->request->post['currentQuantity']) ? (int)$this->request->post['currentQuantity'] : $quantity;

                $product_info = $this->model_catalog_product->getOCTProductPrice($product_id, $quantity);
                $pruduct_all_info = $this->model_catalog_product->getProduct($product_id);
                $product_options = $this->model_catalog_product->getProductOptions($product_id);

                if (!empty($this->request->post['option'])) {
                    $options = $this->request->post['option'];
                } else {
                    $options = [];
                }

                $oct_deals_data = $this->config->get('theme_oct_deals_data') ?: [];
                $use_minimum_step = isset($oct_deals_data['use_minimum_step']) ? (bool)$oct_deals_data['use_minimum_step'] : false;

                if ($quantity < $pruduct_all_info['minimum']) {
                    $quantity = $pruduct_all_info['minimum'];
                }

                if ($use_minimum_step && $pruduct_all_info['minimum'] > 1) {
                    $remainder = $quantity % $pruduct_all_info['minimum'];
                    if ($remainder !== 0) {
                        if ($operation === 'minus') {
                            $quantity = $quantity - $remainder;
                            if ($quantity < $pruduct_all_info['minimum']) {
                                $quantity = $pruduct_all_info['minimum'];
                            }
                        } elseif ($operation === 'plus') {
                            $quantity = $quantity - $remainder + $pruduct_all_info['minimum'];
                        } else {
                            if ($quantity > $current_quantity) {
                                $quantity = $quantity - $remainder + $pruduct_all_info['minimum'];
                            } elseif ($quantity < $current_quantity) {
                                $quantity = $quantity - $remainder;
                                if ($quantity < $pruduct_all_info['minimum']) {
                                    $quantity = $pruduct_all_info['minimum'];
                                }
                            } else {
                                $quantity = $quantity - $remainder + $pruduct_all_info['minimum'];
                            }
                        }
                    }
                }

                $json['quantity'] = $quantity;

                foreach ($product_options as $product_option) {
                    if (is_array($product_option['product_option_value'])) {
                        foreach ($product_option['product_option_value'] as $option_value) {
                            if (isset($options[$product_option['product_option_id']])) {
                                if (($options[$product_option['product_option_id']] == $option_value['product_option_value_id']) || ((is_array($options[$product_option['product_option_id']])) && (in_array($option_value['product_option_value_id'], $options[$product_option['product_option_id']])))) {
                                    if ($option_value['price_prefix'] == '+') {
                                        $option_price += $option_value['price'];
                                    } elseif ($option_value['price_prefix'] == '-') {
                                        $option_price -= $option_value['price'];
                                    }
                                }
                            }
                        }
                    }
                }

                $price = (float)$product_info['discount'] ? (float)$product_info['discount'] * (int)$quantity + (float)$option_price * (int)$quantity : (float)$product_info['price'] * (int)$quantity + (float)$option_price * (int)$quantity;

                $special = (float)$product_info['special'] ? (float)$product_info['special'] * (int)$quantity + (float)$option_price * (int)$quantity : 0;

                if ($special) {
                    $json['special'] = $this->currency->format($this->tax->calculate($special, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $json['you_save'] = '-' . number_format(((float)$price - (float)$special) / (float)$price * 100, 0) . '%';
                    $json['you_save_price'] = $this->currency->format((float)$price - (float)$special, $this->session->data['currency']);
                }

                $json['price'] = $this->currency->format($this->tax->calculate($price, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

                $json['tax'] = $this->currency->format((float)$special ? $special : $price, $this->session->data['currency']);
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {
            $this->response->redirect($this->url->link('error/not_found', '', true));
        }
    }

    public function octAllCategories() {
        //$this->load->language('octemplates/product/octallcategories');
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_oct_all_categories'),
            'href' => $this->url->link('octemplates/product/octallcategories', '', true)
        ];

        $oct_data['breadcrumbs'] = $data['breadcrumbs'];

        $data['oct_breadcrumbs'] = $this->load->controller('octemplates/main/oct_functions/octBreadcrumbs', $oct_data);

        $this->document->setTitle($this->language->get('text_oct_all_categories'));
        //$this->document->setDescription($category_info['meta_description']);
        //$this->document->setKeywords($category_info['meta_keyword']);

        $data['categories'] = [];

        if(isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'webp')) {
            $oct_webP = 1 . '-' . $this->session->data['currency'];
        } else {
            $oct_webP = 0 . '-' . $this->session->data['currency'];
        }

        $result_all_categories = $this->cache->get('octemplates.all_categories.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . $oct_webP);

        if (!$result_all_categories) {
            foreach ($this->model_catalog_category->getCategories() as $category) {
                $filter_data_main = [
                    'filter_category_id' => $category['category_id'],
                    'filter_sub_category' => true
                ];

                // Level 2
                $children_data = [];

                $children = $this->model_catalog_category->getCategories($category['category_id']);

                foreach ($children as $child) {
                    $filter_data = array(
                        'filter_category_id' => $child['category_id'],
                        'filter_sub_category' => true
                    );

                    // Level 3
                    $children_data_2 = [];
                    $children_2      = $this->model_catalog_category->getCategories($child['category_id']);

                    foreach ($children_2 as $child_2) {
                        $filter_data2 = [
                            'filter_category_id' => $child_2['category_id'],
                            'filter_sub_category' => true
                        ];

                        /*
                        $children_3 = $this->model_catalog_category->getCategories($child_2['category_id']);

                        $children_data_3 = [];

                        foreach ($children_3 as $child_3) {
                            $filter_data3 = [
                                'filter_category_id'  => $child_3['category_id'],
                                'filter_sub_category' => true
                            ];

                            $children_data_3[] = [
                                'category_id' => $child_3['category_id'],
                                'count_products' => ($this->config->get('config_product_count') ? $this->model_catalog_product->getTotalProducts($filter_data3) : ''),
                                'name'  => $child_3['name'],
                                'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $child_2['category_id'] . '_' . $child_3['category_id'], true)
                            ];
                        }
                        */

                        $children_data_2[] = [
                            //'children' => $children_data_3,
                            'category_id' => $child_2['category_id'],
                            'count_products' => ($this->config->get('config_product_count') ? $this->model_catalog_product->getTotalProducts($filter_data2) : ''),
                            'name' => $child_2['name'],
                            'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $child_2['category_id'], true)
                        ];
                    }

                    $children_data[] = [
                        'children' => $children_data_2,
                        'count_products' => ($this->config->get('config_product_count') ? $this->model_catalog_product->getTotalProducts($filter_data) : ''),
                        'name' => $child['name'],
                        'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'], true)
                    ];
                }

                // Level 1
                $data['categories'][] = [
                    'name' => $category['name'],
                    'count_products' => ($this->config->get('config_product_count') ? $this->model_catalog_product->getTotalProducts($filter_data_main) : ''),
                    'thumb' => $category['image'] ? $this->model_tool_image->resize($category['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height')) : $this->model_tool_image->resize('no-thumb.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height')),
                    'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'),
                    'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'),
                    'children' => $children_data,
                    'href' => $this->url->link('product/category', 'path=' . $category['category_id'], true)
                ];
            }

            $result_all_categories = $data['categories'];

            $this->cache->set('octemplates.all_categories.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . $oct_webP, $result_all_categories);
        }

        $data['categories'] = $result_all_categories;

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('octemplates/product/oct_all_categories', $data));
    }

    public function mobileContacts() {
        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $data['oct_deals_data'] = $oct_deals_data = $this->config->get('theme_oct_deals_data');

            if (isset($oct_deals_data['mobile_menu']['time']) && $oct_deals_data['mobile_menu']['time']) {
                if (isset($oct_deals_data['contact_open'][(int)$this->config->get('config_language_id')])){
                    $oct_contact_opens = explode(PHP_EOL, $oct_deals_data['contact_open'][(int)$this->config->get('config_language_id')]);

                    foreach ($oct_contact_opens as $oct_contact_open) {
                        if (!empty($oct_contact_open)) {
                            $data['oct_contact_opens'][] = $oct_contact_open;
                        }
                    }
                }
            }

            if (isset($oct_deals_data['mobile_menu']['phones']) && $oct_deals_data['mobile_menu']['phones']) {
                $oct_contact_telephones = explode(PHP_EOL, $oct_deals_data['contact_telephone']);

                foreach ($oct_contact_telephones as $oct_contact_telephone) {
                    if (!empty($oct_contact_telephone)) {
                        $data['oct_contact_telephones'][] = $oct_contact_telephone;
                    }
                }
            }

            if (isset($oct_deals_data['mobile_menu']['address']) && $oct_deals_data['mobile_menu']['address']) {
                if (isset($oct_deals_data['contact_address'][(int)$this->config->get('config_language_id')]) && !empty($oct_deals_data['contact_address'][(int)$this->config->get('config_language_id')])) {
                    $data['contact_address'] = html_entity_decode($oct_deals_data['contact_address'][(int)$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8');
                }

                if (isset($oct_deals_data['contact_map']) && !empty($oct_deals_data['contact_map'])) {
                    $data['contact_map'] = html_entity_decode($oct_deals_data['contact_map'], ENT_QUOTES, 'UTF-8');
                }
            }

            $data['contact'] = $this->url->link('information/contact', '', true);

            $data['socials'] = (isset($oct_deals_data['socials']) && !empty($oct_deals_data['socials'])) ? $oct_deals_data['socials'] : false;

            $this->response->setOutput($this->load->view('octemplates/menu/oct_mobile_contacts', $data));
        }
    }

    public function productViews() {
		if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->load->language('product/product');

			$settings = [
				'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_compare_width') ?? 80,
				'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_compare_height') ?? 80,
				'limit' => 20,
				'mobile' => 1
			];

            $data['oct_popup_view_status'] = $this->config->get('oct_popup_view_status');

			$data['products'] = $this->load->controller('extension/module/oct_product_views', $settings);

			if ($data['products']) {
				$this->response->setOutput($this->load->view('octemplates/module/oct_products_modules', $data));
			}
		} else {
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}
	}

    public function OctDateTime($array) {
        $data = array_merge([], $this->language->load('octemplates/oct_deals'));
    
        $dateParts = preg_split("/[^\d]/", $array[0]);
        $seconds = (isset($array[1]) && $array[1]) ? " ({$dateParts[3]}:{$dateParts[4]})" : '';
        $inputDate = "{$dateParts[0]}{$dateParts[1]}{$dateParts[2]}";
        $today = date('Ymd');
        $yesterday = date("Ymd", strtotime("-1 day"));
    
        if ($inputDate == $today) {
            return $data['text_today'] . $seconds;
        } elseif ($inputDate == $yesterday) {
            return $data['text_yesterday'] . $seconds;
        } else {
            $monthNumber = intval($dateParts[1]);
            $formattedDate = $dateParts[2] . " " . $data['text_month_' . $monthNumber];
    
            if ($dateParts[0] != date('Y')) {
                $formattedDate .= " " . $dateParts[0];
            }
    
            $formattedDate .= $seconds;
    
            return $formattedDate;
        }
    }
    
    
}
