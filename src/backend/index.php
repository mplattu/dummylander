<?php

$VERSION = "Dummylander 0.1";
$DATAFILE = "data/content.json";

if (@$_SERVER['QUERY_STRING'] != "") {
  log_message($_SERVER['QUERY_STRING'], 1);
} else {
  $show_page = new ShowPage($VERSION, $DATAFILE);
}

// Normal termination
exit(0);


function log_message ($message, $exit_level = null) {
  // Write log message to server log
  error_log($message, 4);

  if (!is_null($exit_level)) {
    exit($exit_level);
  }
}



?>
