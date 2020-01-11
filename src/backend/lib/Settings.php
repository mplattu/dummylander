<?php

class Settings {
  private $filename = null;
  private $rules = null;

  function __construct($filename=null) {
    global $DEFAULT_SETTINGS;

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

    if (!$this->create_settings_file($DEFAULT_SETTINGS)) {
      throw new Exception('Failed to create new settings file');
    }
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

  private function create_settings_file($settings) {
    // Creates new settings file if it does not exist

    if (!is_file($this->filename) or !is_readable($this->filename)) {
      log_message("Creating new settings file ".$this->filename, null, 1);
      if (!$this->write_settings_file($settings)) {
        log_message("Failed to create new settings file ".$this->filename, null, 0);
        return false;
      }
    }

    return true;
  }
}

?>
