<?php

$VERSION = "Dummylander 0.2";
$DATAPATH = "data/";
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
  $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), @$_POST['function'], @$_POST['data']);
  echo($admin_api->execute());
}
elseif (@$_POST['password'] != "") {
  $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'loginfailed', null);
  echo($admin_api->execute());
}
else {
  $show_page = new ShowPage($VERSION, remove_trailing_slash($DATAPATH));
}

// Normal termination
exit(0);

?>
