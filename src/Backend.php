<?php

class BackendController extends DefaultController
{
	public function indexAction($params = null) {
		$this->render("index.backend.html.twig");
	}

	public function listAction($params = null) {
		try {
			// Query conditions
			$results_x_page = $this->getResultsPerPage(10);
			$current_offset = isset($this->get['p']) ? ($this->get['p'] - 1) * $results_x_page : 0;
			// Custom Order
			$cm = 'list' . $params['slug'] . 'Action';
			if (method_exists($this, $cm) && !isset($this->get['o'])) {
				$order = $this->$cm();
			} else {
				$order = isset($this->get['o']) ? $this->get['o'] : 'id';
			}
			$dir = isset($this->get['d']) && $this->get['d'] == 1 && $order !== 'sort' ? 'ASC' : 'DESC';
			// Basic Query
			$entity = $this->em
				->getRepository($params['slug'])
				->createQueryBuilder('q');
			// Where manager
			if (isset($this->get['w'])) {
				$entity = $this->whereManager($entity);
			}
			// Number of items
			$aux = $entity->getQuery()->getResult();
			$n = count($aux);
			// Available Filters (must be here before fucking the results)
			$filters = array();
			$cm = 'get' . $params['slug'] . 'Filters';
			if (method_exists($this, $cm)) {
				$filters = $this->$cm($aux);
			}
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
				'dir' => $dir === 'ASC' ? 1 : 0,
				'where' => @$this->get['w']
			),
			'filters' => $filters
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
		try {
			$entity = $this->em->getRepository($params['slug'])->find($params['id']);
		} catch (Exception $e) {
			echo "Entity or Entry not found \n"; die();
		}
		if (method_exists($this, $cm)) {
			$data = $this->$cm($entity->getId());
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
		$entity = $this->setFromPost($this->post[$params['slug']], $entity);

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
		$entity = $this->setFromPost($this->post[$params['slug']], $entity);
		$cm = 'set' . $params['slug'] . 'Action';
		if (method_exists($this, $cm)) {
			$entity = $this->$cm($entity);
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

	/* Custom Filters Methods */

	public function getImageFilters($entity) {
		$filters = array();
		$filters = array(
			'post' => array(
				'title' => 'Post',
				'values' => array(),
				'active' => $this->isActiveFilter('post')
			)
		);
		foreach ($entity as $e) {
			$filters['post']['values'][$e->getPost()->getId()] = $e->getPost()->getTitle();
		}
		return $filters;
	}

	/* Custom Delete Methods */

	/* Custom List Methods */

	public function listPostAction() {
		return 'sort';
	}

	public function listImageAction() {
		return 'sort';
	}

	public function listFileAction() {
		return 'sort';
	}

	/* Custom New Methods */

	public function newTagAction($id = null) {
		return $this->em
			->getRepository('TagType')
			->createQueryBuilder('q')
			->orderBy('q.name', 'ASC')
			->getQuery()
			->getResult(2); // Number 2 is for fetching an array instead of a motherfucker object
	}

	public function newImageAction($id = null) {
		return $this->em
			->getRepository('Post')
			->createQueryBuilder('q')
			->orderBy('q.title', 'ASC')
			->getQuery()
			->getResult(2); // Number 2 is for fetching an array instead of a motherfucker object
	}

	public function newPostAction($id = null) {
		// Categories
		$category = $this->em
			->getRepository('Category')
			->createQueryBuilder('q')
			->orderBy('q.name', 'ASC')
			->getQuery()
			->getResult(2);
		// Tags
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
		// Custom Fields
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
		// Images
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
		// Files
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
		// Custom Fields Types
		$cftype = $this->em
			->getRepository('CfType')
			->createQueryBuilder('q')
			->orderBy('q.name', 'ASC')
			->getQuery()
			->getResult(2);
		$cftype[] = array('id' => 0);
		// Always add an extra field for each type
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

	public function whereManager($entity) {
		$allowed = array('post', 'category');
		$wheres = explode(';', $this->get['w']);
		foreach ($wheres as $w) {
			$aux = explode(':', $w);
			$key = $aux[0];
			if (isset($aux[1])) {
				$value = $aux[1];
				if (in_array($key, $allowed)) {
					$entity->andWhere('q.' . $key . ' = :' . $key);
					$entity->setParameter($key, $value);
				}
			}
		}
		return $entity;
	}

	public function isActiveFilter($filter) {
		$filters = explode(';', @$this->get['w']);
		foreach ($filters as $f) {
			$aux = explode(':', $f);
			$key = $aux[0];
			if (isset($aux[1]) && $filter === $key) {
				return true;
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
		if ($image) {
			$validator = new FileUpload\Validator\Simple(1024 * 1024 * 2, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'));
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
			$res[] = $filename;
		}
		return $res;
	}

	public function isImage($filetype) {
		return in_array($filetype, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'));
	}

	// TO DO Esto no está andando bien, siempre le agrega un -1 por más que no exista el file (ver str2slug)
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