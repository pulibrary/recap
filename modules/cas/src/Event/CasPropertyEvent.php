<?php

/**
 * @file
 * Contains \Drupal\cas\Event\CasPropertyEvent.
 */

namespace Drupal\cas\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\cas\CasPropertyBag;

/**
 * Class CasPropertyEvent.
 */
class CasPropertyEvent extends Event {

  /**
   * Store the CAS property bag.
   *
   * @var \Drupal\cas\CasPropertyBag
   *   The CasPropertyBag for context.
   */
  protected $casPropertyBag;

  /**
   * Constructor.
   *
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The CasPropertyBag of the current login cycle.
   */
  public function __construct(CasPropertyBag $cas_property_bag) {
    $this->casPropertyBag = $cas_property_bag;
  }

  /**
   * CasPropertyBag getter.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The casPropertyBag property.
   */
  public function getCasPropertyBag() {
    return $this->casPropertyBag;
  }

}
