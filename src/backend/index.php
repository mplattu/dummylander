<?php

$VERSION = "Dummylander 0.1";
$DATAFILE = "data/content.json";
$ADMIN_PASSWORD = "secret";

// Log levels:
// 0 - fatal errors
// 1 - some messages
// 2 - everything
$LOG_LEVEL = 1;

log_message("QUERY_STRING:".@$_SERVER['QUERY_STRING'], null, 2);
if (@$_SERVER['QUERY_STRING'] == "admin") {
  $admin_ui = new ShowAdminUI();
}
elseif (@$_POST['password'] != "" and $_POST['password'] == $ADMIN_PASSWORD) {
  $admin_api = new AdminAPI($DATAFILE, @$_POST['function'], @$_POST['data']);
  echo($admin_api->execute());
}
elseif (@$_POST['password'] != "") {
  $admin_api = new AdminAPI($DATAFILE, 'loginfailed', null);
  echo($admin_api->execute());
}
else {
  $show_page = new ShowPage($VERSION, $DATAFILE);
}

// Normal termination
exit(0);


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



?>
