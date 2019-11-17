<?php

class PageContent {
  public $page_data = null;

  public function __construct($data_file) {
    $json = file_get_contents($data_file);
    $this->page_data = json_decode($json, true);
  }

  public function get_page_value($field, $default=null) {
    if (is_null($this->page_data) or
      !array_key_exists('page_values', $this->page_data) or
      !array_key_exists($field, $this->page_data['page_values'])) {
        return $default;
    }

    return $this->page_data['page_values'][$field];
  }

  public function get_parts_count() {
    if (is_null($this->page_data) or !array_key_exists('parts', $this->page_data)) {
      return null;
    }

    return count($this->page_data['parts']);
  }

  public function get_part($index, $field, $default=null) {
    if (!array_key_exists('parts', $this->page_data)) {
      log_message("The page content has no field 'parts'");
      return $default;
    }

    if (!array_key_exists($index, $this->page_data['parts'])) {
      log_message("The page content has not field 'parts'->$index");
      return $default;
    }

    if (!array_key_exists($field, $this->page_data['parts'][$index])) {
      log_message("The page content has no field 'parts'->$index->$field");
      return $default;
    }

    return $this->page_data['parts'][$index][$field];
  }
}

?>
