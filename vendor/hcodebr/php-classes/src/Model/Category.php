<?php

// Tells php the location of this file
namespace Hcode\Model;

// Tells php where the requested class is located from the root folder
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Category extends Model
{
    public static function listAll()
    {
        $sql = new Sql();
        
        return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
    }

    public function save()
    {
        $sql = new Sql();
        
        $results = $sql->select(
            "CALL sp_categories_save(:idcategory, :descategory)",
            array(
            ":idcategory"=>$this->getidcategory(),
            ":descategory"=>$this->getdescategory()
             )
        );

        $this->setData($results[0]);
        Category::updateFile();
    }

    public function get($idcategory)
    {
        $sql = new Sql();
        
        $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", array(
            ":idcategory"=>$idcategory
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
            ":idcategory"=>$this->getidcategory()
        ]);

        Category::updateFile();
    }

    public static function updateFile()
    {
        $categories = Category::listAll();

        $html = [];
        
        foreach ($categories as $row) {
            array_push($html, '<li><a href="/categories/' .$row['idcategory'] . '">' . $row['descategory'] . '</a></li>');
        }
        
        $filename = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html";

        // Converts array into string data
        $data = implode('', $html);

        file_put_contents($filename, $data);
    }

    public function getProducts($related = true)
    {
        $sql = new Sql();
        
        if ($related === true) {
            return $sql->select(
                "SELECT * FROM tb_products WHERE idproduct IN(
                    SELECT a.idproduct 
                    FROM tb_products a 
                    INNER JOIN tb_categoriesproducts b ON a.idproduct = b.idproduct
                    WHERE b.idcategory = :idcategory
                    )",
                [
                        ":idcategory"=>$this->getidcategory()
                    ]
            );
        } else {
            return $sql->select(
                "SELECT * FROM tb_products WHERE idproduct NOT IN(
                    SELECT a.idproduct 
                    FROM tb_products a 
                    INNER JOIN tb_categoriesproducts b ON a.idproduct = b.idproduct
                    WHERE b.idcategory = :idcategory
                    )",
                [
                        ":idcategory"=>$this->getidcategory()
                    ]
            );
        }
    }

    public function getProductsPage($page = 1, $itemsPerPage = 8)
    {
        $start = ($page - 1) * $itemsPerPage; // dynamic start of page
        
        $sql = new Sql();

        $results = $sql->select(
            "SELECT sql_calc_found_rows *
            FROM tb_products a
            INNER JOIN tb_categoriesproducts b ON a.idproduct = b.idproduct
            INNER JOIN tb_categories c ON c.idcategory = b.idcategory
            WHERE c.idcategory = :idcategory
            LIMIT $start, $itemsPerPage",
            [
                ':idcategory'=>$this->getidcategory()
            ]
        );

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal"); // quantity of items on db

        return [
            'data'=>Product::checkList($results),
            'total'=>(int)$resultTotal[0]["nrtotal"],
            'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
        ];
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query(
            "INSERT INTO tb_categoriesproducts(idcategory, idproduct)
             VALUES(:idcategory, :idproduct)",
            [
            'idcategory'=>$this->getidcategory(),
            'idproduct'=>$product->getidproduct()
            ]
        );
    }

    public function removeProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query(
            "DELETE FROM tb_categoriesproducts
             WHERE idcategory = :idcategory
             AND idproduct = :idproduct",
            [
            'idcategory'=>$this->getidcategory(),
            'idproduct'=>$product->getidproduct()
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
            FROM tb_categories 
            ORDER BY descategory
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
            FROM tb_categories 
            WHERE descategory LIKE :search 
            ORDER BY descategory
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
