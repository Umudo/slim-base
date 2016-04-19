<?php
include 'base.php';

\App\Helper\Container::getJobQueue()->consume();
