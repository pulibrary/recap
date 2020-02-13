<?php

namespace Drupal\Tests\cas\Unit\Controller;

use Drupal\cas\CasPropertyBag;
use Drupal\cas\Controller\ServiceController;
use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Exception\CasValidateException;
use Drupal\cas\Service\CasHelper;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Utility\Token;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * ServiceController unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Controller\ServiceController
 */
class ServiceControllerTest extends UnitTestCase {

  /**
   * The mocked CasHelper.
   *
   * @var \Drupal\cas\Service\CasHelper|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casHelper;

  /**
   * The mocked Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The mocked CasValidator.
   *
   * @var \Drupal\cas\Service\CasValidator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casValidator;

  /**
   * The mocked CasUserManager.
   *
   * @var \Drupal\cas\Service\CasUserManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casUserManager;

  /**
   * The mocked CasLogout.
   *
   * @var \Drupal\cas\Service\CasLogout|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casLogout;

  /**
   * The mocked Url Generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockBuilder
   */
  protected $configFactory;

  /**
   * The mocked request parameter bag.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestBag;

  /**
   * The mocked query parameter bag.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $queryBag;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $requestObject;

  /**
   * The mocked messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * The event dispatcher.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $eventDispatcher;

  /**
   * The external auth service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $externalAuth;

  /**
   * The token service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->casValidator = $this->getMockBuilder('\Drupal\cas\Service\CasValidator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->casUserManager = $this->getMockBuilder('\Drupal\cas\Service\CasUserManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->casLogout = $this->getMockBuilder('\Drupal\cas\Service\CasLogout')
      ->disableOriginalConstructor()
      ->getMock();
    $this->configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'error_handling.login_failure_page' => '/user/login',
        'error_handling.message_validation_failure' => '/user/login',
        'login_success_message' => '',
      ],
    ]);
    $this->token = $this->prophesize(Token::class);
    $this->casHelper = new CasHelper($this->configFactory, new LoggerChannelFactory(), $this->token->reveal());
    $this->requestStack = $this->createMock('\Symfony\Component\HttpFoundation\RequestStack');
    $this->urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $this->requestObject = new Request();
    $request_bag = $this->createMock('\Symfony\Component\HttpFoundation\ParameterBag');
    $query_bag = $this->createMock('\Symfony\Component\HttpFoundation\ParameterBag');
    $this->requestObject->query = $query_bag;
    $this->requestObject->request = $request_bag;

    $storage = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage')
      ->setMethods(NULL)
      ->getMock();
    $session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Session')
      ->setConstructorArgs([$storage])
      ->setMethods(NULL)
      ->getMock();
    $session->start();

    $this->requestObject->setSession($session);

    $this->requestBag = $request_bag;
    $this->queryBag = $query_bag;

    $this->messenger = $this->createMock('\Drupal\Core\Messenger\MessengerInterface');

    $this->eventDispatcher = $this->prophesize(ContainerAwareEventDispatcher::class);
    $this->externalAuth = $this->prophesize(ExternalAuthInterface::class);
  }

  /**
   * Tests a single logout request.
   *
   * @dataProvider parameterDataProvider
   */
  public function testSingleLogout($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      TRUE,
      // ticket.
      FALSE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    $this->casLogout->expects($this->once())
      ->method('handleSlo')
      ->with($this->equalTo('<foobar/>'));

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher->reveal(),
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $response = $serviceController->handle();
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('', $response->getContent());
  }

  /**
   * Tests that we redirect to the homepage when no service ticket is present.
   *
   * @dataProvider parameterDataProvider
   */
  public function testMissingTicketRedirectsHome($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      FALSE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    if ($returnto) {
      $this->assertDestinationSetFromReturnTo();
    }

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher->reveal(),
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests that validation and logging in occurs when a ticket is present.
   *
   * @dataProvider parameterDataProvider
   */
  public function testSuccessfulLogin($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    if ($returnto) {
      $this->assertDestinationSetFromReturnTo();
    }

    $validation_data = new CasPropertyBag('testuser');

    $this->assertSuccessfulValidation($returnto);

    // Login should be called.
    $this->casUserManager->expects($this->once())
      ->method('login')
      ->with($this->equalTo($validation_data), $this->equalTo('ST-foobar'));

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher->reveal(),
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests that a user is validated and logged in with Drupal acting as proxy.
   *
   * @dataProvider parameterDataProvider
   */
  public function testSuccessfulLoginProxyEnabled($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    if ($returnto) {
      $this->assertDestinationSetFromReturnTo();
    }

    $this->assertSuccessfulValidation($returnto, TRUE);

    $validation_data = new CasPropertyBag('testuser');
    $validation_data->setPgt('testpgt');

    // Login should be called.
    $this->casUserManager->expects($this->once())
      ->method('login')
      ->with($this->equalTo($validation_data), $this->equalTo('ST-foobar'));

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'proxy.initialize' => TRUE,
      ],
    ]);

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $configFactory,
      $this->messenger,
      $this->eventDispatcher->reveal(),
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests for a potential validation error.
   *
   * @dataProvider parameterDataProvider
   */
  public function testTicketValidationError($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    // Validation should throw an exception.
    $this->casValidator->expects($this->once())
      ->method('validateTicket')
      ->will($this->throwException(new CasValidateException()));

    // Login should not be called.
    $this->casUserManager->expects($this->never())
      ->method('login');

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher->reveal(),
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToSpecialPageOnLoginFailure($serviceController);
  }

  /**
   * Tests for a potential login error.
   *
   * @dataProvider parameterDataProvider
   */
  public function testLoginError($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    $this->assertSuccessfulValidation($returnto);

    // Login should throw an exception.
    $this->casUserManager->expects($this->once())
      ->method('login')
      ->will($this->throwException(new CasLoginException()));

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher->reveal(),
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToSpecialPageOnLoginFailure($serviceController);
  }

