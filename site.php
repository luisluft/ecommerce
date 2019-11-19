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

        $page->setTpl(
            "index",
            [
            'products'=>Product::checkList($products)
            ]
        ); // adds the index.html
    }
);

$app->get(
    "/categories/:idcategory",
    function ($idcategory) {
        $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

        $category = new Category();
        
        $category->get((int)$idcategory);

        $pagination = $category->getProductsPage($page);

        $pages = [];

        for ($i=1; $i <= $pagination['pages']; $i++) {
            array_push(
                $pages,
                [
                'link'=>'/categories/' . $category->getidcategory() . '?page=' . $i,
                'page'=>$i
                 ]
            );
        }

        $page = new Page();

        $page->setTpl(
            "category",
            [
            "category"=>$category->getValues(),
            "products"=>$pagination["data"],
            "pages"=>$pages
            ]
        );
    }
);

$app->get(
    "/products/:desurl",
    function ($desurl) {
        $product = new Product();

        $product->getFromURL($desurl);

        $page = new Page();

        $page->setTpl(
            "product-detail",
            [
                'product'=>$product->getValues(),
                'categories'=>$product->getCategories()
            ]
        );
    }
);
