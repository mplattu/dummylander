<?php

$AUTH_METHODS = Array(
  'file' => null  // Uses the default file path "settings.php"
);

$VERSION = "Dummylander 0.5";
$DATAPATH = "data/";

$PAGE_PROPERTIES = Array(
  'title',
  'favicon-ico',
  'description',
  'image',
  'keywords',
  'style-css'
);

$SECTION_PROPERTIES = Array(
  'margin',
  'padding',
  'height',
  'color',
  'background-image',
  'font-family-google'
);

$DEFAULT_SETTINGS = Array(
  'ADMIN_PASSWORD' => "",
  'LOG_LEVEL' => 1
);

// Characters allowed in filenames. See FileStorage class.
$FILE_UPLOAD_ALLOWED_CHARS_REGEX = 'a-zA-Z01234567890\-_.';

?>
