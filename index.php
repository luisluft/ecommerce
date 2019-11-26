<?php

// Composer's autoload for dependencies
require_once "vendor/autoload.php";

use Slim\Slim;

// Must use this line when webapp uses sessions
session_start();

$app = new Slim();

$app->config('debug', true);

// File creation for all routes
require_once "functions.php";
require_once "site.php";
require_once "admin.php";
require_once "admin-users.php";
require_once "admin-categories.php";
require_once "admin-products.php";
require_once "admin-orders.php";

$app->run();
