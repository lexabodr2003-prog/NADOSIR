<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesStickersCronStickers extends Controller {

	public function index() {
		$cron_password = $this->config->get('oct_stickers_cron_password');

		if (!$cron_password) {
			exit('No Cron Password set in config');
		}

		$this->load->model('octemplates/module/oct_abandoned_cart');
		$ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown';

		if ($this->model_octemplates_module_oct_abandoned_cart->isRateLimited($ip, 'cron_stickers', 5, 300)) {
			exit('Access denied');
		}

		$key = isset($this->request->get['key']) ? (string)$this->request->get['key'] : '';

		if (!$key || $key !== $cron_password) {
			if (!$this->model_octemplates_module_oct_abandoned_cart->checkRateLimit($ip, 'cron_stickers', 5, 300)) {
				$this->log->write('[SECURITY] Multiple failed Cron key attempts (Stickers) from IP: ' . $ip);
			}

			exit('Access denied');
		}

		$this->load->model('octemplates/stickers/oct_stickers');

		if (!empty($this->request->get['pr_id'])) {
			$product_id = (int)$this->request->get['pr_id'];
			$this->model_octemplates_stickers_oct_stickers->generateStickersForSingleProductJson($product_id);

			echo 'Stickers generation done for product_id=' . $product_id;
		} else {
			$this->model_octemplates_stickers_oct_stickers->generateStickersForAllProductsJson();
			echo 'Stickers generation done for ALL products!';
		}
	}
}