<?php

class Bootstrap
{
	public function __construct() {
		// Prepare url
		$this->url = trim($_SERVER['REQUEST_URI'], "/");
		$this->url = explode('?', $this->url);
		$this->url = $this->url[0];
		// Load json
		$this->routes = json_decode(file_get_contents("../routing.json"), true);
		// Replace json keywords with regex
		$this->replaceKeywords();
		// Match or die
		$this->matchRoute();
	}

	/* Replace keywords in routes with regular expresions */
	public function replaceKeywords() {
		$keywords = array(
			'{user}'	=> '([a-zA-Z0-9_-])+',
			'{slug}'	=> '([\.a-zA-Z0-9_-])+',
			'{category}'=> '([a-zA-Z0-9_-])+',
			'{year}'	=> '([0-9]{4})+',
			'{month}'	=> '([0-9]{1,2})+',
			'{day}'		=> '([0-9]{1,2})+',
			'{id}'		=> '([0-9])+'
		);
		$newkeys = str_replace(array_keys($keywords), $keywords, array_keys($this->routes));
		$this->routes = array_combine($newkeys, $this->routes);
	}

	/* Match the url against the json */
	public function matchRoute() {
		foreach($this->routes as $pattern => $callback) {
			if (preg_match('{^' . $pattern . '$}', $this->url) === 1) {
				if(!isset($_SESSION)){ session_start(); }
				// Parameters
				$params = array();
				if (isset($callback['params'])) {
					$url = explode('/', $this->url);
					$url = array_slice($url, -1 * count($callback['params']));
					$params = array_combine(array_keys($callback['params']), $url);
				}
				// Secured (Backend)
				if (isset($callback['secured'])) {
					if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
						$this->dispatch($callback['controller'], $callback['action'], $params);
					} else {
						$this->url = 'http://' . $_SERVER['SERVER_NAME'] . '/login';
						header('location:' . $this->url);
					}
				// Secured (Frontend)
				} elseif (isset($callback['clientsecured'])) {
					if (isset($_SESSION['fauth']) && $_SESSION['fauth'] === true) {
						$this->dispatch($callback['controller'], $callback['action'], $params);
					} else {
						$this->url = 'home/login';
						header('location:' . $this->url);
					}
				// Simple
				} else {
					$this->dispatch($callback['controller'], $callback['action'], $params);
				}
				return;
			}
		}
		// No match
		$this->dispatch('Default', 'error', array('code' => 404));
		return;
	}

	/* Execute the requested action */
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
}