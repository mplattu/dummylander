<?php

class PageContent {
  public $page_data = null;
  private $data_path = "";

  // This field values should possibly be added with $this->data_path prefix
  private $DATA_PATH_FIELD = Array(
    'page' => Array(
      'favicon-ico',
      'image'
    ),
    'part' => Array(
      'background-image',
      'text'
    )
  );

  public function __construct($page_data, $data_path = "") {
    // By default the $page_data is a JSON-encoded string
    $page_data_obj = json_decode($page_data, true);
    if (is_null($page_data_obj)) {
      // $page_data was not a JSON-formatted string, treat it as a filename
      $json = file_get_contents($page_data);
      $this->page_data = json_decode($json, true);
    }
    else {
      // $page_data was a JSON-formatted string
      $this->page_data = $page_data_obj;
    }
    $this->data_path = $data_path;
  }

  private function add_datapath_prefix_one($value) {
    if (!filter_var($value, FILTER_VALIDATE_URL) and !preg_match('/[\/]/', $value)) {
      log_message("add_datapath_prefix returning value with prefix: ".$this->data_path.'/'.$value, null, 2);
      return $this->data_path.'/'.$value;
    }

    return $value;
  }

  private function add_datapath_prefix_text($value) {
    // NB! This does not handle well cases where an image link exists outside and inside backticks
    //     See PageContent_test for more info

    $replacement_count = 0;
    $original_value = $value;

    do {
      $value = preg_replace('/^([^`]*)(!*)\[(.*)\]\(([^\/]*)\)([^`]*)$/m', '$1$2[$3]('.$this->data_path.'/$4)$5', $value, 1, $replacements_made);
      if ($replacements_made > 0) {
        $replacement_count++;
      }
    } while ($replacements_made > 0);

    if ($replacement_count > 0) {
      log_message('add_datapath_prefix_text: '.$replacement_count.' changes:', null, 2);
      log_message('original string: '.$original_value, null, 2);
      log_message('final string   : '.$value, null, 2);
    }
    return $value;
  }

  public function add_datapath_prefix($scope, $field, $value) {
    if (in_array($field, $this->DATA_PATH_FIELD[$scope])) {
      log_message("add_datapath_prefix scope: $scope, field: $field, value: $value", null, 2);
      if ($scope == "part" and $field == "text") {
        return $this->add_datapath_prefix_text($value);
      }

      return $this->add_datapath_prefix_one($value);
    }

    return $value;
  }

  public function get_page_value($field, $default=null) {
    if (is_null($this->page_data) or
      !array_key_exists('page_values', $this->page_data) or
      !array_key_exists($field, $this->page_data['page_values'])) {
        return $default;
    }

    return $this->add_datapath_prefix('page', $field, $this->page_data['page_values'][$field]);
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
      log_message("The page content has no field 'parts'", null, 2);
      return $default;
    }

    if (!array_key_exists($index, $this->page_data['parts'])) {
      log_message("The page content has not field 'parts'->$index", null, 2);
      return $default;
    }

    if (!array_key_exists($field, $this->page_data['parts'][$index])) {
      log_message("The page content has no field 'parts'->$index->$field", null, 2);
      return $default;
    }

    return $this->add_datapath_prefix('part', $field, $this->page_data['parts'][$index][$field]);
  }

}

?>
