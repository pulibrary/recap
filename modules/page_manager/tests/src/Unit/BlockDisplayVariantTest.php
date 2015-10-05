<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\BlockDisplayVariantTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\page_manager\PageExecutable;
use Drupal\page_manager\PageInterface;
use Drupal\page_manager\Plugin\BlockPluginCollection;
use Drupal\page_manager\Plugin\DisplayVariant\BlockDisplayVariant;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the block display variant plugin.
 *
 * @coversDefaultClass \Drupal\page_manager\Plugin\DisplayVariant\BlockDisplayVariant
 *
 * @group PageManager
 */
class BlockDisplayVariantTest extends UnitTestCase {

  /**
   * Tests the access() method.
   *
   * @covers ::access
   */
  public function testAccess() {
    $display_variant = $this->getMockBuilder(BlockDisplayVariant::class)
      ->disableOriginalConstructor()
      ->setMethods(['determineSelectionAccess'])
      ->getMock();
    $display_variant->expects($this->once())
      ->method('determineSelectionAccess')
      ->willReturn(FALSE);
    $this->assertSame(FALSE, $display_variant->access());

    $display_variant = $this->getMockBuilder(BlockDisplayVariant::class)
      ->disableOriginalConstructor()
      ->setMethods(['determineSelectionAccess'])
      ->getMock();
    $display_variant->expects($this->once())
      ->method('determineSelectionAccess')
      ->willReturn(TRUE);
    $this->assertSame(TRUE, $display_variant->access());
  }

  /**
   * Tests the build() method.
   *
   * @covers ::build
   */
  public function testBuildNoCache() {
    $container = new ContainerBuilder();
    $cache_contexts = $this->prophesize(CacheContextsManager::class);
    $container->set('cache_contexts_manager', $cache_contexts->reveal());
    \Drupal::setContainer($container);

    $account = $this->prophesize(AccountInterface::class);

    $block1 = $this->prophesize(BlockPluginInterface::class);
    $block1->access($account)->willReturn(TRUE);
    $block1->build()->willReturn(['#markup' => 'block1_build_value']);
    $block1->getConfiguration()->willReturn(['label' => 'Block label']);
    $block1->getPluginId()->willReturn('block_plugin_id');
    $block1->getBaseId()->willReturn('block_base_plugin_id');
    $block1->getDerivativeId()->willReturn('block_derivative_plugin_id');
    $block1->getCacheTags()->willReturn(['block_plugin:block_plugin_id']);
    $block1->getCacheMaxAge()->willReturn(0);
    $block1->getCacheContexts()->willReturn(['url']);

    $block2 = $this->prophesize()->willImplement(ContextAwarePluginInterface::class)->willImplement(BlockPluginInterface::class);
    $block2->access($account)->willReturn(FALSE);
    $block2->getCacheTags()->willReturn([]);
    $block2->getCacheContexts()->shouldNotBeCalled();
    $block2->build()->shouldNotBeCalled();

    $blocks = [
      'top' => [
        'block1' => $block1->reveal(),
        'block2' => $block2->reveal(),
      ],
    ];
    $block_collection = $this->getMockBuilder(BlockPluginCollection::class)
      ->disableOriginalConstructor()
      ->getMock();
    $block_collection->expects($this->once())
      ->method('getAllByRegion')
      ->willReturn($blocks);

    $context_handler = $this->prophesize(ContextHandlerInterface::class);
    $context_handler->applyContextMapping($block2->reveal(), [])->shouldBeCalledTimes(1);

    $uuid_generator = $this->prophesize(UuidInterface::class);
    $page_title = 'Page title';
    $token = $this->getMockBuilder(Token::class)
      ->disableOriginalConstructor()
      ->getMock();
    $display_variant = $this->getMockBuilder(BlockDisplayVariant::class)
      ->setConstructorArgs([['page_title' => $page_title], 'test', [], $context_handler->reveal(), $account->reveal(), $uuid_generator->reveal(), $token])
      ->setMethods(['getBlockCollection', 'drupalHtmlClass', 'renderPageTitle'])
      ->getMock();

    $page = $this->prophesize(PageInterface::class);
    $page->id()->willReturn('page_id');
    $page->getCacheTags()->willReturn(['page:page_id']);
    $page_executable = new PageExecutable($page->reveal());
    $display_variant->setExecutable($page_executable);

    $display_variant->expects($this->once())
      ->method('getBlockCollection')
      ->willReturn($block_collection);
    $display_variant->expects($this->once())
      ->method('renderPageTitle')
      ->with($page_title)
      ->willReturn($page_title);

    $expected_build = [
      'regions' => [
        'top' => [
          '#prefix' => '<div class="block-region-top">',
          '#suffix' => '</div>',
          'block1' => [
            '#theme' => 'block',
            '#attributes' => [],
            '#weight' => 0,
            '#configuration' => [
              'label' => 'Block label'
            ],
            '#plugin_id' => 'block_plugin_id',
            '#base_plugin_id' => 'block_base_plugin_id',
            '#derivative_plugin_id' => 'block_derivative_plugin_id',
            '#cache' => [
              'keys' => [
                0 => 'page_manager_page',
                1 => 'page_id',
                2 => 'block',
                3 => 'block1',
              ],
              'tags' => [
                0 => 'block_plugin:block_plugin_id',
                1 => 'page:page_id',
              ],
              'contexts' => [
                0 => 'url',
              ],
              'max-age' => 0,
            ],
            'content' => [
              '#markup' => 'block1_build_value',
            ],
          ],
        ],
      ],
      '#title' => 'Page title',
    ];

    // Call build and the #pre_render callback, remove it from the render array
    // to simplify the assertion.
    $build = $display_variant->build();
    $build['regions']['top']['block1'] = $display_variant->buildBlock($build['regions']['top']['block1']);
    unset($build['regions']['top']['block1']['#pre_render']);

    $this->assertSame($expected_build, $build);
  }


