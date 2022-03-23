<?php

namespace tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

echo getcwd();
chdir("core");
require '../vendor/autoload.php';
require './bootstrap.php';



abstract class TestCase extends BaseTestCase
{

}