<?php

namespace Drupal\Tests\cas\Unit\Subscriber;

use Drupal\cas\Service\CasRedirector;
use Drupal\Component\HttpFoundation\SecuredRedirectResponse;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\cas\Unit\Mock\MockCondition;
use Drupal\Tests\cas\Unit\Mock\MockSession;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Subscriber\CasSubscriber;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * CasSubscriber unit tests.
 *
 * @ingroup cas
 *
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Subscriber\CasSubscriber
 */
class CasSubscriberTest extends UnitTestCase {

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * The mocked Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Controllable Request.
   *
   * @var Request
   */
  protected $request;

  /**
   * The mocked condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager|PHPUnit_Framework_MockObject_MockObject
   */
  protected $conditionManager;

  /**
   * The mocked CasHelper.
   *
   * @var \Drupal\cas\Service\CasHelper|PHPUnit_Framework_MockObject_MockObject
   */
  protected $casHelper;

  /**
   * The mocked CasRedirector.
   *
   * @var CasRedirector
   */
  protected $casRedirector;


  /**
   * The mocked route matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeMatcher;

  /**
   * The mocked GetResponseEvent.
   *
   * @var \Symfony\Component\HttpKernel\Event\GetResponseEvent|PHPUnit_Framework_MockObject_MockObject
   */
  protected $event;

  /**
   * The session.
   *
   * @var MockSession
   */
  protected $session;

  /**
   * The mock condition.
   *
   * @var MockCondition;
   */
  protected $condition;

  /**
   * Current route for drupal tests.
   *
   * @var string
   */
  private $route = 'front.page';

  /**
   * The CasRedirectResponse.
   *
   * @var CasRedirectResponse
   */
  private $eventResponse;

  /**
   * Request type.
   *
   * @var int
   */
  private $eventRequestType = HttpKernelInterface::MASTER_REQUEST;

  /**
   * Default config we use for setting up tests.
   *
   * @var array
   */
  private $defaultConfig = [
    'cas.settings' => [
        'forced_login.enabled' => FALSE,
        'forced_login.paths' => ['<front>'],
        'gateway.check_frequency' => CasHelper::CHECK_NEVER,
        'gateway.paths' => ['<front>'],
    ]
  ];

  /**
   * Config data for a forced auth paths.
   *
   * @var array
   */
  private $forcedConfig = [
    'cas.settings' => [
      'forced_login.enabled' => TRUE,
      'forced_login.paths' => ['<front>'],
      'gateway.check_frequency' => CasHelper::CHECK_ALWAYS,
      'gateway.paths' => [ '<front>'],
    ]
  ];

  /**
   * Config data for gateway paths.
   *
   * @var array
   */
  private $gatewayConfig = [
    'cas.settings' => [
      'forced_login.enabled' => FALSE,
      'forced_login.paths' => ['<front>'],
      'gateway.check_frequency' => CasHelper::CHECK_ALWAYS,
      'gateway.paths' => [ '<front>'],
    ]
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Controllable condition for page testing.
    $this->condition = new MockCondition();
    $this->condition->result = TRUE;

    // Replace condition plugin system with controllable condition.
    $this->conditionManager = $this->getMockBuilder('\Drupal\Core\Condition\ConditionManager')
                                   ->disableOriginalConstructor()
                                   ->getMock();
    $this->conditionManager->method('createInstance')->willReturn($this->condition);
    $this->conditionManager->method('execute')->willReturnCallback([$this->condition, 'evaluate']);

    // Request stack used for Injecting requests.
    $this->requestStack = new RequestStack();
    $this->request = new Request();
    $this->requestStack->push($this->request);
    // Controllable session for injection.
    $this->session = new MockSession();
    // Controllable server method.
    $this->request->server = new ServerBag([]);

    // Mock cas helper with hardcoded redirect paths.
    $this->casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
                            ->disableOriginalConstructor()
                            ->setMethods(['getServerBaseUrl', 'getCasServiceUrl', 'log'])
                            ->getMock();
    $this->casHelper->method('getServerBaseUrl')->willReturn('https://example.com/cas');
    $this->casHelper
      ->method('getCasServiceUrl')
      ->willReturnCallback([$this, 'getServiceUrl']
      );

    // Mock event dispatcher to dispatch events.
    $event_dispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->casRedirector = new CasRedirector($this->casHelper, $event_dispatcher);

    // Mock User account interface.
    $this->currentUser = $this->getMock('\Drupal\Core\Session\AccountInterface');

    // Mock up a routematch object.
    $this->route = 'front.page';
    $this->routeMatcher = $this->getMock('\Drupal\Core\Routing\RouteMatchInterface');
    $this->routeMatcher->method('getRouteName')->willReturnCallback(
      [
        $this,
        'getRouteName',
      ]
    );

    // Mock event.
    $this->event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\GetResponseEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $this->event->method('setResponse')->willReturnCallback([$this, 'setEventResponse']);
    $this->event->method('getRequestType')->willReturnCallback([$this, 'getEventRequestType']);

  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->requestStack->pop();
    parent::tearDown();
  }

