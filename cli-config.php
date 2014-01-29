<?php
// to do optimize this file
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
// cl: php vendor/bin/doctrine orm:convert-mapping --from-database yml orm
// cl: php vendor/bin/doctrine orm:generate:entities src
$isDevMode = true;
$driver = new YamlDriver(array("./orm"));
$config = Setup::createAnnotationMetadataConfiguration(array("./src"), $isDevMode);
$config->setMetadataDriverImpl($driver);
$conn = array('driver' => 'pdo_mysql', 'user' => 'root', 'password' => 'root', 'dbname' => 'urquiza_motos', 'host' => 'localhost', 'charset' => 'utf8');
$em = EntityManager::create($conn, $config);
return ConsoleRunner::createHelperSet($em);