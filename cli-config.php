<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
include "./config.php";
$driver = new YamlDriver(array("./orm"));
$cconfig = Setup::createAnnotationMetadataConfiguration(array("./src"), true);
$cconfig->setMetadataDriverImpl($driver);
// The incredible Entity Manager
$em = EntityManager::create($config['DB'], $cconfig);
// Everything is ok?
try {
	$em->getConnection()->connect();
} catch (Exception $e) {
	echo "Connection error \n"; die();
}
return ConsoleRunner::createHelperSet($em);