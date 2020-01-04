<?php

// Add support for PHPunit 5.x:
class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
use PHPUnit\Framework\TestCase;

include_once(__DIR__."/../../../src/backend/lib/global_functions.php");
include_once(__DIR__."/../../../src/backend/lib/global_consts.php");
include_once(__DIR__."/../lib/TestHelpers.php");
include_once(__DIR__."/../lib/TestBrowser.php");

class integration_test extends TestCase {
  private $server_url = 'http://localhost:8080/';

  public function test_admin_login() {
    $th = new TestHelpers();
    $this_pass = $th->random_str(rand(1, 128), $th->RANDOM_KEYSPACE);
    $filename = $th->write_password_file($this_pass, 'dist/settings.php');

    $this->assertEquals("dist/settings.php", $filename);

    $browser = new TestBrowser();
    $data_observed1 = $browser->http_post(
      $this->server_url,
      Array('password'=>$this_pass, 'function'=>'get'),
      "json"
    );

    $this->assertTrue($data_observed1['success'], "Could not log in with correct password: ".$this_pass);

    $this_pass = $this_pass."x";

    $data_observed2 = $browser->http_post(
      $this->server_url,
      Array('password'=>$this_pass.'x', 'function'=>'get'),
      "json"
    );

    $this->assertFalse($data_observed2['success'], "Was able to log in with incorrect password: ".$this_pass);
  }

  public function test_admin_login_notset() {
    $th = new TestHelpers();
    $filename = $th->write_password_file("", 'dist/settings.php');

    $browser = new TestBrowser();
    $data_observed = $browser->http_post(
      $this->server_url,
      Array('password'=>'whateverpassword', 'function'=>'get'),
      "json"
    );

    $this->assertFalse($data_observed['success'], "Was able to log in although no password was set");
    $this->assertEquals("Password in settings.php has not been set", $data_observed['message']);
  }

  public function test_admin_login_randompass() {
    // Make sure we can set & login with whatever random password

    $th = new TestHelpers();

    for ($n=0; $n < 100; $n++) {
      $pass_correct = $th->random_str(rand(1, 128), $th->RANDOM_KEYSPACE);
      $pass_incorrect =  $th->random_str(rand(1, 128), $th->RANDOM_KEYSPACE);

      $this->assertTrue(($pass_correct != $pass_incorrect), "Random passwords are the same. Re-run tests.");

      $filename = $th->write_password_file($pass_correct, 'dist/settings.php');

      $browser = new TestBrowser();
      $data = $browser->http_post(
        $this->server_url,
        Array('password'=>$pass_correct, 'function'=>'get'),
        "json"
      );

      $this->assertTrue($data['success'], "Could not log in with correct password: ".$pass_correct);

      $data = $browser->http_post(
        $this->server_url,
        Array('password'=>$pass_false, 'function'=>'get'),
        "json"
      );

      $this->assertNull($data['success'], "Could log in with incorrect password: ".$pass_incorrect);
    }
  }
}

?>
