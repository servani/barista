<?php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gregwar\Image\Image;

class DefaultController
{
	public function __construct() {
		// in case of a motherfucker phpini configuration
		$this->fuckMagicQuotes();
		// prepare the magic session
		if(!isset($_SESSION)){ session_start(); }
		// include config file params
		include "../config.php";
		$this->config = $config;
		// set url, get, post and session
		$this->url = explode('?', trim($_SERVER['REQUEST_URI'], "/"));
		$this->get = isset($this->url[1]) ? urldecode($this->url[1]) : array();
		$this->post = $_POST;
		$this->session = $_SESSION;
		$this->url = $this->url[0];
		if ($this->get) {
			$this->setGet(); // we need to manipulate the get param a little bit
		}
		// load the awesome vendors
		$this->loadDoctrine(); // database
		$this->loadTwig(); // hTML templating
		$this->loadImageHandler(); // image manipulation
		$this->loadMailer(); // mailer transport agent
		// where the fuck am I?
		$this->instance = $this->whereTheFuckAmI();
	}

	/*
	 *
	 * Vendors
	 *
	 */

	/* Swift Mailer */
	public function loadMailer() {
		$sw = $this->config['SWIFTMAILER'];
		// switch between the transport methods (sendmail, smtp or basic mail function)
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
		// load templates
		$dirs = array("../html/forms","../html/lists","../html/views","../html/widgets");
		$loader = new Twig_Loader_Filesystem($dirs);
		// set up environment
		$params = array('cache' => $this->config['PATHS']['cache'], 'auto_reload' => true, 'autoescape' => true );
		$this->twig = new Twig_Environment($loader, $params);
		// add Extension Core (some basic functions)
		$this->twig->addExtension(new Twig_Extension_Core());
		// set globals from config
		foreach ($this->config['TWIG_GLOBALS'] as $k => $v) {
			$this->twig->addGlobal($k, $v);
		}
		// set custom globals
		if (method_exists($this, 'setCustomGlobals')) {
			$this->setCustomGlobals();
		}
		// register simple functions
		$fn = $this->registerTwigSimpleFunctions();
		foreach ($fn as $f) {
			$this->twig->addFunction($f);
		}
		// register simple filters
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
			$return = false;
			if (is_array($slug)) {
				foreach ($slug as $k => $v) {
					$return = $return || strpos($url[0] . '/', '/'. $k . '/') !== FALSE ? 'active' : '';
				}
			}
			return $return || strpos($url[0] . '/', '/'. $slug . '/') !== FALSE ? 'active' : '';
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
					$res = $obj->$prop()->format('H:i - d/m/Y');
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
				// new line to <br> tag and p
				$bbcode = nl2br($bbcode);
				$bbcode = '<p>' . preg_replace(array("/([\n]{1,})/i", "/([^>])\n([^<])/i"), array("</p>\n<p>", '$1<br />$2'), trim($bbcode)) . '</p>';
			} else {
				// we don't need no paragraphs (8)
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
		// the incredible Entity Manager
		$this->em = EntityManager::create($this->config['DB'], $config);
		// everything is ok?
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
		$error = false;
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
			$error = true;
		}
		$this->render("login.html.twig", array('error' => $error));
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
		// i think this function works fine, but I'm not sure :) btw, it's pretty awful
		// the function parameters are a mess
		$slug = preg_replace("/ /", "-", strtolower($str));
		$a = array("a","e","i","o","u","n","u");
		$b = array("á","é","í","ó","ú","ñ","ü");
		$slug = str_replace($b, $a, $slug);
		$slug = preg_replace("/[^A-Za-z0-9\_\-\.]/", "", $slug);
		$slug = preg_replace("/-+/","-",$slug);
		$slug .= "ñ"; // nice (?)
		$slug = str_replace(array("-ñ","ñ"),"",$slug);
		$fix = '';
		$i = 1;
		$loop = TRUE;
		$res = false;
		// this is shit
		if (is_array($en)) {
			$edit = $en[1];
			$oslug = $en[2];
			$en = $en[0];
		}
		// this can loop for fucking ever
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
			// this will create something like this: $this->get['param1'] = 'value1'
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

