<?php
class ModelExtensionCurrencyCbr extends Model {

	public function editValueByCode($code, $value) {
		$this->db->query("UPDATE `" . DB_PREFIX . "currency` SET `value` = '" . (float)$value . "', `date_modified` = NOW() WHERE `code` = '" . $this->db->escape((string)$code) . "'");
		$this->cache->delete('currency');
	}

	public function refresh() {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, 'https://cbr.ru/scripts/XML_daily.asp');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);

		$response = curl_exec($curl);

		curl_close($curl);

		if ($response) {
			$dom = new \DOMDocument('1.0', 'UTF-8');
			$dom->loadXml($response);

			$valute = $dom->getElementsByTagName('Valute');

			$currencies = [];

			$currencies['RUB'] = 1.0000;

			foreach ($valute as $currency) {
				$curr = (string)$currency->getElementsByTagName('CharCode')->item(0)->nodeValue;
				$rate = (string)$currency->getElementsByTagName('Value')->item(0)->nodeValue;
				$currencies[$curr] = str_replace(',', '.', $rate);
			}

			if ($currencies) {
				$this->load->model('localisation/currency');

				$default = $this->config->get('config_currency');

				$results = $this->model_localisation_currency->getCurrencies();

				foreach ($results as $result) {
					if (isset($currencies[$result['code']])) {
						$from = $currencies['RUB'];
						$to = $currencies[$result['code']];

						$this->editValueByCode($result['code'], $currencies[$default] * ($from / $to));
					}
				}
			}

			$this->editValueByCode($default, '1.00000');
			$this->cache->delete('currency');
		}
	}
}
