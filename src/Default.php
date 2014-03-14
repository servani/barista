<?php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gregwar\Image\Image;

class DefaultController
{
	public function __construct() {
		// In case of a motherfucker phpini configuration
		$this->fuckMagicQuotes();
		// Prepare the magic session
		if(!isset($_SESSION)){ session_start(); }
		// Include config file params
		include "../config.php";
		$this->config = $config;
		// Set url, get, post and session
		$this->url = explode('?', trim($_SERVER['REQUEST_URI'], "/"));
		$this->get = isset($this->url[1]) ? urldecode($this->url[1]) : array();
		$this->post = $_POST;
		$this->session = $_SESSION;
		$this->url = $this->url[0];
		if ($this->get) {
			$this->setGet(); // We need to manipulate the get param a little bit
		}
		// Load the awesome vendors
		$this->loadDoctrine(); // Database
		$this->loadTwig(); // HTML templating
		$this->loadImageHandler(); // Image manipulation
		$this->loadMailer(); // Mailer transport agent
		// Where the fuck am I?
		$this->instance = $this->whereTheFuckAmI();
	}

	/* Proyect Modules */

	public function registerMods() {
		return array(
			'User' => 'Usuarios',
			'' => '',
			'Blog' => array(
				'Post' => 'Posts',
				'Image' => 'Imágenes',
				'File' => 'Documentos',
				'Category' => 'Categorías',
				'Tag' => 'Tags',
				'TagType' => 'Tipos de Tag',
				'CfType' => 'Tipos de Campos Pers.',
			)
		);
	}

	/*
	 *
	 * Vendors
	 *
	 */

	/* Swift Mailer */
	public function loadMailer() {
		$sw = $this->config['SWIFTMAILER'];
		// Switch between the transport methods (sendmail, smtp or basic mail function)
		switch ($sw['transport']) {
			case 'sendmail':
				$transport = Swift_SendmailTransport::newInstance($sw['sendmail']);
				break;
			case 'smtp':
				$transport = Swift_SmtpTransport::newInstance($sw['smtp'], $sw['smtp_port'])
					->setUsername($sw['smtp_user'])
					->setPassword($sw['smtp_pass']);
				break;
			default:
				$transport = Swift_MailTransport::newInstance();
				break;
		}
		$this->mailer = Swift_Mailer::newInstance($transport);
	}

	/* Twig */
	public function loadTwig() {
		// Load templates
		$dirs = array("../html/forms","../html/lists","../html/views","../html/widgets");
		$loader = new Twig_Loader_Filesystem($dirs);
		// Set up environment
		$params = array('cache' => $this->config['PATHS']['cache'], 'auto_reload' => true, 'autoescape' => true );
		$this->twig = new Twig_Environment($loader, $params);
		// Add Extension Core (some basic functions)
		$this->twig->addExtension(new Twig_Extension_Core());
		// Set global variables
		$this->twig->addGlobal('_FRAMEWORK', 'Barista');
		$this->twig->addGlobal('_VERSION', '2.0.1');
		$this->twig->addGlobal('_MODS', $this->registerMods());
		$this->twig->addGlobal('_USER', @$this->session['uname']);
		// Register simple functions
		$fn = $this->registerTwigSimpleFunctions();
		foreach ($fn as $f) {
			$this->twig->addFunction($f);
		}
		// Register simple filters
		$fl = $this->registerTwigSimpleFilters();
		foreach ($fl as $f) {
			$this->twig->addFilter($f);
		}
	}

	public function registerTwigSimpleFunctions() {
		$fn = array();
		/*
		 * name: asset
		 * params: (str) path
		 * return: (str) absolute url to path
		 */
		$fn[] = new Twig_SimpleFunction('asset', function ($path = '') {
			return 'http://' . $_SERVER['SERVER_NAME'] . '/' . $path;
		});
		/*
		 * @ alias for asset()
		 * name: url
		 * params: (str) path
		 * return: (str) absolute url to path
		 */
		$fn[] = new Twig_SimpleFunction('url', function ($path = '') {
			return 'http://' . $_SERVER['SERVER_NAME'] . '/' . $path;
		});
		/*
		 * @ php var dump
		 * name: dump
		 */
		$fn[] = new Twig_SimpleFunction('dump', function ($foo) {
			return var_dump($foo);
		});
		/*
		 * @ determine current section
		 * name: active
		 * params: (str) slug
		 * return: (str) active [or empty string]
		 */
		$fn[] = new Twig_SimpleFunction('active', function ($slug) {
			$url = explode('?', $_SERVER['REQUEST_URI']);
			return strpos($url[0] . '/', '/'. $slug . '/') !== FALSE ? 'active' : '';
		});
		return $fn;
	}

