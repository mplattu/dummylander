<?php

include_once(__DIR__."/../../src/backend/lib/global_consts.php");
include_once(__DIR__."/../../src/backend/lib/global_functions.php");

$options = getopt("p:");

if (is_null(@$options['p']) or @$options['p'] == '') {
  echo("usage: encrypt_password.php -p password_to_encrypt\n\n");
  exit(1);
}
echo(global_password_hash($options['p'])."\n");

?>
