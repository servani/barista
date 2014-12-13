<?php

class BackendController extends DefaultController
{
	/* Setup functions */

	public function setCustomGlobals() {
		// set custom globals to pass to all views
		$this->twig->addGlobal('_USER', @$this->session['uname']);
		$this->twig->addGlobal('_MODS', $this->getBackendModules());
	}

	public function getBackendModules() {
		// 'Entity'		=> 'Nice Name'
		// 'route'		=> 'Nice Name'
		// 'Nice Name'	=>
		//		'Entity'	=> 'Nice Name',
		//		'Entity'	=> 'Nice Name'
		// dividers: N (number) => null
		return array(
			'User' => 'Usuarios',
			'Blog' => array(
				'Post'		=> 'Posts',
				1			=> null,
				'Image'		=> 'Imágenes',
				'File'		=> 'Documentos',
				2			=> null,
				'Category'	=> 'Categorías',
				'Tag'		=> 'Tags',
				'TagType'	=> 'Grupos de tags',
				3			=> null,
				'CfType'	=> 'Tipos de campos personalizados',
			)
		);
	}

	/* Index */

	public function indexAction($params = null) {
		$post = $this->em->getRepository('Post')->findAll();
		$this->render("index.backend.html.twig", array(
			'post' => array(
				'total' => count($post)
			)
		));
	}

	/* Custom data methods for lists */

	public function getPostData() {
		return 'test';
	}

	/* Custom number of default results for lists */

	public function getTagTypeDefaultResults() {
		return 1;
	}

	/* Custom filters methods */

	public function getPostFilters($entity) {
		$filter = array(
			'starred' => array(
				'title' => 'relevancia',
				'values' => array('Sin destacar', 'Destacado'),
				'active' => $this->isActiveFilter('starred'),
			),
			'visible' => array(
				'title' => 'estado',
				'values' => array('Oculto', 'Visible'),
				'active' => $this->isActiveFilter('visible'),
			),
			'category' => array(
				'title' => 'categoría',
				'values' => array(),
				'active' => $this->isActiveFilter('category'),
			),
		);
		foreach ($entity as $e) {
			if ($e->getCategory()) {
				$filter['category']['values'][$e->getCategory()->getId()] = $e->getCategory()->getName();
			}
		}
		arsort($filter['category']['values']);
		return $filter;
	}

	public function getImageFilters($entity) {
		$filter = array(
			'post' => array(
				'title' => 'post',
				'values' => array(),
				'active' => $this->isActiveFilter('post'),
			),
		);
		foreach ($entity as $e) {
			if ($e->getPost()) {
				$filter['post']['values'][$e->getPost()->getId()] = $e->getPost()->getTitle();
			}
		}
		arsort($filter['post']['values']);
		return $filter;
	}

	public function getFileFilters($entity) {
		$filter = array(
			'post' => array(
				'title' => 'post',
				'values' => array(),
				'active' => $this->isActiveFilter('post'),
			),
		);
		foreach ($entity as $e) {
			if ($e->getPost()) {
				$filter['post']['values'][$e->getPost()->getId()] = $e->getPost()->getTitle();
			}
		}
		arsort($filter['post']['values']);
		return $filter;
	}

	public function getTagFilters($entity) {
		$filter = array(
			'tagType' => array(
				'title' => 'tipo',
				'values' => array(),
				'active' => $this->isActiveFilter('tagType'),
			),
		);
		foreach ($entity as $e) {
			if ($e->getTagType()) {
				$filter['tagType']['values'][$e->getTagType()->getId()] = $e->getTagType()->getName();
			}
		}
		arsort($filter['tagType']['values']);
		return $filter;
	}

	/* Custom delete methods */

	// ...

	/* Custom list order */

	public function getPostOrder() {
		return 'sort';
	}

	public function getImageOrder() {
		return 'sort';
	}

	public function getFileOrder() {
		return 'sort';
	}

	/* Custom validate methods */

	public function validateUser($entity) {
		$user = $this->em
			->getRepository('User')
			->createQueryBuilder('q')
			->where('q.username = :username')
			->setParameter('username', $entity->getUsername())
			->getQuery()
			->getOneOrNullResult();
		// if username exists, return error
		if ($user && $user->getId() !== $entity->getId()) {
			return 'El nombre de usuario ya existe';
		}
		return false;
	}

	/* Custom Search Methods */

	public function getPostSearch($entity) {
		$search = array(
			'title' => array(
				'title' => 'título',
				'value' => @$this->post['search']['title']
			),
			'subtitle' => array(
				'title' => 'subtítulo',
				'value' => @$this->post['search']['subtitle']
			),
		);
		return $search;
	}

