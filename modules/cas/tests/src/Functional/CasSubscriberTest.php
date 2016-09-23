<?php

namespace Drupal\Tests\cas\Functional;
use Drupal\Component\Utility\UrlHelper;

/**
 * Tests the CAS forced login controller.
 *
 * @group cas
 */
class CasSubscriberTest extends CasBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas', 'path', 'filter', 'node'];

  /**
   * Test that the CasSubscriber properly forces CAS authentication as expected.
   */
  public function testForcedLoginPaths() {

    $admin = $this->drupalCreateUser(['administer account settings']);
    $this->drupalLogin($admin);

    // Create some dummy nodes so we have some content paths to work with
    // when triggering forced auth paths.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    $node1 = $this->drupalCreateNode();
    $node2 = $this->drupalCreateNode();
    $node3 = $this->drupalCreateNode([
      'path' => [
        ['alias' => '/my/path'],
      ],
    ]);

    // Configure CAS with forced auth enabled for some of our node paths.
    $edit = [
      'server[hostname]' => 'fakecasserver.localhost',
      'server[path]' => '/auth',
      'forced_login[enabled]' => TRUE,
      'forced_login[paths][pages]' => "/node/2\n/my/path",
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');

    $config = $this->config('cas.settings');
    $this->assertTrue($config->get('forced_login.enabled'));
    $this->assertEquals("/node/2\n/my/path", $config->get('forced_login.paths')['pages']);

    $this->drupalLogout();

    $this->disableRedirects();
    $this->prepareRequest();

    $session = $this->getSession();

    // Our forced login subscriber should not intervene when viewing node/1.
    $session->visit($this->buildUrl('node/1', ['absolute' => TRUE]));
    $this->assertEquals(200, $session->getStatusCode());

    // But for node/2 and the node/3 path alias, we should be redirected to
    // the CAS server to login with the proper service URL appended as a query
    // string parameter.
    $url = $this->buildUrl('node/2', ['absolute' => TRUE]);
    $session->visit($url);
    $this->assertEquals(302, $session->getStatusCode());
    $expected_redirect_url = 'https://fakecasserver.localhost/auth/login?' . UrlHelper::buildQuery(['service' => $this->buildServiceUrlWithParams(['returnto' => $url])]);
    $this->assertEquals($expected_redirect_url, $session->getResponseHeader('Location'));

    $url = $this->buildUrl('my/path', ['absolute' => TRUE, 'query' => ['foo' => 'bar']]);
    $session->visit($url);
    $this->assertEquals(302, $session->getStatusCode());
    $expected_redirect_url = 'https://fakecasserver.localhost/auth/login?' . UrlHelper::buildQuery(['service' => $this->buildServiceUrlWithParams(['returnto' => $url])]);
    $this->assertEquals($expected_redirect_url, $session->getResponseHeader('Location'));

    // When we are already logged in, we should not be redirected to the CAS
    // server when hitting a forced login path.
    $this->enabledRedirects();
    $this->drupalLogin($admin);
    $session->visit($this->buildUrl('node/2', ['absolute' => TRUE]));
    $this->assertEquals(200, $session->getStatusCode());
  }

}
