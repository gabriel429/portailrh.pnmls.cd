<?php

/**
 * Redirect to public/index.php for shared hosting (Hostinger)
 */

$publicPath = __DIR__.'/public';

// Change working directory to public
chdir($publicPath);

// Load the front controller
require_once $publicPath.'/index.php';
