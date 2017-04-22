<?php

namespace App\Base;

abstract class Connection
{
    abstract public function getClient();
}
