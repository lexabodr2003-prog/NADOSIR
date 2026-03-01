<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsCatalogProductModification extends Controller {

    public function index(&$route, &$data) {
        // Product ID
        $product_id = (int)$this->request->get['product_id'];

        // Get config data
        $data['oct_deals_data'] = $oct_deals_data = $this->config->get('theme_oct_deals_data');
        $data['use_minimum_step'] = isset($oct_deals_data['use_minimum_step']) ? 1 : 0;

        // Product Atributes
        $limit_attr  = $this->config->get('theme_oct_deals_data_pr_atr_limit') ? $this->config->get('theme_oct_deals_data_pr_atr_limit') : 5;
        $data['oct_attributs'] = (isset($oct_deals_data['product_atributes']) && $oct_deals_data['product_atributes']) ? $this->model_catalog_product->getOctProductAttributes($this->request->get['product_id'], $limit_attr) : '';

        // Popup Purchase
        if ($this->config->get('config_checkout_guest') && $this->config->get('oct_popup_purchase_status')) {
            $data['oct_popup_purchase_status'] = $this->config->get('oct_popup_purchase_status');
        }
        
        // Stock Notify
        if ($this->config->get('oct_stock_notifier_status')) {
            $data['oct_stock_notifier_status'] = $this->config->get('oct_stock_notifier_status');
            $data['oct_stock_notifier_data'] = $this->config->get('oct_stock_notifier_data');
        }

        // One Click Purchase
        if ($this->config->get('config_checkout_guest') && $this->config->get('oct_popup_purchase_byoneclick_status')) {
            $oct_byoneclick_data = $this->config->get('oct_popup_purchase_byoneclick_data');
            $oct_data['oct_byoneclick_status'] = isset($oct_byoneclick_data['product']) ? 1 : 0;
            $oct_data['oct_byoneclick_mask'] = $oct_byoneclick_data['mask'];
            $oct_data['oct_byoneclick_product_id'] = $this->request->get['product_id'];
            $oct_data['oct_byoneclick_page'] = '_product';
            $data['oct_byoneclick'] = $this->load->controller('octemplates/module/oct_popup_purchase/byoneclick', $oct_data);
        }

        // Product Tabs
        $data['oct_product_extra_tabs'] = [];

        if ($this->config->get('oct_product_tabs_status')) {
            $this->load->model('octemplates/module/oct_product_tabs');

            $oct_product_extra_tabs = $this->model_octemplates_module_oct_product_tabs->getProductTabs($product_id);

            if ($oct_product_extra_tabs) {
                foreach ($oct_product_extra_tabs as $extra_tab) {
                    $extra_text = str_replace("<img", "<img class='img-fluid'", html_entity_decode($extra_tab['text'], ENT_QUOTES, 'UTF-8'));

                    $data['oct_product_extra_tabs'][] = [
                        'title' => $extra_tab['title'],
                        'text'  => $extra_text
                    ];
                }
            }
        }

        // Product Other data
        $data['oct_popup_found_cheaper_status'] = $this->config->get('oct_popup_found_cheaper_status');
        $data['oct_stock_display'] = $this->config->get('config_stock_display');
        $data['product_quantity_show'] = $data['oct_deals_data']['product_quantity_show'] ?? 0;

        // Bought Together
        $data['oct_bought_together_status'] = $data['oct_deals_data']['bought_together'] ?? 0;

        if ($data['oct_bought_together_status']) {
            
            $this->load->model('octemplates/helper');
            $this->load->model('catalog/product');

            $data['bought_together_products'] = [];

            $products = [
                ['product_id' => (int)$product_id]
            ];

            $data['oct_bought_together'] = $this->model_octemplates_helper->getAutomaticRecommendations($products);

            if ($data['oct_bought_together']) {
                $width = $this->config->get('theme_oct_deals_image_additional_width') ? $this->config->get('theme_oct_deals_image_additional_width') : 80;
                $height = $this->config->get('theme_oct_deals_image_additional_height') ? $this->config->get('theme_oct_deals_image_additional_height') : 80;

                foreach ($data['oct_bought_together'] as $oct_bought_together_product_id) {

                    $product_info = $this->model_catalog_product->getProduct($oct_bought_together_product_id);

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
                            $data['bought_together'][] = array(
                                'product_id'  => $product_info['product_id'],
                                'thumb'       => $image,
                                'name'        => $product_info['name'],
                                'description' => utf8_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                                'price'       => $price,
                                'special'     => $special,
                                'tax'         => $tax,
                                'rating'      => $rating,
                                'width'       => $width,
                                'height'      => $height,
                                'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                            );
                        }
                    }
                }

                if ($data['bought_together']) {
                    $data['bought_together_products'] = $this->load->view('octemplates/module/oct_bought_together_products', $data);
                }
            }
        }


        // Product JS Button
        if (isset($oct_deals_data['product_js_button']) && !empty($oct_deals_data['product_js_button'])) {
            $data['product_js_button'] = html_entity_decode($oct_deals_data['product_js_button'], ENT_QUOTES, 'UTF-8');
        }

        // Product Adnvanced Tab
        if (isset($oct_deals_data['product_dop_tab']) && !empty($oct_deals_data['product_dop_tab'])) {
            $data['dop_tab'] = [
                'title' => isset($oct_deals_data['product_dop_tab_title'][(int)$this->config->get('config_language_id')]) ? html_entity_decode($oct_deals_data['product_dop_tab_title'][(int)$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8') : '',
                'text' => isset($oct_deals_data['product_dop_tab_text'][(int)$this->config->get('config_language_id')]) ? html_entity_decode($oct_deals_data['product_dop_tab_text'][(int)$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8') : '',
            ];
        }

        // Product Advantages
        if ((isset($oct_deals_data['product_advantage']) && $oct_deals_data['product_advantage'] == 'on') && (isset($oct_deals_data['product_advantages']) && !empty($oct_deals_data['product_advantages']))) {
            foreach ($oct_deals_data['product_advantages'] as $product_advantage) {
                if (isset($product_advantage[(int)$this->config->get('config_language_id')]['title']) && !empty($product_advantage[(int)$this->config->get('config_language_id')]['title'])) {
                    if (isset($product_advantage[(int)$this->config->get('config_language_id')]['link'])) {
                        if ($product_advantage[(int)$this->config->get('config_language_id')]['link'] == "#" || empty($product_advantage[(int)$this->config->get('config_language_id')]['link'])) {
                            $link = "javascript:;";
                        } else {
                            $link = $product_advantage[(int)$this->config->get('config_language_id')]['link'];
                        }
                    } else {
                        $link = "javascript:;";
                    }

                    if (is_file(DIR_IMAGE . $product_advantage['icone'])) {
                        $cached_image = $this->model_tool_image->resize($product_advantage['icone'], 60, 60);
                    } else {
                        $cached_image = $this->model_tool_image->resize('no_image.png', 60, 60);
                    }

                    $data['oct_product_advantages'][] = [
                        'information_id' => isset($product_advantage['information_id']) && !empty($product_advantage['information_id']) ? (int)$product_advantage['information_id'] : 0,
                        'popup' => (isset($product_advantage['popup']) && !empty($product_advantage['popup'])) && (isset($product_advantage['information_id']) && !empty($product_advantage['information_id'])) && (isset($product_advantage['information_id']) && !empty($product_advantage['information_id'])) ? 1 : 0,
                        'icone' => $cached_image,
                        'title' => strip_tags(html_entity_decode($product_advantage[(int)$this->config->get('config_language_id')]['title'], ENT_QUOTES, 'UTF-8')),
                        'text' => isset($product_advantage[(int)$this->config->get('config_language_id')]['text']) ? nl2br(strip_tags(html_entity_decode($product_advantage[(int)$this->config->get('config_language_id')]['text'], ENT_QUOTES, 'UTF-8'))) : '',
                        'link' => $link,
                    ];
                }
            }
        }

        // Product Delivery
        if (isset($oct_deals_data['product_delivery']) && !empty($oct_deals_data['product_delivery'])) {
            $data['oct_product_delivery'] = $this->processProductData($oct_deals_data['product_delivery'], 'oct_product_delivery', ['price', 'link', 'delivery_time']);
        }

        // Product Payment
        if (isset($oct_deals_data['product_payment']) && !empty($oct_deals_data['product_payment'])) {
            $data['oct_product_payment'] = $this->processProductData($oct_deals_data['product_payment'], 'oct_product_payment');
        }

        // Product FaQ
        if (isset($oct_deals_data['product_faq']) && $oct_deals_data['product_faq']) {
            $data['oct_product_faq'] = $this->load->controller('octemplates/faq/oct_product_faq', $data);

            $data['tab_oct_faq'] = sprintf($this->language->get('tab_oct_faq'), $this->model_octemplates_faq_oct_product_faq->getTotalFaqsByProductId((int)$this->request->get['product_id']));
        }

        // Product Description
        $product_description = str_replace("<img", "<img class='img-fluid'", html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8'));
        $data['description'] = $product_description;

        // Product Timer
        if (isset($oct_deals_data['product_timer']) && !empty($oct_deals_data['product_timer'])) {
            $this->load->model('octemplates/timer/special_date');
            $product_info_special = $this->model_octemplates_timer_special_date->getProductSpecialDates($product_id);
            $today = date('Y-m-d');
        
            if ($product_info_special) {
                $data['product_timer'] = true;
                if ($product_info_special['date_end'] == '0000-00-00') {
                    $data['special_date_end'] = $today;
                    $data['end_date_today'] = true;
                } else {
                    $data['special_date_end'] = $product_info_special['date_end'];
                }
            }
        
            $config_timer_enddate = $this->config->get('theme_oct_deals_data_timer_enddate');
            if (isset($config_timer_enddate) && !empty($config_timer_enddate)) {
                if ($config_timer_enddate === '0000-00-00' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $config_timer_enddate)) {
                    $data['special_date_end'] = $today;
                    $data['end_date_today'] = true;
                } else {
                    $data['special_date_end'] = $config_timer_enddate;
                }
            }
        }

        // Product Blog Related Posts
        if (isset($oct_deals_data['product_blog_related']) && $oct_deals_data['product_blog_related']) {
            $this->load->model('octemplates/blog/oct_blogarticle');
            $this->load->model('octemplates/blog/oct_blogcategory');
            $oct_blogsettings_data = $this->config->get('oct_blogsettings_data');
            $articles_results = $this->model_octemplates_blog_oct_blogarticle->getArticleRelatedProductPage($product_id);

            if (!empty($articles_results)) {

                foreach ($articles_results as $result) {
                    
                    $imageName = $result['image'] ?? 'placeholder.png';
                    $image = $this->model_tool_image->resize($imageName, $oct_blogsettings_data['dop_article_width'], $oct_blogsettings_data['dop_article_height']);

                    // Get categories 
                    $blog_category_badge = $this->model_octemplates_blog_oct_blogcategory->getBlogCategoryBadges($result['blogarticle_id']);

                    $cleanShortDescription = trim(strip_tags($result['shot_description']));
                    $description = !empty($cleanShortDescription) ? $result['shot_description'] : $result['description'];

                    $data['oct_related_articles'][] = [
                        'blogarticle_id'   => $result['blogarticle_id'],
                        'thumb'            => $image,
                        'width'            => $oct_blogsettings_data['dop_article_width'],
                        'height'           => $oct_blogsettings_data['dop_article_height'],
                        'name'             => $result['name'],
                        'blog_categories'  => $blog_category_badge,
                        'description'      => utf8_substr(trim(strip_tags(html_entity_decode($description, ENT_QUOTES, 'UTF-8'))), 0, $oct_blogsettings_data['description_length']) . '..',
                        'date_added'       => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                        'href'             => $this->url->link('octemplates/blog/oct_blogarticle', 'blogarticle_id=' . $result['blogarticle_id'])
                    ];
                }
            }
        }

        // OFFICIAL REPRESENTATIVE
        $current_manu_id = (int)($data['manufacturer_id'] ?? 0); 
        $rep_blocks = $oct_deals_data['official_rep'] ?? [];

        $official_rep_html = '';
        $official_rep_logo = '';  
        $official_rep_link = '';

        if ($current_manu_id && $rep_blocks) {
            $lang_id = (int)$this->config->get('config_language_id');

            $this->load->model('catalog/manufacturer');
            $this->load->model('tool/image');

            $global_block = null;
            $specific_block = null;

            foreach ($rep_blocks as $block) {
                $match_all = !empty($block['all_manu']);
                $match_manu = !empty($block['manufacturers']) && in_array($current_manu_id, $block['manufacturers']);

                if ($match_manu) {
                    $specific_block = $block;
                    break;
                }

                if ($match_all && !$global_block) {
                    $global_block = $block;
                }
            }

            $used_block = $specific_block ?? $global_block;

            if ($used_block) {
                if (!empty($used_block['description'][$lang_id])) {
                    $official_rep_html = html_entity_decode($used_block['description'][$lang_id], ENT_QUOTES, 'UTF-8');
                }

                if (!empty($used_block['logo'])) {
                    $manu = $this->model_catalog_manufacturer->getManufacturer($current_manu_id);
                    if ($manu && !empty($manu['image'])) {
                        $official_rep_logo = $this->model_tool_image->resize(
                            $manu['image'],
                            $this->config->get('theme_oct_deals_image_manufacturer_width') ?? 60,
                            $this->config->get('theme_oct_deals_image_manufacturer_height') ?? 60
                        );
                    }
                }
            }

            $official_rep_link = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $current_manu_id);
        }

        $data['official_rep_block'] = $official_rep_html;
        $data['official_rep_logo'] = $official_rep_logo;
        $data['official_rep_logo_width'] = $this->config->get('theme_oct_deals_image_manufacturer_width') ?? 60;
        $data['official_rep_logo_height'] = $this->config->get('theme_oct_deals_image_manufacturer_height') ?? 60;
        $data['official_rep_link'] = $official_rep_link;

        // Product Sets
        if (isset($oct_deals_data['product_sets']) && $oct_deals_data['product_sets']) {

            $data['product_id_to_found'] = (int)$this->request->get['product_id'];
            $data['product_set'] = $this->load->controller('octemplates/module/oct_product_set/setsInProduct', $data);
            $data['product_sets'] = array();
            
            if ($data['product_set']) {
                $data['product_sets'] = $this->load->view('octemplates/module/oct_product_set', $data);
            }
        }
    }

    private function processProductData($productData, $dataKey, $additionalFields = []) {
        $result = [];
    
        if (isset($productData) && !empty($productData)) {
            usort($productData, function($a, $b) {
                return $a['sort_order'] <=> $b['sort_order'];
            });
    
            foreach ($productData as $product) {

                $productTitle = isset($product[(int)$this->config->get('config_language_id')]['title']) ? $product[(int)$this->config->get('config_language_id')]['title'] : '';

                if (is_file(DIR_IMAGE . $product['image'])) {
                    $cached_image = $this->model_tool_image->resize($product['image'], 20, 20);
                } else {
                    $cached_image = $this->model_tool_image->resize('no_image.png', 20, 20);
                }

                $item = [
                    'image' => $cached_image,
                    'title' => strip_tags(html_entity_decode($productTitle, ENT_QUOTES, 'UTF-8')),
                ];

                foreach ($additionalFields as $field) {
                    if (isset($product[(int)$this->config->get('config_language_id')][$field])) {
                        $item[$field] = strip_tags(html_entity_decode($product[(int)$this->config->get('config_language_id')][$field], ENT_QUOTES, 'UTF-8'));
                    } else {
                        $item[$field] = "";
                    }
                }

                $result[] = $item;
            }
        }
    
        return $result;
    }
}