  /**
   * Set Request type.
   *
   * @param SecuredRedirectResponse $response
   *   The cas redirector object.
   */
  public function setEventResponse(SecuredRedirectResponse $response) {
    $this->eventResponse = $response;
  }

  /**
   * Control Event Request type.
   */
  public function getEventRequestType() {
    return $this->eventRequestType;
  }

  /**
   * Control current route.
   *
   * @return string
   *   The route set in the current test.
   */
  public function getRouteName() {
    return $this->route;
  }

  /**
   * Mock function for getting service url based on parameters.
   *
   * @param array $parameters
   *   The service URL parameters.
   *
   * @return string
   *   Fully constructed service URL.
   */
  public function getServiceUrl(array $parameters = []) {
    if ($parameters) {
      return 'http://example.com/casservice?' . UrlHelper::buildQuery($parameters);
    }
    else {
      return 'http://example.com/casservice';
    }
  }

  /**
   * Test our event subscription declaration.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $this->assertThat(
      CasSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][0],
      $this->contains('handle')
    );
  }

  /**
   * Test backing out when we get a sub request.
   *
   * @covers ::handle
   * @covers ::__construct
   */
  public function testHandleSubRequest() {
    $config_factory = $this->getConfigFactoryStub($this->forcedConfig);
    $cas_subscriber = new CasSubscriber(
                              $this->requestStack,
                              $this->routeMatcher,
                              $config_factory,
                              $this->currentUser,
                              $this->conditionManager,
                              $this->casHelper,
                              $this->casRedirector
                            );
    $this->eventRequestType = KernelInterface::SUB_REQUEST;
    $cas_subscriber->handle($this->event);
    $this->assertNull($this->eventResponse, 'No redirect on Sub Requests');
  }

  /**
   * Test backing out when user is authenticated.
   *
   * @covers ::handle
   * @covers ::__construct
   */
  public function testHandleIsAuthenticated() {
    $config_factory = $this->getConfigFactoryStub($this->forcedConfig);
    $cas_subscriber = new CasSubscriber(
                              $this->requestStack,
                              $this->routeMatcher,
                              $config_factory,
                              $this->currentUser,
                              $this->conditionManager,
                              $this->casHelper,
                              $this->casRedirector
    );

    // Build a request.
    $request = new Request();
    // Use our session variable.
    $request->setSession($this->session);
    $this->requestStack->push($request);

    $this->currentUser->expects($this->once())
      ->method('isAuthenticated')
      ->will($this->returnValue(TRUE));
    $cas_subscriber->handle($this->event);
    $this->requestStack->pop();
    $this->assertNull($this->eventResponse, 'No redirect on logged in user');
  }

