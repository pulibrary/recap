<?php

/**
 * @file
 * Contains Drupal\Tests\cas\Unit\Service\CasHelperTest.
 */

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\Service\CasHelper;

/**
 * CasHelper unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasHelper
 */
class CasHelperTest extends UnitTestCase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * The mocked Url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $loggerFactory;

  /**
   * The mocked log channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $loggerChannel;

  /**
   * The session storage.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->urlGenerator = $this->getMock('\Drupal\Core\Routing\UrlGeneratorInterface');
    $this->connection = $this->getMockBuilder('\Drupal\Core\Database\Connection')
                             ->disableOriginalConstructor()
                             ->getMock();
    $this->loggerFactory = $this->getMock('\Drupal\Core\Logger\LoggerChannelFactory');
    $this->loggerChannel = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannel')
                                ->disableOriginalConstructor()
                                ->getMock();
    $this->loggerFactory->expects($this->any())
      ->method('get')
      ->with('cas')
      ->will($this->returnValue($this->loggerChannel));

    $storage = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage')
                    ->setMethods(NULL)
                    ->getMock();
    $this->session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Session')
                          ->setConstructorArgs(array($storage))
                          ->setMethods(NULL)
                          ->getMock();
    $this->session->start();
  }

  /**
   * Test constructing the login URL.
   *
   * @covers ::getServerLoginUrl
   * @covers ::__construct
   * @covers ::getCasServiceUrl
   *
   * @dataProvider getServerLoginUrlDataProvider
   */
  public function testGetServerLoginUrl($service_params, $gateway, $result) {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
      ),
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);

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
    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->will($this->returnValue($return_value));
    $login_url = $cas_helper->getServerLoginUrl($service_params, $gateway);
    $this->assertEquals($result, $login_url);
  }

  /**
   * Provides parameters and expected return values for testGetServerLoginUrl.
   *
   * @return array
   *   The list of parameters and return values.
   *
   * @see \Drupal\Tests\cas\Unit\CasHelperTest::testGetServerLoginUrl()
   */
  public function getServerLoginUrlDataProvider() {
    return array(
      array(
        array(),
        FALSE,
        'https://example.com:443/cas/login?service=https%3A//example.com/client',
      ),
      array(
        array('returnto' => 'node/1'),
        FALSE,
        'https://example.com:443/cas/login?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1',
      ),
      array(
        array(),
        TRUE,
        'https://example.com:443/cas/login?gateway=true&service=https%3A//example.com/client',
      ),
      array(
        array('returnto' => 'node/1'),
        TRUE,
        'https://example.com:443/cas/login?gateway=true&service=https%3A//example.com/client%3Freturnto%3Dnode%252F1',
      ),
    );
  }

  /**
   * Test constructing the CAS Server base url.
   *
   * @covers ::getServerBaseUrl
   * @covers ::__construct
   */
  public function testGetServerBaseUrl() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
      ),
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);

    $this->assertEquals('https://example.com:443/cas/', $cas_helper->getServerBaseUrl());
  }

  /**
   * Test constructing the CAS Server validation url.
   *
   * @covers ::getServerValidateUrl
   * @covers ::formatProxyCallbackURL
   * @covers ::__construct
   *
   * @dataProvider getServerValidateUrlDataProvider
   */
  public function testGetServerValidateUrl($ticket, $service_params, $return, $is_proxy, $can_be_proxied, $protocol) {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => $protocol,
        'proxy.initialize' => $is_proxy,
        'proxy.can_be_proxied' => $can_be_proxied,
      ),
    ));
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

    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->will($this->returnValue($return_value));
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->will($this->returnValue('https://example.com/casproxycallback'));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);
    $this->assertEquals($return, $cas_helper->getServerValidateUrl($ticket, $service_params));

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
    for ($i = 0; $i < 10; $i++) {
      $ticket[$i] = $this->randomMachineName(24);
    }
    return array(
      array(
        $ticket[0],
        array(),
        'https://example.com:443/cas/validate?service=https%3A//example.com/client&ticket=' . $ticket[0],
        FALSE,
        FALSE,
        '1.0',
      ),

      array(
        $ticket[1],
        array('returnto' => 'node/1'),
        'https://example.com:443/cas/validate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[1],
        FALSE,
        FALSE,
        '1.0',
      ),

      array(
        $ticket[2],
        array(),
        'https://example.com:443/cas/serviceValidate?service=https%3A//example.com/client&ticket=' . $ticket[2],
        FALSE,
        FALSE,
        '2.0',
      ),

      array(
        $ticket[3],
        array('returnto' => 'node/1'),
        'https://example.com:443/cas/serviceValidate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[3],
        FALSE,
        FALSE,
        '2.0',
      ),

      array(
        $ticket[4],
        array(),
        'https://example.com:443/cas/proxyValidate?service=https%3A//example.com/client&ticket=' . $ticket[4],
        FALSE,
        TRUE,
        '2.0',
      ),

      array(
        $ticket[5],
        array('returnto' => 'node/1'),
        'https://example.com:443/cas/proxyValidate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[5],
        FALSE,
        TRUE,
        '2.0',
      ),

      array(
        $ticket[6],
        array(),
        'https://example.com:443/cas/serviceValidate?service=https%3A//example.com/client&ticket=' . $ticket[6] . '&pgtUrl=https%3A//example.com/casproxycallback',
        TRUE,
        FALSE,
        '2.0',
      ),

      array(
        $ticket[7],
        array('returnto' => 'node/1'),
        'https://example.com:443/cas/serviceValidate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[7] . '&pgtUrl=https%3A//example.com/casproxycallback',
        TRUE,
        FALSE,
        '2.0',
      ),

      array(
        $ticket[8],
        array(),
        'https://example.com:443/cas/proxyValidate?service=https%3A//example.com/client&ticket=' . $ticket[8] . '&pgtUrl=https%3A//example.com/casproxycallback',
        TRUE,
        TRUE,
        '2.0',
      ),

      array(
        $ticket[9],
        array('returnto' => 'node/1'), 'https://example.com:443/cas/proxyValidate?service=https%3A//example.com/client%3Freturnto%3Dnode%252F1&ticket=' . $ticket[9] . '&pgtUrl=https%3A//example.com/casproxycallback',
        TRUE,
        TRUE,
        '2.0',
      ),
    );
  }

  /**
   * Test setting the PGT in the session.
   *
   * @covers ::storePgtSession
   *
   * @dataProvider storePGTSessionDataProvider
   */
  public function testStorePgtSession($pgt_iou, $pgt) {
    $config_factory = $this->getConfigFactoryStub(array());
    $map = array(array($pgt_iou, $pgt));
    $cas_helper = $this->getMockBuilder('Drupal\cas\Service\CasHelper')
      ->setConstructorArgs(array($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session))
      ->setMethods(array('lookupPgtByPgtIou', 'deletePgtMappingByPgtIou'))
      ->getMock();
    $cas_helper->expects($this->once())
      ->method('lookupPgtByPgtIou')
      ->will($this->returnValueMap($map));

    $cas_helper->storePgtSession($pgt_iou);
    $this->assertEquals($pgt, $this->session->get('cas_pgt'));
  }

  /**
   * Provides parameters and return values for testStorePGTSession.
   *
   * @return array
   *   Parameters and return values.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasHelper::testStorePGTSession()
   */
  public function storePGTSessionDataProvider() {
    return array(
      array($this->randomMachineName(24), $this->randomMachineName(48)),
    );
  }

  /**
   * Test getting the CAS protocol version.
   *
   * @covers ::getCasProtocolVersion
   * @covers ::__construct
   */
  public function testGetCasProtocolVersion() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => '1.0',
      ),
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);
    $this->assertEquals('1.0', $cas_helper->getCasProtocolVersion());
  }

  /**
   * Test getting the SSL verification method.
   *
   * @covers ::getSslVerificationMethod
   */
  public function testGetSslVerificationMethod() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.verify' => 17,
      ),
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);
    $this->assertEquals(17, $cas_helper->getSslVerificationMethod());
  }

  /**
   * Test getting the CA PEM file.
   *
   * @covers ::getCertificateAuthorityPem
   * @covers ::__construct
   */
  public function testGetCertificateAuthorityPem() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.cert' => '/path/to/file/cert.pem',
      ),
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);
    $this->assertEquals('/path/to/file/cert.pem', $cas_helper->getCertificateAuthorityPem());
  }

  /**
   * Test getting the 'act as proxy' configuration.
   *
   * @covers ::isProxy
   * @covers ::__construct
   */
  public function testIsProxy() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'proxy.initialize' => TRUE,
      ),
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);
    $this->assertEquals(TRUE, $cas_helper->isProxy());
  }

  /**
   * Test getting the 'can be proxied' configuration.
   *
   * @covers ::canBeProxied
   * @covers ::__construct
   */
  public function testCanBeProxied() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'proxy.can_be_proxied' => TRUE,
      ),
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);
    $this->assertEquals(TRUE, $cas_helper->canBeProxied());
  }

  /**
   * Test getting the proxy chain configuration.
   *
   * @covers ::getProxyChains
   * @covers ::__construct
   */
  public function testGetProxyChains() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'proxy.proxy_chains' => 'https://example.com',
      ),
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);
    $this->assertEquals('https://example.com', $cas_helper->getProxyChains());
  }

  /**
   * Test the logging capability.
   *
   * @covers ::log
   * @covers ::__construct
   */
  public function testLoggingOn() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'debugging.log' => TRUE,
      )
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);
    $this->loggerChannel->expects($this->once())
      ->method('log');
    $cas_helper->log('This is a test.');
  }

  /**
   * Test to make sure we don't log when we're not configured to.
   *
   * @covers ::log
   * @covers ::__construct
   */
  public function testLoggingOff() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'debugging.log' => FALSE,
      )
    ));
    $cas_helper = new CasHelper($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session);
    $this->loggerChannel->expects($this->never())
      ->method('log');
    $cas_helper->log('This is a test, but should not be logged as such.');
  }

  /**
   * Test generating the server logout url with no service parameter.
   *
   * @covers ::getServerLogoutUrl
   */
  public function testGetServerLogoutUrlNoRedirect() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'logout.logout_destination' => '',
      )
    ));
    $cas_helper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->setConstructorArgs(array($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session))
      ->setMethods(array('getServerBaseUrl'))
      ->getMock();
    $cas_helper->expects($this->once())
      ->method('getServerBaseUrl')
      ->will($this->returnValue('https://example.com/'));
    $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
                    ->disableOriginalConstructor()
                    ->getMock();
    $this->assertEquals('https://example.com/logout', $cas_helper->getServerLogoutUrl($request));
  }

  /**
   * Test generating the logout url with front page specified.
   *
   * @covers ::getServerLogoutUrl
   */
  public function testGetServerLogoutUrlFrontPage() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'logout.logout_destination' => '<front>',
      )
    ));
    $cas_helper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->setConstructorArgs(array($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session))
      ->setMethods(array('getServerBaseUrl'))
      ->getMock();
    $cas_helper->expects($this->once())
      ->method('getServerBaseUrl')
      ->will($this->returnValue('https://example.com/'));
    $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
                    ->disableOriginalConstructor()
                    ->getMock();
    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->will($this->returnValue('https://example.com/frontpage'));
    $this->assertEquals('https://example.com/logout?service=https%3A//example.com/frontpage', $cas_helper->getServerLogoutUrl($request));
  }

  /**
   * Test generating the logout url with external url specified.
   *
   * @covers ::getServerLogoutUrl
   */
  public function testGetServerLogoutUrlExternalUrl() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'logout.logout_destination' => 'https://foo.example.com',
      )
    ));
    $cas_helper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->setConstructorArgs(array($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session))
      ->setMethods(array('getServerBaseUrl', 'isExternal'))
      ->getMock();
    $cas_helper->expects($this->once())
      ->method('getServerBaseUrl')
      ->will($this->returnValue('https://example.com/'));
    $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
                    ->disableOriginalConstructor()
                    ->getMock();
    $cas_helper->expects($this->once())
      ->method('isExternal')
      ->will($this->returnValue(TRUE));
    $this->assertEquals('https://example.com/logout?service=https%3A//foo.example.com', $cas_helper->getServerLogoutUrl($request));
  }

  /**
   * Test generating the logout url with an internal Drupal path specified.
   *
   * @covers ::getServerLogoutUrl
   */
  public function testGetServerLogoutUrlInternalPath() {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'logout.logout_destination' => 'node/1',
      )
    ));
    $cas_helper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->setConstructorArgs(array($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session))
      ->setMethods(array('getServerBaseUrl', 'isExternal'))
      ->getMock();
    $cas_helper->expects($this->once())
      ->method('getServerBaseUrl')
      ->will($this->returnValue('https://example.com/'));
    $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
                    ->disableOriginalConstructor()
                    ->getMock();
    $request->expects($this->once())
      ->method('getSchemeAndHttpHost')
      ->will($this->returnValue('https://bar.example.com'));
    $cas_helper->expects($this->once())
      ->method('isExternal')
      ->will($this->returnValue(FALSE));
    $this->assertEquals('https://example.com/logout?service=https%3A//bar.example.com/node/1', $cas_helper->getServerLogoutUrl($request));
  }

  /**
   * Test providing CAS logout override.
   *
   * @dataProvider provideCasLogoutOverrideDataProvider
   */
  public function testProvideCasLogoutOverride($config, $cas_authenticated) {
    $config_factory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'logout.cas_logout' => $config,
      )
    ));
    $cas_helper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->setConstructorArgs(array($config_factory, $this->urlGenerator, $this->connection, $this->loggerFactory, $this->session))
      ->setMethods(array('isCasSession'))
      ->getMock();
    $cas_helper->expects($this->any())
      ->method('isCasSession')
      ->willReturn($cas_authenticated);
    $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
                    ->disableOriginalConstructor()
                    ->getMock();
    $session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session')
                    ->disableOriginalConstructor()
                    ->setMethods(['getId'])
                    ->getMock();
    if ($config) {
      $request->expects($this->once())
       ->method('getSession')
       ->willReturn($session);
      $session->expects($this->once())
       ->method('getId')
       ->willReturn($this->randomMachineName(8));
    }

    $this->assertEquals($config && $cas_authenticated, $cas_helper->provideCasLogoutOverride($request));
  }

  /**
   * Provide configuration for testProvideCasLogoutOverride()
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasHelperTest::testProvideCasLogoutOverride
   */
  public function provideCasLogoutOverrideDataProvider() {
    return [
      [TRUE, TRUE],
      [TRUE, FALSE],
      [FALSE, TRUE],
      [FALSE, FALSE],
    ];
  }
}
