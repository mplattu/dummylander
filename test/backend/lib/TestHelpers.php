<?php

class TestHelpers {
  public $RANDOM_KEYSPACE = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYåäöÅÄÖ§½!"#¤%&/()=\'[]';

  /**
    * Generate a random string, using a cryptographically secure
    * pseudorandom number generator (random_int)
    *
    * This function uses type hints now (PHP 7+ only), but it was originally
    * written for PHP 5 as well.
    *
    * For PHP 7, random_int is a PHP core function
    * For PHP 5.x, depends on https://github.com/paragonie/random_compat
    * https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
    *
    * @param int $length      How many characters do we want?
    * @param string $keyspace A string of all possible characters
    *                         to select from
    * @return string
    */
  function random_str(
      int $length = 64,
      string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string
  {
    if ($length < 1) {
        throw new \RangeException("Length must be a positive integer");
    }

    $pieces = [];
    $max = mb_strlen($keyspace, "UTF-8") - 1;
    for ($i = 0; $i < $length; ++$i) {
      $pieces []= mb_substr($keyspace, random_int(0, $max), 1, "UTF-8");
    }
    return implode('', $pieces);
  }

  function get_temp_filename() {
    return tempnam(sys_get_temp_dir(), "DummylanderTestHelpers_");
  }

  private function write_settings_file($final_pass, $filename=null) {
    global $DEFAULT_SETTINGS;

    if (is_null($DEFAULT_SETTINGS)) {
      throw new Exception("You have to include global_consts.php for this test");
    }

    $settings = $DEFAULT_SETTINGS;
    $settings['ADMIN_PASSWORD'] = $final_pass;

    if (is_null($filename)) {
      $filename = $this->get_temp_filename();
    }

    $c = Array();
    array_push($c, "<?php");
    array_push($c, "/*");
    array_push($c, json_encode($settings));
    array_push($c, "*/");
    array_push($c, "?>\n");

    $file_content = implode("\n", $c)."\n";
    $bytes_written = file_put_contents($filename, $file_content);

    return $filename;
  }

  function write_password_file_emptypass($filename=null) {
    return $this->write_settings_file("", $filename);
  }

  function write_password_file($password, $filename=null) {
    return $this->write_settings_file(global_password_hash($password), $filename);
  }

  function get_expected_index_html() {
    global $VERSION;

    $expected_page = <<<EOT
<!DOCTYPE html>
<html>
<head><!-- This landing page has been created with Dummylander 0.8 -->
<meta charset="UTF-8"><meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Dummylander 0.8</title>
<link href="https://fonts.googleapis.com/css?family=Shadows+Into+Light|Rajdhani&display=swap" rel="stylesheet" />
<link rel="icon" href="data/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="data/favicon.ico" type="image/x-icon" />
<meta name="description" content="Description of your Dummylander site which might be shown by search engines and when sharing your www address" />
<style>a:link {color:inherit} a:visited {color:inherit} a:hover { text-decoration-color: red }</style>
<meta property="og:site_name" content="Dummylander 0.8" />
<meta property="og:title" content="Dummylander 0.8" />
<meta property="og:description" content="Description of your Dummylander site which might be shown by search engines and when sharing your www address" />
<meta property="og:image" content="http://localhost:8080/data/favicon.ico" />
<style>#page table { margin: 0 auto; } #page img { max-width: 100%; } #page { font-family: Arial,Helvetica,sans-serif; }</style></head><body style='margin:0; padding:0;'><div id='page'><section id="sec0" style="background-image:url('data/space.jpg'); background-position: center; background-repeat: no-repeat; background-size: cover;  height:400px; padding:0; color:#000000; text-align:center;"></section><section id="sec1" style="margin:10px; padding:0; color:#000000; text-align:center;"><h1>Hello World!</h1>
<p>You have successfully installed Dummylander 0.8. Small step for you, but a giant leap to Internet!</p>
<p>But hey - please doublecheck you've done all steps of the installation procedure at <a href="https://github.com/mplattu/dummylander">Dummylander GitHub site</a>.</p></section><section id="sec2" style="margin:10px; padding:0; color:#000000; text-align:center;"><h2>Page is Built from Parts</h2>
<p>The page is built on parts which may contain text and images. The flower and a heading above is the first parts while this text is a second part.</p>
<p>The text fields may contain <a href="https://www.markdownguide.org/cheat-sheet">markdown-formatted</a> syntax. This allows you to give <strong>structure</strong> to your text.</p></section><section id="sec3" style="font-family:'Shadows Into Light', cursive; margin:10px; padding:0; color:#000000; text-align:center;"><h2>Fonts</h2>
<p>Each part may have its own font definition. It is easy to use <a href="https://fonts.google.com/">open fonts delivered by Google</a>.</p>
<p>This part uses <em>Shadows Into Light</em> font.</p></section><section id="sec4" style="margin:10px; padding:0; color:#F90000; text-align:center;"><h2>Font Color</h2>
<p>The font color can be changed for each part.</p></section><section id="sec5" style="font-family:'Rajdhani', cursive; margin:10px; padding:0; color:#000000; text-align:center;"><h2>Tables</h2>
<p>Tables are particulary easy to create with markdown syntax:</p>
<table>
<thead>
<tr>
<th>Service</th>
<th>Price</th>
</tr>
</thead>
<tbody>
<tr>
<td>Some Service</td>
<td>25 €</td>
</tr>
<tr>
<td>Some other service</td>
<td>35 €</td>
</tr>
<tr>
<td>A third service</td>
<td>55 €</td>
</tr>
</tbody>
</table></section><section id="sec6" style="margin:10px; padding:0; color:#000000; text-align:center;"><h2>Images and Documents</h2>
<p>Markdown syntax allows you to add images:</p>
<p><img src="data/favicon.ico" alt="Alternative text!" /></p>
<p>Naturally, you can share <a href="data/sample-document.pdf">documents</a> too.</p></section><section id="sec7" style="margin:10px; padding:0; color:#000000; text-align:center;"><h2>HTML</h2>
<p>You are not limited to the quick and easy markdown syntax. <a href="https://www.markdownguide.org/cheat-sheet">The syntax</a> allows you to embed HTML tags. Here we have a clickable image:</p>
<p><a href="https://duckduckgo.com/"><img src='data/DuckDuckGo-DaxSolo.svg' width="50px" height="50px"></a></p></section></div></body></html>

EOT;

    $expected_page = preg_replace('/###VERSION###/', $VERSION, $expected_page);

    return $expected_page;
  }

  public function write_random_file($filename, $size) {
    if ($size == 0) {
      $handle = fopen($filename);
      if ($handle == false) {
        return false;
      }
      return fclose($handle);
    }
    return file_put_contents($filename, random_bytes($size));
  }
}

?>
