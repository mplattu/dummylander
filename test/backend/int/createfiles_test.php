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
  private $path_datadir = __DIR__."/../../../dist/data/";

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

    $this->assertEquals("array", gettype($page), "Returned value: ".print_r($page, true));
    $this->assertArrayHasKey("success", $page, "Returned value: ".print_r($page, true));
    $this->assertFalse($page['success'], "Returned value: ".print_r($page, true));
    $this->assertEquals("Failed to create new settings file", $page['message'], "Returned value: ".print_r($page, true));
  }

  public function test_contentjson_gets_created() {
    // Make sure the data/content.json and all sample files get created

    // Clean and remove data/
    foreach (glob($this->path_datadir.'*') as $this_file) {
      $this->assertTrue(unlink($this_file), "Could not delete ".$this_file);
    }
    $this->assertTrue(rmdir($this->path_datadir), "Could not remove ".$this->path_datadir);

    $browser = new TestBrowser();
    $page = $browser->http_get($this->server_url, Array(), null);

    $this->assertTrue(is_dir($this->path_datadir), "The server has not created ".$this->path_datadir);

    $expected_files = Array('content.json', 'DuckDuckGo-DaxSolo.svg', 'favicon.ico', 'sample-document.pdf', 'space.jpg');
    $existing_files = Array();
    foreach (glob($this->path_datadir.'*') as $this_file) {
      array_push($existing_files, basename($this_file));
    }

    sort($expected_files);
    sort($existing_files);

    $this->assertEquals($expected_files, $existing_files, print_r($expected_files, true)."\n".print_r($existing_files, true));

    $this->assertEquals('af35fd3b421be82abef5361a91d0f766', md5_file($this->path_datadir.'content.json'));
    $this->assertEquals('5d8676c52e7f7652e0c9021269683f1b', md5_file($this->path_datadir.'DuckDuckGo-DaxSolo.svg'));
    $this->assertEquals('aeb105f05de6111e112b7744a1d59db2', md5_file($this->path_datadir.'favicon.ico'));
    $this->assertEquals('5cb6872b80a8c3594fd6c60465a8418f', md5_file($this->path_datadir.'sample-document.pdf'));
    $this->assertEquals('2685e32e97b74fcb21ded7698eaf4da9', md5_file($this->path_datadir.'space.jpg'));
  }

  public function test_contentjson_create_fails() {
    // Now we want to make sure the server handles a situation where the
    // data/ or its contents cannot be created

    // Clean and remove data/
    foreach (glob($this->path_datadir.'*') as $this_file) {
      $this->assertTrue(unlink($this_file), "Could not delete ".$this_file);
    }
    if (is_dir($this->path_datadir)) {
      $this->assertTrue(rmdir($this->path_datadir), "Could not remove ".$this->path_datadir);
    }

    // Make the data/ dir as set perms to read only
    $this->assertTrue(mkdir($this->path_datadir, 0500), "Could not create ".$this->path_datadir);

    $browser = new TestBrowser();
    $page = $browser->http_get($this->server_url, Array(), "json");

    // Remove readonly data/
    $this->assertTrue(chmod($this->path_datadir, 0700));
    $this->assertTrue(rmdir($this->path_datadir));

    $this->assertEquals("array", gettype($page), "Returned value: ".print_r($page, true));
    $this->assertArrayHasKey("success", $page, "Returned value: ".print_r($page, true));
    $this->assertFalse($page['success'], "Returned value: ".print_r($page, true));
    $this->assertEquals("Failed to create data directory", $page['message'], "Returned value: ".print_r($page, true));
  }
}
