<?php

// Add support for PHPunit 5.x:
class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
use PHPUnit\Framework\TestCase;

include_once("global_consts.php");
include_once("global_functions.php");
include_once("TestHelpers.php");
include_once(__DIR__."/../lib/TestHelpers.php");

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

  function test_global_password_hash() {
    global $MAX_PASSWORD_LENGTH;

    $th = new TestHelpers();

    try {
      $hashed = global_password_hash(null);
    }
    catch (Exception $e) {
      $this->assertEquals("global_password_hash() requires a string as a password", $e->getMessage());
    }

    $hashed = global_password_hash("password");
    $this->assertEquals("string", gettype($hashed), "Hashed string is: $hashed");

    // Should be able to hash an empty string
    $hashed = global_password_hash("");
    $this->assertEquals("string", gettype($hashed), "Hashed string: $hashed");

    // Should be able to hash passwords 1-$MAX_PASSWORD_LENGTH
    for ($n=0; $n < 10; $n++) {
      $password = $th->random_str(random_int(1,$MAX_PASSWORD_LENGTH), $th->RANDOM_KEYSPACE);
      $hashed = global_password_hash($password);
      $this->assertEquals("string", gettype($hashed), "Password: $password Hashed string: $hashed");
    }

    // Should be able to hash password which is max length
    $password = $th->random_str($MAX_PASSWORD_LENGTH, $th->RANDOM_KEYSPACE);
    $hashed = global_password_hash($password);
    $this->assertEquals("string", gettype($hashed), "Password: $password Hashed string: $hashed");

    // Should not be able to hash password one longer than max
    $password = $th->random_str($MAX_PASSWORD_LENGTH+1, $th->RANDOM_KEYSPACE);
    try {
      $hashed = global_password_hash($password);
    }
    catch (Exception $e) {
      $this->assertEquals("global_password_hash() got too long password", $e->getMessage());
    }

    // Should not be able to hash passwords longer than 72 chars
    for ($n=0; $n < 10; $n++) {
      $password = $th->random_str(random_int($MAX_PASSWORD_LENGTH+1,512), $th->RANDOM_KEYSPACE);
      try {
        $hashed = global_password_hash($password);
      }
      catch (Exception $e) {
        $this->assertEquals("global_password_hash() got too long password", $e->getMessage());
      }
    }
  }
}


?>
