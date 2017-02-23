<?php

namespace Drupal\Tests\cas\Unit\Mock;


use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Defines mock condition that can be controlled.
 * Class MockCondition
 * @package Drupal\Tests\cas\Unit\Mock
 */
class MockCondition extends ConditionPluginBase {
  public $result;
  /** @noinspection PhpMissingParentConstructorInspection */

  /**
   * Disable internal contstructor
   */
  public function __construct() {

  }

  /**
   * Disable summary method
   */
  public function summary() {
  }

  public function evaluate() {
    return $this->result;
  }

}
