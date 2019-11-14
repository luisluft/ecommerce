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

$app->get(
    '/admin/users',
    function () {
        User::verifyLogin();

        $users = User::listAll();

        $page = new PageAdmin();

        $page->setTpl("users", array(
            "users"=>$users
        ));
    }
);

$app->get(
    "/admin/users/create",
    function () {
        User::verifyLogin();
        $page = new PageAdmin();
        $page->setTpl("users-create");
    }
);

$app->post(
    "/admin/users/create",
    function () {
        User::verifyLogin();

        $user = new User();

        $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

        $user->setData($_POST);

        $user->save();

        header("Location: /admin/users");
        exit;
    }
);

// This route must be atop of the route  '.../:iduser' because SLIM will fail to understand it otherwise
$app->get(
    "/admin/users/:iduser/delete",
    function ($iduser) {
        User::verifyLogin();

        $user = new User();
        
        $user->get((int)$iduser);

        $user->delete();
        
        header("Location: /admin/users");

        exit;
    }
);

$app->get(
    '/admin/users/:iduser', // In this case the parameter binding is automatic
    function ($iduser) {
        User::verifyLogin();
        $user = new User();
        $user->get((int)$iduser);
        $page = new PageAdmin();
        $page->setTpl("users-update", array(
            "user"=>$user->getValues()
        ));
    }
);


$app->post(
    "/admin/users/:iduser",
    function ($iduser) {
        User::verifyLogin();
        
        $user = new User();
        
        $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

        $user->get((int)$iduser);
        
        $user->setData($_POST);
        
        $user->update();

        header("Location: /admin/users");
        
        exit;
    }
);

$app->run();
