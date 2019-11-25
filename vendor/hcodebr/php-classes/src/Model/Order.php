<?php

// Tells php the location of this file
namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Cart;

class Order extends Model
{
    const SUCCESS = "Order-Success";
    const ERROR = "Order-Error";

    /**
     * Saves the values inside the PHP object to the database
     * via query
     */
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select(
            "CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)",
            [
                ':idorder'=>$this->getidorder(),
                ':idcart'=>$this->getidcart(),
                ':iduser'=>$this->getiduser(),
                ':idstatus'=>$this->getidstatus(),
                ':idaddress'=>$this->getidaddress(),
                ':vltotal'=>$this->getvltotal()
            ]
        );

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public function get($idorder)
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.idorder = :idorder",
            [
                ':idorder'=>$idorder
            ]
        );

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select(
            "SELECT * 
            FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            ORDER BY a.dtregister DESC"
        );
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query(
            "DELETE FROM tb_orders
            WHERE idorder = :idorder",
            [
                ':idorder'=>$this->getidorder()
            ]
        );
    }

    public function getCart():Cart
    {
        $cart = new Cart();

        $cart->get((int)$this->getidcart());

        return $cart;
    }

    public static function setSuccess($msg)
    {
        $_SESSION[Order::SUCCESS] = $msg;
    }

    public static function getSuccess()
    {
        $msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : "";

        Order::clearSuccess();
        
        return $msg;
    }

    public static function clearSuccess()
    {
        $_SESSION[Order::SUCCESS] = null;
    }

    public static function setError($msg)
    {
        $_SESSION[Order::ERROR] = $msg;
    }

    public static function getError()
    {
        $msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : "";

        Order::clearError();
        
        return $msg;
    }

    public static function clearError()
    {
        $_SESSION[Order::ERROR] = null;
    }

    
    /**
     * Query database for all users and limits the amount
     * of shown users per page displayed
     *
     * @param int $page [optional] page to start
     * @param int $itemsPerPage [optional] number of items displayed per page
     *
     * @return array
     * ['data'] for the query results,
     * ['total'] for the total amount of items in database,
     * ['pages'] for the total amount of pages displayed.
     */
    public static function getPage($page = 1, $itemsPerPage = 10)
    {
        $start = ($page - 1) * $itemsPerPage; // dynamic start of page
        
        $sql = new Sql();

        $results = $sql->select(
            "SELECT sql_calc_found_rows *
            FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            ORDER BY a.dtregister DESC
            LIMIT $start, $itemsPerPage"
        );

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal"); // quantity of items on db

        return [
            'data'=>$results,
            'total'=>(int)$resultTotal[0]["nrtotal"],
            'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
        ];
    }

    /**
     * Query database for all users related to the
     * searched string $search, and limits the amount
     * of shown users per page displayed
     *
     * @param string $search the string to search for in database
     * @param int $page [optional] page to start
     * @param int $itemsPerPage [optional] number of items displayed per page
     *
     * @return array
     * ['data'] for the query results,
     * ['total'] for the total amount of items in database,
     * ['pages'] for the total amount of pages displayed.
     */
    public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
    {
        $start = ($page - 1) * $itemsPerPage; // dynamic start of page
        
        $sql = new Sql();

        $results = $sql->select(
            "SELECT sql_calc_found_rows *
            FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.idorder = :id
            OR f.desperson LIKE :search
            ORDER BY a.dtregister DESC
            LIMIT $start, $itemsPerPage",
            [
                ':id'=>$search,
                ':search'=>'%' . $search . '%'
            ]
        );

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal"); // quantity of items on db

        return [
            'data'=>$results,
            'total'=>(int)$resultTotal[0]["nrtotal"],
            'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
        ];
    }
}
