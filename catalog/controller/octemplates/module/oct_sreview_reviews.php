<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesModuleOCTSreviewReviews extends Controller {
    public function index() {
        $this->load->language('octemplates/module/oct_sreview_reviews');
        $this->load->language('octemplates/oct_deals');
        $oct_sreview_setting_status = $this->config->get('oct_sreview_setting_status');

        if (isset($oct_sreview_setting_status) && $oct_sreview_setting_status == "on") {
            $oct_sreview_setting_data = $this->config->get('oct_sreview_setting_data');

            $this->load->model('octemplates/module/oct_sreview_reviews');
            $this->load->model('octemplates/microdata');

            if (isset($this->request->get['sort'])) {
                $sort = $this->request->get['sort'];
            } else {
                $sort = 'sort_order_asc';
            }

            if (isset($this->request->get['page'])) {
                $page = (int)$this->request->get['page'];
            } else {
                $page = 1;
            }

            if (isset($this->request->get['limit'])) {
                $limit = (int)$this->request->get['limit'];
            } else {
                $limit = 20;
            }

            if (isset($this->request->get['ajax']) && $this->request->get['ajax'] == '1') {
                $filter_data = [
                    'sort' => $sort,
                    'start' => ($page - 1) * $limit,
                    'limit' => $limit
                ];

                $results = $this->model_octemplates_module_oct_sreview_reviews->getReviews($filter_data);
                $review_total = $this->model_octemplates_module_oct_sreview_reviews->getTotalReviews($filter_data);

                $reviews = [];
                foreach ($results as $result) {
                    $review_subjects = $this->model_octemplates_module_oct_sreview_reviews->getReviewSubjects($result['oct_sreview_review_id']);
                    $review_subject_array = [];

                    if ($review_subjects) {
                        foreach ($review_subjects as $review_subject) {
                            $subject_info = $this->model_octemplates_module_oct_sreview_reviews->getSubject($review_subject['oct_sreview_subject_id']);
                            if ($subject_info) {
                                $review_subject_array[] = [
                                    'name' => $subject_info['name'],
                                    'rating' => $review_subject['rating']
                                ];
                            }
                        }
                    }

                    $reviews[] = [
                        'oct_sreview_review_id' => $result['oct_sreview_review_id'],
                        'author' => $result['author'],
                        'review_subject_array' => $review_subject_array,
                        'avg_rating' => sprintf($this->language->get('text_rating'), round($result['avg_rating'], 1)),
                        'avg_rating_stars' => (float)$result['avg_rating'],
                        'date_added' => $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($result['date_added'], 1)),
                        'date_added_microdata' => $result['date_added'],
                        'text' => html_entity_decode($result['text'], ENT_QUOTES, 'UTF-8'),
                        'admin_text' => html_entity_decode($result['admin_text'] ?? '', ENT_QUOTES, 'UTF-8')
                    ];
                }

                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode([
                    'reviews' => $reviews,
                    'has_more' => ($page * $limit) < $review_total
                ]));
                return;
            }

            $data['breadcrumbs'] = [];

            $data['breadcrumbs'][] = [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home')
            ];

            $reviews = $this->model_octemplates_module_oct_sreview_reviews->getReviews();

            if (isset($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_title']) && !empty($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_title'])) {
                $this->document->setTitle($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_title']);
            } else {
                $this->document->setTitle($this->language->get('heading_title'));
            }

            if (isset($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_meta_description']) && !empty($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_meta_description'])) {
                $this->document->setDescription($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_meta_description']);
            }

            if (isset($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_meta_keywords']) && !empty($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_meta_keywords'])) {
                $this->document->setKeywords($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_meta_keywords']);
            }

            $data['heading_title'] = (isset($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_h1']) && !empty($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_h1'])) ? $oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_h1'] : $this->language->get('heading_title');

            $data['description'] = (isset($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_description']) && !empty($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_description'])) ? html_entity_decode($oct_sreview_setting_data['language'][(int)$this->config->get('config_language_id')]['seo_description'], ENT_QUOTES, 'UTF-8') : '';

            $data['breadcrumbs'][] = [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('octemplates/module/oct_sreview_reviews')
            ];

            $filter_data = [
                'sort' => $sort,
                'start' => ($page - 1) * $limit,
                'limit' => $limit
            ];

            $data['subjects'] = $this->model_octemplates_module_oct_sreview_reviews->getSubjects();
            $data['store_rating_with_subjects'] = $this->model_octemplates_module_oct_sreview_reviews->getTotalStoreReviewsBySubject();

            $review_total = $this->model_octemplates_module_oct_sreview_reviews->getTotalReviews($filter_data);

            if (isset($review_total) && !empty($review_total)) {
                $data['review_total'] = $review_total;
            } else {
                $data['review_total'] = 0;
            }

            $data['store_rating'] = round($this->model_octemplates_module_oct_sreview_reviews->getTotalStoreReviews(), 1);

            if ($this->customer->isLogged()) {
                $data['author'] = $this->customer->getFirstName(). ' '. $this->customer->getLastName();
            } else {
                $data['author'] = '';
            }

            $data['reviews'] = [];

            $results = $this->model_octemplates_module_oct_sreview_reviews->getReviews($filter_data);

            foreach ($results as $result) {
                $review_subjects = $this->model_octemplates_module_oct_sreview_reviews->getReviewSubjects($result['oct_sreview_review_id']);

                $review_subject_array = [];

                if ($review_subjects) {
                    foreach ($review_subjects as $review_subject) {
                        $subject_info = $this->model_octemplates_module_oct_sreview_reviews->getSubject($review_subject['oct_sreview_subject_id']);

                        if ($subject_info) {
                            $review_subject_array[] = [
                                'name' => $subject_info['name'],
                                'rating' => $review_subject['rating']
                            ];
                        }
                    }
                }

                if (!isset($result['admin_text'])) {
                    $result['admin_text'] = '';
                }

                $data['reviews'][] = [
                    'oct_sreview_review_id' => $result['oct_sreview_review_id'],
                    'author' => $result['author'],
                    'review_subject_array' => $review_subject_array,
                    'avg_rating' => sprintf($this->language->get('text_rating'), round($result['avg_rating'], 1)),
                    'avg_rating_stars' => (float)$result['avg_rating'],
                    'date_added' => $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($result['date_added'], 1)),
                    'date_added_microdata' => $result['date_added'],
                    'text' => html_entity_decode($result['text'], ENT_QUOTES, 'UTF-8'),
                    'admin_text' => html_entity_decode($result['admin_text'], ENT_QUOTES, 'UTF-8')
                ];
            }

            $data['has_more'] = ($page * $limit) < $review_total;
            $data['current_page'] = $page;
            $data['ajax_url'] = $this->url->link('octemplates/module/oct_sreview_reviews', 'ajax=1&sort=' . $sort);

            $data['sorts'] = [];

            $data['sorts'][] = [
                'text' => $this->language->get('text_date_added_asc'),
                'value' => 'date_added_asc',
                'href' => $this->url->link('octemplates/module/oct_sreview_reviews', '&sort=date_added_asc')
            ];

            $data['sorts'][] = [
                'text' => $this->language->get('text_date_added_desc'),
                'value' => 'date_added_desc',
                'href' => $this->url->link('octemplates/module/oct_sreview_reviews', '&sort=date_added_desc')
            ];

            $data['sorts'][] = [
                'text' => $this->language->get('text_author_asc'),
                'value' => 'author_asc',
                'href' => $this->url->link('octemplates/module/oct_sreview_reviews', '&sort=author_asc')
            ];

            $data['sorts'][] = [
                'text' => $this->language->get('text_author_desc'),
                'value' => 'author_desc',
                'href' => $this->url->link('octemplates/module/oct_sreview_reviews', '&sort=author_desc')
            ];

            $data['limits'] = [];

            $limits = array_unique([
                $this->config->get($this->config->get('config_theme') . '_product_limit'),
                25,
                50,
                75,
                100
            ]);

            sort($limits);

            foreach ($limits as $value) {
                $data['limits'][] = [
                    'text' => $value,
                    'value' => $value,
                    'href' => $this->url->link('octemplates/module/oct_sreview_reviews', 'limit=' . $value)
                ];
            }

            if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('oct_sreview_reviews', (array)$this->config->get('config_captcha_page'))) {
                $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
            } else {
                $data['captcha'] = '';
            }

            $data['sort'] = $sort;
            $data['limit'] = $limit;

            $data['continue'] = $this->url->link('common/home');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $data['page_title'] = $this->document->getTitle();
            $data['page_description'] = $this->document->getDescription();

            if (empty($data['page_description'])) {
                $data['page_description'] = sprintf($this->language->get('text_reviews_description'), $this->config->get('config_name'));
            }

            $data['reviews_microdata'] = $this->model_octemplates_microdata->generateReviewPageMicrodata([
                'page_url' => $this->url->link('octemplates/module/oct_sreview_reviews'),
                'page_title' => $data['page_title'],
                'page_description' => $data['page_description'],
                'average_rating' => $data['store_rating'],
                'total_reviews' => $data['review_total'],
                'reviews' => $data['reviews'],
                'breadcrumbs' => $data['breadcrumbs']
            ]);

            $this->response->setOutput($this->load->view('octemplates/module/oct_sreview_reviews', $data));
        } else {
            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            $data['breadcrumbs'][] = [
                'text' => $this->language->get('text_error'),
                'href' => $this->url->link('octemplates/module/oct_sreview_reviews', $url)
            ];

            $this->document->setTitle($this->language->get('text_error'));

            $data['heading_title'] = $this->language->get('text_error');

            $data['text_error'] = $this->language->get('text_error');

            $data['button_continue'] = $this->language->get('button_continue');

            $data['continue'] = $this->url->link('common/home');

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    public function write() {
        if ($this->config->get('oct_sreview_setting_status') && isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->load->language('octemplates/module/oct_sreview_reviews');

            $json = [];

            if ($this->request->server['REQUEST_METHOD'] == 'POST') {
                if ((utf8_strlen($this->request->post['author']) < 3) || (utf8_strlen($this->request->post['author']) > 25)) {
                    $json['error']['author'] = $this->language->get('error_author');
                }

                if ((utf8_strlen($this->request->post['text']) < 25) || (utf8_strlen($this->request->post['text']) > 1000)) {
                    $json['error']['text'] = $this->language->get('error_text');
                }

                if (!isset($this->request->post['rating'])) {
                    $json['error']['rating'] = $this->language->get('error_rating');
                }

                if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('oct_sreview_reviews', (array)$this->config->get('config_captcha_page'))) {
                    $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

                    if ($captcha) {
                        $json['error']['captcha'] = $captcha;
                    }
                }

                if (isset($this->request->post['rating'])) {
                    $this->load->model('octemplates/module/oct_sreview_reviews');

                    $sreview_subject_ids = $this->model_octemplates_module_oct_sreview_reviews->getSubjects();

                    foreach ($sreview_subject_ids as $subject_id) {
                        if (!array_key_exists($subject_id['oct_sreview_subject_id'], $this->request->post['rating'])) {
                            $json['error']['rating'] = $this->language->get('error_rating');
                        }
                    }
                }

                if (!isset($json['error'])) {
                    $this->load->model('octemplates/module/oct_sreview_reviews');

                    $this->model_octemplates_module_oct_sreview_reviews->addReview($this->request->post);

                    $oct_sms_settings = $this->config->get('oct_sms_settings');
                    $template_code = 'oct_sreview_reviews';
                    $language_id = (int)$this->config->get('config_language_id');

                    if (isset($oct_sms_settings['templates'][$template_code]['status']) && $oct_sms_settings['templates'][$template_code]['status']) {
                        if (isset($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && !empty($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
                            $sms_template = $oct_sms_settings['templates'][$template_code]['message'][$language_id];
                        } else {
                            $sms_template = $this->language->get('default_sms_template');
                        }

                        if (!empty($sms_template)) {
                            $customer_name = '';
                            if (isset($this->request->post['author'])) {
                                $customer_name = strip_tags($this->request->post['author']);
                                $customer_name = mb_substr($customer_name, 0, 50);
                            }

                            $replace = array(
                                '[customer_name]' => $customer_name,
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

                    $json['success'] = $this->language->get('text_success');
                }
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {
            $this->response->redirect($this->url->link('error/not_found', '', true));
        }
    }
}
