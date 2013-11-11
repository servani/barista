<?php

class Bootstrap
{
	public function __construct() {
		$this->url = trim($_SERVER['REQUEST_URI'], "/");
		$this->routes = json_decode(file_get_contents("../routing.json"), true);
		$this->replaceKeywords();
		$this->matchRoute();
	}
	/* Replace keywords in routes with regular expresions */
	public function replaceKeywords() {
		$keywords = array(
			'{user}'	=> '([a-zA-Z0-9_-])+',
			'{slug}'	=> '([a-zA-Z0-9_-])+',
			'{year}'	=> '([0-9]{4})+',
			'{month}'	=> '([0-9]{1,2})+',
			'{day}'		=> '([0-9]{1,2})+',
			'{id}'		=> '([0-9])+'
		);
		$newkeys = str_replace(array_keys($keywords), $keywords, array_keys($this->routes));
		$this->routes = array_combine($newkeys, $this->routes);
	}

	public function matchRoute() {
		foreach($this->routes as $pattern => $callback) {
			if (preg_match('{^' . $pattern . '$}', $this->url) === 1) {
				$params = array();
				if (isset($callback['params'])) {
					$url = explode('/', $this->url);
					$url = array_slice($url, -1 * count($callback['params']));
					$params = array_combine(array_keys($callback['params']), $url);
				}
				$dc = $this->loadDefaultController();
				if ($this->isSecured()) {
					if ($dc->isAuthenticated()) {
						$this->dispatch($callback['controller'], $callback['action'], $params);
					} else {
						$dc->promptLogin();
					}
				} else {
					$this->dispatch($callback['controller'], $callback['action'], $params);
				}
				return;
			}
		}
		// No match
		$this->handle404();
		return;
	}

	public function dispatch($controller, $action, $params) {
		// Include Controller File
		require_once "src/{$controller}.php";
		// Load Action
		$controller .= 'Controller';
		$action.= 'Action';
		$c = new $controller();
		$c->$action($params);
		return;
	}

	public function handle404() {
		echo "404"; die();
	}

	public function loadDefaultController() {
		require_once "../src/Default.php";
		return new DefaultController();
	}

	public function isSecured() {
		return strpos($this->url, 'admin') !== FALSE;
	}
}