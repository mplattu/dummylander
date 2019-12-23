<?php

class AdminAPI {
  private $page_storage = null;
  private $function = null;
  private $data = null;
  private $data_path = null;

  function __construct($data_path, $function, $data) {
    $this->page_storage = new PageStorage($data_path."/content.json");
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

    if ($this->function == "loginfailed") {
      return $this->get_return_data(false, null, $this->data);
    }
  }
}

?>
