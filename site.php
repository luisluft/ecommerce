<?php

use Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\Product;

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
