<?php
namespace Template;
final class Twig {
	private $data = array();

	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function render($filename, $code = '') {
		if (!$code) {
			$file = DIR_TEMPLATE . $filename . '.twig';

			if (defined('DIR_CATALOG') && is_file(DIR_MODIFICATION . 'admin/view/template/' . $filename . '.twig')) {	
                $code = file_get_contents(DIR_MODIFICATION . 'admin/view/template/' . $filename . '.twig');
            } elseif (is_file(DIR_MODIFICATION . 'catalog/view/theme/' . $filename . '.twig')) {
                $code = file_get_contents(DIR_MODIFICATION . 'catalog/view/theme/' . $filename . '.twig');
            } elseif (is_file($file)) {
				$code = file_get_contents($file);
			} else {
				throw new \Exception('Error: Could not load template ' . $file . '!');
				exit();
			}
		}

		// initialize Twig environment
		$config = array(
			'autoescape'  => false,
			'debug'       => false,
			'auto_reload' => true,
			'cache'       => DIR_CACHE . 'template/'
		);

		try {
			
			$l1 = new \Twig\Loader\ArrayLoader(array($filename . '.twig' => $code));
			$l2 = new \Twig\Loader\FilesystemLoader([DIR_TEMPLATE]);
			$loader = new \Twig\Loader\ChainLoader([$l1, $l2]);
			

      // OCFilter start
      if (isset($loader) && class_exists('\Twig_Loader_Chain') && class_exists('\Twig_Loader_Filesystem')) {
        $loader_filesystem = new \Twig_Loader_Filesystem(DIR_TEMPLATE);

        $loader = new \Twig_Loader_Chain(array($loader, $loader_filesystem));
      } else if (isset($loader) && class_exists('\Twig\Loader\FilesystemLoader') && class_exists('\Twig\Loader\ChainLoader')) {
        $loader_filesystem = new \Twig\Loader\FilesystemLoader(DIR_TEMPLATE);

        $loader = new \Twig\Loader\ChainLoader(array($loader, $loader_filesystem));
      }
      // OCFilter end
      

			$twig = new \Twig\Environment($loader, $config);

			return $twig->render($filename . '.twig', $this->data);
		} catch (\Exception $e) {
			trigger_error('Error: Could not load template ' . $filename . '!');
			exit();
		}	
	}	
}
