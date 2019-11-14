<?php

// Tells php the location of this file
namespace Hcode\Model;

// Tells php where the requested class is located from the root folder
use \Hcode\DB\Sql;
use \Hcode\Model;

class User extends Model
{
    const SESSION = "User";
    
    public static function login($login, $password)
    {
        // Access database with the login inputted via form
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN"=>$login
        ));
        
        // Invalid username response
        if (count($results) === 0) {
            throw new \Exception("Usuário ou senha inválidos", 1);
        }

        // Valid username response
        $data = $results[0];
        if (password_verify($password, $data["despassword"]) === true) {
            $user = new User();
            $user->setData($data);
            $_SESSION[User::SESSION] = $user->getValues();
            return $user;
        } else {
            throw new \Exception("Usuário ou senha inválidos", 1);
        }
    }

    public static function verifyLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION]) // Session does not exist
            ||
            !$_SESSION[User::SESSION] // Session is empty
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0 // iduser has invalid value
            ||
            (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin // Logged in user is not admin
        ) {
            header("Location: /admin/login");
            exit;
        }
    }

    public static function logout()
    {
        $_SESSION[User::SESSION] = null;
    }
}
