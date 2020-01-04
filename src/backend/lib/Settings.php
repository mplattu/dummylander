<?php

class Settings {
  private $filename = null;
  private $rules = null;

  function __construct($filename=null) {
    if (is_null($filename) or ($filename === "")) {
      $this->filename = "settings.php";
    }
    else {
      $this->filename = $filename;
    }

    $this->rules = Array(
      'ADMIN_PASSWORD' => "string",
      'LOG_LEVEL' => "integer"
    );
  }

  function get_filename() {
    return $this->filename;
  }

  function set_value($field, $value) {
    $field = strtoupper($field);

    if (!array_key_exists($field, $this->rules)) {
      log_message("Trying to set field $field which does not exist");
      return false;
    }

    if (gettype($value) != $this->rules[$field]) {
      log_message("Trying to set field $field to value $value, which is illegal type ".gettype($value));
      return false;
    }

    $settings = $this->read_settings_file();
    $settings[$field] = $value;
    return $this->write_settings_file($settings);
  }

  function get_value($field) {
    $field = strtoupper($field);

    if (!array_key_exists($field, $this->rules)) {
      log_message("Trying to get field $field which does not exist");
      return false;
    }

    $settings = $this->read_settings_file();
    return @$settings[$field];
  }

  private function read_settings_file() {
    if (!is_readable($this->filename)) {
      log_message("Settings file ".$this->filename." is not readable");
      return Array();
    }

    $file = file_get_contents($this->filename);

    $settings = Array();

    if (preg_match('/(\{.*\})/', $file, $matches)) {
      $settings = json_decode($matches[1], true);
    }

    return $settings;
  }

  private function write_settings_file($settings) {
    if (!is_writable($this->filename)) {
      log_message("Settings file ".$this->filename." is not writable");
      return false;
    }

    $c = Array();
    array_push($c, "<?php");
    array_push($c, "/*");
    array_push($c, json_encode($settings));
    array_push($c, "*/");
    array_push($c, "?>");

    $bytes_written = file_put_contents($this->filename, join("\n", $c)."\n");

    if ($bytes_written == false) {
      return false;
    }

    return true;
  }
}

?>
