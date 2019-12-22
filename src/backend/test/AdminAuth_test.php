<?php

// Add support for PHPunit 5.x:
class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
use PHPUnit\Framework\TestCase;

include_once("global_functions.php");
include_once("AdminAuth.php");

class AdminAuth_test extends TestCase {
  private $KEYSPACE = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXY§½!"#¤%&/()=\'[]';

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
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
  }

  function write_password_file($password) {
    $wspace1 = str_pad('', rand(0,5));
    $wspace2 = str_pad('', rand(0,5));
    $wspace3 = str_pad('', rand(0,5));

    $file_content = "<?php\n\n\$ADMIN_PASSWORD".$wspace1."=".$wspace2."\"".$password."\"".$wspace3.";\n\n?>\n";
    $filename = tempnam(sys_get_temp_dir(), "AdminAuth_test_");
    $bytes_written = file_put_contents($filename, $file_content);

    $this->assertEquals($bytes_written, strlen($file_content), "Filename: $filename\n\n".$file_content);

    return $filename;
  }

  function test_nomethods () {
    try {
      $aa1 = new AdminAuth();
    }
    catch (Exception $e) {
      $this->assertEquals($e->getMessage(), "AdminAuth requires authentication methods as an array");
    }

    try {
      $aa2 = new AdminAuth(1);
    }
    catch (Exception $e) {
      $this->assertEquals($e->getMessage(), "AdminAuth requires authentication methods as an array");
    }

    try {
      $aa3 = new AdminAuth(Array());
    }
    catch (Exception $e) {
      $this->assertEquals($e->getMessage(), "AdminAuth requires authentication methods as an array");
    }
  }

  function test_file () {
    $filename1 = $this->write_password_file('secret');
    $aa1 = new AdminAuth(Array('file'=>$filename1));
    $this->assertTrue($aa1->is_admin('secret'), "Failing password file: $filename1");
    unlink($filename1);

    $filename2 = $this->write_password_file('foobar');
    $aa2 = new AdminAuth(Array('file'=>$filename2));
    $this->assertTrue($aa2->is_admin('foobar'), "Failing password file: $filename2");
    $this->assertFalse($aa2->is_admin('baafor'), "Failing password file: $filename2");
    unlink($filename2);

    $filename3 = $this->write_password_file('badchars\'\"');
    $aa3 = new AdminAuth(Array('file'=>$filename3));
    $this->assertTrue($aa3->is_admin('badchars\'\"'), "Failing password file: $filename3");
    unlink($filename3);

    $filename = Array();
    $aa = Array();
    for ($n=0; $n < 100; $n++) {
      $this_pass = $this->random_str(rand(1, 128), $this->KEYSPACE);
      $filename[$n] = $this->write_password_file($this_pass);
      $aa[$n] = new AdminAuth(Array('file'=>$filename[$n]));

      $this->assertTrue(
        $aa[$n]->is_admin($this_pass),
        "Failing password file: ".$filename[$n]
      );

      $wrong_pass = $this->random_str(rand(1,128), $this->KEYSPACE);
      $this->assertFalse(
        $aa[$n]->is_admin($wrong_pass),
        "Correct password: ".$this_pass." wrong password: ".$wrong_pass." file: ".$filename[$n]
      );

      unlink($filename[$n]);
    }
  }
}

?>
