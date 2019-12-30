<?php

class TestBrowser {
  protected $useragent = 'Testbrowser';
  protected $cookie_file_location = null;
  protected $last_auth = null;

  public function __construct() {
    // Create cookie file

    $this->cookie_file_location = tempnam(sys_get_temp_dir(), 'TestBrowserCookies');

    $cfile = fopen($this->cookie_file_location, "w");
    fclose($cfile);
  }

  public function __destruct() {
    unlink($this->cookie_file_location);
    $this->cookie_file_location = null;
  }

  public function http_connect($url, $fields, $do_post=false, $result_type="") {
    // Make HTTP GET call to $url
    // if $do_post is set use HTTP POST
    // if $result_type == "json" return a decoded object

    $browser = curl_init();

    curl_setopt($browser, CURLOPT_URL, $url);
    curl_setopt($browser, CURLOPT_RETURNTRANSFER, true);
    // GET is the default method
    if ($do_post) {
      curl_setopt($browser, CURLOPT_POST, true);
    }
    //curl_setopt($browser, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($browser, CURLOPT_POSTFIELDS, $fields);

    curl_setopt($browser, CURLOPT_COOKIEJAR, $this->cookie_file_location);
    curl_setopt($browser, CURLOPT_COOKIEFILE, $this->cookie_file_location);

    $result = curl_exec($browser);
    curl_close($browser);

    if ($result_type == "json") {
      return json_decode($result, true);
    }

    return $result;

    // Store auth to class
    if (array_key_exists('auth', $result_arr)) {
      $this->last_auth = $result_arr['auth'];
    }

    return $result_arr;
  }

  public function http_get($url, $fields, $result_type) {
    return $this->http_connect($url, $fields, false, $result_type);
  }

  public function http_post($url, $fields, $result_type) {
    return $this->http_connect($url, $fields, true, $result_type);
  }

}

?>