	public function registerTwigSimpleFilters() {
		$fl = array();
		/*
		 * @ get obj property
		 * name: get
		 * params: (obj) obj, (str) prop, (str) empty
		 * return: (str) object property value
		 */
		$fl[] = new Twig_SimpleFilter('get', function ($obj, $prop, $empty = '-') {
			if (strpos($prop, '.') !== FALSE) {
				$arr = explode('.', $prop);
				$prop = 'get' . $arr[0];
				$sub_prop = 'get' . $arr[1];
				if (method_exists($obj, $prop) && method_exists($obj->$prop(), $sub_prop)) {
					return $obj->$prop()->$sub_prop();
				}
				return $empty;
			}
			$prop = 'get' . $prop;
			if (strpos($prop, 'Date') !== FALSE) {
				try {
					$res = $obj->$prop()->format('d-m-Y H:i');
				} catch(Exception $e) {
					$res = $empty;
				}
				return $res;
			}
			if (method_exists($obj, $prop)) {
				return $obj->$prop();
			}
			return $empty;
		});
		/*
		 * @ first n words given a string
		 * name: words
		 * params: (str) string, (int) n
		 * return: (str) string
		 */
		$fl[] = new Twig_SimpleFilter('words', function ($string, $n = 20) {
			$string = strip_tags($string);
			$string = preg_replace('#\[[^\]]+\]#', '', $string);
			$res = $string;
			$array = explode(' ', $string);
			if (count($array)<= $n) {
				$res = $string;
			} else {
				array_splice($array, $n);
				$res = implode(' ', $array).'...';
			}
			return $res;
		});
		/*
		 * @ bbcode parser
		 * name: bbcode
		 * params: (str) string
		 * params: (bool)
		 * return: (str) string
		 */
		$fl[] = new Twig_SimpleFilter('bbcode', function ($bbcode, $p = true) {
			/* Basically remove HTML tag's functionality */
			$bbcode = htmlspecialchars($bbcode);
			/* Bold text */
			$match["b"] = "/\[b\](.*?)\[\/b\]/is";
			$replace["b"] = "<b>$1</b>";
			/* Italics */
			$match["i"] = "/\[i\](.*?)\[\/i\]/is";
			$replace["i"] = "<i>$1</i>";
			/* Underline */
			$match["u"] = "/\[u\](.*?)\[\/u\]/is";
			$replace["u"] = "<span style=\"text-decoration: underline\">$1</span>";
			/* Links */
			$match["url"] = "/\[url=(.*?)\](.*?)\[\/url\]/is";
			$replace["url"] = "<a target=\"_blank\" href=\"$1\">$2</a>";
			/* list */
			$match["li"] = "/\[\*\](.*?)(\n|\r\n?)/is";
			$replace["li"] = "<li>$1</li>";
			$match["ul"] = "/\[list\](\n|\r\n?)(.*?)\[\/list\]/is";
			$replace["ul"] = "<ul class=\"bbcodelist\">$2</ul>";
			$match["ol"] = "/\[list=1\](\n|\r\n?)(.*?)\[\/list\]/is";
			$replace["ol"] = "<ul class=\"bbcodelist num\">$2</ul>";
			/* Parse */
			$bbcode = preg_replace($match, $replace, $bbcode);
			if ($p) {
				// New line to <br> tag and p
				$bbcode = nl2br($bbcode);
				$bbcode = '<p>' . preg_replace(array("/([\n]{1,})/i", "/([^>])\n([^<])/i"), array("</p>\n<p>", '$1<br />$2'), trim($bbcode)) . '</p>';
			} else {
				// We don't need no paragraphs (8)
				$bbcode = preg_replace(array("/([^>])\n([^<])/i"), array('$1<br />$2'), trim($bbcode));
			}
			/* Return parsed contents */
			$dc = new DefaultController();
			return $dc->stripBBtags($bbcode);
		});
		return $fl;
	}

	/* Doctrine */
	public function loadDoctrine() {
		$driver = new YamlDriver(array("../orm"));
		$config = Setup::createAnnotationMetadataConfiguration(array("../src"), true);
		$config->setMetadataDriverImpl($driver);
		// The incredible Entity Manager
		$this->em = EntityManager::create($this->config['DB'], $config);
		// Everything is ok?
		try {
			$this->em->getConnection()->connect();
		} catch (Exception $e) {
			echo "Connection error \n"; die();
		}
	}

	/* Gregwar Image Handler */
	public function loadImageHandler() {
		// 8^o
		$this->image = new Image;
	}

	/*
	 *
	 * Common methods
	 *
	 */

	public function fuckMagicQuotes() {
		/*
			I want to give a special thanks to the guy who wrote this function:
			Thank you dude, I don't know you, but you are fucking awesome!
		*/
		if (get_magic_quotes_gpc()) {
			$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
			while (list($key, $val) = each($process)) {
				foreach ($val as $k => $v) {
					unset($process[$key][$k]);
					if (is_array($v)) {
						$process[$key][stripslashes($k)] = $v;
						$process[] = &$process[$key][stripslashes($k)];
					} else {
						$process[$key][stripslashes($k)] = stripslashes($v);
					}
				}
			}
			unset($process);
		}
	}

