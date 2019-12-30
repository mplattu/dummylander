<?php

class FileStorage {
  private $IGNORE_FILES = null;
  private $data_path = null;
  private $last_error = "";

  public function __construct($data_path) {
    $this->data_path = $data_path;

    $this->IGNORE_FILES = Array('', '.', '..', 'content.json');
  }

  public function get_last_error() {
    $last_error = $this->last_error;
    $this->last_error = "";
    return $last_error;
  }

  private function set_last_error($error) {
    $this->last_error = $error;
  }

  public function get_file_list() {
    $file_list = Array();

    if ($handle = opendir($this->data_path)) {
      while (false !== ($entry = readdir($handle))) {
        if (!in_array($entry, $this->IGNORE_FILES)) {
          $entry_data = Array(
            'name' => $entry,
            'size' => filesize($this->data_path.DIRECTORY_SEPARATOR.$entry)
          );
          array_push($file_list, $entry_data);
        }
      }
    }

    return $file_list;
  }

  private function valid_filename($filename) {
    global $FILE_UPLOAD_ALLOWED_CHARS_REGEX;

    // Make sure the filename does not contain a directory separator
    if (strpos(DIRECTORY_SEPARATOR, $filename) !== false) {
      return null;
    }

    // Allow only listed characters
    $filename = basename($filename);
    $filename = preg_replace('/[^'.$FILE_UPLOAD_ALLOWED_CHARS_REGEX.']/', '', $filename);

    // Make sure the filename is not one of the forbidden files
    if (in_array($filename, $this->IGNORE_FILES)) {
      return null;
    }

    return $this->data_path.DIRECTORY_SEPARATOR.$filename;
  }

  public function upload_file($upload_file_data) {
    if ($upload_file_data['error']) {
      if (is_file($upload_file_data['tmp_name'])) {
        unlink($upload_file_data['tmp_name']);
      }
      return false;
    }

    $valid_filename = $this->valid_filename($upload_file_data['name']);
    if (is_null($valid_filename)) {
      if (is_file($upload_file_data['tmp_name'])) {
        unlink($upload_file_data['tmp_name']);
      }
      return false;
    }

    if (file_exists($valid_filename)) {
      if (is_file($upload_file_data['tmp_name'])) {
        unlink($upload_file_data['tmp_name']);
      }
      $this->set_last_error("'".$upload_file_data['name']."' already exists");
      return false;
    }

    return move_uploaded_file($upload_file_data['tmp_name'], $valid_filename);
  }

  public function delete_file($filename) {
    $valid_filename = $this->valid_filename($filename);
    if (is_null($valid_filename)) {
      return false;
    }

    if (unlink($valid_filename) > 0) {
      return true;
    }

    $this->set_last_error("Unable to remove file '".$filename."'");

    return false;
  }
}

?>
