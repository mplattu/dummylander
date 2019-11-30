<?php

$VERSION = "Dummylander 0.1";
$DATAFILE = "data/content.json";
$ADMIN_PASSWORD = "secret";

log_message(@$_SERVER['QUERY_STRING']);
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


function log_message ($message, $exit_level = null) {
  // Write log message to server log
  error_log($message, 4);

  if (!is_null($exit_level)) {
    exit($exit_level);
  }
}



?>