  /**
   * Tests the build() method when blocks can be cached.
   *
   * @covers ::build
   */
  public function testBuildCache() {
    $container = new ContainerBuilder();
    $cache_contexts = $this->prophesize(CacheContextsManager::class);
    $container->set('cache_contexts_manager', $cache_contexts->reveal());
    \Drupal::setContainer($container);

    $account = $this->prophesize(AccountInterface::class);

    $block1 = $this->prophesize(BlockPluginInterface::class);
    $block1->access($account)->willReturn(TRUE);
    $block1->getConfiguration()->willReturn(['label' => 'Block label']);
    $block1->getPluginId()->willReturn('block_plugin_id');
    $block1->getBaseId()->willReturn('block_base_plugin_id');
    $block1->getDerivativeId()->willReturn('block_derivative_plugin_id');
    $block1->getCacheTags()->willReturn(['block_plugin1:block_plugin_id']);
    $block1->getCacheMaxAge()->willReturn(3600);
    $block1->getCacheContexts()->willReturn(['url']);

    $block2 = $this->prophesize()->willImplement(ContextAwarePluginInterface::class)->willImplement(BlockPluginInterface::class);
    $block2->access($account)->willReturn(TRUE);
    $block2->getConfiguration()->willReturn([]);
    $block2->getPluginId()->willReturn('block_plugin_id');
    $block2->getBaseId()->willReturn('block_base_plugin_id');
    $block2->getDerivativeId()->willReturn('block_derivative_plugin_id');
    $block2->getCacheContexts()->willReturn([]);
    $block2->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $block2->getCacheTags()->willReturn(['block_plugin2:block_plugin_id']);
    $blocks = [
      'top' => [
        'block1' => $block1->reveal(),
        'block2' => $block2->reveal(),
      ],
    ];
    $block_collection = $this->getMockBuilder(BlockPluginCollection::class)
      ->disableOriginalConstructor()
      ->getMock();
    $block_collection->expects($this->once())
      ->method('getAllByRegion')
      ->willReturn($blocks);

    $context_handler = $this->prophesize(ContextHandlerInterface::class);
    $context_handler->applyContextMapping($block2->reveal(), [])->shouldBeCalledTimes(1);

    $uuid_generator = $this->prophesize(UuidInterface::class);
    $page_title = 'Page title';
    $token = $this->getMockBuilder(Token::class)
      ->disableOriginalConstructor()
      ->getMock();
    $display_variant = $this->getMockBuilder(BlockDisplayVariant::class)
      ->setConstructorArgs([['page_title' => $page_title, 'uuid' => 'UUID'], 'test', [], $context_handler->reveal(), $account->reveal(), $uuid_generator->reveal(), $token])
      ->setMethods(['getBlockCollection', 'drupalHtmlClass', 'renderPageTitle'])
      ->getMock();

    $page = $this->prophesize(PageInterface::class);
    $page->id()->willReturn('page_id');
    $page->getCacheTags()
      ->willReturn(['page:page_id'])
      ->shouldBeCalled();
    $page_executable = new PageExecutable($page->reveal());
    $display_variant->setExecutable($page_executable);

    $display_variant->expects($this->once())
      ->method('getBlockCollection')
      ->willReturn($block_collection);
    $display_variant->expects($this->once())
      ->method('renderPageTitle')
      ->with($page_title)
      ->willReturn($page_title);

    $expected_cache_block1 = [
      'keys' => ['page_manager_page', 'page_id', 'block', 'block1'],
      'tags' => ['block_plugin1:block_plugin_id', 'page:page_id'],
      'contexts' => ['url'],
      'max-age' => 3600,
    ];
    $expected_cache_block2 = [
      'keys' => ['page_manager_page', 'page_id', 'block', 'block2'],
      'tags' => ['block_plugin2:block_plugin_id', 'page:page_id'],
      'contexts' => [],
      'max-age' => Cache::PERMANENT,
    ];

    $expected_cache_page = [
      'keys' => ['page_manager_page', 'page_id', 'UUID', 'block1', 'block2'],
      'contexts' => ['url'],
      'max-age' => 3600,
    ];

    $build = $display_variant->build();
    $this->assertSame([$display_variant, 'buildBlock'], $build['regions']['top']['block1']['#pre_render'][0]);
    $this->assertSame([$display_variant, 'buildBlock'], $build['regions']['top']['block2']['#pre_render'][0]);
    $this->assertSame($expected_cache_block1, $build['regions']['top']['block1']['#cache']);
    $this->assertSame($expected_cache_block2, $build['regions']['top']['block2']['#cache']);
    $this->assertSame($expected_cache_page, $build['regions']['#cache']);

    $block1->build()->willReturn([
      '#markup' => 'block1_build_value',
    ]);
    $block2->build()->willReturn([]);

    $block1_build = $display_variant->buildBlock($build['regions']['top']['block1']);
    $block2_build = $display_variant->buildBlock($build['regions']['top']['block2']);
    $this->assertSame(['#markup' => 'block1_build_value'], $block1_build['content']);
    $this->assertSame(['#markup' => '', '#cache' => $expected_cache_block2], $block2_build);
  }

