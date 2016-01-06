<?php

/**
 * @file
 * Contains Drupal\Tests\cas\Unit\Service\CasValidatorTest.
 */

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\Service\CasValidator;
use GuzzleHttp\Exception\ClientException;
use Drupal\cas\CasPropertyBag;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Middleware;
use Drupal\cas\Service\CasHelper;

/**
 * CasHelper unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasValidator
 */
class CasValidatorTest extends UnitTestCase {

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
    $mock = new MockHandler([new Response(200, array(), $response)]);
    $handler = HandlerStack::create($mock);
    $container = [];
    $history = Middleware::history($container);
    $handler->push($history);
    $httpClient = new Client(['handler' => $handler]);

    $casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
                      ->disableOriginalConstructor()
                      ->getMock();
    $casValidator = new CasValidator($httpClient, $casHelper);

    $casHelper->expects($this->any())
              ->method('getCasProtocolVersion')
              ->will($this->returnValue($version));

    $casHelper->expects($this->once())
              ->method('getSslVerificationMethod')
              ->willReturn($ssl_verification);

    $casHelper->expects($this->any())
              ->method('getCertificateAuthorityPem')
              ->will($this->returnValue('foo'));

    $casHelper->expects($this->any())
              ->method('isProxy')
              ->will($this->returnValue($is_proxy));

    $casHelper->expects($this->any())
              ->method('canBeProxied')
              ->will($this->returnValue($can_be_proxied));

    $casHelper->expects($this->any())
              ->method('getProxyChains')
              ->will($this->returnValue($proxy_chains));

    $property_bag = $casValidator->validateTicket($version, $ticket, array());

