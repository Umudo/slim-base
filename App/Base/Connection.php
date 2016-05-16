<?php

namespace App\Base;


abstract class Connection
{
    abstract function getClient();
}