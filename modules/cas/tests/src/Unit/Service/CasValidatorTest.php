<?php

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\Service\CasValidator;
use Drupal\cas\CasPropertyBag;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Middleware;
use Drupal\cas\Service\CasHelper;
use Symfony\Component\EventDispatcher\Event;

/**
 * CasValidator unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasValidator
 */
class CasValidatorTest extends UnitTestCase {

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $eventDispatcher;

  /**
   * Storage for events during tests.
   *
   * @var array
   */
  protected $events;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Mock event dispatcher to dispatch events.
    $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Dispatch an event.
   *
   * @param string $event_name
   *   Name of event fired.
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   Event fired.
   */
  public function dispatchEvent($event_name, Event $event) {
    $this->events[$event_name] = $event;
    switch ($event_name) {
      case CasHelper::EVENT_PRE_VALIDATE:
        $event->setValidationPath("customPath");
        $event->setParameter("foo", "bar");
        break;

      case CasHelper::EVENT_POST_VALIDATE:
        $propertyBag = $event->getCasPropertyBag();
        $propertyBag->setAttribute('email', ['modified@example.com']);
        break;

    }
  }

  /**
   * Test validation of Cas tickets.
   *
   * @covers ::__construct
   * @covers ::validateTicket
   * @covers ::validateVersion1
   * @covers ::validateVersion2
   * @covers ::verifyProxyChain
   * @covers ::parseAllowedProxyChains
   * @covers ::parseServerProxyChain
   *
   * @dataProvider validateTicketDataProvider
   */
  public function testValidateTicket($version, $ticket, $username, $response, $is_proxy, $can_be_proxied, $proxy_chains, $ssl_verification) {
    // Setup Guzzle to return a mock response.
    $mock = new MockHandler([new Response(200, [], $response)]);
    $handler = HandlerStack::create($mock);
    $transactions = [];
    $history = Middleware::history($transactions);
    $handler->push($history);
    $httpClient = new Client(['handler' => $handler]);

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => $version,
        'server.verify' => $ssl_verification,
        'server.cert' => 'foo',
        'proxy.initialize' => $is_proxy,
        'proxy.can_be_proxied' => $can_be_proxied,
        'proxy.proxy_chains' => $proxy_chains,
      ],
    ]);

    $casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->disableOriginalConstructor()
      ->getMock();

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);

    $property_bag = $casValidator->validateTicket($ticket);

    $this->assertEquals($username, $property_bag->getUsername());
  }

  /**
   * Provides parameters and return values for testValidateTicket.
   *
   * @return array
   *   Parameters and return values.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasValidatorTest::testValidateTicket
   */
  public function validateTicketDataProvider() {
    // First test case: protocol version 1.
    $user1 = $this->randomMachineName(8);
    $response1 = "yes\n$user1\n";
    $params[] = [
      '1.0',
      $this->randomMachineName(24),
      $user1,
      $response1,
      FALSE,
      FALSE,
      '',
      CasHelper::CA_CUSTOM,
    ];

    // Second test case: protocol version 2, no proxies.
    $user2 = $this->randomMachineName(8);
    $response2 = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
        <cas:authenticationSuccess>
          <cas:user>$user2</cas:user>
        </cas:authenticationSuccess>
       </cas:serviceResponse>";
    $params[] = [
      '2.0',
      $this->randomMachineName(24),
      $user2,
      $response2,
      FALSE,
      FALSE,
      '',
      CasHelper::CA_NONE,
    ];

    // Third test case: protocol version 2, initialize as proxy.
    $user3 = $this->randomMachineName(8);
    $pgt_iou3 = $this->randomMachineName(24);
    $response3 = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
         <cas:authenticationSuccess>
           <cas:user>$user3</cas:user>
             <cas:proxyGrantingTicket>PGTIOU-$pgt_iou3
           </cas:proxyGrantingTicket>
         </cas:authenticationSuccess>
       </cas:serviceResponse>";
    $params[] = [
      '2.0',
      $this->randomMachineName(24),
      $user3,
      $response3,
      TRUE,
      FALSE,
      '',
      CasHelper::CA_DEFAULT,
    ];

    // Fourth test case: protocol version 2, can be proxied.
    $user4 = $this->randomMachineName(8);
    $proxy_chains = '/https:\/\/example\.com/ /https:\/\/foo\.com/' . PHP_EOL . '/https:\/\/bar\.com/';
    $response4 = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
         <cas:authenticationSuccess>
           <cas:user>$user4</cas:user>
             <cas:proxies>
               <cas:proxy>https://example.com</cas:proxy>
               <cas:proxy>https://foo.com</cas:proxy>
             </cas:proxies>
         </cas:authenticationSuccess>
       </cas:serviceResponse>";
    $params[] = [
      '2.0',
      $this->randomMachineName(24),
      $user4,
      $response4,
      FALSE,
      TRUE,
      $proxy_chains,
      CasHelper::CA_DEFAULT,
    ];

    // Fifth test case: protocol version 2, proxy in both directions.
    $user5 = $this->randomMachineName(8);
    $pgt_iou5 = $this->randomMachineName(24);
    // Use the same proxy chains as the fourth test case.
    $response5 = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:authenticationSuccess>
          <cas:user>$user5</cas:user>
          <cas:proxyGrantingTicket>PGTIOU-$pgt_iou5</cas:proxyGrantingTicket>
          <cas:proxies>
            <cas:proxy>https://https://bar.com</cas:proxy>
          </cas:proxies>
         </cas:authenticationSuccess>
      </cas:serviceResponse>";
    $params[] = [
      '2.0',
      $this->randomMachineName(24),
      $user5,
      $response5,
      TRUE,
      TRUE,
      $proxy_chains,
      CasHelper::CA_DEFAULT,
    ];

    return $params;
  }

  /**
   * Test validation failure conditions for the correct exceptions.
   *
   * @covers ::validateTicket
   * @covers ::validateVersion1
   * @covers ::validateVersion2
   * @covers ::verifyProxyChain
   * @covers ::parseAllowedProxyChains
   * @covers ::parseServerProxyChain
   *
   * @dataProvider validateTicketExceptionDataProvider
   */
  public function testValidateTicketException($version, $response, $is_proxy, $can_be_proxied, $proxy_chains, $exception, $exception_message, $http_client_exception) {
    if ($http_client_exception) {
      $mock = new MockHandler([
        new RequestException($exception_message, new Request('GET', 'test')),
      ]);
    }
    else {
      $mock = new MockHandler([new Response(200, [], $response)]);
    }
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->disableOriginalConstructor()
      ->getMock();

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => $version,
        'proxy.initialize' => $is_proxy,
        'proxy.can_be_proxied' => $can_be_proxied,
        'proxy.proxy_chains' => $proxy_chains,
      ],
    ]);

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);

    $this->setExpectedException($exception, $exception_message);
    $ticket = $this->randomMachineName(24);
    $casValidator->validateTicket($ticket, []);
  }

  /**
   * Provides parameters and return values for testValidateTicketException.
   *
   * @return array
   *   Parameters and return values.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasValidatorTest::testValidateTicketException
   */
  public function validateTicketExceptionDataProvider() {
    /* There are nine different exception messages that can occur. We test for
     * each one. Currently, they are all of type 'CasValidateException', so we
     * set that up front. If that changes in the future, we can rework this bit
     * without changing the function signature.
     */
    $exception_type = '\Drupal\cas\Exception\CasValidateException';

    /* The first exception is actually a 'recasting' of an http client
     * exception.
     */
    $params[] = [
      '2.0',
      '',
      FALSE,
      FALSE,
      '',
      $exception_type,
      'External http client exception',
      TRUE,
    ];

    /* Protocol version 1 can throw two exceptions: 'no' text is found, or
     * 'yes' text is not found (in that order).
     */
    $params[] = [
      '1.0',
      "no\n\n",
      FALSE,
      FALSE,
      '',
      $exception_type,
      'Ticket did not pass validation.',
      FALSE,
    ];
    $params[] = [
      '1.0',
      "Foo\nBar?\n",
      FALSE,
      FALSE,
      '',
      $exception_type,
      'Malformed response from CAS server.',
      FALSE,
    ];

    // Protocol version 2: Malformed XML.
    $params[] = [
      '2.0',
      "<> </ </> <<",
      FALSE,
      FALSE,
      '',
      $exception_type,
      'XML from CAS server is not valid.',
      FALSE,
    ];

    // Protocol version 2: Authentication failure.
    $ticket = $this->randomMachineName(24);
    $params[] = [
      '2.0',
      '<cas:serviceResponse xmlns:cas="http://example.com/cas">
      <cas:authenticationFailure code="INVALID_TICKET">
      Ticket ' . $ticket . ' not recognized
      </cas:authenticationFailure>
      </cas:serviceResponse>',
      FALSE,
      FALSE,
      '',
      $exception_type,
      "Error Code INVALID_TICKET: Ticket $ticket not recognized",
      FALSE,
    ];

    // Protocol version 2: Neither authentication failure nor authentication
    // succes found.
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authentication>
      Username
      </cas:authentication>
      </cas:serviceResponse>",
      FALSE,
      FALSE,
      '',
      $exception_type,
      "XML from CAS server is not valid.",
      FALSE,
    ];

    // Protocol version 2: No user specified in authenticationSuccess.
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authenticationSuccess>
      Username
      </cas:authenticationSuccess>
      </cas:serviceResponse>",
      FALSE,
      FALSE,
      '',
      $exception_type,
      "No user found in ticket validation response.",
      FALSE,
    ];

    // Protocol version 2: Proxy chain mismatch.
    $proxy_chains = '/https:\/\/example\.com/ /https:\/\/foo\.com/' . PHP_EOL . '/https:\/\/bar\.com/';
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authenticationSuccess>
      <cas:user>username</cas:user>
      <cas:proxies>
      <cas:proxy>https://example.com</cas:proxy>
      <cas:proxy>https://bar.com</cas:proxy>
      </cas:proxies>
      </cas:authenticationSuccess>
      </cas:serviceResponse>",
      FALSE,
      TRUE,
      $proxy_chains,
      $exception_type,
      "Proxy chain did not match allowed list.",
      FALSE,
    ];

    // Protocol version 2: Proxy chain mismatch with non-regex proxy chain.
    $proxy_chains = 'https://bar.com /https:\/\/foo\.com/' . PHP_EOL . '/https:\/\/bar\.com/';
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authenticationSuccess>
      <cas:user>username</cas:user>
      <cas:proxies>
      <cas:proxy>https://example.com</cas:proxy>
      <cas:proxy>https://bar.com</cas:proxy>
      </cas:proxies>
      </cas:authenticationSuccess>
      </cas:serviceResponse>",
      FALSE,
      TRUE,
      $proxy_chains,
      $exception_type,
      "Proxy chain did not match allowed list.",
      FALSE,
    ];

    // Protocol version 2: No PGTIOU provided when initialized as proxy.
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authenticationSuccess>
      <cas:user>username</cas:user>
      </cas:authenticationSuccess>
      </cas:serviceResponse>",
      TRUE,
      FALSE,
      '',
      $exception_type,
      "Proxy initialized, but no PGTIOU provided in response.",
      FALSE,
    ];

    // Unknown protocol version.
    $params[] = [
      'foobarbaz',
      "<text>",
      FALSE,
      FALSE,
      '',
      $exception_type,
      "Unknown CAS protocol version specified: foobarbaz",
      FALSE,
    ];

    return $params;
  }

  /**
   * Test parsing out CAS attributes from response.
   *
   * @covers ::validateVersion2
   * @covers ::parseAttributes
   */
  public function testParseAttributes() {
    $ticket = $this->randomMachineName(8);
    $service_params = [];
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
    <cas:authenticationSuccess>
    <cas:user>username</cas:user>
    <cas:attributes>
    <cas:email>foo@example.com</cas:email>
    <cas:memberof>cn=foo,o=example</cas:memberof>
    <cas:memberof>cn=bar,o=example</cas:memberof>
    </cas:attributes>
    </cas:authenticationSuccess>
    </cas:serviceResponse>";
    $mock = new MockHandler([new Response(200, [], $response)]);
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.version' => '2.0',
      ],
    ]);

    $casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->disableOriginalConstructor()
      ->getMock();

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);
    $expected_bag = new CasPropertyBag('username');
    $expected_bag->setAttributes([
      'email' => ['foo@example.com'],
      'memberof' => ['cn=foo,o=example', 'cn=bar,o=example'],
    ]);
    $actual_bag = $casValidator->validateTicket($ticket, $service_params);
    $this->assertEquals($expected_bag, $actual_bag);
  }

  /**
   * Tests the post validation event dispatched by the listener.
   *
   * @covers ::validateTicket
   */
  public function testPostValidateEvent() {
    // Mock up listener on dispatched event.
    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback([$this, 'dispatchEvent']);
    $this->events = [];

    $ticket = $this->randomMachineName(8);
    $service_params = [];
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
    <cas:authenticationSuccess>
    <cas:user>username</cas:user>
    <cas:attributes>
    <cas:email>foo@example.com</cas:email>
    <cas:memberof>cn=foo,o=example</cas:memberof>
    <cas:memberof>cn=bar,o=example</cas:memberof>
    </cas:attributes>
    </cas:authenticationSuccess>
    </cas:serviceResponse>";
    $mock = new MockHandler([new Response(200, [], $response)]);
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.version' => '2.0',
      ],
    ]);

    $casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->disableOriginalConstructor()
      ->getMock();

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);
    $expected_bag = new CasPropertyBag('username');
    $expected_bag->setAttributes([
      'email' => ['modified@example.com'],
      'memberof' => ['cn=foo,o=example', 'cn=bar,o=example'],
    ]);
    $actual_bag = $casValidator->validateTicket($ticket, $service_params);
    $this->assertEquals($expected_bag, $actual_bag);
  }

  /**
   * Tests the pre validation event dispatched by the listener.
   *
   * @covers ::validateTicket
   */
  public function testPreValidateEvent() {
    // Mock up listener on dispatched event.
    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback([$this, 'dispatchEvent']);
    $this->events = [];

    $ticket = $this->randomMachineName(8);
    $mock = new MockHandler([new Response(200, [], "")]);
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.version' => '2.0',
      ],
    ]);

    $casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->disableOriginalConstructor()
      ->getMock();

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);
    $expected_url = "customPath?service&ticket=" . $ticket . '&foo=bar';
    $actual_url = $casValidator->getServerValidateUrl($ticket);
    $this->assertEquals($expected_url, $actual_url);
  }

  /**
   * Test constructing the CAS Server validation url.
   *
   * @param string $ticket
   *   Ticket given for the test.
   * @param array $service_params
   *   Service paramters given for the test.
   * @param string $return
   *   Expected return value.
   * @param bool $is_proxy
   *   Expected value for isProxy method call.
   * @param bool $can_be_proxied
   *   Can be proxied value for the test.
   * @param string $protocol
   *   Protocol used for the test.
   *
   * @covers ::getServerValidateUrl
   * @covers ::formatProxyCallbackURL
   * @covers ::__construct
   *
   * @dataProvider getServerValidateUrlDataProvider
   */
  public function testGetServerValidateUrl($ticket, array $service_params, $return, $is_proxy, $can_be_proxied, $protocol) {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => $protocol,
        'proxy.initialize' => $is_proxy,
        'proxy.can_be_proxied' => $can_be_proxied,
      ],
    ]);
    if (!empty($service_params)) {
      $params = '';
      foreach ($service_params as $key => $value) {
        $params .= '&' . $key . '=' . urlencode($value);
      }
      $params = '?' . substr($params, 1);
      $return_value = 'https://example.com/client' . $params;
    }
    else {
      $return_value = 'https://example.com/client';
    }

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');
    $urlGenerator->expects($this->once())
      ->method('generate')
      ->will($this->returnValue($return_value));
    $urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->will($this->returnValue('https://example.com/casproxycallback'));

    $httpClient = $this->createMock('GuzzleHttp\Client');

    $casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->disableOriginalConstructor()
      ->getMock();

    $casHelper->method('getServerBaseUrl')
      ->willReturn('https://example-server.com/cas/');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);
    $this->assertEquals($return, $casValidator->getServerValidateUrl($ticket, $service_params));

  }

  /**
   * Provides parameters and return values for testGetServerValidateUrl.
   *
   * @return array
   *   The list of parameters and return values.
   *
   * @see \Drupal\Tests\cas\Unit\CasHelperTest::testGetServerValidateUrl()
   */
  public function getServerValidateUrlDataProvider() {
    /*
     * There are ten possible permutations here: protocol version 1.0 does not
     * support proxying, so we check with and without additional parameters in
     * the service URL. Protocol 2.0 supports proxying, so there are 2^3 = 8
     * permutations to check here: with and without additional parameters,
     * whether or not to initialize as a proxy, and whether or not the client
     * can be proxied.
     */
    $ticket = '';
    for ($i = 0; $i < 10; $i++) {
      $ticket[$i] = $this->randomMachineName(24);
    }
    return [
      [
        $ticket[0],
        [],
        'https://example-server.com/cas/validate?service=https%3A//example.com/client&ticket=' . $ticket[0],
        FALSE,
        FALSE,
        '1.0',
      ],
      [
        $ticket[1],
        ['returnto' => 'node/1'],
        'https://example-server.com/cas/validate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[1],
        FALSE,
        FALSE,
        '1.0',
      ],
      [
        $ticket[2],
        [],
        'https://example-server.com/cas/serviceValidate?service=https%3A//example.com/client&ticket=' . $ticket[2],
        FALSE,
        FALSE,
        '2.0',
      ],
      [
        $ticket[3],
        ['returnto' => 'node/1'],
        'https://example-server.com/cas/serviceValidate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[3],
        FALSE,
        FALSE,
        '2.0',
      ],
      [
        $ticket[4],
        [],
        'https://example-server.com/cas/proxyValidate?service=https%3A//example.com/client&ticket=' . $ticket[4],
        FALSE,
        TRUE,
        '2.0',
      ],
      [
        $ticket[5],
        ['returnto' => 'node/1'],
        'https://example-server.com/cas/proxyValidate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[5],
        FALSE,
        TRUE,
        '2.0',
      ],
      [
        $ticket[6],
        [],
        'https://example-server.com/cas/serviceValidate?service=https%3A//example.com/client&ticket=' . $ticket[6] . '&pgtUrl=https%3A//example.com/casproxycallback',
        TRUE,
        FALSE,
        '2.0',
      ],
      [
        $ticket[7],
        ['returnto' => 'node/1'],
        'https://example-server.com/cas/serviceValidate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[7] . '&pgtUrl=https%3A//example.com/casproxycallback',
        TRUE,
        FALSE,
        '2.0',
      ],
      [
        $ticket[8],
        [],
        'https://example-server.com/cas/proxyValidate?service=https%3A//example.com/client&ticket=' . $ticket[8] . '&pgtUrl=https%3A//example.com/casproxycallback',
        TRUE,
        TRUE,
        '2.0',
      ],
      [
        $ticket[9],
        ['returnto' => 'node/1'],
        'https://example-server.com/cas/proxyValidate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[9] . '&pgtUrl=https%3A//example.com/casproxycallback',
        TRUE,
        TRUE,
        '2.0',
      ],
    ];
  }

}
