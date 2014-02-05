<?php
$url = @$_SERVER['SERVER_NAME'];
// Prod Params
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
		'cache'	=> __DIR__ . '/cache/'
	),
	'SWIFTMAILER' => array(
		'transport'	=> 'sendmail', /* [sendmail, smtp, mail] */
		'sendmail'	=> '/usr/sbin/sendmail -bs',
		'smtp'		=> 'smtp.example.org',
		'smtp_port'	=> 25,
		'smtp_user'	=> 'username',
		'smtp_pass'	=> 'password'
	)
);
// Overwrite prod params for each instance
if (strpos($url, 'local.') !== FALSE || !$url) {
	// Local
	$config['DB']['user'] = 'root';
} elseif (strpos($url, 'dev.') !== FALSE) {
	// Dev
	$config['DB']['user'] = 'root';
}