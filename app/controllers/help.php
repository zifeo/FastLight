<?php

class Help extends Controller_registered {

	public function __construct() {
		parent::__construct();
		
		$this->html->title = 'Help';
	}
	
	public function index() {
		$this->view('help/index');
	}
}