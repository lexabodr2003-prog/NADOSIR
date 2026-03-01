<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsNotFound extends Controller {

    public function index(&$route, &$data) {
        $oct_404_page_status = $this->config->get('oct_404_page_status');
    
        if ($oct_404_page_status) {
            $oct_404_page_data = $this->config->get('oct_404_page_data');
            $language_id = (int)$this->config->get('config_language_id');
            $module_text = $oct_404_page_data['module_text'][$language_id] ?? null;
    
            if (!empty($module_text['title'])) {
                $data['heading_title'] = $module_text['title'];
                $this->document->setTitle($data['heading_title']);
            }
    
            $data['oct_404_image'] = '';
    
            if (!empty($oct_404_page_data['image'])) {
                $protocol = (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) ? $this->config->get('config_ssl') : $this->config->get('config_url');
                $data['oct_404_image'] = $protocol . 'image/' . $oct_404_page_data['image'];
            }
    
            if (!empty($module_text['text'])) {
                $data['text_error'] = html_entity_decode($module_text['text'], ENT_QUOTES, 'UTF-8');
            }
        }
    }    
}