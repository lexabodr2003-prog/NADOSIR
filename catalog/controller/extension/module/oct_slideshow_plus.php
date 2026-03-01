<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerExtensionModuleOctSlideshowPlus extends Controller {
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

		$this->load->model('octemplates/module/oct_slideshow_plus');
		$this->load->model('tool/image');
		$this->config->set('footer_swiper', true);

		$data['oct_slideshows_plus'] = [];

		$results = $this->model_octemplates_module_oct_slideshow_plus->getSlideshow($setting['slideshow_id']);

		$server = $this->request->server['HTTPS'] ? $this->config->get('config_ssl') : $this->config->get('config_url');

		foreach ($results as $key => $result) {

			$image = json_decode($result['image'], true);

			if (isset($image[(int)$this->config->get('config_language_id')]) && is_file(DIR_IMAGE.$image[(int)$this->config->get('config_language_id')])) {
				$data['status_additional_banners']	= $result['status_additional_banners'];
				$data['position_banners']			= $result['position_banners'];
				$data['timer_view']					= $result['timer_view'];
				$data['full_img']					= $result['full'];

				if (isset($setting['preload_img']) && $setting['preload_img'] && $key == 0) {
					$this->document->setOCTPreload($this->model_tool_image->resize($image[(int)$this->config->get('config_language_id')], $setting['width'], $setting['height']));
				}

				$mobile_image = json_decode($result['mobile_image'], true);

				if ($data['full_img'] && isset($image[(int)$this->config->get('config_language_id')])) {
					$image_info = @getimagesize(DIR_IMAGE . $image[(int)$this->config->get('config_language_id')]);
					$image_width = $image_info[0];
					$image_height = $image_info[1];
				}

				if ($data['full_img'] && isset($mobile_image[(int)$this->config->get('config_language_id')])) {
					$mobile_image_info = @getimagesize(DIR_IMAGE . $mobile_image[(int)$this->config->get('config_language_id')]);
					$mobile_image_width = $mobile_image_info[0];
					$mobile_image_height = $mobile_image_info[1];
				}

				$data['oct_slideshows_plus'][] = [
					'title'                  => $result['title'],
					'button'                 => $result['button'],
					'link'                   => ($result['link'] == '#' or empty($result['link'])) ? 'javascript:;' : $result['link'],
					'background_color'       => $result['background_color'],
					'title_color'            => $result['title_color'],
					'text_color'             => $result['text_color'],
					'button_color'           => $result['button_color'],
					'button_background'      => $result['button_background'],
					'button_color_hover'     => $result['button_color_hover'],
					'button_background_hover' => $result['button_background_hover'],
					'description'            => html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'),
					'image'                  => ($data['full_img'] && isset($image_width) && isset($image_height)) ? $this->model_tool_image->resize($image[(int)$this->config->get('config_language_id')], $image_width, $image_height) : $this->model_tool_image->resize($image[(int)$this->config->get('config_language_id')], $setting['width'], $setting['height']),
					'image_width'			 => isset($image_width) ? $image_width : $setting['width'],
					'image_height'			 => isset($image_height) ? $image_height : $setting['height'],
					'mobile_image'           => ($data['full_img'] && isset($mobile_image_width) && isset($mobile_image_height)) ? $this->model_tool_image->resize($mobile_image[(int)$this->config->get('config_language_id')], $mobile_image_width, $mobile_image_height) : false,
					'mobile_width'			 => isset($mobile_image_width) ? $mobile_image_width : false,
					'mobile_height'			 => isset($mobile_image_height) ? $mobile_image_height : false,
				];
			}
		}

		$data['paginations_status'] = isset($setting['paginations_status']) && $setting['paginations_status'] ? true : false;
		$data['slider_with_megamenu'] = isset($setting['slider_with_megamenu']) && $setting['slider_with_megamenu'] ? true : false;
		$data['slider_type'] = isset($setting['slider_type']) && $setting['slider_type'] ? $setting['slider_type'] : 1;
		$data['autoplay_status'] = isset($setting['slider_autoplay']) && $setting['slider_autoplay'] ? true : false;
		$data['arrows_status'] = isset($setting['arrows_status']) && $setting['arrows_status'] ? true : false;

		$data['module'] = $module++;

		$data['slideshow_id'] = $setting['slideshow_id'];

		return $this->load->view('octemplates/module/oct_slideshow_plus', $data);
	}
}
