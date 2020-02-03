<?php

function log_message ($message, $exit_level = null, $log_level=2) {
  global $LOG_LEVEL;

  if (defined('STDIN')) {
    // Executed from CLI (tests?)
    echo("LOG: ".$message."\n");
  }
  elseif ($log_level <= $LOG_LEVEL) {
    // Write to server log
    error_log($message, 4);
  }

  if (!is_null($exit_level)) {
    exit($exit_level);
  }
}

function remove_trailing_slash($path) {
  return preg_replace('/[\\\\\/]+$/', '', $path);
}

function get_my_url($url = null) {
  if (is_null($url)) {
    if (@$_SERVER['TEST_MY_URL'] != "") {
      $url = $_SERVER['TEST_MY_URL'];
    }
    else {
      $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
    }
  }

  $url = preg_replace('/[^\/]*?$/', '', $url);

  return $url;
}

function custom_error_handler($severity, $message, $file, $line) {
  throw new ErrorException($message, $severity, $severity, $file, $line);
}

function global_password_hash($password) {
  global $MAX_PASSWORD_LENGTH;

  if (gettype($password) != "string") {
    throw new Exception("global_password_hash() requires a string as a password");
  }

  if (mb_strlen($password, "UTF-8") > $MAX_PASSWORD_LENGTH) {
    throw new Exception("global_password_hash() got too long password");
  }

  return password_hash($password, PASSWORD_BCRYPT);
}

?>
