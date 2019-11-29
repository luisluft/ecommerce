<?php

// Tells php the location of this file
namespace Hcode\Model;

// Tells php where the requested class is located from the root folder
use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model
{
    const SESSION_ERROR = "AddressError";

    public static function getCEP($nrcep)
    {
        // Make sure CEP is only numbers. (e.g. 013101-00 -> 01310100)
        $nrcep = str_replace('-', '', $nrcep);

        // Initialize tracking of URL
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$nrcep/json/");

        // Waits for return of function
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Does not require SSL authentication
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // True returns array and not object
        $data = json_decode(curl_exec($ch), true);

        // Ends the tracking of URL
        curl_close($ch);

        return $data;
    }

    public function loadfromCEP($nrcep)
    {
        $data = Address::getCEP($nrcep);
        
        if (isset($data['logradouro']) && $data['logradouro']) {
            $this->setdesaddress($data['logradouro']);
            $this->setdescomplement($data['complemento']);
            $this->setdesdistrict($data['bairro']);
            $this->setdescity($data['localidade']);
            $this->setdesstate($data['uf']);
            $this->setdescountry('Brasil');
            $this->setdeszipcode($nrcep);
        }
    }

    /**
     * Save the values inside the object instance
     * into the database
     */
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select(
            "CALL sp_addresses_save(
                :idaddress, :idperson, :desaddress, :desnumber, :descomplement,
                :descity, :desstate, :descountry, :deszipcode, :desdistrict)",
            [
                ':idaddress'=>$this->getidaddress(),
                ':idperson'=>$this->getidperson(),
                ':desaddress'=>$this->getdesaddress(),
                ':desnumber'=>$this->getdesnumber(),
                ':descomplement'=>$this->getdescomplement(),
                ':descity'=>$this->getdescity(),
                ':desstate'=>$this->getdesstate(),
                ':descountry'=>$this->getdescountry(),
                ':deszipcode'=>$this->getdeszipcode(),
                ':desdistrict'=>$this->getdesdistrict()
            ]
        );

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public static function setMsgError($msg)
    {
        $_SESSION[Address::SESSION_ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = (isset($_SESSION[Address::SESSION_ERROR]))? $_SESSION[Address::SESSION_ERROR] : "";

        Address::clearMsgError();
        
        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[Address::SESSION_ERROR] = null;
    }
}
