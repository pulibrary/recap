<?php

/**
 * @file
 * Contains \Drupal\page_manager\EventSubscriber\CurrentUserContext.
 */

namespace Drupal\page_manager\EventSubscriber;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\page_manager\Event\PageManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets the current user as a context.
 */
class CurrentUserContext implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new CurrentUserContext.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(AccountInterface $account, EntityManagerInterface $entity_manager) {
    $this->account = $account;
    $this->userStorage = $entity_manager->getStorage('user');
  }

  /**
   * Adds in the current user as a context.
   *
   * @param \Drupal\page_manager\Event\PageManagerContextEvent $event
   *   The page entity context event.
   */
  public function onPageContext(PageManagerContextEvent $event) {
    $id = $this->account->id();
    $current_user = $this->userStorage->load($id);

    $context = new Context(new ContextDefinition('entity:user', $this->t('Current user')));
    $context->setContextValue($current_user);
    $event->getPageExecutable()->addContext('current_user', $context);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PageManagerEvents::PAGE_CONTEXT][] = 'onPageContext';
    return $events;
  }

}
