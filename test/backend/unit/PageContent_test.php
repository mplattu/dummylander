<?php

// Add support for PHPunit 5.x:
class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
use PHPUnit\Framework\TestCase;

include_once("global_functions.php");
include_once("PageContent.php");

class PageContent_test extends TestCase {

  function test_add_datapath_prefix_images() {
    $pc = new PageContent("data/content.json", "data");

    $this->assertEquals('https://example.com/data/some.file', $pc->add_datapath_prefix('page', 'favicon-ico', 'https://example.com/data/some.file'));
    $this->assertEquals('data/some.file', $pc->add_datapath_prefix('page', 'favicon-ico', 'data/some.file'));
    $this->assertEquals('/data/some.file', $pc->add_datapath_prefix('page', 'favicon-ico', '/data/some.file'));
    $this->assertEquals('data/some.file', $pc->add_datapath_prefix('page', 'favicon-ico', 'some.file'));

    $this->assertEquals('https://example.com/data/some.file', $pc->add_datapath_prefix('page', 'title', 'https://example.com/data/some.file'));
    $this->assertEquals('data/some.file', $pc->add_datapath_prefix('page', 'title', 'data/some.file'));
    $this->assertEquals('/data/some.file', $pc->add_datapath_prefix('page', 'title', '/data/some.file'));
    $this->assertEquals('some.file', $pc->add_datapath_prefix('page', 'title', 'some.file'));

    $this->assertEquals('https://example.com/data/some.file', $pc->add_datapath_prefix('part', 'background-image', 'https://example.com/data/some.file'));
    $this->assertEquals('data/some.file', $pc->add_datapath_prefix('part', 'background-image', 'data/some.file'));
    $this->assertEquals('/data/some.file', $pc->add_datapath_prefix('part', 'background-image', '/data/some.file'));
    $this->assertEquals('data/some.file', $pc->add_datapath_prefix('part', 'background-image', 'some.file'));

    $this->assertEquals('https://example.com/data/some.file', $pc->add_datapath_prefix('part', 'color', 'https://example.com/data/some.file'));
    $this->assertEquals('data/some.file', $pc->add_datapath_prefix('part', 'color', 'data/some.file'));
    $this->assertEquals('/data/some.file', $pc->add_datapath_prefix('part', 'color', '/data/some.file'));
    $this->assertEquals('some.file', $pc->add_datapath_prefix('part', 'color', 'some.file'));

    $this->assertEquals(
      'Markdown image with ![alt text!](https://example.com/data/some.file) and text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown image with ![alt text!](https://example.com/data/some.file) and text')
    );
    $this->assertEquals(
      'Markdown image with ![alt text!](data/some.file) and text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown image with ![alt text!](data/some.file) and text')
    );
    $this->assertEquals(
      'Markdown image with ![alt text!](/data/some.file) and text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown image with ![alt text!](/data/some.file) and text')
    );
    $this->assertEquals(
      'Markdown image with ![alt text!](data/some.file) and text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown image with ![alt text!](some.file) and text')
    );

    $this->assertEquals(
      'Markdown `image with ![alt text!](some.file) and` text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown `image with ![alt text!](some.file) and` text')
    );

    $this->assertEquals(
      'Markdown image with ![alt text!](data/some.file) and text. Markdown image with ![alt text!](data/some.file) and text.',
      $pc->add_datapath_prefix('part', 'text', 'Markdown image with ![alt text!](some.file) and text. Markdown image with ![alt text!](some.file) and text.')
    );

    $this->assertEquals(
     '```\nMarkdown image with ![alt text!](some.file) and text.\n```',
     $pc->add_datapath_prefix('part', 'text', '```\nMarkdown image with ![alt text!](some.file) and text.\n```')
    );

    // This is a known bug. The add_datapath_prefix_text() does not handle cases where there are pictures outside and inside backticks
    // $this->assertEquals(
    //  '![alt text!](data/some.file)\n```\nMarkdown image with ![alt text!](some.file) and text.\n```',
    //  $pc->add_datapath_prefix('part', 'text', '![alt text!](some.file)\n```\nMarkdown image with ![alt text!](some.file) and text.\n```')
    // );
    //
    // $this->assertEquals(
    //  '![alt text!](data/some.file) `Markdown image with ![alt text!](some.file) and text.`',
    //  $pc->add_datapath_prefix('part', 'text', '![alt text!](some.file) `Markdown image with ![alt text!](some.file) and text.`')
    // );
  }

  function test_add_datapath_prefix_link() {
    $pc = new PageContent("data/content.json", "data");

    // These should not change
    $this->assertEquals(
      'Markdown text with [link](https://example.com/data/some.file) and text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown text with [link](https://example.com/data/some.file) and text')
    );
    $this->assertEquals(
      'Markdown text with [link](data/some.file) and text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown text with [link](data/some.file) and text')
    );
    $this->assertEquals(
      'Markdown text with [link](/data/some.file) and text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown text with [link](/data/some.file) and text')
    );
    $this->assertEquals(
      'Markdown text with [link](data/some.file) and text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown text with [link](some.file) and text')
    );

    // This should change
    $this->assertEquals(
      'Markdown text with [link](data/some.file) and text',
      $pc->add_datapath_prefix('part', 'text', 'Markdown text with [link](some.file) and text')
    );
  }
}

?>
