<?php

class BackendController extends DefaultController
{
	public function indexAction($params = null) {
		$this->render("index.backend.html.twig");
	}

	public function listAction($params = null) {
		$cm = 'list' . $params['slug'] . 'Action';
		$data = false;
		if (method_exists($this, $cm)) {
			$entity = $this->$cm();
		} else {
			$entity = $this->em->getRepository($params['slug'])->findAll();
		}
		$this->render($params['slug'] . ".list.html.twig", array(
			'entity' => $entity,
			'entityName' => $params['slug']
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
		$entity = $this->em->getRepository($params['slug'])->find($params['id']);
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

		$this->em->persist($entity);
		$this->em->flush();
		$this->redirect("admin/list/" . $params['slug']);
	}

	public function updateAction($params = null) {
		$entity = $this->em->getRepository($params['slug'])->find($params['id']);
		$entity = $this->setFromPost($_POST[$params['slug']], $entity);
		if (isset($_FILES) && count($_FILES)) {
			$entity = $this->setFromFiles($_FILES, $entity, $params['slug']);
		}

		$cm = 'set' . $params['slug'] . 'Action';
		if (method_exists($this, $cm)) {
			$entity = $this->$cm($entity);
		}

		$this->em->persist($entity);
		$this->em->flush();
		$this->redirect("admin/list/" . $params['slug']);
	}

	public function deleteAction($params = null) {
		$entity = $this->em->getRepository($params['slug'])->find($params['id']);
		$this->em->remove($entity);
		$this->em->flush();
		$this->redirect("admin/list/" . $params['slug']);
	}

	public function deleteFileAction($params = null) {
		if (isset($_POST['en'], $_POST['prop'], $_POST['id'])) {
			$entity = $this->em->getRepository($_POST['en'])->find($_POST['id']);
			$property = 'set' . $_POST['prop'];
			$entity->$property(null);
			$this->em->persist($entity);
			$this->em->flush();
			return true;
		}
		return false;
	}

	/* Custom List Methods */

	public function listUserAction() {}

	/* Custom New Methods */

	public function newUserAction() {}

	/* Custom Set Methods */

	public function setUserAction($entity) {}

	/* Custom Image Handlers */

	public function handleUserImage($path, $file) { /* User must have image field */ }

	/* Helpers */

	public function setFromPost($post, $entity) {
		foreach ($post as $key => $value) {
			$property = 'set' . $key;
			if (strpos($property, 'setId') !== FALSE) {
				$r = str_replace('setId', '', $property);
				if (class_exists($r)) {
					$value = $this->em->getRepository($r)->find($value);
				}
			} else if (strpos($property, 'setDate') !== FALSE) {
				try {
					$value = new DateTime($value);
				} catch(Exception $e) {
					$value = new DateTime();
				}
			}
			$entity->$property($value);
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