  /**
   * Test backing out when the current route is the service route.
   *
   * @covers ::handle
   * @covers ::isIgnoreableRoute
   * @covers ::__construct
   */
  public function testHandleIsIgnoreableRoute() {
    $config_factory = $this->getConfigFactoryStub($this->forcedConfig);
    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );
    $this->route = "cas.service";
    $cas_subscriber->handle($this->event);
    $this->assertNull($this->eventResponse, 'No Redirectors on ignorable route');
  }

  /**
   * Test backing out when we have cas_temp_disable_auto_auth.
   *
   * @covers ::handle
   * @covers ::__construct
   */
  public function testHandleTempDisable() {
    $config_factory = $this->getConfigFactoryStub($this->defaultConfig);
    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );
    $this->request->setSession($this->session);

    $this->session->session_values  = ['cas_temp_disable_auto_auth' => TRUE];
    $cas_subscriber->handle($this->event);
    $this->assertEmpty($this->session->session_values);
  }

  /**
   * Test handling a forced login path.
   *
   * @covers ::handle
   * @covers ::handleForcedPath
   * @covers ::__construct
   */
  public function testHandleForcedPath() {
    $config_factory = $this->getConfigFactoryStub($this->forcedConfig);

    $cas_subscriber = new CasSubscriber(
                              $this->requestStack,
                              $this->routeMatcher,
                              $config_factory,
                              $this->currentUser,
                              $this->conditionManager,
                              $this->casHelper,
                              $this->casRedirector
                            );

    $cas_subscriber->handle($this->event);

    // Make sure we got back the expected response.
    $this->assertNotNull($this->eventResponse, 'Redirect response found');
    $this->assertNotContains('gateway=true', $this->eventResponse->getTargetUrl(), 'No Gateway set');
    $this->assertInstanceOf('\Drupal\core\Routing\TrustedRedirectResponse', $this->eventResponse, 'Cacheable Response');
    $this->assertContains('returnto', $this->eventResponse->getTargetUrl());
  }

  /**
   * Test 'failing through' the forced login check due to config option.
   *
   * @covers ::handle
   * @covers ::handleForcedPath
   * @covers ::handleGateway
   */
  public function testHandleForcedPathWithConfigOff() {
    $config_factory = $this->getConfigFactoryStub($this->defaultConfig);
    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );
    $cas_subscriber->handle($this->event);
    $this->assertNull($this->eventResponse, 'No Redirect when not configured');
  }

  /**
   * Test 'failing through' the forced login check due to no condition match.
   *
   * @covers ::handle
   * @covers ::handleForcedPath
   * @covers ::handleGateway
   */
  public function testHandleForcedPathNoConditionMatch() {
    $config_factory = $this->getConfigFactoryStub($this->forcedConfig);
    $this->condition->result = FALSE;

    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );

    $cas_subscriber->handle($this->event);
    $this->assertNull($this->eventResponse, 'No Redirect if the page does not match');
  }

  /**
   * Test exiting out of handleGateway if we're not configured to do it.
   *
   * @covers ::handle
   * @covers ::handleGateway
   */
  public function testHandleGatewayConfigOff() {
    $config_factory = $this->getConfigFactoryStub($this->defaultConfig);
    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );
    $cas_subscriber->handle($this->event);
    $this->assertNull($this->eventResponse);
  }

  /**
   * Tests that web crawlers do not trigger gateway mode.
   *
   * @covers ::handle
   * @covers ::handleGateway
   * @covers ::isCrawlerRequest
   */
  public function testGatewayModeIgnoredWhenWebCrawlerRequest() {
    $config_factory = $this->getConfigFactoryStub($this->gatewayConfig);
    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );
    $this->request->server->set('HTTP_USER_AGENT', 'gsa-crawler');
    $cas_subscriber->handle($this->event);
    $this->assertNull($this->eventResponse, 'No Redirector for crawlers');
  }

  /**
   * Test exiting out of gateway if we're not on a configured path.
   *
   * @covers ::handle
   * @covers ::handleGateway
   */
  public function testHandleGatewayWithPathNotInConfig() {
    $config = $this->defaultConfig;
    $config['cas.settings']['gateway.check_frequency'] = CasHelper::CHECK_ALWAYS;
    $config_factory = $this->getConfigFactoryStub($config);
    $this->condition->result = FALSE;
    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );
    $cas_subscriber->handle($this->event);

    $this->assertNull($this->eventResponse, 'No redirection event when path match');
  }

  /**
   * Test exiting out of gateway if CHECK_ONCE and we already checked.
   *
   * @covers ::handle
   * @covers ::handleGateway
   */
  public function testHandleGatewayWithGatewayAlreadyChecked() {
    $config = $this->gatewayConfig;
    $config['cas.settings']['gateway.check_frequency'] = CasHelper::CHECK_ONCE;
    $config_factory = $this->getConfigFactoryStub($config);
    $this->condition->result = TRUE;

    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );

    // preset the cas_gateway checked TRUE event.
    $this->session->set('cas_gateway_checked', TRUE);
    $this->request->setSession($this->session);

    $cas_subscriber->handle($this->event);
    $this->assertNull($this->eventResponse, 'Do not check gateway if session variable is set');
  }

  /**
   * Test processing gateway with CHECK_ONCE to make sure SESSION gets set.
   *
   * @covers ::handle
   * @covers ::handleGateway
   */
  public function testHandleGatewayWithCheckOnceSuccess() {
    $config = $this->gatewayConfig;
    $config['cas.settings']['gateway.check_frequency'] = CasHelper::CHECK_ONCE;
    $config_factory = $this->getConfigFactoryStub($config);
    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );
    // Use mock sessions.
    $this->session->session_values = [];
    $this->request->setSession($this->session);
    $cas_subscriber->handle($this->event);
    $this->condition->result = TRUE;
    $this->assertArrayHasKey('cas_gateway_checked', $this->session->all());
    $this->assertNotNull($this->eventResponse, 'Got a redirector');
    $this->assertInstanceOf('\Drupal\cas\CasRedirectResponse', $this->eventResponse, 'Not Cacheable');
    $this->assertContains('gateway=true', $this->eventResponse->getTargetUrl());
    $this->assertContains('returnto', $this->eventResponse->getTargetUrl(), 'Verify returnto');
  }

  /**
   * Make sure on403 handler works if the path is configured for forced login.
   *
   * @covers ::on403
   * @covers ::handleForcedPath
   * @covers ::__construct
   */
  public function testHandle403() {
    $config_factory = $this->getConfigFactoryStub($this->forcedConfig);
    $this->condition->result = TRUE;
    $this->currentUser->method('isAnonymous')->willReturn(TRUE);
    $cas_subscriber = new CasSubscriber(
      $this->requestStack,
      $this->routeMatcher,
      $config_factory,
      $this->currentUser,
      $this->conditionManager,
      $this->casHelper,
      $this->casRedirector
    );
    // Mock event.
    $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $event->method('setResponse')->willReturnCallback([$this, 'setEventResponse']);
    $cas_subscriber->on403($event);
    // Make sure we got back the expected response.
    $this->assertNotNull($this->eventResponse, 'Redirect response found');

    $this->assertInstanceOf('\Drupal\core\Routing\TrustedRedirectResponse', $this->eventResponse, 'Cachable redirect');
    $this->assertNotContains('gateway=true', $this->eventResponse->getTargetUrl(), 'No Gateway set');
  }

}
