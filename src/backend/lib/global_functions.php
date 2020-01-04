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

?>
