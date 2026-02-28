<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsAdminReview extends Controller {

    public function afterAdd($route, $data, $review_id) {

        $this->load->model('octemplates/main/oct_settings');

        $additional_data = [
            'positive_text' => isset($this->request->post['positive_text']) ? $this->request->post['positive_text'] : '',
            'negative_text' => isset($this->request->post['negative_text']) ? $this->request->post['negative_text'] : '',
            'admin_answer' => isset($this->request->post['admin_answer']) ? $this->request->post['admin_answer'] : ''
        ];
    
        $this->model_octemplates_main_oct_settings->addOctReviewData($review_id, $additional_data);
        
    }

    public function afterEdit($route, $data, $review_id) {

        $this->load->model('octemplates/main/oct_settings');

        if (isset($this->request->get['review_id'])) {

            $review_id = (int)$this->request->get['review_id'];

            $additional_data['positive_text'] = isset($this->request->post['positive_text']) ? $this->request->post['positive_text'] : '';
            $additional_data['negative_text'] = isset($this->request->post['negative_text']) ? $this->request->post['negative_text'] : '';
            $additional_data['admin_answer'] = isset($this->request->post['admin_answer']) ? $this->request->post['admin_answer'] : '';

            $this->model_octemplates_main_oct_settings->editOctReviewData($review_id, $additional_data);
        }
        
    }

    public function adminEditForm($route, &$data) {

		$this->load->model('octemplates/main/oct_settings');

        if (isset($this->request->get['review_id'])) {
            $review_id = (int)$this->request->get['review_id'];
            $additional_data = $this->model_octemplates_main_oct_settings->getOctReviewData($review_id);

            $data['positive_text'] = isset($additional_data['positive_text']) ? $additional_data['positive_text'] : '';
            $data['negative_text'] = isset($additional_data['negative_text']) ? $additional_data['negative_text'] : '';
            $data['admin_answer'] = isset($additional_data['admin_answer']) ? $additional_data['admin_answer'] : '';
        }
        
    }
}