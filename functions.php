<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;

function formatPrice($vlprice)
{
    if (!$vlprice > 0) {
        $vlprice = 0;
    }

    return number_format((float)$vlprice, 2, ",", ".");
}

function formatDate($date)
{
    return date('d/m/Y', strtotime($date));
}

function checkLogin($inadmin = true)
{
    return User::checkLogin($inadmin);
}

function getUserName()
{
    // Get the values from tb_users table
    $user = User::getFromSession();
    
    // Insert into the object the values from tb_persons correspondent to the
    // logged in user (in this case, the required one is the person's name)
    $user->get($user->getiduser());

    return $user->getdesperson();
}

function getCartNrQtd()
{
    $cart = Cart::getFromSession();

    $totals = $cart->getProductsTotals();

    return $totals['nrqtd'];
}

function getCartVlSubtotal()
{
    $cart = Cart::getFromSession();

    $totals = $cart->getProductsTotals();

    return formatPrice($totals['vlprice']);
}
