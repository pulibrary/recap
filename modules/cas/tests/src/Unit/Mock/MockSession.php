<?php

namespace Drupal\Tests\cas\Unit\Mock;


use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class MockSession extends Session  {
  /** @var  array Session values */
  public $session_values;

  /** @noinspection PhpMissingParentConstructorInspection */
  public function __construct() {

  }

  public function has($name) {
    return isset($this->session_values[$name]);
  }

  public function get($name, $default = NULL) {
    if (isset($this->session_values[$name])) {
      return $this->session_values[$name];
    }
    else {
      return $default;
    }
  }

  public function set($name, $value) {
    $this->session_values[$name] = $value;
  }

  public function remove($name) {
    unset($this->session_values[$name]);
  }

  public function all() {
    return $this->session_values;
  }



}