	/* Custom new methods */

	public function newTagData($id = null) {
		return $this->em
			->getRepository('TagType')
			->createQueryBuilder('q')
			->orderBy('q.name', 'ASC')
			->getQuery()
			->getResult(2); // number 2 is for fetching an array instead of a motherfucker object
	}

	public function newImageData($id = null) {
		return $this->em
			->getRepository('Post')
			->createQueryBuilder('q')
			->orderBy('q.title', 'ASC')
			->getQuery()
			->getResult(2); // number 2 is for fetching an array instead of a motherfucker object
	}

	public function newFileData($id = null) {
		return $this->em
			->getRepository('Post')
			->createQueryBuilder('q')
			->orderBy('q.title', 'ASC')
			->getQuery()
			->getResult(2); // number 2 is for fetching an array instead of a motherfucker object
	}

	public function newPostData($id = null) {
		// categories
		$category = $this->em
			->getRepository('Category')
			->createQueryBuilder('q')
			->orderBy('q.name', 'ASC')
			->getQuery()
			->getResult(2);
		// tags
		$tag = $this->em
			->getRepository('Tag')
			->createQueryBuilder('q')
			->orderBy('q.name', 'ASC')
			->getQuery()
			->getResult();
		$tags = array();
		foreach ($tag as $t) {
			$i = $t->getTagType();
			$i = $i ? $i->getId() : 0;
			$tags[$i][] = $t->getName();
		}
		// custom Fields
		$cf = array();
		if ($id) {
			$cf = $this->em
				->getRepository('CustomField')
				->createQueryBuilder('q')
				->join('q.post', 'p')
				->where('p.id = :id')
				->setParameter('id', $id)
				->orderBy('q.id', 'ASC')
				->getQuery()
				->getResult();
		}
		foreach ($cf as $c) {
			$type = $c->getCfType() ? $c->getCfType()->getId() : 0;
			$ccf[$type][] = $c;
		}
		// images
		$img = array();
		if ($id) {
			$img = $this->em
				->getRepository('Image')
				->createQueryBuilder('q')
				->join('q.post', 'p')
				->where('p.id = :id')
				->setParameter('id', $id)
				->orderBy('q.src', 'ASC')
				->getQuery()
				->getResult(2);
		}
		// files
		$file = array();
		if ($id) {
			$file = $this->em
				->getRepository('File')
				->createQueryBuilder('q')
				->join('q.post', 'p')
				->where('p.id = :id')
				->setParameter('id', $id)
				->orderBy('q.src', 'ASC')
				->getQuery()
				->getResult(2);
		}
		// custom Fields Types
		$cftype = $this->em
			->getRepository('CfType')
			->createQueryBuilder('q')
			->orderBy('q.name', 'ASC')
			->getQuery()
			->getResult(2);
		$cftype[] = array('id' => 0);
		// always add an extra field for each type
		foreach ($cftype as $c) {
			$ccf[$c['id']][] = array(
				'title' => '',
				'value' => '',
				'attribute' => ''
			);
		}
		return array(
			'categories' => $category,
			'tags' => $tags,
			'cf' => $ccf,
			'cftype' => $cftype,
			'img' => $img,
			'file' => $file
		);
	}

	/* Custom set (create and update) methods */

	public function setUserAction($entity) {
		// don't set password if already is a md5 hash
		if (!preg_match('/^[a-f0-9]{32}$/', $entity->getPassword())) {
			$entity->setPassword(md5($entity->getPassword()));
		}
		return $entity;
	}

	public function setCategoryAction($entity) {
		$edit = $entity->getSlug() ? true : false;
		return $entity->setSlug($this->str2slug($entity->getName(), array('Category', $edit, $entity->getSlug())));
	}

	public function setTagAction($entity) {
		$edit = $entity->getSlug() ? true : false;
		return $entity->setSlug($this->str2slug($entity->getName(), array('Tag', $edit, $entity->getSlug())));
	}

	public function setPostAction($entity) {
		// creation Date
		if (!$entity->getCreationDate()) { // == if already exists
			$now = new DateTime();
			$entity->setCreationDate($now);
			$entity->setSort(time());
		}
		// slug
		$edit = $entity->getSlug() ? true : false;
		$slug = $this->str2slug($entity->getTitle(), array('Post', $edit, $entity->getSlug()));
		$entity->setSlug($slug);
		return $entity;
	}

	/* Custom image handlers */

	// ...
}