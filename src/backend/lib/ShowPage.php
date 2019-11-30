<?php

class ShowPage {
  private $version = "";

  function __construct($version, $datafile) {
    $this->version = $version;

    if (is_readable($datafile)) {
      $page = new PageContent($datafile);

      $this->render_header($page);
      $this->render_content($page);
      $this->render_footer($page);
    } else {
      log_message("Data file $datafile is not readable", 1);
    }
  }

  private function get_html_tag($html, $value) {
    $tag = "";

    if (!is_null($value)) {
      $tag = preg_replace('/###/', $value, $html);
      log_message('Adding tag: '.$tag);
    }

    return $tag;
  }

  function render_header($page) {
    $head_tags = Array();

    array_push($head_tags, $this->get_html_tag('<!-- This landing page has been created with ### -->', $this->version));

    array_push($head_tags, '<meta charset="UTF-8"><meta http-equiv="content-type" content="text/html; charset=utf-8" />');
    array_push($head_tags, '<meta name="viewport" content="width=device-width, initial-scale=1.0" />');
    array_push($head_tags, $this->get_html_tag('<title>###</title>', $page->get_page_value('title')));
    array_push($head_tags, $this->get_html_tag('<link href="https://fonts.googleapis.com/css?family=###&display=swap" rel="stylesheet" />', $page->get_page_google_fonts_value()));
    array_push($head_tags, $this->get_html_tag('<link rel="icon" href="###" type="image/x-icon" />', $page->get_page_value('favicon-ico')));
    array_push($head_tags, $this->get_html_tag('<link rel="shortcut icon" href="###" type="image/x-icon" />', $page->get_page_value('favicon-ico')));
    array_push($head_tags, $this->get_html_tag('<meta name="description" content="###" />', $page->get_page_value('description')));

    array_push($head_tags, $this->get_html_tag('<meta property="og:site_name" content="###" />', $page->get_page_value('title')));
    array_push($head_tags, $this->get_html_tag('<meta property="og:title" content="###" />', $page->get_page_value('title')));
    array_push($head_tags, $this->get_html_tag('<meta property="og:description" content="###" />', $page->get_page_value('description')));

    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <?php echo(join("\n", $head_tags)."\n"); ?>
      <style>
        table { margin: 0 auto; }
      </style>
    </head>
    <body style="margin:0; padding: 0;
      font-family: <?php echo($page->get_page_value('font-family', 'Arial,Helvetica,sans-serif')); ?>
    ">
    <?php
  }

  function render_footer($page) {
    ?>
    </body>
    </html>
    <?php
  }

  function render_content($page) {
    $part_count = $page->get_parts_count();

    if (is_null($part_count)) {
      log_message("Page does not contain any parts");
    } else {
      for ($n=0; $n < $part_count; $n++) {
        $this->render_part($page, $n);
      }
    }
  }

  function render_part($page, $index) {
    $parsedown = new Parsedown();

    $style_tags = Array();

    array_push($style_tags, $this->get_html_tag(
      "background-image:url('###'); background-position: center center;",
      $page->get_part($index, 'background-image')
    ));
    array_push($style_tags, $this->get_html_tag("height:###;", $page->get_part($index, 'height')));
    array_push($style_tags, $this->get_html_tag("font-family:###, cursive;", $page->get_part($index, 'font-family-google')));

    array_push($style_tags, $this->get_html_tag("margin:###;", $page->get_part($index, 'margin', '10px')));
    array_push($style_tags, $this->get_html_tag("padding:###;", $page->get_part($index, 'padding', '0')));
    array_push($style_tags, $this->get_html_tag("color:###;", $page->get_part($index, 'color', '#000000')));
    array_push($style_tags, $this->get_html_tag("text-align:###;", $page->get_part($index, 'text-align', 'center')));

    ?>
      <section
        id="sec<?php echo($index); ?>"
        style="
          <?php echo(join("\n", $style_tags)."\n"); ?>
          "
      >

      <?php echo($parsedown->text($page->get_part($index, 'text'))); ?>

      </section>
    <?php
    log_message($page->get_part($index, 'text'));
  }

}

?>
