<?php

//========================
//! Core file, contains:
//  - App class
//  - Model class
//  - Controller class
//  - Parse class
//========================

(DEBUGGING) ?: error_reporting(0);

/** MODS autoloader */
spl_autoload_register(function($mods) {
	$file = MODS . strtolower($mods) .'.php';	
    if (is_file($file)) { // must be in MODS folder
	    include $file;	    
	    if (!class_exists($mods, false)) {
	    	throw new Exception('unfound mods : '. $mods);
		}
    } else {
	    throw new Exception('unknown mods : '. $mods);
    }
}, true);

/** Exception handler @todo improve error handling */
set_exception_handler(function($e) {
	echo 'Exception : '. $e->getMessage();
});

/** Store and perform all general work and methods for all sub-classes (model, controller, parse) */
abstract class App {

	private static $secured = false;
	private static $post = false;
	private static $temp;
	private static $classname = '';
	private static $method = '';
	private static $rights = DEFAULTRIGHTS;
	private static $id = 0;
	protected static $sqled = 0;
	protected static $parsed = 0;
	
	// secure a session with a limited time and a hashed token
	private static function sessionSecure() {
		session_start();
		$time = time()+($_SESSION['ti'] == true) ? -1800: 0;
		$id = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . floor($time/3600) . substr(__KEY, 0, 8));

