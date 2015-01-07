<?php
/**
 * Created by PhpStorm.
 * User: Neil
 * Date: 03/01/2015
 * Time: 15:01
 */

namespace App\Exception;


class ValidationException extends \Exception
{
    private $data = array();

    public function __construct($message, $code = 0, $data = array())
    {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}