<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Product;

$app->get(
    '/admin/products',
    function () {
        User::verifyLogin();
        // String searched inside textbox
        $search = (isset($_GET['search'])) ? $_GET['search'] : "";
        
        // What page the table is
        $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

        // Empty vs inputted text for search
        if ($search != '') {
            $pagination = Product::getPageSearch($search, $page);
        } else {
            $pagination = Product::getPage($page);
        }

        $pages = [];

        for ($i=0; $i < $pagination['pages']; $i++) {
            $path = http_build_query(['page'=>$i+1, 'search'=>$search]);
            
            array_push($pages, ['href'=>'/admin/products?' . $path, 'text'=>$i+1]);
        }

        $page = new PageAdmin();

        $page->setTpl(
            "products",
            array(
                "products"=>$pagination['data'],
                "search"=>$search,
                "pages"=>$pages
            )
        );
    }
);

$app->get(
    '/admin/products/create',
    function () {
        User::verifyLogin();

        $page = new PageAdmin(); // adds the header and footer

        $page->setTpl("products-create");
    }
);

$app->post(
    '/admin/products/create',
    function () {
        User::verifyLogin();

        $product = new Product();

        $product->setData($_POST);
        
        $product->save();

        header("Location: /admin/products");

        exit;
    }
);

$app->get(
    '/admin/products/:idproduct',
    function ($idproduct) {
        User::verifyLogin();

        $product = new Product();
        
        $product->get((int)$idproduct);

        $page = new PageAdmin(); // adds the header and footer

        $page->setTpl("products-update", [
            'product'=>$product->getValues()
        ]);
    }
);

$app->post(
    '/admin/products/:idproduct',
    function ($idproduct) {
        User::verifyLogin();

        $product = new Product();
        
        $product->get((int)$idproduct);

        $product->setData($_POST);
        
        $product->save();

        $product->setPhoto($_FILES['file']);

        header('Location: /admin/products');

        exit;
    }
);

$app->get(
    '/admin/products/:idproduct/delete',
    function ($idproduct) {
        User::verifyLogin();

        $product = new Product();
        
        $product->get((int)$idproduct);

        $product->delete();

        header('Location: /admin/products');

        exit;
    }
);
