<?php
include "./config.php";
$cache_dir = $config['PATHS']['cache'];
$upload_dir = $config['PATHS']['upload'];

// Create cache dir
@mkdir($cache_dir);
chmod($cache_dir, 0777);

// Create upload dir
@mkdir($upload_dir);
chmod($upload_dir, 0777);

// To do create .gitignore files with content: [^.]*
