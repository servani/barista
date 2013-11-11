<?php

class FrontendController extends DefaultController
{
	/* Home */
	public function indexAction($params = null) {
		$this->render("index.html.twig", array(
			'foo' => 'bar'
		));
	}
}