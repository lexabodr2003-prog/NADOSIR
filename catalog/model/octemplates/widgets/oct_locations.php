<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOCTemplatesWidgetsOctLocations extends Model {
    
    public function getOCTLocations() {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_location` ORDER BY `sort`");
    
        $oct_locations = [];
    
        foreach ($query->rows as $location) {
            $thumb = $this->getLocationThumb($location);
            $description = json_decode($location['description'], true);
            $phone = $this->getLocationPhones($location);
            $open = $this->getLocationOpens($description);
    
            $oct_locations[] = [
                'title' => $this->getDescriptionValue($description, 'title'),
                'address' => $this->getDescriptionValue($description, 'address'),
                'open' => $open,
                'phone' => $phone,
                'map' => html_entity_decode($location['map'], ENT_QUOTES, 'UTF-8'),
                'thumb' => $thumb,
                'link' => html_entity_decode($location['link'], ENT_QUOTES, 'UTF-8')
            ];
        }
    
        return $oct_locations;
    }
    
    private function getLocationThumb($location) {
        if (isset($location['image']) && !empty($location['image']) && file_exists(DIR_IMAGE.$location['image'])) {
            $this->load->model('tool/image');
            return $this->model_tool_image->resize($location['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'));
        } else {
            return false;
        }
    }
    
    private function getDescriptionValue($description, $key) {
        if (isset($description[(int)$this->config->get('config_language_id')][$key])) {
            return html_entity_decode($description[(int)$this->config->get('config_language_id')][$key], ENT_QUOTES, 'UTF-8');
        } else {
            return '';
        }
    }
    
    private function getLocationPhones($location) {
        $phones = !empty($location['phone']) ? explode(PHP_EOL, $location['phone']) : [];
        return array_map(function($phone) {
            return html_entity_decode(trim($phone), ENT_QUOTES, 'UTF-8');
        }, $phones);
    }
    
    private function getLocationOpens($description) {
        $opens = (isset($description[(int)$this->config->get('config_language_id')]['open']) && !empty($description[(int)$this->config->get('config_language_id')]['open'])) 
            ? explode(PHP_EOL, isset($description[(int)$this->config->get('config_language_id')]['open']) ? $description[(int)$this->config->get('config_language_id')]['open'] : '') 
            : [];
    
        return array_map(function($open) {
            return html_entity_decode(trim($open), ENT_QUOTES, 'UTF-8');
        }, $opens);
    }
}
