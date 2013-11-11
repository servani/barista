<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;
$noecho = true;
require_once 'index.php';
return ConsoleRunner::createHelperSet($em);