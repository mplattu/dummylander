<?php

function log_message ($message, $exit_level = null, $log_level=null) {
  global $LOG_LEVEL;

  // Write log message to server log
  if ($log_level <= $LOG_LEVEL) {
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
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
  }

  $url = preg_replace('/[^\/]*?$/', '', $url);

  return $url;
}

?>