	/* Validate url */

	public function validUrl($url) {
		if (!strstr($url, 'http://') && !strstr($url, 'https://')) {
			$url = 'http://' . $url;
		}
		if(filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
			return false;
		}
		return $url;
	}

	/* XHR */

	public function xhrAction($params = null) {
		if ($this->post) {
			$method = 'XHR' . $params['slug'];
			if (method_exists($this, $method)) {
				print_r(json_encode($this->$method()));
				die();
			}
			print_r(json_encode(array('success' => false)));
			die();
		}
		echo "Error";
		die();
	}

	/* Backend */

	public function listAction($params = null) {
		try {
			// query conditions
			$default_results = 10;
			$cm = 'get' . $params['slug'] . 'DefaultResults';
			if (method_exists($this, $cm)) {
				$default_results = $this->$cm();
			}
			$results_x_page = $this->getResultsPerPage($default_results);
			$current_offset = isset($this->get['p']) ? ($this->get['p'] - 1) * $results_x_page : 0;
			// custom Order
			$cm = 'get' . $params['slug'] . 'Order';
			if (method_exists($this, $cm) && !isset($this->get['o'])) {
				$order = $this->$cm();
			} else {
				$order = isset($this->get['o']) ? $this->get['o'] : 'id';
			}
			$dir = isset($this->get['d']) && $this->get['d'] == 1 && $order !== 'sort' ? 'ASC' : 'DESC';
			// basic Query
			$entity = $this->em
				->getRepository($params['slug'])
				->createQueryBuilder('q');
			// where manager
			if (isset($this->get['w'])) {
				$entity = $this->whereManager($entity);
			}
			// number of items
			$aux = $entity->getQuery()->getResult();
			$n = count($aux);
			// available Filters (must be here before fucking with the results)
			$filters = array();
			$cm = 'get' . $params['slug'] . 'Filters';
			if (method_exists($this, $cm)) {
				$filters = $this->$cm($aux);
			}
			// query requested
			$entity = $entity->setMaxresults($results_x_page)
				->setFirstResult($current_offset)
				->orderBy('q.' . $order, $dir)
				->getQuery()
				->getResult();
		} catch (Exception $e) {
			echo "Entity not found \n"; die();
		}
		// custom data
		$cm = 'get' . $params['slug'] . 'Data';
		if (method_exists($this, $cm) && !isset($this->get['o'])) {
			$data = $this->$cm();
		}
		$this->render($params['slug'] . ".list.html.twig", array(
			'entity' => $entity,
			'entityName' => $params['slug'],
			'q' => array(
				'results' => $n,
				'max' => $results_x_page,
				'nofpages' => ceil($n / $results_x_page),
				'page' => $current_offset / $results_x_page + 1,
				'order' => $order,
				'dir' => $dir === 'ASC' ? 1 : 0,
				'where' => @$this->get['w']
			),
			'filters' => $filters,
			'data' => @$data
		));
	}

	public function newAction($params = null, $entity = null, $error = false) {
		$cm = 'new' . $params['slug'] . 'Data';
		$data = false;
		if (method_exists($this, $cm)) {
			$data = $this->$cm();
		}
		$this->render($params['slug'] . ".form.html.twig", array(
			'entityName' => $params['slug'],
			'edit' => false,
			'data' => $data,
			'entity' => $entity,
			'error' => $error
		));
	}

	public function editAction($params = null, $entity = null, $error = false) {
		try {
			$entity = $this->em->getRepository($params['slug'])->find($params['id']);
		} catch (Exception $e) {
			echo "Entity or Entry not found \n"; die();
		}
		$cm = 'new' . $params['slug'] . 'Data';
		$data = false;
		if (method_exists($this, $cm)) {
			$data = $this->$cm($entity->getId());
		}
		$this->render($params['slug'] . ".form.html.twig", array(
			'entity' => $entity,
			'entityName' => $params['slug'],
			'edit' => true,
			'data' => $data,
			'error' => $error
		));
	}

