<?php
define('JWT_SECRET', 'test-secret-key-that-is-very-long-and-strong-right-isnt-it');
define('JWT_EXPIRY', 3600);
define('JWT_REFRESH_EXPIRY', 604800);

require __DIR__ . '/../vendor/autoload.php';