    // Test that we sent the correct ssl option to the http client.
    foreach ($container as $transaction) {
      switch ($ssl_verification) {
        case CasHelper::CA_CUSTOM:
          $this->assertEquals('foo', $transaction['options']['verify']);
          break;
        case CasHelper::CA_NONE:
          $this->assertEquals(FALSE, $transaction['options']['verify']);
          break;
        default:
          $this->assertEquals(TRUE, $transaction['options']['verify']);
      }
    }
    $this->assertEquals($username, $property_bag->getUsername());
  }

  /**
   * Provides parameters and return values for testValidateTicket.
   *
   * @return array
   *   Parameters and return values.
   *
   * @see \Drupal\Tests\cas\Service\CasValidatorTest::testValidateTicket
   */
  public function validateTicketDataProvider() {
    // First test case: protocol version 1.
    $user1 = $this->randomMachineName(8);
    $response1 = "yes\n$user1\n";
    $params[] = array(
      '1.0',
      $this->randomMachineName(24),
      $user1,
      $response1,
      FALSE,
      FALSE,
      '',
      CasHelper::CA_CUSTOM,
    );

    // Second test case: protocol version 2, no proxies.
    $user2 = $this->randomMachineName(8);
    $response2 = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
        <cas:authenticationSuccess>
          <cas:user>$user2</cas:user>
        </cas:authenticationSuccess>
       </cas:serviceResponse>";
    $params[] = array(
      '2.0',
      $this->randomMachineName(24),
      $user2,
      $response2,
      FALSE,
      FALSE,
      '',
      CasHelper::CA_NONE,
    );

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
    $params[] = array(
      '2.0',
      $this->randomMachineName(24),
      $user3,
      $response3,
      TRUE,
      FALSE,
      '',
      CasHelper::CA_DEFAULT,
    );

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
    $params[] = array(
      '2.0',
      $this->randomMachineName(24),
      $user4,
      $response4,
      FALSE,
      TRUE,
      $proxy_chains,
      CasHelper::CA_DEFAULT,
    );

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
    $params[] = array(
      '2.0',
      $this->randomMachineName(24),
      $user5,
      $response5,
      TRUE,
      TRUE,
      $proxy_chains,
      CasHelper::CA_DEFAULT,
    );

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
      $mock = new MockHandler([new RequestException($exception_message, new Request('GET', 'test'))]);
    }
    else {
      $mock = new MockHandler([new Response(200, array(), $response)]);
    }
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
                      ->disableOriginalConstructor()
                      ->getMock();
    $casValidator = new CasValidator($httpClient, $casHelper);

    $casHelper->expects($this->any())
              ->method('getCasProtocolVersion')
              ->will($this->returnValue($version));

    $casHelper->expects($this->any())
              ->method('isProxy')
              ->will($this->returnValue($is_proxy));

    $casHelper->expects($this->any())
              ->method('canBeProxied')
              ->will($this->returnValue($can_be_proxied));

    $casHelper->expects($this->any())
              ->method('getProxyChains')
              ->will($this->returnValue($proxy_chains));

    $this->setExpectedException($exception, $exception_message);
    $ticket = $this->randomMachineName(24);
    $casValidator->validateTicket($ticket, array());
  }

  /**
   * Provides parameters and return values for testValidateTicketException.
   *
   * @return array
   *   Parameters and return values.
   *
   * @see \Drupal\Tests\cas\Service\CasValidatorTest::testValidateTicketException
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
    $params[] = array('2.0', '', FALSE, FALSE, '', $exception_type, 'External http client exception', TRUE);

    /* Protocol version 1 can throw two exceptions: 'no' text is found, or
     * 'yes' text is not found (in that order).
     */
    $params[] = array(
      '1.0',
      "no\n\n",
      FALSE,
      FALSE,
      '',
      $exception_type,
      'Ticket did not pass validation.',
      FALSE,
    );
    $params[] = array(
      '1.0',
      "Foo\nBar?\n",
      FALSE,
      FALSE,
      '',
      $exception_type,
      'Malformed response from CAS server.',
      FALSE,
    );

    // Protocol version 2: Malformed XML.
    $params[] = array(
      '2.0',
      "<> </ </> <<",
      FALSE,
      FALSE,
      '',
      $exception_type,
      'XML from CAS server is not valid.',
      FALSE,
    );

    // Protocol version 2: Authentication failure.
    $ticket = $this->randomMachineName(24);
    $params[] = array(
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
         <cas:authenticationFailure code=\"INVALID_TICKET\">
           Ticket $ticket not recognized
         </cas:authenticationFailure>
       </cas:serviceResponse>",
      FALSE,
      FALSE,
      '',
      $exception_type,
      "Error Code INVALID_TICKET: Ticket $ticket not recognized",
      FALSE,
    );

    // Protocol version 2: Neither authentication failure nor authentication
    // succes found.
    $params[] = array(
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
    );

    // Protocol version 2: No user specified in authenticationSuccess.
    $params[] = array(
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
    );

    // Protocol version 2: Proxy chain mismatch.
    $proxy_chains = '/https:\/\/example\.com/ /https:\/\/foo\.com/' . PHP_EOL . '/https:\/\/bar\.com/';
    $params[] = array(
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
    );

    // Protocol version 2: Proxy chain mismatch with non-regex proxy chain.
    $proxy_chains = 'https://bar.com /https:\/\/foo\.com/' . PHP_EOL . '/https:\/\/bar\.com/';
    $params[] = array(
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
    );

    // Protocol version 2: No PGTIOU provided when initialized as proxy.
    $params[] = array(
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
    );

    // Unknown protocol version.
    $params[] = array(
      'foobarbaz',
      "<text>",
      FALSE,
      FALSE,
      '',
      $exception_type,
      "Unknown CAS protocol version specified: foobarbaz",
      FALSE,
    );

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
    $service_params = array();
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
    $mock = new MockHandler([new Response(200, array(), $response)]);
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
                      ->disableOriginalConstructor()
                      ->getMock();

    $casHelper->expects($this->any())
              ->method('getCasProtocolVersion')
              ->willReturn('2.0');

    $casValidator = new CasValidator($httpClient, $casHelper);
    $expected_bag = new CasPropertyBag('username');
    $expected_bag->setAttributes(array(
      'email' => array('foo@example.com'),
      'memberof' => array('cn=foo,o=example', 'cn=bar,o=example'),
    ));
    $actual_bag = $casValidator->validateTicket($ticket, $service_params);
    $this->assertEquals($expected_bag, $actual_bag);
  }

}
