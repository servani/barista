<?php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gregwar\Image\Image;

class DefaultController
{
	public function __construct() {
		if(!isset($_SESSION)){ session_start(); }
		$this->loadLess();
		$this->loadTwig();
		$this->loadDoctrine();
		$this->loadImageHandler();
	}

	/*
	 *
	 * Vendors
	 *
	 */

	/* Less */
	public function loadLess() {
		$this->less = new lessc;
		$this->less->setFormatter("compressed");
		$this->less->checkedCompile("./css/styles.less", "./css/styles.css");
		$this->less->checkedCompile("./css/styles.backend.less", "./css/styles.backend.css");
	}

	/* Twig */
	public function loadTwig() {
		$loader = new Twig_Loader_Filesystem(array("../views", "../widgets", "../forms", "../lists"));
		$this->twig = new Twig_Environment($loader, array('cache' => '../cache', 'auto_reload' => true, 'autoescape' => true ));
		$this->twig->addExtension(new Twig_Extension_Core());
		// Some globals
		$this->twig->addGlobal('user', $this->getAuthenthicatedUser());

		/* Some custom functions */

		// Return asset path
		/* to do rewrite */
		$fn[] = new Twig_SimpleFunction('asset', function ($path = '') {
			return 'http://' . $_SERVER['SERVER_NAME'] . '/' . $path;
		});
		// Return absolute url
		/* to do rewrite */
		$fn[] = new Twig_SimpleFunction('url', function ($path = '') {
			return 'http://' . $_SERVER['SERVER_NAME'] . '/' . $path;
		});
		// PHP var dump
		$fn[] = new Twig_SimpleFunction('dump', function ($foo) {
			return var_dump($foo);
		});
		// For menu active element based on string (slug)
		$fn[] = new Twig_SimpleFunction('active', function ($str) {
			return strpos($_SERVER['REQUEST_URI'] . '/', '/'. $str . '/') !== FALSE ? 'active' : '';
		});

		/* Some custom Filters */
		// First n words
		/* to do strip bbcode? */
		$filter[] = new Twig_SimpleFilter('words', function ($string, $n = 20) {
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
		// bbcode filter
		/* to do rewrite func */
		$filter[] = new Twig_SimpleFilter('bbcode', function ($bbcode) {
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
			/* New line to <br> tag and p */
			$bbcode = nl2br($bbcode);
			$bbcode = '<p>'.preg_replace(array("/([\n]{1,})/i", "/([^>])\n([^<])/i"), array("</p>\n<p>", '$1<br />$2'), trim($bbcode)).'</p>';
			/* Return parsed contents */
			$dc = new DefaultController();
			return $dc->stripBBtags($bbcode);
		});

		foreach ($fn as $f) {
			$this->twig->addFunction($f);
		}
		foreach ($filter as $f) {
			$this->twig->addFilter($f);
		}
	}

	/* Doctrine */
	public function loadDoctrine() {
		$isDevMode = true;
		$driver = new YamlDriver(array("../orm"));
		$config = Setup::createAnnotationMetadataConfiguration(array("../src"), $isDevMode);
		$config->setMetadataDriverImpl($driver);
		/* to do handle exception and create config file */
		$conn = array('driver' => 'pdo_mysql', 'user' => 'root', 'password' => 'root', 'dbname' => 'framework', 'host' => 'localhost', 'charset' => 'utf8');
		$this->em = EntityManager::create($conn, $config);
	}

	/* Gregwar Image Handler */
	public function loadImageHandler() {
		$this->image = new Image;
	}

	/*
	 *
	 * Common methods
	 *
	 */

	public function getAuthenthicatedUser() {
		if ($this->isAuthenticated()) {
			return array('id' => $_SESSION['uid'], 'name' => $_SESSION['uname']);
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

	/* Is Authenticated */
	public function isAuthenticated() {
		return isset($_SESSION['auth']) && $_SESSION['auth'] === true;
	}

	/* Render a Twig template */
	public function render($template, $params = array()) {
		$this->twig->loadTemplate($template)->display($params);
	}

	/* Redirect */
	public function redirect($url) {
		header('Location: http://'. $_SERVER['SERVER_NAME'] . '/' . $url);
		die();
	}

	/* Upload Dir */
	public function getUploadDir() {
		return __DIR__ . '/../public/content/';
	}

	/* Generate slug from string */
	public function str2slug($str, $en = false, $path = false, $ext = false) {
		$slug = preg_replace("/ /", "-", strtolower($str));
		$a = array("a","e","i","o","u","n","u");
		$b = array("á","é","í","ó","ú","ñ","ü");
		$slug = str_replace($b, $a, $slug);
		$slug = preg_replace("/[^A-Za-z0-9\_\-\.]/", "", $slug);
		$slug = preg_replace("/-+/","-",$slug);
		$slug .= "ñ";
		$slug = str_replace(array("-ñ","ñ"),"",$slug);

			$fix = '';
			$i = 1;
			$loop = TRUE;
			$res = false;
			while ($loop) {
				if ($en) {
					$res = $this->em->getRepository($en)->findBySlug($slug . $fix);
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
}