	public function getAuthenthicatedUser() {
		if ($this->isAuthenticated()) {
			return array('id' => $_SESSION['uid'], 'name' => $_SESSION['uname']);
		}
		return null;
	}

	public function getAuthenthicatedClient() {
		if ($this->isAuthenticatedClient()) {
			return array('id' => $_SESSION['fuid'], 'name' => $_SESSION['funame']);
		}
		return null;
	}

	/* Promt Login */
	public function promptLogin() {
		$this->render("login.html.twig");
	}

	/* Login */
	public function authenticateAction() {
		if (isset($_POST['username']) && isset($_POST['password'])) {
			$username = $_POST['username'];
			$password = md5($_POST['password']);
			$user = $this->em
				->getRepository('User')
				->createQueryBuilder('u')
				->where('u.username = :username')
				->setParameter('username', $username)
				->andWhere('u.password = :password')
				->setParameter('password', $password)
				->getQuery()
				->getOneOrNullResult();
			if ($user) {
				$_SESSION['auth'] = true;
				$_SESSION['uid'] = $user->getId();
				$_SESSION['uname'] = $user->getName();
				$this->redirect('admin');
				return true;
			}
		}
		$this->render("login.html.twig");
		return false;
	}

	/* Logout */
	public function logoutAction() {
		$_SESSION['auth'] = false;
		$this->redirect('login');
	}

	/* Logout Client */
	public function logoutClientAction() {
		$_SESSION['fauth'] = false;
		$_SESSION['products'] = array();
		$this->redirect('home');
	}

	/* Is Authenticated */
	public function isAuthenticated() {
		return isset($_SESSION['auth']) && $_SESSION['auth'] === true;
	}

	/* Is Authenticated Client */
	public function isAuthenticatedClient() {
		return isset($_SESSION['fauth']) && $_SESSION['fauth'] === true;
	}

	/* Render a Twig template */
	public function render($template, $params = array()) {
		try {
			$this->twig->loadTemplate($template)->display($params);
		} catch (Exception $e) {
			echo "Template not found \n"; die();
		}
	}

	/* Redirect */
	public function redirect($url) {
		header('Location: http://'. $_SERVER['SERVER_NAME'] . '/' . $url);
		die();
	}

	/* Upload Dir */
	public function getUploadDir() {
		return $this->config['PATHS']['upload'];
	}

	/* Generate slug from string */
	public function str2slug($str, $en = false, $path = false, $ext = false) {
		// I think this function work fine, but I'm not sure :) btw, it's pretty awful
		// The function parameters are a mess
		$slug = preg_replace("/ /", "-", strtolower($str));
		$a = array("a","e","i","o","u","n","u");
		$b = array("á","é","í","ó","ú","ñ","ü");
		$slug = str_replace($b, $a, $slug);
		$slug = preg_replace("/[^A-Za-z0-9\_\-\.]/", "", $slug);
		$slug = preg_replace("/-+/","-",$slug);
		$slug .= "ñ"; // Nice (?)
		$slug = str_replace(array("-ñ","ñ"),"",$slug);
		$fix = '';
		$i = 1;
		$loop = TRUE;
		$res = false;
		// This is shit
		if (is_array($en)) {
			$edit = $en[1];
			$oslug = $en[2];
			$en = $en[0];
		}
		// This can loop for fucking ever
		while ($loop) {
			if ($en) {
				$aslug = $slug . $fix;
				$res = $this->em->getRepository($en)->findBySlug($aslug);
				if ($edit && count($res) === 1 && $aslug === $oslug) {
					$res = false;
				}
			} elseif ($path) {
				$res = file_exists($path . '/' . $slug . $fix . '.' . $ext);
			}
			if ($res) {
				$fix = '-' . $i++;
			} else {
				$loop = FALSE;
				$slug .= $fix;
			}
		}
		return $slug;
	}

	/* Strip unused bbtags */
	public function stripBBtags ($string) {
		$pattern = "|[[\/\!]*?[^\[\]]*?]|si";
		$replace = "";
		return preg_replace($pattern, $replace, $string);
	}

	/* Set get request */
	public function setGet() {
		$get = explode('&', $this->get);
		$this->get = array();
		foreach ($get as $g) {
			$aux = explode('=', $g);
			// This will create something like this: $this->get['param1'] = 'value1'
			$this->get[$aux[0]] = isset($aux[1]) ? $aux[1] : null;
		}
	}

	/* Define instance */
	public function whereTheFuckAmI() {
		$url = $_SERVER['SERVER_NAME'];
		if (strpos($url, 'local.') !== FALSE) {
			return "local";
		} elseif (strpos($url, 'dev.') !== FALSE) {
			return "dev";
		}
		return "prod";
	}
}