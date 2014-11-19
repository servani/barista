<?php
$config = array(
	'DB' => array(
		'driver'	=> 'pdo_mysql',
		'user'		=> 'root',
		'password'	=> 'root',
		'dbname'	=> 'barista',
		'host'		=> 'localhost',
		'charset'	=> 'utf8'
	),
	'PATHS' => array(
		'upload'	=> __DIR__ . '/web/content/',
		'cache'		=> __DIR__ . '/cache/'
	),
	'SWIFTMAILER' => array(
		'transport'	=> 'sendmail', /* [sendmail, smtp, mail] */
		'sendmail'	=> '/usr/sbin/sendmail -bs',
		'smtp'		=> 'smtp.example.org',
		'smtp_port'	=> 25,
		'smtp_user'	=> '',
		'smtp_pass'	=> ''
	),
	'TWIG_GLOBALS' => array(
		'_FRAMEWORK' => 'Barista',
		'_VERSION' => '2.0.1',
		'_PROJECT' => 'Barista'
	),
);
/* Overrides */
$url = @$_SERVER['SERVER_NAME'];
if (strpos($url, 'local.') !== FALSE || !$url) {
	$config['DB']['user'] = 'root'; // local
} elseif (strpos($url, 'dev.') !== FALSE) {
	$config['DB']['user'] = 'root'; // dev
}