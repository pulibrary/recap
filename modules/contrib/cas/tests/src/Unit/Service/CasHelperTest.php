<?php

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\cas\Service\CasHelper;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Utility\Token;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

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

    $this->loggerFactory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactory');
    $this->loggerChannel = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();
    $this->loggerFactory->expects($this->any())
      ->method('get')
      ->with('cas')
      ->will($this->returnValue($this->loggerChannel));
    $this->token = $this->prophesize(Token::class);
    $this->token->replace('Use <a href="[cas:login-url]">CAS login</a>')
      ->willReturn('Use <a href="/caslogin">CAS login</a>');
    $this->token->replace('<script>alert("Hacked!");</script>')
      ->willReturn('<script>alert("Hacked!");</script>');
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
    return [
      [
        [],
        'https://example.com/client',
      ],
      [
        ['returnto' => 'node/1'],
        'https://example.com/client?returnto=node%2F1',
      ],
    ];
  }

  /**
   * Test the logging capability.
   *
   * @covers ::log
   * @covers ::__construct
   */
  public function testLogWhenDebugTurnedOn() {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'advanced.debug_log' => TRUE,
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, $this->loggerFactory, $this->token->reveal());

    // The actual logger should be called twice.
    $this->loggerChannel->expects($this->exactly(2))
      ->method('log');

    $cas_helper->log(LogLevel::DEBUG, 'This is a debug log');
    $cas_helper->log(LogLevel::ERROR, 'This is an error log');
  }

  /**
   * Test our log wrapper when debug logging is off.
   *
   * @covers ::log
   * @covers ::__construct
   */
  public function testLogWhenDebugTurnedOff() {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'advanced.debug_log' => FALSE,
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, $this->loggerFactory, $this->token->reveal());

    // The actual logger should only called once, when we log an error.
    $this->loggerChannel->expects($this->once())
      ->method('log');

    $cas_helper->log(LogLevel::DEBUG, 'This is a debug log');
    $cas_helper->log(LogLevel::ERROR, 'This is an error log');
  }

  /**
   * @covers ::handleReturnToParameter
   */
  public function testHandleReturnToParameter() {
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'advanced.debug_log' => FALSE,
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, new LoggerChannelFactory(), $this->token->reveal());

    $request = new Request(['returnto' => 'node/1']);

    $this->assertFalse($request->query->has('destination'));
    $this->assertSame('node/1', $request->query->get('returnto'));

    $cas_helper->handleReturnToParameter($request);

    // Check that the 'returnto' has been copied to 'destination'.
    $this->assertSame('node/1', $request->query->get('destination'));
    $this->assertSame('node/1', $request->query->get('returnto'));
  }

  /**
   * Tests the message generator.
   *
   * @covers ::getMessage
   */
  public function testGetMessage() {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'arbitrary_message' => 'Use <a href="[cas:login-url]">CAS login</a>',
        'messages' => [
          'empty_message' => '',
          'do_not_trust_user_input' => '<script>alert("Hacked!");</script>',
        ],
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, $this->loggerFactory, $this->token->reveal());

    $message = $cas_helper->getMessage('arbitrary_message');
    $this->assertInstanceOf(FormattableMarkup::class, $message);
    $this->assertEquals('Use <a href="/caslogin">CAS login</a>', $message);

    // Empty message.
    $message = $cas_helper->getMessage('messages.empty_message');
    $this->assertSame('', $message);

    // Check hacker entered message.
    $message = $cas_helper->getMessage('messages.do_not_trust_user_input');
    // Check that the dangerous tags were stripped-out.
    $this->assertEquals('alert("Hacked!");', $message);
  }

}
