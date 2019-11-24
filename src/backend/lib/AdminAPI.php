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

  function execute() {
    if ($this->function == "get") {
      return $this->page_storage->get_data_json();
    }
  }
}

?>
