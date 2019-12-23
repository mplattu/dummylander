<?php

class AdminAuth {
  private $methods;
  private $last_error;

  function __construct($methods=null) {
    if (!is_array($methods) or sizeof($methods) < 1) {
      $this->raise_exception("AdminAuth requires authentication methods as an array");
      $this->methods = null;
    }
    else {
      $this->methods = $methods;
    }
    $this->last_error = null;
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
    if (is_null($this->methods)) {
      $this->raise_exception("No authentication methods defined");
    }

    foreach ($this->methods as $method => $param) {
      $auth_success = $this->is_admin_method($method, $param, $password);
      if ($auth_success) {
        return true;
      }
    }

    return false;
  }

  private function is_admin_method($method, $method_param, $authentication) {
    if ($method == "file") {
      return $this->is_admin_file($method_param, $authentication);
    }

    $this->raise_exception("Unknown authentication method: ".$method);
  }

  private function is_admin_file($filename, $password) {
    if (!file_exists($filename)) {
      return false;
    }

    if (!is_readable($filename)) {
      $this->set_last_error("Authentication password file $filename is not readable");
      return false;
    }

    $file = file_get_contents($filename);

    if (preg_match('/\$ADMIN_PASSWORD\s*=\s*"(.*)"/', $file, $matches)) {
      $file_password = $matches[1];

      if ($file_password === "") {
        $this->set_last_error("Password in $filename has not been set");
        return false;
      }

      if ($file_password === $password) {
        return true;
      }

      return false;
    }

    $this->set_last_error("Authentication password file $filename is not in valid format");
    return false;
  }
}

?>
