<?php

$AUTH_METHODS = Array(
  'file' => 'settings.php'
);

$VERSION = "Dummylander 0.4";
$DATAPATH = "data/";

// Log levels:
// 0 - fatal errors
// 1 - some messages
// 2 - everything
$LOG_LEVEL = 1;

$admin_auth = new AdminAuth($AUTH_METHODS);

log_message("QUERY_STRING:".@$_SERVER['QUERY_STRING'], null, 2);
if (@$_SERVER['QUERY_STRING'] == "admin") {
  $admin_ui = new ShowAdminUI();
}
elseif (@$_POST['password'] != "") {
  $is_admin = false;
  $admin_message = null;

  try {
    $is_admin = $admin_auth->is_admin($_POST['password']);
  }
  catch (Exception $e) {
    $admin_message = $e->getMessage();
    log_message("Authentication error: ".$admin_message);
  }

  if (is_null($admin_message)) {
    $admin_message = $admin_auth->get_last_error();
  }

  if ($is_admin) {
    $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), @$_POST['function'], @$_POST['data']);
    echo($admin_api->execute());
  }
  else {
    $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'loginfailed', $admin_message);
    echo($admin_api->execute());
  }
}
else {
  $show_page = new ShowPage($VERSION, remove_trailing_slash($DATAPATH));
}

// Normal termination
exit(0);

?>
