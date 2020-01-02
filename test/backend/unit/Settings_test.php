<?php

// Add support for PHPunit 5.x:
class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
use PHPUnit\Framework\TestCase;


include_once(__DIR__."/../../../src/backend/lib/global_functions.php");
include_once(__DIR__."/../../../src/backend/lib/global_consts.php");
include_once(__DIR__."/../../../src/backend/lib/Settings.php");
include_once(__DIR__."/../lib/TestHelpers.php");

class Settings_test extends TestCase {
  function test_get_filename() {
    $th = new TestHelpers();
    $settings_filename = $th->get_temp_filename();
    $s = new Settings($settings_filename);

    $this->assertEquals($settings_filename, $s->get_filename());

    unlink($settings_filename);
  }

  function test_set_and_get_values() {
    // Create settings file, write and read values

    $th = new TestHelpers();
    $settings_filename = $th->get_temp_filename();
    $s = new Settings($settings_filename);

    $this->assertTrue($s->set_value('ADMIN_PASSWORD', ""), "Could not set ADMIN_PASSWORD to empty string");
    $this->assertTrue($s->set_value('LOG_LEVEL', 0), "Could not set LOG_LEVEL to 0");

    $this->assertEquals("", $s->get_value('ADMIN_PASSWORD'));
    $this->assertEquals(0, $s->get_value('LOG_LEVEL'));

    $this->assertTrue($s->set_value('ADMIN_PASSWORD', 'string"with"quotes'), "Could not set ADMIN_PASSWORD with quotes");
    $this->assertEquals('string"with"quotes', $s->get_value('ADMIN_PASSWORD'));

    for ($n=0; $n < 10; $n++) {
      $rnd_password = $th->random_str(random_int(1,128), $th->RANDOM_KEYSPACE);
      $rnd_log_level = random_int(0,2);

      $this->assertTrue($s->set_value('ADMIN_PASSWORD', $rnd_password));
      $this->assertTrue($s->set_value('LOG_LEVEL', $rnd_log_level));

      $this->assertEquals($rnd_password, $s->get_value('ADMIN_PASSWORD'));
      $this->assertEquals($rnd_log_level, $s->get_value('LOG_LEVEL'));
    }

    unlink($settings_filename);
  }

  function test_set_illegal_values() {
    // Try to set illegal fields and values

    $th = new TestHelpers();
    $settings_filename = $th->get_temp_filename();
    $s = new Settings($settings_filename);

    $this->assertFalse($s->set_value('FOOBAR', 1));
    $this->assertFalse($s->set_value('ADMIN_PASSWORD', Array('foo'=>'bar')));
    $this->assertFalse($s->set_value('LOG_LEVEL', "string value"));

    unlink($settings_filename);
  }
}


?>
