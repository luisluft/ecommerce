<?php

// Tells php the location of this file
namespace Hcode\Model;

// Tells php where the requested class is located from the root folder
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Product extends Model
{
    public static function listAll()
    {
        $sql = new Sql();
        
        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
    }

    public static function checkList($list)
    {
        foreach ($list as &$row) {
            $p = new Product();

            $p->setData($row);

            $row = $p->getValues();
        }

        return $list;
    }

    public function save()
    {
        $sql = new Sql();
        
        $results = $sql->select(
            "CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)",
            array(
            ":idproduct"=>$this->getidproduct(),
            ":desproduct"=>$this->getdesproduct(),
            ":vlprice"=>$this->getvlprice(),
            ":vlwidth"=>$this->getvlwidth(),
            ":vlheight"=>$this->getvlheight(),
            ":vllength"=>$this->getvllength(),
            ":vlweight"=>$this->getvlweight(),
            ":desurl"=>$this->getdesurl()
             )
        );

        $this->setData($results[0]);
    }

    public function get($idproduct)
    {
        $sql = new Sql();
        
        $results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", array(
            ":idproduct"=>$idproduct
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
            ":idproduct"=>$this->getidproduct()
        ]);
    }


    public function checkPhoto()
    {
        $fileName = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
            "resources" . DIRECTORY_SEPARATOR .
            "site" . DIRECTORY_SEPARATOR .
            "img" . DIRECTORY_SEPARATOR .
            "products" . DIRECTORY_SEPARATOR .
            $this->getidproduct() . ".jpg";
        
        // This code is url so you do not need the 'directory separator' constant
        if (file_exists($fileName)) {
            $url = "/resources/site/img/products/" . $this->getidproduct() . ".jpg";
        } else {
            $url = "/resources/site/img/product.jpg"; // default picture when there is none
        }

        $this->setdesphoto($url);
    }

    public function getValues()
    {
        $this->checkPhoto();

        $values = parent::getValues();

        return $values;
    }

    public function setPhoto($file)
    {
        $extension = explode('.', $file['name']);

        $extension = end($extension);
    
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file['tmp_name']);
                break;
            
            case 'gif':
                $image = imagecreatefromgif($file['tmp_name']);
                break;
            
            case 'png':
                $image = imagecreatefrompng($file['tmp_name']);
                break;
        }

        $destination = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
        "resources" . DIRECTORY_SEPARATOR .
        "site" . DIRECTORY_SEPARATOR .
        "img" . DIRECTORY_SEPARATOR .
        "products" . DIRECTORY_SEPARATOR .
        $this->getidproduct() . ".jpg";

        imagejpeg($image, $destination);

        imagedestroy($image);

        $this->checkPhoto();
    }

    public function getFromURL($desurl)
    {
        $sql = new Sql();
        
        $rows = $sql->select(
            "SELECT * FROM tb_products
            WHERE desurl = :desurl
            LIMIT 1",
            [
                ':desurl'=>$desurl
            ]
        );

        $this->setData($rows[0]); // loads into the object the data
    }

    public function getCategories()
    {
        $sql = new Sql();
        
        return $sql->select(
            "SELECT * FROM tb_categories a 
            INNER JOIN tb_categoriesproducts b
            ON a.idcategory = b.idcategory
            WHERE b.idproduct = :idproduct",
            [
                ':idproduct'=>$this->getidproduct()
            ]
        );
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
            FROM tb_products 
            ORDER BY desproduct
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
            FROM tb_products 
            WHERE desproduct LIKE :search 
            ORDER BY desproduct
            LIMIT $start, $itemsPerPage",
            [
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
