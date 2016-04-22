<?php
include 'base.php';

if (isset($argv[1]) && $argv[1] == "consume") {
    \App\Helper\Container::getJobQueue()->consume();
} else {
    \App\Helper\Container::getJobQueue()->decide();
}
