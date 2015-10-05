<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\PageManagerRoutesTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\page_manager\PageInterface;
use Drupal\page_manager\Routing\PageManagerRoutes;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the page manager route subscriber.
 *
 * @coversDefaultClass \Drupal\page_manager\Routing\PageManagerRoutes
 *
 * @group PageManager
 */
class PageManagerRoutesTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked page storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pageStorage;

  /**
   * The tested page route subscriber.
   *
   * @var \Drupal\page_manager\Routing\PageManagerRoutes
   */
  protected $routeSubscriber;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    $this->pageStorage = $this->prophesize(ConfigEntityStorageInterface::class);

    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
    $this->entityManager->getStorage('page')
      ->willReturn($this->pageStorage);

    $this->routeSubscriber = new PageManagerRoutes($this->entityManager->reveal());
  }

  /**
   * Tests adding a route for the fallback page.
   *
   * @covers ::alterRoutes
   */
  public function testAlterRoutesWithFallback() {
    // Set up the fallback page.
    $page = $this->prophesize(PageInterface::class);
    $page->status()
      ->willReturn(TRUE)
      ->shouldBeCalledTimes(1);
    $page->getPath()->shouldNotBeCalled();
    $page->isFallbackPage()
      ->willReturn(TRUE)
      ->shouldBeCalledTimes(1);
    $pages['page1'] = $page->reveal();

    $this->pageStorage->loadMultiple()
      ->willReturn($pages)
      ->shouldBeCalledTimes(1);

    $collection = new RouteCollection();
    $route_event = new RouteBuildEvent($collection);
    $this->routeSubscriber->onAlterRoutes($route_event);

    // The collection should be empty.
    $this->assertSame(0, $collection->count());
  }

  /**
   * Tests adding routes for enabled and disabled pages.
   *
   * @covers ::alterRoutes
   */
  public function testAlterRoutesWithStatus() {
    // Set up a valid page.
    $page1 = $this->prophesize(PageInterface::class);
    $page1->status()
      ->willReturn(TRUE)
      ->shouldBeCalledTimes(1);
    $page1->getPath()
      ->willReturn('/page1')
      ->shouldBeCalledTimes(1);
    $page1->isFallbackPage()
      ->willReturn(FALSE);
    $page1->label()
      ->willReturn('Page label')
      ->shouldBeCalledTimes(1);
    $page1->usesAdminTheme()
      ->willReturn(TRUE)
      ->shouldBeCalledTimes(1);
    $pages['page1'] = $page1->reveal();

    // Set up a disabled page.
    $page2 = $this->prophesize(PageInterface::class);
    $page2->status()
      ->willReturn(FALSE)
      ->shouldBeCalledTimes(1);
    $pages['page2'] = $page2->reveal();

    $this->pageStorage->loadMultiple()
      ->willReturn($pages)
      ->shouldBeCalledTimes(1);

    $collection = new RouteCollection();
    $route_event = new RouteBuildEvent($collection);
    $this->routeSubscriber->onAlterRoutes($route_event);

    // Only the valid page should be in the collection.
    $this->assertSame(1, $collection->count());
    $route = $collection->get('page_manager.page_view_page1');
    $expected_defaults = [
      '_entity_view' => 'page_manager_page',
      'page_manager_page' => 'page1',
      '_title' => 'Page label',
    ];
    $expected_requirements = [
      '_entity_access' => 'page_manager_page.view',
    ];
    $expected_options = [
      'compiler_class' => 'Symfony\Component\Routing\RouteCompiler',
      'parameters' => [
        'page_manager_page' => [
          'type' => 'entity:page',
        ],
      ],
      '_admin_route' => TRUE,
    ];
    $this->assertMatchingRoute($route, '/page1', $expected_defaults, $expected_requirements, $expected_options);
  }

  /**
   * Tests overriding an existing route.
   *
   * @covers ::alterRoutes
   */
  public function testAlterRoutesOverrideExisting() {
    // Set up a page with the same path as an existing route.
    $page = $this->prophesize(PageInterface::class);
    $page->status()
      ->willReturn(TRUE)
      ->shouldBeCalledTimes(1);
    $page->getPath()
      ->willReturn('/test_route')
      ->shouldBeCalledTimes(1);
    $page->isFallbackPage()->willReturn(FALSE);
    $page->label()->willReturn(NULL);
    $page->usesAdminTheme()->willReturn(FALSE);

    $this->pageStorage->loadMultiple()
      ->willReturn(['page1' => $page->reveal()])
      ->shouldBeCalledTimes(1);

    $collection = new RouteCollection();
    $collection->add('test_route', new Route('test_route', [], [], ['parameters' => ['foo' => 'bar']]));
    $route_event = new RouteBuildEvent($collection);
    $this->routeSubscriber->onAlterRoutes($route_event);

    // The normal route name is not used, the existing route name is instead.
    $this->assertSame(1, $collection->count());
    $this->assertNull($collection->get('page_manager.page_view_page1'));

    $route = $collection->get('test_route');
    $expected_defaults = [
      '_entity_view' => 'page_manager_page',
      'page_manager_page' => 'page1',
      '_title' => NULL,
    ];
    $expected_requirements = [
      '_entity_access' => 'page_manager_page.view',
    ];
    $expected_options = [
      'compiler_class' => 'Symfony\Component\Routing\RouteCompiler',
      'parameters' => [
        'page_manager_page' => [
          'type' => 'entity:page',
        ],
        'foo' => 'bar',
      ],
      '_admin_route' => FALSE,
    ];
    $this->assertMatchingRoute($route, '/test_route', $expected_defaults, $expected_requirements, $expected_options);
  }

  /**
   * Asserts that a route object has the expected properties.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to test.
   * @param string $expected_path
   *   The expected path for the route.
   * @param array $expected_defaults
   *   The expected defaults for the route.
   * @param array $expected_requirements
   *   The expected requirements for the route.
   * @param array $expected_options
   *   The expected options for the route.
   */
  protected function assertMatchingRoute(Route $route, $expected_path, $expected_defaults, $expected_requirements, $expected_options) {
    $this->assertSame($expected_path, $route->getPath());
    $this->assertSame($expected_defaults, $route->getDefaults());
    $this->assertSame($expected_requirements, $route->getRequirements());
    $this->assertSame($expected_options, $route->getOptions());
  }

}
