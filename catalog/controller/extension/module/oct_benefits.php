<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerExtensionModuleOctBenefits extends Controller {
    public function index($setting) {

        $this->load->model('tool/image');

	    static $module = 0;

        if (isset($setting['status']) && $setting['status'] && !empty($setting['oct_benegits_data'])) {
            $data['oct_benefits'] = $setting['oct_benegits_data'];
            $data['language_id'] = (int)$this->config->get('config_language_id');
            $data['oct_class'] = 12 / count($setting['oct_benegits_data']);

            foreach ($data['oct_benefits'] as &$benefit) {
                if (is_file(DIR_IMAGE . $benefit['icon'])) {
                    $benefit['icon'] = $this->model_tool_image->resize($benefit['icon'], 100, 100);
                } else {
                    $benefit['icon'] = $this->model_tool_image->resize('no_image.png', 100, 100);
                }
            }

            $data['oct_main_class'] = (count($setting['oct_benegits_data']) % 2 === 0) ? false : true;

			$data['module'] = $module++;

            return $this->load->view('octemplates/module/oct_benefits', $data);
        }
    }
}
