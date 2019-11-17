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

  function render_header($page) {
    $google_font_tag = "";
    if (!is_null($page->get_page_value('fonts-google'))) {
      $google_font_tag = '<link href="https://fonts.googleapis.com/css?family='.$page->get_page_value('fonts-google').'&display=swap" rel="stylesheet">';
    }

    ?>
    <DOCTYPE html>
    <html>
    <head>
      <?php echo("<!-- This landing page has been created with ".$this->version." -->\n"); ?>
      <meta charset="UTF-8">
      <meta http-equiv="content-type" content="text/html; charset=utf-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title><?php echo($page->get_page_value('title')); ?></title>
      <?php echo($google_font_tag); ?>
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

    $background_tag = "";
    if (!is_null($page->get_part($index, 'background-image'))) {
      $background_tag =
        'background-image:url('.$page->get_part($index, 'background-image').');'.
        'background-position: center center;';
    }

    $height_tag = "";
    if (!is_null($page->get_part($index, 'height'))) {
      $height_tag = 'height:'.$page->get_part($index, 'height').';';
    }

    $font_family_google_tag = "";
    if (!is_null($page->get_part($index, 'font-family-google'))) {
      $font_family_google_tag = 'font-family: \''.$page->get_part($index, 'font-family-google').'\', cursive;';
    }

    ?>
      <section
        id="sec<?php echo($index); ?>"
        style="
          margin:<?php echo($page->get_part($index, 'margin', '10px')); ?>;
          padding:<?php echo($page->get_part($index, 'padding', '0')); ?>;
          <?php echo($height_tag); ?>
          background: <?php echo($page->get_part($index, 'background', '#ffffff')); ?>;
          <?php echo($background_tag); ?>
          color: <?php echo($page->get_part($index, 'color', '#000000')); ?>;
          text-align: <?php echo($page->get_part($index, 'text-align', 'center')); ?>;
          <?php echo($font_family_google_tag); ?>
          "
      >

      <?php echo($parsedown->text($page->get_part($index, 'text'))); ?>

      </section>
    <?php
    log_message($page->get_part($index, 'text'));
  }

}

?>
