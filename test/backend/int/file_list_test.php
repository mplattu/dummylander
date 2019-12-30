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

  public function test_file_list_and_delete() {
    $files = 10;

    $upload_files = Array();
    $final_files = Array();
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-_';

    $th = new TestHelpers();
    $browser = new TestBrowser();

    // Upload files
    for ($n=0; $n < $files; $n++) {
      $upload_files[$n] = $th->random_str(random_int(5,15), $keyspace);
      $status = $th->write_random_file($upload_files[$n], random_int(10240, 40960));

      $final_files[$n] = $this->get_filename_final($upload_files[$n]);

      $this->file_upload($upload_files[$n], $final_files[$n]);
    }

    // Reset password
    $this_pass = $th->random_str(rand(1, 128), $th->RANDOM_KEYSPACE);
    $pass_filename = $th->write_password_file($this_pass, 'dist/settings.php');

    // Get file list
    $data = $browser->http_post(
      $this->server_url,
      Array('password'=>$this_pass, 'function'=>'file_list'),
      "json"
    );

    $this->assertTrue($data['success']);

    // Build $filelist
    $filelist = Array();
    foreach ($data['data'] as $this_entry) {
      array_push($filelist, $this_entry['name']);
    }

    // Check and delete remote files
    for ($n=0; $n < $files; $n++) {
      $this->assertTrue(in_array($upload_files[$n], $filelist), $upload_files[$n]." is not in ".print_r($filelist, true));

      $data = $browser->http_post(
        $this->server_url,
        Array('password'=>$this_pass, 'function'=>'file_delete', 'data'=>$upload_files[$n]),
        "json"
      );

      $this->assertTrue($data['success'], "Could not delete ".$upload_files[$n]);
    }

    // Get file list
    $data = $browser->http_post(
      $this->server_url,
      Array('password'=>$this_pass, 'function'=>'file_list'),
      "json"
    );

    $this->assertTrue($data['success']);

    // Build $filelist
    $filelist = Array();
    foreach ($data['data'] as $this_entry) {
      array_push($filelist, $this_entry['name']);
    }

    // Check file list
    for ($n=0; $n < $files; $n++) {
      $this->assertFalse(in_array($upload_files[$n], $filelist), $upload_files[$n]." is in ".print_r($filelist, true));
    }

    // Delete local files
    for ($n=0; $n < $files; $n++) {
      unlink($upload_files[$n]);
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