  /**
   * An event listener alters username before attempting to load user.
   *
   * @covers ::handle
   *
   * @dataProvider parameterDataProvider
   */
  public function testEventListenerChangesCasUsername($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    $this->eventDispatcher
      ->dispatch(Argument::type('string'), Argument::type(Event::class))
      ->will(function (array $args) {
        if ($args[0] === CasHelper::EVENT_PRE_USER_LOAD_REDIRECT && $args[1] instanceof CasPreUserLoadRedirectEvent) {
          $args[1]->getPropertyBag()->setUsername('foobar');
        }
      });

    $expected_bag = new CasPropertyBag('foobar');

    $this->casUserManager->expects($this->once())
      ->method('login')
      ->with($this->equalTo($expected_bag), 'ST-foobar');

    $this->casValidator->expects($this->once())
      ->method('validateTicket')
      ->with($this->equalTo('ST-foobar'))
      ->will($this->returnValue($expected_bag));

    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->with('<front>')
      ->willReturn('/user/login');

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher->reveal(),
      $this->externalAuth->reveal()
    );
    $serviceController->handle();
  }

  /**
   * Asserts that user is redirected to a special page on login failure.
   */
  private function assertRedirectedToSpecialPageOnLoginFailure($serviceController) {
    // Service controller calls Url:: methods directly, since there's no
    // existing service class to use instead of that. This makes unit testing
    // hard. We need to place mock services that Url:: uses in the container.
    $path_validator = $this->createMock('Drupal\Core\Path\PathValidatorInterface');
    $unrouted_url_assember = $this->createMock('Drupal\Core\Utility\UnroutedUrlAssemblerInterface');
    $unrouted_url_assember
      ->expects($this->atLeastOnce())
      ->method('assemble')
      ->will($this->returnValue('/user/login'));
    $container_builder = new ContainerBuilder();
    $container_builder->set('path.validator', $path_validator);
    $container_builder->set('unrouted_url_assembler', $unrouted_url_assember);

    \Drupal::setContainer($container_builder);

    $response = $serviceController->handle();
    $this->assertTrue($response->isRedirect('/user/login'));
  }

  /**
   * Provides different query string params for tests.
   *
   * We want most test cases to behave accordingly for the matrix of
   * query string parameters that may be present on the request. This provider
   * will turn those params on or off.
   */
  public function parameterDataProvider() {
    return [
      // "returnto" not set.
      [FALSE],
      // "returnto" set.
      [TRUE],
    ];
  }

  /**
   * Assert user redirected to homepage when controller invoked.
   */
  private function assertRedirectedToFrontPageOnHandle($serviceController) {
    // URL Generator will generate a path to the homepage.
    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->with('<front>')
      ->will($this->returnValue('http://example.com/front'));

    $response = $serviceController->handle();
    $this->assertTrue($response->isRedirect('http://example.com/front'));
  }

  /**
   * Assert that the destination query param is set when returnto is present.
   */
  private function assertDestinationSetFromReturnTo() {
    $this->queryBag->expects($this->once())
      ->method('set')
      ->with('destination')
      ->will($this->returnValue('node/1'));
  }

  /**
   * Asserts that validation is executed.
   */
  private function assertSuccessfulValidation($returnto, $for_proxy = FALSE) {
    $service_params = [];
    if ($returnto) {
      $service_params['returnto'] = 'node/1';
    }

    $validation_data = new CasPropertyBag('testuser');
    if ($for_proxy) {
      $validation_data->setPgt('testpgt');
    }

    // Validation service should be called for that ticket.
    $this->casValidator->expects($this->once())
      ->method('validateTicket')
      ->with($this->equalTo('ST-foobar'), $this->equalTo($service_params))
      ->will($this->returnValue($validation_data));
  }

  /**
   * Mock our request and query bags for the provided parameters.
   *
   * This method accepts each possible parameter that the Sevice Controller
   * may need to deal with. Each parameter passed in should just be TRUE or
   * FALSE. If it's TRUE, we also mock the "get" method for the appropriate
   * parameter bag to return some predefined value.
   *
   * @param bool $returnto
   *   If returnto param should be set.
   * @param bool $logout_request
   *   If logoutRequest param should be set.
   * @param bool $ticket
   *   If ticket param should be set.
   */
  private function setupRequestParameters($returnto, $logout_request, $ticket) {
    // Request params.
    $map = [
      ['logoutRequest', $logout_request],
    ];
    $this->requestBag->expects($this->any())
      ->method('has')
      ->will($this->returnValueMap($map));

    $map = [];
    if ($logout_request === TRUE) {
      $map[] = ['logoutRequest', NULL, '<foobar/>'];
    }
    if (!empty($map)) {
      $this->requestBag->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));
    }

    // Query string params.
    $map = [
      ['returnto', $returnto],
      ['ticket', $ticket],
    ];
    $this->queryBag->expects($this->any())
      ->method('has')
      ->will($this->returnValueMap($map));

    $map = [];
    if ($returnto === TRUE) {
      $map[] = ['returnto', NULL, 'node/1'];
    }
    if ($ticket === TRUE) {
      $map[] = ['ticket', NULL, 'ST-foobar'];
    }
    if (!empty($map)) {
      $this->queryBag->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));
    }

    // Query string "all" method should include all params.
    $all = [];
    if ($returnto) {
      $all['returnto'] = 'node/1';
    }
    if ($ticket) {
      $all['ticket'] = 'ST-foobar';
    }
    $this->queryBag->method('all')
      ->will($this->returnValue($all));
  }

}