	public function createAction($params = null) {
		$entity = new $params['slug'];
		$entity = $this->setFromPost($this->post[$params['slug']], $entity);

		$cm = 'validate' . $params['slug'];
		$error = false;
		if (method_exists($this, $cm)) {
			$error = $this->$cm($entity);
		}

		if (!$error) {
			$cm = 'set' . $params['slug'] . 'Action';
			if (method_exists($this, $cm)) {
				$entity = $this->$cm($entity);
			}
			try {
				$this->em->persist($entity);
				$this->em->flush();
			} catch (Exception $e) {
				echo "Cannot persist entity to database \n"; die();
			}
			$this->redirect("admin/list/" . $params['slug']);
		} else {
			$this->newAction($params, $entity, $error);
		}
	}

	public function updateAction($params = null) {
		$entity = $this->em->getRepository($params['slug'])->find($params['id']);
		$entity = $this->setFromPost($this->post[$params['slug']], $entity);

		$cm = 'validate' . $params['slug'];
		$error = false;
		if (method_exists($this, $cm)) {
			$error = $this->$cm($entity);
		}

		if (!$error) {
			$cm = 'set' . $params['slug'] . 'Action';
			if (method_exists($this, $cm)) {
				$entity = $this->$cm($entity);
			}
			try {
				$this->em->persist($entity);
				$this->em->flush();
			} catch (Exception $e) {
				echo "Cannot persist entity to database \n"; die();
			}
			$this->redirect("admin/list/" . $params['slug']);
		} else {
			$this->editAction($params, $entity, $error);
		}
	}

	public function deleteAction($params = null) {
		$entity = $this->em->getRepository($params['slug'])->find($params['id']);
		$cm = 'delete' . $params['slug'] . 'Action';
		if (method_exists($this, $cm)) {
			$entity = $this->$cm($entity);
		}
		$this->em->remove($entity);
		$this->em->flush();
		$this->redirect("admin/list/" . $params['slug']);
	}

	/* Backend XHR */

	public function XHRsaveorder() {
		$res = array('success' => false);
		if (isset($this->post['items'])) {
			try {
				$entity = $this->em
					->getRepository($this->post['en'])
					->createQueryBuilder('q')
					->where('q.id IN (:ids)')
					->setParameter('ids', $this->post['items'])
					->orderBy('q.sort', 'DESC')
					->getQuery()
					->getResult();
			} catch (Exception $e) {
				return $res;
			}
			$values = array();
			$max = $entity[0]->getSort();
			if ($max === NULL) {
				$max = time();
				$entity[0]->setSort($max);
			}
			foreach ($this->post['items'] as $i) {
				$values[$i] = $max--;
			}
			foreach ($entity as $e) {
				$e->setSort($values[$e->getId()]);
				$this->em->persist($e);
			}
			$this->em->flush();
			$res['success'] = true;
		}
		return $res;
	}

	public function XHRupload() {
		$res = array('success' => false);
		if (isset($_FILES) && $_FILES) {
			$files = $this->uploadFile(@$this->post['filetype']);
			if ($files) {
				$res['files'] = $files;
				$res['success'] = true;
			}
		}
		return $res;
	}

	public function XHRtoggleflag() {
		$res['success'] = false;
		if (isset($this->post['en'], $this->post['id'], $this->post['prop'])) {
			$entity = $this->em->getRepository($this->post['en'])->find($this->post['id']);
			$setProp = 'set' . $this->post['prop'];
			$getProp = 'get' . $this->post['prop'];
			if (method_exists($entity, $setProp)) {
				$entity->$setProp(!$entity->$getProp());
				$this->em->persist($entity);
				$this->em->flush();
				$res['success'] = true;
			}
		}
		return $res;
	}

	public function XHRdeletefile() {
		if (isset($this->post['filename'])) {
			$fullname = $this->getUploadDir() . $this->post['filename'];
			if (unlink($fullname) !== FALSE) {
				return true;
			}
		}
		return false;
	}

	/* Backend helpers */

	public function whereManager($entity) {
		$wheres = explode('|', $this->get['w']);
		foreach ($wheres as $w) {
			$aux = explode(':', $w);
			$key = $aux[0];
			if (isset($aux[1])) {
				$value = $aux[1];
				$entity->andWhere('q.' . $key . ' = :' . $key);
				$entity->setParameter($key, $value);
			}
		}
		return $entity;
	}

