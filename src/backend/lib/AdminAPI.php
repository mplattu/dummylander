<?php

class AdminAPI {
  private $page_storage = null;
  private $function = null;
  private $data = null;

  function __construct($data_path, $function, $data) {
    $this->page_storage = new PageStorage($data_path."/content.json");
    $this->function = $function;
    $this->data = $data;
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

    if ($this->function == "loginfailed") {
      return $this->get_return_data(false, null, $this->data);
    }
  }
}

?>
