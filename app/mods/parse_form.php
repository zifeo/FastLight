<?php

/** Example of extending Parse class for defining new template tags */
class Parse_form extends Parse {

	public function __construct(&$vars) {
		parent::__construct($vars);
		setlocale(LC_ALL, SITELANG);
	}
	
	/** Text field */
	protected function text($max, $name, $var = null, $more = '') {
		$id = $this->conv($name);
		$name = strtr($name, '_', ' ');
		$output .= '<label for="'. $id .'" class="">'. $name .'</label>'."\n";
		$output .= '<input type="text" class="" id="'. $id .'" maxlength="'. $max .'" name="'. $var .'" value="'. $this->getVar($var) .'"'. strtr($more, '_', ' ') .' required>'."\n";
		return $output;
	}
	
	// Convert encoding
	private function conv($data) {
		return iconv('UTF-8', 'ASCII//TRANSLIT', html_entity_decode(strtolower($data)));
	}
	
}