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
}