  /**
   * Tests the submitConfigurationForm() method.
   *
   * @covers ::submitConfigurationForm
   *
   * @dataProvider providerTestSubmitConfigurationForm
   */
  public function testSubmitConfigurationForm($values, $update_block_count) {
    $display_variant = $this->getMockBuilder(BlockDisplayVariant::class)
      ->disableOriginalConstructor()
      ->setMethods(['updateBlock'])
      ->getMock();
    $display_variant->expects($update_block_count)
      ->method('updateBlock');

    $form = [];
    $form_state = (new FormState())->setValues($values);
    $display_variant->submitConfigurationForm($form, $form_state);
    $this->assertSame($values['label'], $display_variant->label());
  }

  /**
   * Provides data for testSubmitConfigurationForm().
   */
  public function providerTestSubmitConfigurationForm() {
    $data = [];
    $data[] = [
      [
        'label' => 'test_label1',
      ],
      $this->never(),
    ];
    $data[] = [
      [
        'label' => 'test_label2',
        'blocks' => ['foo1' => []],
      ],
      $this->once(),
    ];
    $data[] = [
      [
        'label' => 'test_label3',
        'blocks' => ['foo1' => [], 'foo2' => []],
      ],
      $this->exactly(2),
    ];
    return $data;
  }

}
