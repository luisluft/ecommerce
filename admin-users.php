<?php

use Hcode\PageAdmin;
use Hcode\Model\User;

$app->get(
    '/admin/users',
    function () {
        User::verifyLogin();

        // String searched inside textbox
        $search = (isset($_GET['search'])) ? $_GET['search'] : "";
        
        // What page the table is
        $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

        // Stores
        if ($search != '') {
            $pagination = User::getPageSearch($search, $page);
        } else {
            $pagination = User::getPage($page);
        }

        $pages = [];

        for ($i=0; $i < $pagination['pages']; $i++) {
            $path = http_build_query(['page'=>$i+1, 'search'=>$search]);
            
            array_push($pages, ['href'=>'/admin/users?' . $path, 'text'=>$i+1]);
        }

        $page = new PageAdmin();

        $page->setTpl(
            "users",
            array(
                "users"=>$pagination['data'],
                "search"=>$search,
                "pages"=>$pages
            )
        );
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
        $page->setTpl(
            "users-update",
            array("user"=>$user->getValues())
        );
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
