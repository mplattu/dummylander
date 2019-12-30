<?php

// Add support for PHPunit 5.x:
class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
use PHPUnit\Framework\TestCase;

$new_include_paths = Array(
  __DIR__."/../../../src/backend/lib/",
  __DIR__."/../../../src/backend/ext/",
  __DIR__."/../lib/"
);

set_include_path(get_include_path().PATH_SEPARATOR.join(PATH_SEPARATOR, $new_include_paths));

include_once("Parsedown.php");
include_once("global_consts.php");
include_once("global_functions.php");
include_once("TestHelpers.php");
include_once("AdminAPI.php");
include_once("ShowPage.php");
include_once("PageContent.php");

class AdminAPI_test extends TestCase {

  function test_render_default_page() {
    global $DATAPATH, $VERSION;

    $th = new TestHelpers();
    $expected_page = $th->get_expected_index_html();

    $show_page = new ShowPage($VERSION, remove_trailing_slash($DATAPATH));
    $rendered_page = $show_page->get_html_page();

    $expected_page_arr = explode("\n", $expected_page);
    $rendered_page_arr = explode("\n", $rendered_page);

    $this->assertEquals(count($expected_page_arr), count($rendered_page_arr), "Number of lines differ");
    $this->assertEquals($expected_page, $rendered_page);
  }
}

?>
