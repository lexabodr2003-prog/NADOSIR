<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsLiveSearch extends Controller {

    const DEFAULT_DELAY = 500;
    const DEFAULT_COUNT_SYMBOL = 2;

    public function index(&$route, &$data) {
        if ($this->config->get('theme_oct_deals_live_search_status')) {
            $oct_live_search_data = $this->config->get('theme_oct_deals_live_search_data');

            $data['oct_live_search_status'] = $this->config->get('theme_oct_deals_live_search_status');
            $data['delay_setting'] = $this->getValueOrDefault($oct_live_search_data, 'delay', self::DEFAULT_DELAY);
            $data['count_symbol'] = $this->getValueOrDefault($oct_live_search_data, 'count_symbol', self::DEFAULT_COUNT_SYMBOL);
        }
    }

    private function getValueOrDefault($array, $key, $default) {
        return isset($array[$key]) && $array[$key] ? (int)$array[$key] : $default;
    }
}
