<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Response class
*/
class Response {
	private $headers = array();
	private $level = 0;
	private $output;

	/**
	 * Constructor
	 *
	 * @param	string	$header
	 *
 	*/
	public function addHeader($header) {
		$this->headers[] = $header;
	}
	
	/**
	 * 
	 *
	 * @param	string	$url
	 * @param	int		$status
	 *
 	*/
	public function redirect($url, $status = 302) {
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
		exit();
	}
	
	/**
	 * 
	 *
	 * @param	int		$level
 	*/
	public function setCompression($level) {
		$this->level = $level;
	}
	
	/**
	 * 
	 *
	 * @return	array
 	*/
	public function getOutput() {
		return $this->output;
	}
	
	/**
	 * 
	 *
	 * @param	string	$output
 	*/	
	public function setOutput($output) {
		$this->output = $output;
	}
	
	/**
	 * 
	 *
	 * @param	string	$data
	 * @param	int		$level
	 * 
	 * @return	string
 	*/
	private function compress($data, $level = 0) {
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
			$encoding = 'gzip';
		}

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
			$encoding = 'x-gzip';
		}

		if (!isset($encoding) || ($level < -1 || $level > 9)) {
			return $data;
		}

		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

		if (headers_sent()) {
			return $data;
		}

		if (connection_status()) {
			return $data;
		}

		$this->addHeader('Content-Encoding: ' . $encoding);

		return gzencode($data, (int)$level);
	}
	
	/**
	 * 
 	*/
	public function output() {

			if (!defined('HTTP_CATALOG') && $this->output) $this->output = str_replace('index.php?route=common/home', '', $this->output);

			$oct_output = $this->output;

			if ($oct_output) {

				preg_match_all("/<style>(.*?)<\/style>/is", $oct_output, $oct_css_code);

				if (isset($oct_css_code[1]) && !empty($oct_css_code[1]) && !defined('HTTP_CATALOG')) {
					$oct_output = preg_replace("/<style>.*?<\/style>/is", '', $oct_output);

					$styles = "<style>";

					foreach ($oct_css_code[1] as $code) {
						$styles .= $code;
					}

					$styles .= "</style></head>";

					$oct_output = str_replace("</head>", $styles, $oct_output);
				}

				if (preg_match('/<script id="dataFonts">/is', $oct_output, $oct_js) && !defined('HTTP_CATALOG')) {

					preg_match_all(
						'/<script(?![^>]*(?:\sid|\ssrc|\sdata-module="remarketing"))[^>]*>(.*?)<\/script>/is',
						$oct_output,
						$oct_inline_scripts
					);

					if (!empty($oct_inline_scripts[0])) {
						$js_code = implode("\r\n", $oct_inline_scripts[0]) . "\r\n";
						$oct_output = preg_replace(
							'/<script(?![^>]*(?:\sid|\ssrc|\sdata-module="remarketing"))[^>]*>(.*?)<\/script>/is',
							'',
							$oct_output
						);

						preg_match(
							'/<script(?![^>]*\sdata-module="remarketing")\s+src="https?:\/\/[^\/]+\/min(.*?)<\/script>/is',
							$oct_output,
							$oct_min_script
						);
						$oct_output = preg_replace(
							'/<script(?![^>]*\sdata-module="remarketing")\s+src="https?:\/\/[^\/]+\/min(.*?)<\/script>/is',
							'',
							$oct_output
						);

						preg_match_all(
							'/<script(?![^>]*\sdata-module="remarketing")\s+src="(.*?)<\/script>/is',
							$oct_output,
							$oct_js_code_lines
						);
						$oct_output = preg_replace(
							'/<script(?![^>]*\sdata-module="remarketing")\s+src="(.*?)<\/script>/is',
							'',
							$oct_output
						);

						$js_src = !empty($oct_js_code_lines[0])
							? implode("\r\n", $oct_js_code_lines[0]) . "\r\n"
							: '';

						$oct_output = str_replace(
							'</footer>',
							'</footer>' . $js_src . $js_code,
							$oct_output
						);
					}
				}

			}

			$this->output = $oct_output;
			
		if ($this->output) {
			$output = $this->level ? $this->compress($this->output, $this->level) : $this->output;
			
			if (!headers_sent()) {
				foreach ($this->headers as $header) {
					header($header, true);
				}
			}
			
			echo $output;
		}
	}
}
