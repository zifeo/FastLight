<?php

//====================================================================
//! FastLight framework
//  Requires : PHP +5.4.0, Mcrypt and the Standard PHP Library (SPL)
//====================================================================

/* Set BENMARKING and DEBUGGING to FALSE in production state */
define('BENCHMARKING',  TRUE);
(!BENCHMARKING) ?: define('TIME', microtime(true));
define('DEBUGGING',		TRUE);
define('PARSING',		TRUE);
define('SANITIZE',		FALSE);

/* If some paths have to be changed */
define('ROOT',			__DIR__ .'/');
define('APP',			ROOT .'app/');
define('CONTROLLERS',	ROOT .'app/controllers/');
define('MODELS',		ROOT .'app/models/');
define('MODS',			ROOT .'app/mods/');
define('VIEWS',			ROOT .'app/views/');

require(APP.'config.php');
require(APP.'core.php');

/* Let's go ! */
App::load($_GET['route']);