	public function isActiveFilter($filter) {
		$filters = explode('|', @$this->get['w']);
		foreach ($filters as $f) {
			$aux = explode(':', $f);
			$key = $aux[0];
			if (isset($aux[1]) && $filter === $key) {
				return array('active' => true, 'value' => $aux[1]);
			}
		}
		return false;
	}

	public function getResultsPerPage($default) {
		$max = 100;
		$min = 1;
		if (isset($this->get['n'])) {
			$n = abs(intval($this->get['n']));
			$n = $n <= $max ? $n : $max;
			$n = $n < $min ? $default : $n;
		} elseif (isset($this->session['results_x_page'])) {
			$n = $this->session['results_x_page'];
		} else {
			$n = $default;
		}
		$_SESSION['results_x_page'] = $n;
		return $n;
	}

	public function setFromPost($post, $entity) {
		foreach ($post as $key => $value) {
			// Custom fucking fields
			if (strpos($key, 'CustomField') !== FALSE) {
				// Delete all entity cfs if edit
				if ($entity->getId()) {
					$cf = $this->em
						->getRepository('CustomField')
						->createQueryBuilder('q')
						->where('q.post = :id')
						->setParameter('id', $entity->getId())
						->getQuery()
						->getResult();
					foreach ($cf as $c) {
						$this->em->remove($c);
					}
				}
				// Process all the custom fields at once
				foreach ($value as $v) {
					// If isset title and value (attr is optional)
					if ($v['Title'] && $v['Value']) {
						$cf = new CustomField;
						$cf->setTitle($v['Title']);
						$cf->setValue($v['Value']);
						$cf->setAttributes($v['Attr']);
						if (@$v['Type']) {
							$t = $this->em->getRepository('CfType')->find($v['Type']);
							if ($t) {
								$cf->setCfType($t);
							}
						}
						$cf->setPost($entity);
						$this->em->persist($cf);
					}
				}
				// Delete CustoField[Type] from $post
				foreach ($post as $key => $value) {
					if (strpos($key, 'CustomField') !== FALSE) {
						unset($post[$key]);
					}
				}
			// Maybe I should rewrite the following tag stuff
			} elseif (strpos($key, 'Tags') !== FALSE) {
				$values = explode(', ', $value); // in other word: tags
				// Capitalize All!
				$values = array_map('ucwords', $values);
				// Delete duplicated
				$values = array_unique($values);
				// Global tags or type tags?
				$type = explode('-', $key);
				$type = isset($type[1]) ? intval($type[1]) : false;
				$tag = $entity->getTag();
				foreach ($tag as $t) {
					// This is a motherfucker IF statement
					// And I wont explain it
					// (Yes, I'm sure I will regret)
					// Update (2 h after): I regret
					// Update (2 h after I regret): FUCK
					if (($type && $t->getTagType() && $t->getTagType()->getId() === $type) ||
							($type === false && !$t->getTagType())) {
						if (!in_array($t->getName(), $values)) {
							$entity->removeTag($t);
						}
						// Remove from $values the existing relationships
						if (($rk = array_search($t->getName(), $values)) !== false) {
							unset($values[$rk]);
						}
					}
				}
				// (insert and) create relations
				foreach ($values as $v) {
					if (!$v) { continue; }
					$tag = $this->em
						->getRepository('Tag')
						->createQueryBuilder('q')
						->join('q.tagType', 'tt')
						->setMaxresults(1)
						->where('q.name = :name')
						->setParameter('name', $v);
					if ($type) {
						$tag = $tag->andWhere('tt.id = :type')
							->setParameter('type', $type);
					} else {
						$tag = $tag->andWhere('tt.id is NULL');
					}
					$tag = $tag
						->getQuery()
						->getOneOrNullResult();
					if (!$tag) {
						// If not exist, create the tag
						$ntag = new Tag;
						$ntag->setName($v);
						$ntag = $this->setTagAction($ntag);
						if ($type) {
							$tagtype = $this->em->getRepository('TagType')->find($type);
							$ntag->setTagType($tagtype);
						}
						$this->em->persist($ntag);
						$tag = $ntag;
					}
					$entity->addTag($tag);
				}
			// Multiple images / files
			} elseif (strpos($key, 'Images') !== FALSE || strpos($key, 'Files') !== FALSE) {
				$en = strpos($key, 'Images') !== FALSE ? 'Image' : 'File';
				$asset = explode(', ', $value);
				// If edit remove all from db first
				if ($entity->getId()) {
					$e = $this->em
						->getRepository($en)
						->createQueryBuilder('q')
						->where('q.post = :id')
						->setParameter('id', $entity->getId())
						->getQuery()
						->getResult();
					foreach ($e as $d) {
						if (($k = array_search($d->getSrc(), $asset)) !== FALSE) {
							// If img is in insert array, ignore (unset)
							unset($asset[$k]);
						} else {
							// Otherwise, remove the obsolete img
							$this->em->remove($d);
						}
					}
				}
				// Add
				foreach ($asset as $a) {
					if ($a) {
						$i = new $en;
						$i->setSrc($a);
						$i->setPost($entity);
						$now = new DateTime();
						$i->setSort(time());
						$this->em->persist($i);
					}
				}
			} elseif (strpos($key, 'password-repeat') !== FALSE) {
				unset($post[$key]);
			} else {
				$property = 'set' . $key;
				if (strpos($property, 'setId') !== FALSE) {
					$r = str_replace('setId', '', $property);
					if (class_exists($r)) {
						$value = $this->em->getRepository($r)->find($value);
					}
					if (!method_exists($entity, $property)) {
						$property = str_replace('setId', 'set', $property);
					}
				} else if (strpos($property, 'setCreationDate') !== FALSE ||
							strpos($property, 'setPublicDate') !== FALSE) {
					try {
						$value = new DateTime($value);
					} catch(Exception $e) {
						$value = new DateTime();
					}
				}
				$entity->$property($value);
			}
		}
		return $entity;
	}

