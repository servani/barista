<?php

class BackendController extends DefaultController
{
	public function indexAction($params = null) {
		$this->render("index.backend.html.twig");
	}

	public function listAction($params = null) {
		try {
			// Query conditions
			$results_x_page = 3;
			$current_offset = isset($this->get['p']) ? ($this->get['p'] - 1) * $results_x_page : 0;
			$cm = 'list' . $params['slug'] . 'Action';
			if (method_exists($this, $cm) && !isset($this->get['o'])) {
				$order = $this->$cm();
			} else {
				$order = isset($this->get['o']) ? $this->get['o'] : 'id';
			}
			$dir = isset($this->get['d']) && $this->get['d'] == 1 ? 'ASC' : 'DESC';
			// Basic Query
			$entity = $this->em
				->getRepository($params['slug'])
				->createQueryBuilder('q');
			// Number of items
			$n = count($entity->getQuery()->getResult());
			// Query requested
			$entity = $entity->setMaxresults($results_x_page)
				->setFirstResult($current_offset)
				->orderBy('q.' . $order, $dir)
				->getQuery()
				->getResult();
		} catch (Exception $e) {
			echo "Entity not found \n"; die();
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
				'dir' => $dir === 'ASC' ? 1 : 0
			)
		));
	}

	public function newAction($params = null) {
		$cm = 'new' . $params['slug'] . 'Action';
		$data = false;
		if (method_exists($this, $cm)) {
			$data = $this->$cm();
		}
		$this->render($params['slug'] . ".form.html.twig", array(
			'entityName' => $params['slug'],
			'edit' => false,
			'data' => $data
		));
	}

	public function editAction($params = null) {
		$cm = 'new' . $params['slug'] . 'Action';
		$data = false;
		if (method_exists($this, $cm)) {
			$data = $this->$cm();
		}
		try {
			$entity = $this->em->getRepository($params['slug'])->find($params['id']);
		} catch (Exception $e) {
			echo "Entity or Entry not found \n"; die();
		}
		$this->render($params['slug'] . ".form.html.twig", array(
			'entity' => $entity,
			'entityName' => $params['slug'],
			'edit' => true,
			'data' => $data
		));
	}

	public function createAction($params = null) {
		$entity = new $params['slug'];
		$entity = $this->setFromPost($_POST[$params['slug']], $entity);
		if (isset($_FILES) && count($_FILES)) {
			$entity = $this->setFromFiles($_FILES, $entity, $params['slug']);
		}

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
	}

	public function updateAction($params = null) {
		$entity = $this->em->getRepository($params['slug'])->find($params['id']);
		$entity = $this->setFromPost($_POST[$params['slug']], $entity);
		$cm = 'set' . $params['slug'] . 'Action';
		if (method_exists($this, $cm)) {
			$entity = $this->$cm($entity);
		}

		if (isset($_FILES) && count($_FILES)) {
			$entity = $this->setFromFiles($_FILES, $entity, $params['slug']);
		}
		try {
			$this->em->persist($entity);
			$this->em->flush();
		} catch (Exception $e) {
			echo "Cannot persist entity to database. <br> Error: <pre>" . $e . "</pre>"; die();
		}
		$this->redirect("admin/list/" . $params['slug']);
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

	public function XHRtogglestate() {
		/*
		if (isset($_POST['en'], $_POST['id'])) {
			$entity = $this->em->getRepository($_POST['en'])->find($_POST['id']);
			$entity->setVisible(!$entity->getVisible());
			$this->em->persist($entity);
			$this->em->flush();
			return true;
		}
		return false;
		*/
	}

	public function XHRtogglestarred() {
		/*
		if (isset($_POST['en'], $_POST['id'])) {
			$entity = $this->em->getRepository($_POST['en'])->find($_POST['id']);
			$entity->setStarred(!$entity->getStarred());
			$this->em->persist($entity);
			$this->em->flush();
			return true;
		}
		return false;
		*/
	}

	public function XHRdeletefile() {
		/*
		if (isset($_POST['en'], $_POST['prop'], $_POST['id'])) {
			$entity = $this->em->getRepository($_POST['en'])->find($_POST['id']);
			$property = 'set' . $_POST['prop'];
			$entity->$property(null);
			$this->em->persist($entity);
			$this->em->flush();
			return true;
		}
		return false;
		*/
	}

	/* Custom Delete Methods */

	/* Custom List Methods */

	public function listPostAction() {
		return 'sort';
	}

	/* Custom New Methods */

	public function newTagAction() {
		return $this->em
			->getRepository('TagType')
			->createQueryBuilder('q')
			->orderBy('q.name', 'ASC')
			->getQuery()
			->getResult(2); // Number 2 is for fetching an array instead of a motherfucker object
	}

	public function newPostAction() {
		return $this->em
			->getRepository('Category')
			->createQueryBuilder('q')
			->orderBy('q.name', 'ASC')
			->getQuery()
			->getResult(2); // Number 2 is for fetching an array instead of a motherfucker object
	}

	/* Custom Set Methods */

	public function setUserAction($entity) {
		// Don't set password if already is a md5 hash
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
		// Creation Date
		if (!$entity->getCreationDate()) { // == if already exists
			$now = new DateTime();
			$entity->setCreationDate($now);
			$entity->setSort(time());
		}
		// Slug
		$edit = $entity->getSlug() ? true : false;
		$slug = $this->str2slug($entity->getTitle(), array('Post', $edit, $entity->getSlug()));
		$entity->setSlug($slug);
		return $entity;
	}

	/* Custom Image Handlers */

	/* Helpers */

	public function setFromPost($post, $entity) {
		foreach ($post as $key => $value) {
			if (is_array($value)) {
				$property = 'get' . $key;
				$e = $entity->$property();
				$property = 'remove' . $key;
				foreach ($e as $t) {
					$entity->$property($t);
				}
				foreach ($value as $v) {
					if (class_exists($key)) {
						$v = $this->em->getRepository($key)->find($v);
						$property = 'add' . $key;
						$entity->$property($v);
					}
				}
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
				} else if (strpos($property, 'setCreationDate') !== FALSE || strpos($property, 'setPublicDate') !== FALSE) {
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

	public function setFromFiles($files, $entity, $en) {
		$path = $this->getUploadDir() . $entity->getUploadDir();
		$pathresolver = new FileUpload\PathResolver\Simple($path);
		$filesystem = new FileUpload\FileSystem\Simple();
		$validator = new FileUpload\Validator\Simple(1024 * 1024 * 2, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'));
		foreach ($files as $key => $f) {
			if ($f['error']) { continue; }
			// Upload
			$fileupload = new FileUpload\FileUpload($f, $_SERVER);
			$fileupload->setPathResolver($pathresolver);
			$fileupload->setFileSystem($filesystem);
			$fileupload->addValidator($validator);
			$file = $fileupload->processAll();
			$file = $file[0][0]; // arr(error, type, name, size, path)
			if ($file->error) { continue; }
			// Rename + chmod
			$filename = $this->rename($file->path, $path);
			// Handle Image
			if ($this->isImage($file->type)) {
				$this->handleImage($path, $filename, $key, $en);
			}
			// Persist
			$property = 'set' . $key;
			$entity->$property($filename);
		}
		return $entity;
	}

	public function isImage($filetype) {
		return in_array($filetype, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'));
	}

	public function rename($filename) {
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


}