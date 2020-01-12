<?php

class PageStorage {
  private $data_file = null;

  public function __construct($data_file) {
    $this->data_file = $data_file;
  }

  private function write_file($filename, $content) {
    set_error_handler("custom_error_handler");

    try {
      $bytes_written = file_put_contents($filename, $content);
    }
    catch (Exception $e) {
      log_message("Error while writing settings file ".$filename.": ".$e->getMessage());
      $bytes_written = false;
    }

    restore_error_handler();

    if ($bytes_written === false) {
      return false;
    }

    return true;
  }

  private function create_content_files() {
    global $DEFAULT_CONTENT, $DEFAULT_FILES, $DEFAULT_PERMISSIONS;

    $data_file_path = dirname($this->data_file).DIRECTORY_SEPARATOR;

    $all_ok = true;

    if (! is_dir($data_file_path)) {
      $perms = $DEFAULT_PERMISSIONS;
      if (is_null($perms)) {
        $perms = fileperms('.');
      }

      if (! mkdir($data_file_path, $perms, true)) {
        $all_ok = false;
      }
    }

    if (! $this->write_file($this->data_file, json_encode($DEFAULT_CONTENT))) {
      $all_ok = false;
    }

    foreach ($DEFAULT_FILES as $this_filename => $this_content) {
      if ($all_ok and ! $this->write_file($data_file_path.$this_filename, base64_decode($this_content))) {
        $all_ok = false;
      }
    }

    return $all_ok;
  }

  public function get_data_json() {
    if (is_file($this->data_file) and is_readable($this->data_file)) {
      return file_get_contents($this->data_file);
    }

    if ($this->create_content_files()) {
      return file_get_contents($this->data_file);
    }

    return null;
  }

  public function set_data_json($json) {
    return file_put_contents($this->data_file, $json, LOCK_EX);
  }
}

?>
