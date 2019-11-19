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

    /**
     * Return the logged in user or return a empty User object
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
        if (User::checkLogin($inadmin)) {
            header("Location: /admin/login");
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
            "SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser",
            array(":iduser"=>$iduser)
        );
        $data = $results[0];
        $this->setData($data);
    }

    public function save()
    {
        $sql = new Sql();
        
        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
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
            ":despassword"=>$this->getdespassword(),
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

    public static function getForgot($email)
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

                $link = "http://www.foguinho.com.br/admin/forgot/reset?code=$code";

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

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery =:idrecovery", array(
            ":idrecovery"=>$idrecovery
        ));
    }

    public function setPassword($password)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password"=>$password,
            ":iduser"=>$this->getiduser()
        ));
    }
}
