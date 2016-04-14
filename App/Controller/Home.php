<?php
/**
 * Created by PhpStorm.
 * User: umutcanguney
 * Date: 14/04/16
 * Time: 14:31
 */

namespace App\Controller;


use App\Base\Controller;

class Home extends Controller
{
    public function indexAction()
    {
        $this->response->getBody()->write("Hello World");
        return $this->response;
    }
}