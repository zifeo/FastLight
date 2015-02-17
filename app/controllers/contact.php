<?php

class Contact extends Controller_registered {

	public function __construct() {
		parent::__construct();
		
		$this->html->title = 'Contact';
	}
	
	public function index() {
		$this->html->error = false;
		$this->html->messageBody = '';
		$this->view('contact/index');
	}
	
	public function send($message = null) {
		if ($post = $this->requirePOST($token)) {
			// ...
		}
	}
	
}