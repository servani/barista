<?php

class FrontendController extends DefaultController
{
	/* Home */
	public function indexAction($params = null) {
		$user = $this->em->getRepository('User')->findAll();
		/* Render */
		$this->render("index.html.twig", array(
			'user' => $user
		));
	}

	/* Ajax */
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
}