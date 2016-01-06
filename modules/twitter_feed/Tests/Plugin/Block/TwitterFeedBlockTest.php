<?php

/**
 * Class TwitterFeedBlockTest
 * Tests the block display for module.
 */

class TwitterFeedBlockTest extends PHPUnit_Framework_TestCase
{
  // ...

  public function testCanBeNegated()
  {
    // Arrange
    $a = new Money(1);

    // Act
    $b = $a->negate();

    // Assert
    $this->assertEquals(-1, $b->getAmount());
  }

  // ...
}
