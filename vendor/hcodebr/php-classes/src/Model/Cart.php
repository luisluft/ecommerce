<?php

// Tells php the location of this file
namespace Hcode\Model;

// Tells php where the requested class is located from the root folder
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model
{
    const SESSION = "Cart";

    public static function getFromSession()
    {
        $cart = new Cart();

        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
        } else {
            $cart->getFromSessionID();
            
            // Create new cart when none exists
            if (!(int)$cart->getidcart() > 0) {
                $data = [
                    'dessessionid'=>session_id()
                ];

                if (User::checkLogin(false)) {
                    $user = User::getFromSession();

                    $data['iduser'] = $user->getiduser();
                }

                $cart->setData($data);

                $cart->save();

                $cart->setToSession();
            }
        }
    }


    public function setToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }


    /**
     * Try to restore cart from the current logged in session
     */
    public function getFromSessionID()
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_carts
            WHERE dessessionid = :dessessionid",
            [
                ':dessessionid'=>session_id()
            ]
        );

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public function get(int $idcart)
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_carts
            WHERE idcart = :idcart",
            [
                ':idcart'=>$idcart
            ]
        );

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    /**
     * Save the current cart from the webpage to tb_carts
     *
     * @return empty
     */
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select(
            "CALL sp_carts_save(
                :idcart,
                :dessessionid,
                :iduser,
                :deszipcode,
                :vlfreight,
                :nrdays
                )",
            [
                ':idcart'=>$this->getidcart(),
                ':dessessionid'=>$this->getdessessionid(),
                ':iduser'=>$this->getiduser(),
                ':deszipcode'=>$this->getdeszipcode(),
                ':vlfreight'=>$this->getvlfreight(),
                ':nrdays'=>$this->getnrdays()
            ]
        );

        $this->setData($results[0]);
    }
}
