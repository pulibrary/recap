<?php

/**
 * @file
 * Contains \Drupal\page_manager\EventSubscriber\StaticContext.
 */

namespace Drupal\page_manager\EventSubscriber;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\page_manager\Context\EntityLazyLoadContext;
use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\page_manager\Event\PageManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds static context.
 */
class StaticContext implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new StaticContext.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Adds in the current user as a context.
   *
   * @param \Drupal\page_manager\Event\PageManagerContextEvent $event
   *   The page entity context event.
   */
  public function onPageContext(PageManagerContextEvent $event) {
    $executable = $event->getPageExecutable();
    $static_contexts = $executable->getPage()->getStaticContexts();

    foreach ($static_contexts as $name => $static_context) {
      $context = new EntityLazyLoadContext(new ContextDefinition($static_context['type'], $static_context['label']), $this->entityManager, $static_context['value']);
      $executable->addContext($name, $context);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PageManagerEvents::PAGE_CONTEXT][] = 'onPageContext';
    return $events;
  }

}
