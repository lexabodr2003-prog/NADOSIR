<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerExtensionModuleOctShopReview extends Controller {
    public function index($setting) {
        if ($this->registry->has('oct_mobiledetect')) {
            if ($this->oct_mobiledetect->isMobile() && !$this->oct_mobiledetect->isTablet()) {
                $data['oct_isMobile'] = $this->oct_mobiledetect->isMobile();
            }

            if ($this->oct_mobiledetect->isTablet()) {
                $data['oct_isTablet'] = $this->oct_mobiledetect->isTablet();
            }
        }

        static $module = 0;

        $this->load->language('octemplates/module/oct_shop_review');

        $data['heading_title'] = $this->language->get('heading_title');

        $this->load->model('octemplates/module/oct_sreview_reviews');

        $data['reviews'] = [];
        $data['reviews_count'] = 2;

        if (empty($setting['limit'])) {
            $setting['limit'] = '8';
        }

        $filter_data = [
            'limit' => $setting['limit'],
            'start' => 0
        ];

        $data['position'] = isset($setting['position']) ? $setting['position'] : '';

        $data['subjects'] = $this->model_octemplates_module_oct_sreview_reviews->getSubjects();
        $data['store_rating_with_subjects'] = $this->model_octemplates_module_oct_sreview_reviews->getTotalStoreReviewsBySubject();

        $review_total = $this->model_octemplates_module_oct_sreview_reviews->getTotalReviews($filter_data);

        if (isset($review_total) && !empty($review_total)) {
            $data['review_total'] = $review_total;
        } else {
            $data['review_total'] = 0;
        }

        $data['store_rating'] = round($this->model_octemplates_module_oct_sreview_reviews->getTotalStoreReviews(), 1);
        
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
                'author' => $result['author'],
                'review_subject_array' => $review_subject_array,
                'avg_rating' => sprintf($this->language->get('text_rating'), round($result['avg_rating'], 1)),
                'avg_rating_stars' => $result['avg_rating'],
                'date_added' => $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($result['date_added'], 0)),
                'text' => html_entity_decode($result['text'], ENT_QUOTES, 'UTF-8'),
                'admin_text' => html_entity_decode($result['admin_text'], ENT_QUOTES, 'UTF-8')
            ];
        }

        $data['href_all_reviews'] = $this->url->link('octemplates/module/oct_sreview_reviews');
        $data['module'] = $module++;

        if (!empty($data['reviews'])) {
	        $data['reviews_count'] = count($data['reviews']);

            return $this->load->view('octemplates/module/oct_shop_review', $data);
        }
    }
}
