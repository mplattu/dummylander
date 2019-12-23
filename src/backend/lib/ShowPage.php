<?php

include_once("global_functions.php");

class ShowPage {
  private $version = "";
  private $page_content = null;

  function __construct($version, $datapath, $page_json=null) {
    $this->version = $version;

    if (is_null($page_json)) {
      $datafile = $datapath."/content.json";

      if (is_readable($datafile)) {
        $this->page_content = new PageContent($datafile, $datapath);
      } else {
        log_message("Data file $datafile is not readable", 1, 0);
      }
    }
    else {
      $this->page_content = new PageContent($page_json, $datapath);
    }
  }

  function get_html_page() {
    return $this->render_header().$this->render_content().$this->render_footer();
  }

  function get_html_preview() {
    return $this->get_body_style_tag().$this->render_content();
  }

  function get_html_googlefonts() {
    return $this->get_html_tag('<link href="https://fonts.googleapis.com/css?family=###&display=swap" rel="stylesheet" />', $this->page_content->get_page_google_fonts_value());
  }

  private function get_html_tag($html, $value) {
    if (!is_null($value) and $value != "") {
      return preg_replace('/###/', $value, $html);
    }

    return null;
  }

  private function array_push_if_set(&$array, $element) {
    if (!is_null($element) and $element != "") {
      array_push($array, $element);
    }
  }

  private function get_body_style_tag() {
    return "<style>#page table { margin: 0 auto; } #page img { max-width: 100%; } #page { font-family: Arial,Helvetica,sans-serif; }</style>";
  }

  function render_header() {
    $head_tags = Array();

    $this->array_push_if_set($head_tags, $this->get_html_tag('<!-- This landing page has been created with ### -->', $this->version));

    $this->array_push_if_set($head_tags, '<meta charset="UTF-8"><meta http-equiv="content-type" content="text/html; charset=utf-8" />');
    $this->array_push_if_set($head_tags, '<meta name="viewport" content="width=device-width, initial-scale=1.0" />');
    $this->array_push_if_set($head_tags, $this->get_html_tag('<title>###</title>', $this->page_content->get_page_value('title')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<link href="https://fonts.googleapis.com/css?family=###&display=swap" rel="stylesheet" />', $this->page_content->get_page_google_fonts_value()));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<link rel="icon" href="###" type="image/x-icon" />', $this->page_content->get_page_value('favicon-ico')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<link rel="shortcut icon" href="###" type="image/x-icon" />', $this->page_content->get_page_value('favicon-ico')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta name="description" content="###" />', $this->page_content->get_page_value('description')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta name="keywords" content="###" />', $this->page_content->get_page_value('keywords')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<style>###</style>', $this->page_content->get_page_value('style-css')));

    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta property="og:site_name" content="###" />', $this->page_content->get_page_value('title')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta property="og:title" content="###" />', $this->page_content->get_page_value('title')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta property="og:description" content="###" />', $this->page_content->get_page_value('description')));

    if (strlen($this->page_content->get_page_value('image')) > 0) {
      $this->array_push_if_set($head_tags, $this->get_html_tag('<meta property="og:image" content="###" />', get_my_url().$this->page_content->get_page_value('image')));
    }

    $html = "<!DOCTYPE html>\n<html>\n<head>";
    $html .= join("\n", $head_tags)."\n";
    $html .= $this->get_body_style_tag()."</head>";
    $html .= "<body style='margin:0; padding:0;'>";

    return $html;
  }

  function render_footer() {
    return "</body></html>\n";
  }

  function render_content() {
    $part_count = $this->page_content->get_parts_count();

    $html = "";

    if (is_null($part_count)) {
      log_message("Page does not contain any parts", null, 1);
    } else {
      for ($n=0; $n < $part_count; $n++) {
        $html .= $this->render_part($n);
      }
    }

    return "<div id='page'>".$html."</div>";
  }

  function render_part($index) {
    $parsedown = new Parsedown();

    $style_tags = Array();

    $this->array_push_if_set($style_tags, $this->get_html_tag(
      "background-image:url('###'); background-position: center; background-repeat: no-repeat; background-size: cover; ",
      $this->page_content->get_part($index, 'background-image')
    ));
    $this->array_push_if_set($style_tags, $this->get_html_tag("height:###;", $this->page_content->get_part($index, 'height')));
    $this->array_push_if_set($style_tags, $this->get_html_tag("font-family:'###', cursive;", $this->page_content->get_part($index, 'font-family-google')));

    $this->array_push_if_set($style_tags, $this->get_html_tag("margin:###;", $this->page_content->get_part($index, 'margin', '10px')));
    $this->array_push_if_set($style_tags, $this->get_html_tag("padding:###;", $this->page_content->get_part($index, 'padding', '0')));
    $this->array_push_if_set($style_tags, $this->get_html_tag("color:###;", $this->page_content->get_part($index, 'color', '#000000')));
    $this->array_push_if_set($style_tags, $this->get_html_tag("text-align:###;", $this->page_content->get_part($index, 'text-align', 'center')));

    $html = "<section id=\"sec".$index."\" style=\"".join(" ", $style_tags)."\">";

    $html .= $parsedown->text($this->page_content->get_part($index, 'text'));

    $html .= '</section>';

    return $html;
  }

}

?>
