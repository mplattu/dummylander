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

  public function test_myself() {
    $filename = $this->get_filename();
    $this->assertTrue(is_file($filename));
    unlink($filename);

    $this->assertEquals('dist/data/some.file', $this->get_filename_final('/tmp/some.file'));
  }

  public function test_upload_zerolength() {
    $th = new TestHelpers();

    for ($n=0; $n < 5; $n++) {
      $filename = $this->get_filename();
      $status = $th->write_random_file($filename, 0);

      $filename_final = $this->get_filename_final($filename);

      $this->file_upload($filename, $filename_final);

      unlink($filename);
      unlink($filename_final);
    }
  }

  public function test_upload_small() {
    $th = new TestHelpers();

    for ($n=0; $n < 5; $n++) {
      $filename = $this->get_filename();
      $status = $th->write_random_file($filename, random_int(1024, 40960));

      $filename_final = $this->get_filename_final($filename);

      $this->file_upload($filename, $filename_final);

      unlink($filename);
      unlink($filename_final);
    }
  }

  public function test_upload_large() {
    $th = new TestHelpers();

    for ($n=0; $n < 5; $n++) {
      $filename = $this->get_filename();
      $status = $th->write_random_file($filename, random_int(1024000, 2048000));

      $filename_final = $this->get_filename_final($filename);

      $this->file_upload($filename, $filename_final);

      unlink($filename);
      unlink($filename_final);
    }
  }

  public function test_upload_nastyfilenames() {
    $th = new TestHelpers();

    $nastyfilenames = Array(
      '/tmp/some1.file' => 'some1.file',
      '/some2.file' => 'some2.file',
      'foo/bar/some3.file' => 'some3.file',
      'path/.some4.file' => '.some4.file',
      '../../../some5.file' => 'some5.file',
      'hähää.doc' => 'hh.doc',
      'foo_bar-yeah.pdf' => 'foo_bar-yeah.pdf'
    );

    foreach ($nastyfilenames as $nasty => $real) {
      $filename = $this->get_filename();
      $status = $th->write_random_file($filename, random_int(1024, 40960));

      $filename_final = $this->get_filename_final($real);

      $this->file_upload($filename, $filename_final, $nasty);

      unlink($filename);
      unlink($filename_final);
    }
  }

  private function get_filename() {
    return tempnam(sys_get_temp_dir(), "TestUpload_");
  }

  private function get_filename_final($filename) {
    return "dist/data/".basename($filename);
  }

  private function file_upload($upload_filename, $final_filename, $postname=null) {
    $th = new TestHelpers();
    $this_pass = $th->random_str(rand(1, 128), $th->RANDOM_KEYSPACE);
    $pass_filename = $th->write_password_file($this_pass, 'dist/settings.php');

    $curlfile = new CURLFile($upload_filename, "application/octet-stream", $postname);

    $browser = new TestBrowser();
    $data = $browser->http_post(
      $this->server_url,
      Array('password'=>$this_pass, 'function'=>'file_upload', 'file_upload'=>$curlfile),
      "json"
    );

    $this->assertTrue($data['success'], "Failed to upload '$upload_filename', message: '".$data['message']."'");

    // Make sure the files have same MD5sum
    $md5_og = md5_file($upload_filename);
    $md5_up = md5_file($final_filename);

    $this->assertEquals($md5_og, $md5_up, "Original: $upload_filename Uploaded: $final_filename");
  }

}

?>
