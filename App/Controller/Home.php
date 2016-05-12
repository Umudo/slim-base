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
        $this->data = ['test' => 'View Render Test'];
        $this->renderView($this->response, 'test.php');
    }

    public function exampleStringAction()
    {
        return "Hello World";
    }

    public function exampleJSONAction()
    {
        return array("text" => "Hello World");
    }

    public function exampleResponseAction()
    {
        $this->response->getBody()->write("Response Test");
        return clone $this->response;
    }
}