<?php

use Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\Product;
use \Hcode\Model\Category;

// Redirects the site via GET at route '/'
$app->get(
    '/',
    function () {
        $products = Product::listAll();

        $page = new Page(); // adds the header and footer

        $page->setTpl("index", [
            'products'=>Product::checkList($products)
        ]); // adds the index.html
    }
);

$app->get(
    "/categories/:idcategory",
    function ($idcategory) {
        $category = new Category();
        
        $category->get((int)$idcategory);

        $page = new Page();

        $page->setTpl(
            "category",
            [
            "category"=>$category->getValues(),
            "products"=>Product::checkList($category->getProducts())
            ]
        );
    }
);
