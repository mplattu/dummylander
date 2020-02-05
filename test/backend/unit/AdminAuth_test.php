<?php

// Add support for PHPunit 5.x:
class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
use PHPUnit\Framework\TestCase;

include_once("global_functions.php");
include_once("global_consts.php");
include_once("AdminAuth.php");
include_once("Settings.php");
include_once(__DIR__."/../lib/TestHelpers.php");

class AdminAuth_test extends TestCase {
  function test_wrongparams () {
    try {
      $aa2 = new AdminAuth(1);
    }
    catch (Exception $e) {
      $this->assertEquals($e->getMessage(), "Parameter must be a filename to settings file");
    }

    try {
      $aa3 = new AdminAuth(Array());
    }
    catch (Exception $e) {
      $this->assertEquals($e->getMessage(), "Parameter must be a filename to settings file");
    }
  }

  function test_file () {
    global $MAX_PASSWORD_LENGTH;
    
    $th = new TestHelpers();

    $filename1 = $th->write_password_file('secret');
    $aa1 = new AdminAuth($filename1);
    $this->assertTrue($aa1->is_admin('secret'), "Failing password file: $filename1");
    unlink($filename1);

    $filename2 = $th->write_password_file('foobar');
    $aa2 = new AdminAuth($filename2);
    $this->assertTrue($aa2->is_admin('foobar'), "Failing password file: $filename2");
    $this->assertFalse($aa2->is_admin('baafor'), "Failing password file: $filename2");
    unlink($filename2);

    $filename3 = $th->write_password_file('badchars\'"');
    $aa3 = new AdminAuth($filename3);
    $this->assertTrue($aa3->is_admin('badchars\'"'), "Failing password file: $filename3");
    unlink($filename3);

    $filename = Array();
    $aa = Array();
    for ($n=0; $n < 25; $n++) {
      $this_pass = $th->random_str(rand(1, $MAX_PASSWORD_LENGTH), $th->RANDOM_KEYSPACE);
      $filename[$n] = $th->write_password_file($this_pass);
      $aa[$n] = new AdminAuth($filename[$n]);

      $this->assertTrue(
        $aa[$n]->is_admin($this_pass),
        "Failing password file: ".$filename[$n]
      );

      $wrong_pass = $th->random_str(rand(1, $MAX_PASSWORD_LENGTH), $th->RANDOM_KEYSPACE);
      $this->assertFalse(
        $aa[$n]->is_admin($wrong_pass),
        "Correct password: ".$this_pass." wrong password: ".$wrong_pass." file: ".$filename[$n]
      );

      unlink($filename[$n]);
    }
  }
}

?>
