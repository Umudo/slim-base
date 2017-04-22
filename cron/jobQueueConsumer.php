<?php
include 'base.php';

if (isset($argv[1]) && $argv[1] == 'consume') {
    \App\Helper\Container::getJobQueue()->consume();
} else {
    $run_until = time() + 59;
    do {
        \App\Helper\Container::getJobQueue()->decide();
        sleep(1);
    } while (time() < $run_until);
}
