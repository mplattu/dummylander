<?php

class AdminAPI {
  private $page_storage = null;
  private $function = null;
  private $data = null;

  function __construct($data_file, $function, $data) {
    $this->page_storage = new PageStorage($data_file);
    $this->function = $function;
    $this->data = $data;
  }

  private function get_return_data($success, $data = null) {
    $return_data = Array(
      'success' => $success
    );

    if (!is_null($data)) {
      $return_data['data'] = $data;
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
      return $this->get_return_data(false);
    }
  }
}

?>