		if ($_SESSION['id'] != $id) {
			self::sessionRenew();
			$_SESSION['id'] = $id;
			$_SESSION['ti'] = ($time%3600 > 1800) ? true: false;
		} else if (is_numeric($_SESSION['ur']) && is_numeric($_SESSION['ui'])) {
			self::$rights = $_SESSION['ur'];
			self::$id = $_SESSION['ui'];
		}
		self::$secured = true;
		session_regenerate_id(true);
	}
	
	/** Secure the session by changing the setup */
	protected static function sessionRenew() {
		$_SESSION = array();
		session_destroy();
		session_start();
		session_regenerate_id(true);
	}
	
	/** Correct encoding and get rid of html tag */
	protected static function clean($var) {
		$var = mb_convert_encoding(trim($var), 'UTF-8', 'UTF-8');
		return htmlspecialchars($var, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
	
	/** Route a request of the following pattern : /class/method/var1/.../varN -> class->method(var1,...,varN) */
	public static function load($page = '') {

		(self::$secured) ?: self::sessionSecure();
		($page != '') ?: $page = HOMEPAGE;
						
		$path = explode('/', strtolower($page));
		$classname = ucfirst($path[0]);
		$file = CONTROLLERS . $path[0] .'.php';
		
		// class path error handling
		if (is_file($file)) {
   			(class_exists($classname, false)) ?: include $file;
   			
   			if (is_subclass_of($classname, 'Controller')) {

	   			if (!isset($path[1]) || $path[1] == '') {
					self::$method = 'index';
				} else if (!is_callable(array($classname, $path[1])) || substr($path[1], 0, 2) == '__' || method_exists('App', $path[1])) {					
					self::$method = 'show404';
				} else {
					self::$method = $path[1];
				}
				
				self::$classname = $path[0];
				$class = new $classname();
   			}
   		}
   		if (!is_object($class)) {
   			self::$classname = 'controller';
			self::$method = 'show404';
			$class = new Controller();
   		}

   		// vars cleaning
   		self::$temp = $_SESSION['te'];
   		$_SESSION['te'] = array();
   		unset($path[0], $path[1]);
   		($_SERVER['REQUEST_METHOD'] != 'POST') ?: self::$post = array_map(array('App', 'clean'), $_POST);
   		$_GET = $_POST = array();
   		$vars = array_map(array('App', 'clean'), $path);

		$class->__before();
		call_user_func_array(array($class, self::$method), $vars);	
		$class->__after();
	}
	
	/** Generate a token */
	protected function token() {
		$hash = hash('sha256', mt_rand() . substr(__KEY, 7, 8));
		$this->temp('token', $hash);
		return $hash;
	}
	
	/** Generate a hash */
	protected function hash($sel = '') {
		return hash('md5', mt_rand() . $sel . substr(__KEY, 15, 8));
	}
	
	/** Specific hash for passwords */
	protected function pass($mdp) {
		return hash('sha512', $mdp . substr(__KEY, 23, 8));
	}
	
	/** Enforce token for POST request */
	protected function requirePOST($token) {
		return ($token !== null && $token == $this->temp('token')) ? self::$post: false;
	}
	
	/** Store and get some flashed vars (1 load lifetime) */
	protected function temp($name = null, $val = null) {
		if ($val != null) {
			$_SESSION['te'][$name] = $val;
		} else {
			return self::$temp[$name];
		}
	}
	
	/** Get the current page (class) */
	protected function getClass() {
		return self::$classname;
	}
	
	/** Get the current page (method) */
	protected function getMethod() {
		return self::$method;
	}

	/** Generate site url */
	protected function url($path = '') {
		return SITEURL . $path;
	}
	
	/** Set the current user */
	protected function setUser($newId) {
		$_SESSION['ui'] = self::$id = $newId;
	}
	
	/** Get the current user */
	protected function getUser() {
		if (self::$id != 0) {
			return self::$id;
		} else {
			return false;
		}
	}
	
	/** Set the current level of right */
	protected function setRights($newRights) {
		$_SESSION['ur'] = self::$rights = $newRights;
	}
	
	/** Check if the specified rights are owned */
	protected function hasRights($rights) {
		if (self::$rights >= $rights) {
			return true;
		} else {
			return false;
		}
	}
	
	/** Encode a text */
	protected function encrypt($plaintext) {
        $td = mcrypt_module_open(__CYPHER, '', __MODE, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
        mcrypt_generic_init($td, __KEY, $iv);
        $crypttext = mcrypt_generic($td, $plaintext);
        mcrypt_generic_deinit($td);
        return base64_encode($iv.$crypttext);
    }

	/** Decode a text */
    protected function decrypt($crypttext) {
        $crypttext = base64_decode($crypttext);
        $plaintext = '';
        $td        = mcrypt_module_open(__CYPHER, '', __MODE, '');
        $ivsize    = mcrypt_enc_get_iv_size($td);
        $iv        = substr($crypttext, 0, $ivsize);
        $crypttext = substr($crypttext, $ivsize);
        if ($iv) {
            mcrypt_generic_init($td, __KEY, $iv);
            $plaintext = mdecrypt_generic($td, $crypttext);
        }
        return trim($plaintext);
    }
}

/** Base model to inherit from, gather all SQL methods */
abstract class Model extends App {
	
	private static $pdo = null;

	/** Perform a specified SQL request and format the result */
	protected function sql($sql, $vars = null, $fetchAll = true) {
		(self::$pdo !== null) ?: $this->PDOconnect();
		App::$sqled++;
		$req = self::$pdo->prepare($sql);
		$req->execute($vars);
		if ($fetchAll) {
			$ret = $req->fetchAll();
			$req->closeCursor();
			return $ret;
		} else {
			return $req;
		}
	}
	
	/** Get the last inserted ID */
	public function last() {
		(self::$pdo !== null) ?: $this->PDOconnect();
		return self::$pdo->lastInsertId();
	}
	
	/** Perform a specified SQL statement (request without result) */
	protected function state($sql, $vars = null) {
		(self::$pdo !== null) ?: $this->PDOconnect();
		App::$sqled++;
		$req = self::$pdo->prepare($sql);
		$req->execute($vars);
		return $req->rowCount();
	}
	
	// Connexion setup
	private function PDOconnect() {
		$dsn = __DB .':host='. __SERVER .';port='. __PORT .';dbname='. __BASE;
		$options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
						 PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8, lc_time_names = "'. SITELANG .'"');
		
		try {
			self::$pdo = new PDO($dsn, __USERNAME, __PASSWORD, $options);
		} catch (Exception $e) {
			throw new Exception('unconnected db : '. $e->getMessage());
		}
	}
}

/** Base controller to inherit from */
class Controller extends App {

	private $views = array();
	private $aborted = false;
	private $parser = 'Parse';
	protected $html;

	/** Constructor */
	public function __construct() {
		$this->html = new stdClass();
	}
	
	/** Bufferize and render the output before sending the server answer back */
	public function __destruct() {
		if (!$this->aborted) {
			$this->html = (array) $this->html;
			$buffer = '';
			$elements = count($this->views);
	
			for ($e = 0; $e < $elements; $e++) {
				$buffer .= file_get_contents($this->views[$e]);
			}
			if (SANITIZE) {
				$cleaner = array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s');
				$cleaned = array('>','<','\\1');
				$buffer = preg_replace($cleaner, $cleaned, $buffer);
			}
			if (PARSING) {
				$parser = new $this->parser($this->html);
				echo $parser->parse($buffer);
			} else {
				echo $buffer;
			}
		}
    }
	
	/** First function called */
	public function __before() {
		$this->view(HEADER);
	}
	
	/** Last function called */
	public function __after() {
		$this->view(FOOTER);
	}
	
	/** Index set to 404 by default */
	public function index() {
		$this->show404();
	}
	
	/** Error page */
	public function show404() {
		$this->html->title = ERRORTITLE;
		$this->view(ERROR404);
	}
	
	/** Parse a template */
	protected function parser($class) {
		$this->parser = $class;
	}
	
	/** Set a view up */
	protected function view($view, $getContent = false, $vars = null) {
	
		$file = VIEWS . $view .'.php';
		if ($getContent) {
			$parser = new $this->parser($vars);
			return $parser->parse(file_get_contents($file));
		} else if (is_file($file)) {
	    	$this->views[] = $file;
		} else {
			throw new Exception('unknown view : ' .$view);
		}
	}
	
	/** Set a model up */
	protected function model($model) {
	
		$class = strtolower($model);
		$classname = ucfirst($class) .'_model';
		$file = MODELS . $model .'.php';

		if (is_file($file)) { 

	    	(class_exists($classname, false)) ?: include $file;
	    
			if (is_subclass_of($classname, 'Model') && !is_object($this->$class)) {
				$this->$class = new $classname();
			} else {
				throw new Exception('unfound model : ' .$model);
			}
		} else {
			throw new Exception('unknown model : ' .$model);
		}
	}
	
	/** Transfer vars disponibility from controller to render */
	protected function html($array) {
		$this->html = (object) array_merge((array) $this->html, (array) $array);
	}
	
	/** Redirect the current request */
	protected function redirect($route, $http = false) {
		if ($http == false) {
			$this->aborted = true;
			App::load($route);
		} else {
			header('location:'. App::url($route));
		}
		exit();
	}
}

/** Base controller to inherit from */
class Parse extends App {
	
	private $lines = null;
	private $current = 1;
	private $regex = '/\{([\/\w&;|_ ]+)\}/';
	private $vars = null;
	private $length;
	
	/** Contructor */
	public function __construct(&$vars) {
		$this->vars = $vars;
	}

	/** Parse a template */
	public function parse($data) {
		$this->lines = preg_split($this->regex, $data, -1, PREG_SPLIT_DELIM_CAPTURE);	
		$this->length = count($this->lines);
		while ($this->current < $this->length) {
			$this->evalueTag($this->current);
			$this->current += 2;
		}
		return implode('', $this->lines);
	}
	
	/** Check and return var if exist */
	protected function getVar($var) {
		if (defined($var) && substr($var, 0, 2) != '__') {
			$var = constant($var);
		} else {
			$var = $this->vars[$var];
		}
		if (!is_numeric($var) && is_bool($var)) {
			return ($var) ? 'true' : 'false';
		}
		return $var;
	}
	
	/** Remove lines from the template */
	private function removeLines($from, $to = null) {
		if ($to == null) {
			unset($this->lines[$from]);
		} else {
			for ($k = $from; $k <= $to; $k++) {
				unset($this->lines[$k]);
			}
		}
	}
	
	/** Get corresponding end tag */
	private function getEndtagPosition($tag, $from) {	
		$level = 1;
		while ($from < $this->length && $level) {
			$from += 2;
			if ($this->lines[$from] == '/'.$tag) {
				$level--;
			} else if (substr($this->lines[$from], 0, strlen($tag)) == $tag) {
				$level++;
			}
		}
		return $from;
	}
	
	/** Element : create a link */
	protected function url($file = '') {

		$vars = '';
		$args = func_get_args();
		$numargs = count($args);
		for ($i = 1; $i < $numargs; $i++) {
			$vars .= '/'. $this->getVar($args[$i]);
		}
		return App::url($file . $vars);
	}
	
	/** Element : create a benchmark */
	protected function benchmarking() {
		if (BENCHMARKING) {
			$mem = round(memory_get_usage()/1000);
			$ms = round((microtime(true)-TIME)*1000, 2);			
			return $ms .'ms. '. $mem .'kB. '. App::$parsed .'vars. '. App::$sqled .'reqs.';
		} else {
			return '';
		}
	}
	
	// Get rid of a part of the template
	private function combine($from, $to) {
		$output = '';
		for ($k = $from; $k <= $to; $k++) {
			if ($k%2 == 0) {
				$output .= $this->lines[$k];
			} else {
				$output .= '{'. $this->lines[$k] .'}';
			}
		}
		return $output;
	}
	
	// Evaluate only/if/each and if defined other methods
	private function evalueTag($line) {
	
		if ($this->lines[$line] != '') {
			App::$parsed++;
			$tag = explode(' ', $this->lines[$line]);
			$id = $tag[0];
			unset($tag[0]);
						
			if (method_exists($this, $id) && substr($id, 0, 2) != '__' && (!method_exists('App', $id) || $id == 'url')) {
				$this->lines[$line] = call_user_func_array(array(&$this, $id), $tag);		
			} else {
				switch ($id) {
					case 'only':
						$endtag = $this->getEndtagPosition($id, $line);
						if (defined($tag[1]) && App::hasRights(constant($tag[1]))) {
							$this->removeLines($line);
							$this->removeLines($endtag);
						} else {
							$this->removeLines($line, $endtag);
							$this->current = $endtag;	
						}
						break;
		
					case 'if':
						$endtag = $this->getEndtagPosition($id, $line);	
						$var1 = $this->getVar($tag[1]);
						$var2 = $this->getVar($tag[3]);
						if (($var2 == null && eval('return ('. $var1 .'==true);')) || 
							eval('return ('. $var1 . $tag[2] . $var2 .');')) {
							$this->removeLines($line);
							$this->removeLines($endtag);							
						} else {
							$this->removeLines($line, $endtag);			
							$this->current = $endtag;
						}
						break;
					
					case 'each':
						$endtag = $this->getEndtagPosition($id, $line);

						$var = $this->getVar($tag[1]);
						if (is_array($var)) {
						
							$parser = get_class($this);
							$pattern = $this->combine($line+1, $endtag-1);
							$this->lines[$line] = '';
							foreach ($var as $v) {
								
								$recursive = new $parser($v);
								$this->lines[$line] .= $recursive->parse($pattern);
							}
														
							$this->removeLines($line+1, $endtag);
						} else {
							$this->removeLines($line, $endtag);
						}	
						$this->current = $endtag;	
						break;
						
					default:
						$var = $this->getVar($id);
						if (is_string($var) || is_numeric($var)) {
							$this->lines[$line] = $var;
						} else {
							$this->removeLines($line);
						}
				}
			}
		}
	}
}