<?php

class FrontendController extends DefaultController
{
	public function setCustomGlobals() {
		// set custom globals to pass to all views
		// $this->twig->addGlobal('_USER', @$this->session['uname']);
	}
	/* Home */
	public function indexAction($params = null) {
		$user = $this->em->getRepository('User')->findAll();
		/* Render */
		$this->render("index.html.twig", array(
			'user' => $user
		));
	}
}