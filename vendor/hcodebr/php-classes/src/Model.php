<?php

// Located at the root directory which was defined at the 'composer.json' file
namespace Hcode;

class Model
{
    private $values = [];

    /**
    * Dynamically calls every possible 'setter' and 'getter'
    */
    public function __call($name, $args)
    {
        $method = substr($name, 0, 3);
        $fieldName = substr($name, 3, strlen($name));

        switch ($method) {
            case 'get':
                return (isset($this->values[$fieldName]))? $this->values[$fieldName] : null;
                break;

            case 'set':
                $this->values[$fieldName] = $args[0];
                break;
        }
    }

    /**
     * Sets all the variables inside an array into the object
     */
    public function setData($data = array())
    {
        foreach ($data as $key => $value) {
            $this->{"set" . $key}($value);
        }
    }

    /**
     * Returns all the values inside of the current object
     * 
     * @return $this->values
     */
    public function getValues()
    {
        return $this->values;
    }
}
