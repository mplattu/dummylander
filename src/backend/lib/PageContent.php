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

  // Returns value you can give to Google Fonts CSS tag, e.g. "Playfair+Display|Tomorrow"
  // <link href="https://fonts.googleapis.com/css?family=Playfair+Display|Tomorrow&display=swap" rel="stylesheet" />
  // In case no Google Fonts are used returns null

  public function get_page_google_fonts_value() {
    $fonts_used = Array();

    for ($n=0; $n < $this->get_parts_count(); $n++) {
      $this_font = $this->get_part($n, 'font-family-google');
      if (!is_null($this_font) and $this_font != "") {
        array_push($fonts_used, urlencode($this->get_part($n, 'font-family-google')));
      }
    }

    if (count($fonts_used) > 0) {
      return join('|', $fonts_used);
    }

    return null;
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
