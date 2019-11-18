<?php

use Hcode\Page;
use \Hcode\PageAdmin;

// Redirects the site via GET at route '/'
$app->get(
    '/',
    function () {
        $page = new Page(); // adds the header and footer
        $page->setTpl("index"); // adds the index.html
    }
);
