<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOCTemplatesModuleOctProductMainImageOption extends Model {

    public function makeDB() {

        $this->load->model('octemplates/main/oct_settings');

        if (!$this->model_octemplates_main_oct_settings->checkIfTableExist(DB_PREFIX . 'oct_product_image_by_option')) {

            $sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_product_image_by_option` ( ";
            $sql .= "`product_id` int(11) NOT NULL, ";
            $sql .= "`product_image_id` int(11) NOT NULL, ";
            $sql .= "`option_value_id` int(11) NOT NULL ";
            $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            
            $this->db->query($sql);

        }  
    }
}