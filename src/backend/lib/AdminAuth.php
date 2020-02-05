<?php

class AdminAuth {
  private $last_error;
  private $settings_filename;

  function __construct($settings_filename=null) {
    $this->last_error = null;

    if (!is_null($settings_filename) and gettype($settings_filename) != "string") {
      $this->raise_exception("Parameter must be a filename to settings file");
    }
    $this->settings_filename = $settings_filename;
  }

  private function log_message($message) {
    log_message("AdminAuth error: ".$message);
  }

  private function set_last_error($message) {
    $this->last_error = $message;
    $this->log_message($message);
  }

  private function raise_exception($message) {
    $this->set_last_error($message);
    throw new Exception($message);
  }

  function get_last_error() {
    $error = $this->last_error;
    $this->last_error = null;
    return $error;
  }

  function is_admin($password) {
    $s = new Settings($this->settings_filename);

    $file_password = $s->get_value('ADMIN_PASSWORD');

    if (is_null($file_password) or $file_password === "") {
      $this->set_last_error("Password in ".$s->get_filename()." has not been set");
      return false;
    }

    return password_verify($password, $file_password);
  }
}

?>
