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

  public function test_get_page() {
    $th = new TestHelpers();
    $page_expected = $th->get_expected_index_html();

    $browser = new TestBrowser();
    $page_observed = $browser->http_get($this->server_url, Array(), null);

    $this->assertEquals($page_expected, $page_observed);
  }
}

?>
