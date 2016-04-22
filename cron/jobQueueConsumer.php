<?php
include 'base.php';


if ($argv[1] == "consume")  {
    \App\Helper\Container::getJobQueue()->consume();
} else {
    \App\Helper\Container::getJobQueue()->decide();
}
