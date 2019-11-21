<?php

use \Hcode\Model\User;

function formatPrice($vlprice)
{
    return number_format((float)$vlprice, 2, ",", ".");
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
