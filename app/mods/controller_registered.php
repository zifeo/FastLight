<?php

/** Example of extending Controller class for checking authorization */
class Controller_registered extends Controller {

	public function __construct() {
		parent::__construct();
		
		(App::hasRights(REGISTERED)) ?: $this->redirect('auth', true);
		
		$this->html->priv = "You are in the registered part";		
	}
	
}