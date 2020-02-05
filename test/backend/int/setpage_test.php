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

  public function test_admin_set_data_page() {
    global $PAGE_PROPERTIES, $MAX_PASSWORD_LENGTH;

    $th = new TestHelpers();
    $this_pass = $th->random_str(random_int(1, $MAX_PASSWORD_LENGTH), $th->RANDOM_KEYSPACE);
    $filename = $th->write_password_file($this_pass, 'dist/settings.php');

    $browser = new TestBrowser();
    $data = $browser->http_post(
      $this->server_url,
      Array('password'=>$this_pass, 'function'=>'get'),
      "json"
    );
    $data_login = $data['data'];

    foreach ($PAGE_PROPERTIES as $test_property) {
      // Make sure the empty string clears the value and others remain the same
      $this->admin_set_data($data_login, $test_property, "");

      // Make sure a random string is stored and others remain the same
      $random_string = $th->random_str(random_int(1, $MAX_PASSWORD_LENGTH), $th->RANDOM_KEYSPACE);
      $this->admin_set_data($data_login, $test_property, $random_string);
    }
  }

  public function test_admin_set_data_section () {
    global $SECTION_PROPERTIES, $MAX_PASSWORD_LENGTH;

    $th = new TestHelpers();
    $this_pass = $th->random_str(random_int(1, $MAX_PASSWORD_LENGTH), $th->RANDOM_KEYSPACE);
    $filename = $th->write_password_file($this_pass, 'dist/settings.php');

    $browser = new TestBrowser();
    $data = $browser->http_post(
      $this->server_url,
      Array('password'=>$this_pass, 'function'=>'get'),
      "json"
    );
    $data_login = $data['data'];

    for ($part=0; $part < count($data_login['parts']); $part++) {
      foreach ($SECTION_PROPERTIES as $test_property) {
        // Make sure the empty string clears the value and others remain the same
        $this->admin_set_data($data_login, $test_property, "", $part);

        // Make sure a random string is stored and others remain the same
        $random_string = $th->random_str(random_int(1, $MAX_PASSWORD_LENGTH), $th->RANDOM_KEYSPACE);
        $this->admin_set_data($data_login, $test_property, $random_string, $part);
      }
    }
  }

  private function admin_set_data($og_page_data, $test_property, $test_value, $part=null) {
    global $PAGE_PROPERTIES, $SECTION_PROPERTIES, $MAX_PASSWORD_LENGTH;

    $cg_page_data = $og_page_data;

    if (is_null($part)) {
      // We're testing page values
      $cg_page_data['page_values'][$test_property] = $test_value;
    }
    else {
      $cg_page_data['parts'][$part][$test_property] = $test_value;
    }

    // Make a new random password
    $th = new TestHelpers();
    $password = $th->random_str(random_int(1, $MAX_PASSWORD_LENGTH), $th->RANDOM_KEYSPACE);
    $filename = $th->write_password_file($password, 'dist/settings.php');

    $browser = new TestBrowser();

    $json = json_encode($cg_page_data);
    $this->assertTrue(($json != false), "Could not encode changed array to JSON: ".print_r($cg_page_data, true));

    $data = $browser->http_post(
      $this->server_url,
      Array('password'=>$password, 'function'=>'set', 'data'=>$json),
      "json"
    );

    $this->assertTrue($data['success'], "Failed to set data (property: $test_property), response: ".$data);

    $data = $browser->http_post(
      $this->server_url,
      Array('password'=>$password, 'function'=>'get'),
      "json"
    );

    $this->assertTrue($data['success'], "Failed to get data (property: $test_property)");

    $data_changed = $data['data'];

    if (is_null($part)) {
      // We're testing page properties
      foreach ($PAGE_PROPERTIES as $this_property) {
        if ($this_property == $test_property) {
          $this->assertEquals(
            $test_value,
            $data_changed['page_values'][$this_property],
            "Current property: ".$this_property
          );
        }
        else {
          $this->assertEquals(
            $og_page_data['page_values'][$this_property],
            $data_changed['page_values'][$this_property],
            "Current property: ".$this_property
          );
        }
      }
    }
    else {
      // We're testing section properties
      for ($p=0; $p < count($og_page_data['parts']); $p++) {
        foreach ($SECTION_PROPERTIES as $this_property) {
          if (($part == $p) and ($this_property == $test_property)) {
            $this->assertEquals(
              $test_value,
              $data_changed['parts'][$p][$this_property],
              "Current part: $p property: ".$this_property
            );
          }
          else {
            $this->assertEquals(
              $og_page_data['parts'][$p][$this_property],
              $data_changed['parts'][$p][$this_property],
              "Current part: $p property: ".$this_property
            );
          }
        }
      }
    }
  }

}

?>
