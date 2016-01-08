<?php

/**
 * @file
 * Contains \Drupal\cas\Controller\ForceLoginController.
 */

namespace Drupal\cas\Controller;

use Drupal\cas\Service\CasHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ForceLoginController.
 */
class ForceLoginController implements ContainerInjectionInterface {
  /**
   * The cas helper to get config settings from.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * Used to get query string parameters from the request.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param CasHelper $cas_helper
   *   The CAS helper service.
   * @param RequestStack $request_stack
   *   Symfony request stack.
   */
  public function __construct(CasHelper $cas_helper, RequestStack $request_stack) {
    $this->casHelper = $cas_helper;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('cas.helper'), $container->get('request_stack'));
  }

  /**
   * Handles a page request for our forced login route.
   */
  public function forceLogin() {
    // TODO: What if CAS is not configured? need to handle that case.
    $query_params = $this->requestStack->getCurrentRequest()->query->all();
    $cas_login_url = $this->casHelper->getServerLoginUrl($query_params);
    $this->casHelper->log("Cas forced login route, redirecting to: $cas_login_url");

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheTags(array(
      'config:cas.settings',
    ));
    $response = TrustedRedirectResponse::create($cas_login_url, 302);
    $response->addCacheableDependency($cacheable_metadata);

    return $response;
  }

}