	public function uploadFile($filetype) {
		$image = $filetype === 'image' ? true : false;
		$max_size = 2;
		if ($image) {
			$validator = new FileUpload\Validator\Simple(1024 * 1024 * $max_size, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'));
		}
		$res = array();
		$path = $this->getUploadDir();
		$pathresolver = new FileUpload\PathResolver\Simple($path);
		$filesystem = new FileUpload\FileSystem\Simple();
		foreach ($_FILES as $key => $f) {
			$arr = explode('-', $key);
			$en = $arr[0];
			$prop = $arr[1];
			if ($f['error']) { continue; }
			// Upload
			$fileupload = new FileUpload\FileUpload($f, $_SERVER);
			$fileupload->setPathResolver($pathresolver);
			$fileupload->setFileSystem($filesystem);
			if ($image) {
				$fileupload->addValidator($validator);
			}
			$file = $fileupload->processAll();
			$file = $file[0][0]; // arr(error, type, name, size, path)
			if ($file->error) { continue; }
			// Rename
			$filename = $this->rename($file->path);
			if ($image) {
				// Handle Image
				if ($this->isImage($file->type)) {
					$this->handleImage($path, $filename, $key, $en);
				}
			}
			$res[] = array(
				'filename' => $filename,
				'path' => $this->config['PATHS']['upload_nice']
			);
		}
		return $res;
	}

	public function isImage($filetype) {
		return in_array($filetype, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'));
	}

	public function rename($filename) {
		// TO DO fix, siempre le agrega un -1 por más que no exista el file (ver str2slug)
		$path = pathinfo($filename);
		$nicename = $this->str2slug($path['filename'], false, $path['dirname'], $path['extension']);
		rename($filename, $path['dirname'] . '/' . $nicename . '.' . $path['extension']);
		return $nicename . '.' . $path['extension'];
	}

	public function handleImage($path, $file, $prop, $en) {
		$cm = 'handle' . $en . $prop;
		if (method_exists($this, $cm)) {
			$this->$cm($path, $file);
		}
	}

	/* Errors */

	public function errorAction($params) {
		// not found
		if ($params['code'] === 404) {
			$this->render("404.html.twig");
		}
	}
}