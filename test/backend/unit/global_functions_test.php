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

  function test_get_my_url() {
    $this->assertEquals('http://somedomain.com/', get_my_url('http://somedomain.com/'));
    $this->assertEquals('https://somedomain.com:8080/', get_my_url('https://somedomain.com:8080/'));

    $this->assertEquals('http://somedomain.com/dir/', get_my_url('http://somedomain.com/dir/'));
    $this->assertEquals('https://somedomain.com:8080/dir/', get_my_url('https://somedomain.com:8080/dir/'));

    $this->assertEquals('http://somedomain.com/dir/', get_my_url('http://somedomain.com/dir/index.php'));
    $this->assertEquals('https://somedomain.com:8080/dir/', get_my_url('https://somedomain.com:8080/dir/index.php'));

    $this->assertEquals('http://somedomain.com/dir/', get_my_url('http://somedomain.com/dir/index.php?admin'));
    $this->assertEquals('https://somedomain.com:8080/dir/', get_my_url('https://somedomain.com:8080/dir/index.php?admin'));
  }
}


?>
