<?php

// Add support for PHPunit 5.x:
class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
use PHPUnit\Framework\TestCase;

include_once("global_functions.php");

class global_functions_test extends TestCase {
  function test_remove_trailing_slash() {

    // Unix style
    $this->assertEquals('data', remove_trailing_slash('data/'));
    $this->assertEquals('/data', remove_trailing_slash('/data//'));
    $this->assertEquals('/data', remove_trailing_slash('/data///'));
    $this->assertEquals('/data/data', remove_trailing_slash('/data/data///'));

    // Windows paths
    $this->assertEquals('data', remove_trailing_slash('data\\'));
    $this->assertEquals('\\data', remove_trailing_slash('\\data\\'));
    $this->assertEquals('\\data', remove_trailing_slash('\\data\\\\'));

    // Mixed
    $this->assertEquals('data', remove_trailing_slash('data/\\'));
    $this->assertEquals('data', remove_trailing_slash('data\\/'));
    $this->assertEquals('/data', remove_trailing_slash('/data/\\'));
    $this->assertEquals('\\data', remove_trailing_slash('\\data\\/'));
  }
}


?>
