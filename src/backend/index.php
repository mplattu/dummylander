<?php

// Log levels:
// 0 - fatal errors
// 1 - some messages
// 2 - everything
$LOG_LEVEL = 1;

try {
  $s = new Settings();
}
catch (Exception $e) {
  log_message("Failed to create settings file: ".$e->getMessage());

  $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'failedtocreatesettinsfile', $e->getMessage());
  echo($admin_api->execute());
  exit(0);
}

if ($s->get_value('ADMIN_PASSWORD') == "") {
  echo(create_admin_password_and_default_page());
  exit(0);
}

$s_log_level = $s->get_value('LOG_LEVEL');
if (!is_null($s_log_level)) {
  $LOG_LEVEL = $s_log_level;
}

$admin_auth = new AdminAuth();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
  $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'uploadlimitexceeded', "Too large file");
  echo($admin_api->execute());
  exit(0);
}

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
    $response = $admin_api->execute();
  }
  else {
    $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'loginfailed', $admin_message);
    $response = $admin_api->execute();
  }

  log_message("Admin response: ".print_r($response, true));
  echo($response);
}
else {
  $show_page = new ShowPage($VERSION, remove_trailing_slash($DATAPATH));
  echo($show_page->get_html_page());
}

// Normal termination
exit(0);

function create_admin_password_and_default_page() {
  global $DATAPATH, $VERSION;

  $settings = new Settings();

  // Set initial password
  $new_admin_password = consts_random_string(97, 122);

  if (! $settings->set_value('ADMIN_PASSWORD', global_password_hash($new_admin_password))) {
    $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'message_fail', 'Failed to set initial password');
    return $admin_api->execute();
  }

  $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'message_success', 'Your admin password is: '.$new_admin_password);
  $status_message = $admin_api->execute();

  // Create default page
  $show_page = new ShowPage($VERSION, remove_trailing_slash($DATAPATH));
  $page_html = $show_page->get_html_page();
  if (is_null($page_html)) {
    $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'message_fail', 'Failed to create data directory');
    $status_message = $admin_api->execute();
  }

  return $status_message;
}

?>
