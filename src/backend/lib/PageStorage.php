<?php

class PageStorage {
  private $data_file = null;

  public function __construct($data_file) {
    $this->data_file = $data_file;
  }

  public function get_data_json() {
    return file_get_contents($this->data_file);
  }

  public function set_data_json($json) {
    return file_put_contents($this->data_file, $json, LOCK_EX);
  }
}

?>