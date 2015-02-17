<?php

class Home extends Controller {

	public function __construct() {
		parent::__construct();
		
		$this->html->title = 'Home';
	}
	
	public function index() {
		$this->view('home/index');
	}
	
}