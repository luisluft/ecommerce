<?php
// Composer's autoload for dependencies
require_once "vendor/autoload.php";

// Must use this line when webapp uses sessions
session_start();

// Namespaces inside the vendor folder
use Slim\Slim;
use Hcode\Page;
use Hcode\PageAdmin;
use Hcode\Model\User;

$app = new Slim();

$app->config('debug', true);

// Redirects the site via GET at route '/'
$app->get(
    '/',
    function () {
        $page = new Page(); // adds the header and footer
        $page->setTpl("index"); // adds the index.html
    }
);

// Route direction for the admin page
$app->get(
    '/admin',
    function () {
        User::verifyLogin();
        $page = new PageAdmin(); // adds the header and footer
        $page->setTpl("index"); // adds the index.html
    }
);

$app->get(
    '/admin/login',
    function () {
        $page = new PageAdmin(
            [
            "header" => false,
            "footer" => false
            ]
        );
        $page->setTpl("login");
    }
);

$app->post(
    '/admin/login',
    function () {
        User::login($_POST["login"], $_POST["password"]);
        header("Location: /admin");
        exit;
    }
);

$app->get(
    '/admin/logout',
    function () {
        User::logout();
        header("Location: /admin/login");
        exit;
    }
);

$app->run();
