<?php

/**
 * @file
 * Contains \Drupal\page_manager\Entity\PageViewBuilder.
 */

namespace Drupal\page_manager\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view builder for page entities.
 */
class PageViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = [];
    /** @var $entity \Drupal\page_manager\PageInterface */
    if ($display_variant = $entity->getExecutable()->selectDisplayVariant()) {
      $build = $display_variant->build();
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = [];
    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

}
