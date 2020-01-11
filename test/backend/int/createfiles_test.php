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
  private $path_settings = __DIR__."/../../../dist/settings.php";

  public function test_settings_gets_created() {
    // Make sure the settings.php gets created
    // The unit tests make sure the default file is created correctly

    unlink($this->path_settings);
    $this->assertFalse(is_file($this->path_settings));

    $browser = new TestBrowser();
    $page = $browser->http_get($this->server_url, Array(), null);

    $this->assertTrue(is_file($this->path_settings));
  }

  public function test_settings_create_fails() {
    // Now we want to make sure the server handles a situation where the
    // settings.php cannot be created

    unlink($this->path_settings);
    $this->assertFalse(is_file($this->path_settings));

    // Make the settins dir to unwriteable condition
    $path_settings_dir = dirname($this->path_settings);
    $original_perms = fileperms($path_settings_dir);
    $this->assertTrue(chmod($path_settings_dir, 0500));

    $browser = new TestBrowser();
    $page = $browser->http_get($this->server_url, Array(), "json");

    $this->assertTrue(chmod($path_settings_dir, $original_perms));

    $this->assertFalse($page['success']);
    $this->assertEquals("Failed to create new settings file", $page['message']);
  }
}
