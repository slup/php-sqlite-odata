<?php

class Template {

	private $vars  = array();
	
	public function render($template_name) {
		header('Content-type: application/xml;charset=utf-8');
		
		extract($this->vars);
		
		ob_start();
		include($template_name);
		return ob_get_clean();
	}
	
	public function __get($name) {
		return $this->vars[$name];
	}
	
	public function __set($name, $value) {
		$this->vars[$name] = $value;
	}
}
?>