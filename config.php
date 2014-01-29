<?php
// to do create config.dev.php
$config = array(
	'DB' => array(
		'driver'	=> 'pdo_mysql',
		'user'		=> 'root',
		'password'	=> 'root',
		'dbname'	=> 'framework',
		'host'		=> 'localhost',
		'charset'	=> 'utf8'
	),
	'PATHS' => array(
		'upload'	=> __DIR__ . '/web/content/'
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