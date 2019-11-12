<?php 

// Composer's autoload for dependencies
require_once("vendor/autoload.php");

// Namespaces inside the vendor folder
use Slim\Slim;
use Hcode\Page;
use Hcode\PageAdmin;

$app = new Slim();

$app->config('debug', true);

// Redirects the site via GET at route '/'
$app->get('/', function() {
	$page = new Page(); // adds the header and footer
	$page->setTpl("index"); // adds the index.html 
});

// Route direction for the admin page
$app->get('/admin', function() {
	$page = new PageAdmin(); // adds the header and footer
	$page->setTpl("index"); // adds the index.html 
});

$app->run();

 ?>