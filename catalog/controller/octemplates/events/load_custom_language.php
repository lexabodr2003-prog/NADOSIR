<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsLoadCustomLanguage extends Controller {

    public function index(&$route, &$data, &$output) {

        if ($route == 'account/voucher') {
            return;
        }

        $this->event->unregister('language/*/after', 'octemplates/events/load_custom_language');

        $language = $this->session->data['language'];

        $language_dir = file_exists(DIR_MODIFICATION . 'catalog/language/' . $language . '/octemplates/oct_deals.php') 
            ? DIR_MODIFICATION . 'catalog/language/' . $language . '/octemplates/oct_deals.php' 
            : DIR_LANGUAGE . $language . '/octemplates/oct_deals.php';

        $custom_data = array();
        $custom_data = $this->getCustomLanguageData($language_dir);

        if (!empty($custom_data)) {
            foreach ($custom_data as $key => $value) {
                $this->language->set($key, html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
            }
        } 
    }

    private function getCustomLanguageData($file) {
        $custom_data = array();
        include($file);
        return isset($_) ? $_ : $custom_data;
    }
}