<?php

/**
 * @file
 * Contains \Drupal\cas\ProxyCallbackController.
 */

namespace Drupal\cas\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Drupal\cas\Service\CasHelper;

/**
 * Class ProxyCallbackController.
 */
class ProxyCallbackController implements ContainerInjectionInterface {
  /**
   * Used when inserting the CAS PGT into the database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Used to get params from the current request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Used for logging.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * Constructor.
   *
   * @param Connection $database_connection
   *   The database service.
   * @param RequestStack $request_stack
   *   The Symfony request stack.
   * @param CasHelper $cas_helper
   *   The CasHelper.
   */
  public function __construct(Connection $database_connection, RequestStack $request_stack, CasHelper $cas_helper) {
    $this->connection = $database_connection;
    $this->requestStack = $request_stack;
    $this->casHelper = $cas_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // This needs to get the necessary __construct requirements from
    // the container.
    return new static($container->get('database'), $container->get('request_stack'), $container->get('cas.helper'));
  }

  /**
   * Route callback for the ProxyGrantingTicket information.
   *
   * This function stores the incoming PGTIOU and pgtId parameters so that
   * the incoming response from the CAS Server can be looked up.
   */
  public function callback() {
    // @TODO: Check that request is coming from configured CAS server to avoid
    // filling up the table with bogus pgt values.
    $request = $this->requestStack->getCurrentRequest();
    // Check for both a pgtIou and pgtId parameter. If either is not present,
    // inform CAS Server of an error.
    if (!($request->query->get('pgtId') && $request->query->get('pgtIou'))) {
      $this->casHelper->log("Missing necessary parameters for PGT.");
      return Response::create('Missing necessary parameters', 400);
    }
    else {
      // Store the pgtIou and pgtId in the database for later use.
      $pgt_id = $request->query->get('pgtId');
      $pgt_iou = $request->query->get('pgtIou');
      $this->storePgtMapping($pgt_iou, $pgt_id);
      $this->casHelper->log("Storing pgtId $pgt_id with pgtIou $pgt_iou");
      // PGT stored properly, tell CAS Server to proceed.
      return Response::create('OK', 200);
    }
  }

  /**
   * Store the pgtIou to pgtId mapping in the database.
   *
   * @param string $pgt_iou
   *   The pgtIou from CAS Server.
   * @param string $pgt_id
   *   The pgtId from the CAS server.
   *
   * @codeCoverageIgnore
   */
  protected function storePgtMapping($pgt_iou, $pgt_id) {
    $this->connection->insert('cas_pgt_storage')
      ->fields(
        array('pgt_iou', 'pgt', 'timestamp'),
        array($pgt_iou, $pgt_id, time())
      )
      ->execute();
  }

}
