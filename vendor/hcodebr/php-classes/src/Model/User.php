<?php

// Tells php the location of this file
namespace Hcode\Model;

// Tells php where the requested class is located from the root folder
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model
{
    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret"; // This value must be kept secret so only the code creator will be able to decrypt the hashes
    const SECRET_IV = "HcodePhp7_Secret_IV"; // This value must be kept secret so only the code creator will be able to decrypt the hashes
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserRegisterError";
    const SUCCESS = "UserSuccess";

    /**
     * Return the logged in user or return a empty User object
     *
     * @return object User instance from the current session if there is one.
     */
    public static function getFromSession()
    {
        $user = new User();

        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION] > 0) {
            $user->setData($_SESSION[User::SESSION]);
        }
        return $user;
    }

    /**
     * Check if there is a logged in user and if it
     * has the necessary admin privileges to access the page requested
     *
     * @param bool $inadmin Whether the page accessed is for admin use only
     *
     * @return bool true if has access, false if not
     */
    public static function checkLogin($inadmin = true)
    {
        if (!isset($_SESSION[User::SESSION]) // Session does not exist
            || !$_SESSION[User::SESSION] // Session is empty
            || !(int)$_SESSION[User::SESSION]["iduser"] > 0 // iduser has invalid value
        ) {
            // User not logged in
            return false;
        } else {
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
                // Page requested is admin page and logged in user is also an admin
                return true;
            } elseif ($inadmin === false) {
                // Page requested is not admin page
                return true;
            } else {
                // User not logged in
                return false;
            }
        }
    }

    /**
     * Validate inputted username and password with database
     * and creates the user Session
     *
     * @param string $login username already created
     * @param string $password password correspondent to the $username
     *
     * @return object User() containing the session parameters or
     * in case of invalid pair throw an error message
     */
    public static function login($login, $password)
    {
        // Access database with the login inputted via form
        $sql = new Sql();
        $results = $sql->select(
            "SELECT * FROM tb_users a 
            INNER JOIN tb_persons b 
            ON a.idperson = b.idperson 
            WHERE a.deslogin = :LOGIN",
            array(
            ":LOGIN"=>$login
            )
        );
        
        // Invalid username response
        if (count($results) === 0) {
            throw new \Exception("Usuário ou senha inválidos", 1);
        }

        // Valid username response
        $data = $results[0];
        if (password_verify($password, $data["despassword"]) === true) {
            $user = new User();

            $data['desperson'] = ($data['desperson']);

            $user->setData($data);
            
            $_SESSION[User::SESSION] = $user->getValues();
            
            return $user;
        } else {
            throw new \Exception("Usuário ou senha inválidos", 1);
        }
    }

    /**
     * If logged in and has access to the specified privileges does nothing;
     * If logged out redirects the user to the login page;
     * If admin privileges are required redirects user to admin login page;
     *
     * @param boolean $inadmin true for admin page, false for user page.
     *
     * @return void
     */
    public static function verifyLogin($inadmin = true)
    {
        if (!User::checkLogin($inadmin)) {
            if ($inadmin) {
                header("Location: /admin/login");
            } else {
                header("Location: /login");
            }
            exit;
        }
    }


    public static function logout()
    {
        $_SESSION[User::SESSION] = null;
    }

    public static function listAll()
    {
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    public function get($iduser)
    {
        $sql = new Sql();
        
        $results = $sql->select(
            "SELECT * FROM tb_users a 
            INNER JOIN tb_persons b 
            USING(idperson) 
            WHERE a.iduser = :iduser",
            array(":iduser"=>$iduser)
        );
        
        $data = $results[0];

        $data['desperson'] = ($data['desperson']);
        
        $this->setData($data);
    }

    public function save()
    {
        $sql = new Sql();
        
        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>User::getPasswordHash($this->getdespassword()),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function update()
    {
        $sql = new Sql();
        
        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>User::getPasswordHash($this->getdespassword()),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();
        
        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$this->getiduser()
        ));
    }

    public static function getForgot($email, $inadmin = true)
    {
        $sql = new Sql();
        
        $results = $sql->select(
            "SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email",
            array(
            ":email"=>$email
            )
        );

        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.", 1);
        } else {
            $data = $results[0];
            
            $results2 = $sql->select(
                "CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",
                array(
                ":iduser"=>$data["iduser"],
                ":desip"=>$_SERVER["REMOTE_ADDR"]
                )
            );

            if (count($results2) === 0) {
                throw new Exception("Não foi possível recuperar a senha.", 1);
            } else {
                $dataRecovery = $results2[0];

                $code = openssl_encrypt(
                    $dataRecovery['idrecovery'],
                    'AES-128-CBC',
                    pack("a16", User::SECRET),
                    0,
                    pack("a16", User::SECRET_IV)
                );

                $code = base64_encode($code);

                if ($inadmin === true) {
                    $link = "http://www.foguinho.com.br/admin/forgot/reset?code=$code";
                } else {
                    $link = "http://www.foguinho.com.br/forgot/reset?code=$code";
                }

                $mailer = new Mailer(
                    $data["desemail"],
                    $data["desperson"],
                    "Redefinir senha da Foguinho Store",
                    "forgot",
                    array(
                    "name"=>$data["desperson"],
                    "link"=>$link
                    )
                );

                $mailer->send();

                return $link;
            }
        }
    }

    public static function validForgotDecrypt($code)
    {
        $decodedData = base64_decode($code); // Variable previously encoded inside $this->getForgot

        $idrecovery = openssl_decrypt(
            $decodedData,
            'AES-128-CBC',
            pack("a16", User::SECRET),
            0,
            pack("a16", User::SECRET_IV)
        );

        $sql = new Sql();

        $results = $sql->select(
            "SELECT * 
        FROM tb_userspasswordsrecoveries a 
        INNER JOIN tb_users b USING(iduser) 
        INNER JOIN tb_persons c USING(idperson) 
        WHERE a.idrecovery = :idrecovery 
        AND a.dtrecovery IS NULL
        AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()",
            array(
            ":idrecovery"=>$idrecovery
            )
        );

        if (count($results)===0) {
            throw new Exception("Não foi possível recuperar a senha", 1);
        } else {
            return $results[0];
        }
    }

    public static function setForgotUsed($idrecovery)
    {
        $sql = new Sql();

        $sql->query(
            "UPDATE tb_userspasswordsrecoveries 
            SET dtrecovery = NOW() 
            WHERE idrecovery =:idrecovery",
            array(
            ":idrecovery"=>$idrecovery
            )
        );
    }

    /**
     * Insert the password inside the object instance to
     * the database without hashing it.
     *
     * @param string $password password of the respective user.
     *
     * @return void
     */
    public function setPassword($password)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password"=>$password,
            ":iduser"=>$this->getiduser()
        ));
    }

    public static function setError($msg)
    {
        $_SESSION[User::ERROR] = $msg;
    }

    public static function getError()
    {
        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : "";

        User::clearError();
        
        return $msg;
    }

    public static function clearError()
    {
        $_SESSION[User::ERROR] = null;
    }

    /**
     * Hashes the password for later insertion in the database
     *
     * @param string $password password of the respective user
     *
     * @return void
     */
    public static function getPasswordHash($password)
    {
        return password_hash(
            $password,
            PASSWORD_DEFAULT,
            [
                'cost'=>12
            ]
        );
    }

    public static function setRegisterError($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }

    public static function clearRegisterError()
    {
        $_SESSION[User::ERROR_REGISTER] = null;
    }
    
    public static function getRegisterError()
    {
        $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

        User::clearRegisterError();

        return $msg;
    }

    /**
     * Query the database for all the data related to the
     * provided login information
     *
     * @param string $login existing login to the site
     *
     * @return boolean true if login exists
     * false if it does not
     */
    public static function checkLoginExist($login)
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_users
            WHERE deslogin = : deslogin",
            [
                ':deslogin'=>$login
            ]
        );

        return (count($results) > 0);
    }

    public static function setSuccess($msg)
    {
        $_SESSION[User::SUCCESS] = $msg;
    }

    public static function getSuccess()
    {
        $msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : "";

        User::clearSuccess();
        
        return $msg;
    }

    public static function clearSuccess()
    {
        $_SESSION[User::SUCCESS] = null;
    }

    public function getOrders()
    {
        $sql = new Sql();
        
        $results = $sql->select(
            "SELECT * FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.iduser = :iduser",
            [
                ':iduser'=>$this->getiduser()
            ]
        );

        return $results;
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
            FROM tb_users a 
            INNER JOIN tb_persons b 
            USING(idperson) 
            ORDER BY b.desperson
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
            FROM tb_users a 
            INNER JOIN tb_persons b 
            USING(idperson) 
            WHERE b.desperson LIKE :search 
            OR b.desemail LIKE :search 
            OR a.deslogin LIKE :search
            ORDER BY b.desperson
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
