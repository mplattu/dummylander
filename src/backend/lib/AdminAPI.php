<?php

class AdminAPI {
  private $page_storage = null;
  private $file_storage = null;
  private $function = null;
  private $data = null;
  private $data_path = null;

  function __construct($data_path, $function, $data) {
    $this->page_storage = new PageStorage($data_path."/content.json");
    $this->file_storage = new FileStorage($data_path);
    $this->admin_auth = new AdminAuth();
    try {
      $this->settings = new Settings();
    }
    catch (Exception $e) {
      // Failed to create settings file - try to continue without the object
      $this->settings = null;
    }

    $this->function = $function;
    $this->data = $data;
    $this->data_path = $data_path;
  }

  private function get_return_data($success, $data = null, $message = null) {
    $return_data = Array(
      'success' => $success,
      'message' => ''
    );

    if (!is_null($data)) {
      $return_data['data'] = $data;
    }

    if (is_null($return_data['success'])) {
      $return_data['success'] = false;
    }

    if (!is_null($message)) {
      $return_data['message'] = $message;
    }

    return json_encode($return_data);
  }

  private function is_json($str) {
    $obj = json_decode($str);
    if (is_null($obj)) {
      return false;
    }

    return true;
  }

  private function get_preview_html($page_data) {

    if ($this->is_json($page_data)) {
      $show_page = new ShowPage("0", $this->data_path, $page_data);
      return Array(
        'html' => $show_page->get_html_preview(),
        'head' => $show_page->get_html_googlefonts()
      );
    }
    else {
      return "<p>Given parameter is not a JSON-formatted object</p>";
    }
  }

  function execute() {
    log_message("Execute, function: '".$this->function."' data: ".print_r($this->data, true));

    if ($this->function == "get") {
      $data = $this->page_storage->get_data_json();

      if (!$data) {
        return $this->get_return_data(false);
      }

      return $this->get_return_data(true, json_decode($data, true));
    }

    if ($this->function == "set") {
      if (!$this->page_storage->set_data_json($this->data)) {
        return $this->get_return_data(false);
      }

      return $this->get_return_data(true);
    }

    if ($this->function == "preview") {
      return $this->get_return_data(true, $this->get_preview_html($this->data));
    }

    if ($this->function == "file_list") {
      return $this->get_return_data(true, $this->file_storage->get_file_list());
    }

    if ($this->function == "file_upload") {
      log_message(print_r($_FILES['file_upload'], true));
      $upload_success = $this->file_storage->upload_file($_FILES['file_upload']);
      return $this->get_return_data($upload_success, $this->file_storage->get_file_list(), $this->file_storage->get_last_error());
    }

    if ($this->function == "file_delete") {
      $delete_success = $this->file_storage->delete_file($this->data);
      return $this->get_return_data($delete_success, $this->file_storage->get_file_list(), $this->file_storage->get_last_error());
    }

    if ($this->function == "change_password") {
      if (is_null($this->settings)) {
        // We don't have settings class - probably the file or the directory containing the file is in
        // unwriteable mode
        return $this->get_return_data(false, null, "Backend error - check file permissions");
      }

      if ($this->admin_auth->is_admin($this->data['old_password'])) {
        $change_password = $this->settings->set_value('ADMIN_PASSWORD', global_password_hash($this->data['new_password']));
        return $this->get_return_data($change_password, null, "");
      }

      return $this->get_return_data(false, null, "Check old password");
    }

    if ($this->function == "message_success") {
      return $this->get_return_data(true, null, $this->data);
    }

    if ($this->function == "message_fail") {
      return $this->get_return_data(false, null, $this->data);
    }

    if ($this->function == "loginfailed") {
      return $this->get_return_data(false, null, $this->data);
    }

    if ($this->function == "uploadlimitexceeded") {
      return $this->get_return_data(false, null, $this->data);
    }

    if ($this->function == "failedtocreatesettinsfile") {
      return $this->get_return_data(false, null, $this->data);
    }
  }
}

?>
