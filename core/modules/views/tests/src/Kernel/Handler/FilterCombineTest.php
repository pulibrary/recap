<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the combine filter handler.
 *
 * @group views
 */
class FilterCombineTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('entity_test');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view', 'entity_test_fields');

  /**
   * Map column names.
   *
   * @var array
   */
  protected $columnMap = array(
    'views_test_data_name' => 'name',
    'views_test_data_job' => 'job',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('entity_test');
  }

  public function testFilterCombineContains() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + array(
      'job' => array(
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ),
    ));

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'contains',
        'fields' => array(
          'name',
          'job',
        ),
        'value' => 'ing',
      ),
    ));

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'John',
        'job' => 'Singer',
      ),
      array(
        'name' => 'George',
        'job' => 'Singer',
      ),
      array(
        'name' => 'Ringo',
        'job' => 'Drummer',
      ),
      array(
        'name' => 'Ginger',
        'job' => NULL,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests if the filter can handle removed fields.
   *
   * Tests the combined filter handler when a field overwrite is done
   * and fields set in the combine filter are removed from the display
   * but not from the combined filter settings.
   */
  public function testFilterCombineContainsFieldsOverwritten() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + array(
      'job' => array(
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ),
    ));

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'contains',
        'fields' => array(
          'name',
          'job',
          // Add a dummy field to the combined fields to simulate
          // a removed or deleted field.
          'dummy',
        ),
        'value' => 'ing',
      ),
    ));

    $this->executeView($view);
    // Make sure this view will not get displayed.
    $this->assertTrue($view->build_info['fail'], "View build has been marked as failed.");
    // Make sure this view does not pass validation with the right error.
    $errors = $view->validate();
    $this->assertEquals(t('Field %field set in %filter is not set in display %display.', array('%field' => 'dummy', '%filter' => 'Global: Combine fields filter', '%display' => 'Master')), reset($errors['default']));
  }

  /**
   * Tests that the combine field filter is not valid on displays that don't use
   * fields.
   */
  public function testNonFieldsRow() {
    $view = Views::getView('entity_test_fields');
    $view->setDisplay();

    // Set the rows to a plugin type that doesn't support fields.
    $view->displayHandlers->get('default')->overrideOption('row', array(
      'type' => 'entity:entity_test',
      'options' => array(
        'view_mode' => 'teaser',
      ),
    ));
    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'name' => array(
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'contains',
        'fields' => array(
          'name',
        ),
        'value' => 'ing',
      ),
    ));
    $this->executeView($view);
    $errors = $view->validate();
    // Check that the right error is shown.
    $this->assertEquals(t('%display: %filter can only be used on displays that use fields. Set the style or row format for that display to one using fields to use the combine field filter.', array('%filter' => 'Global: Combine fields filter', '%display' => 'Master')), reset($errors['default']));
  }

  /**
   * Additional data to test the NULL issue.
   */
  protected function dataSet() {
    $data_set = parent::dataSet();
    $data_set[] = array(
      'name' => 'Ginger',
      'age' => 25,
      'job' => NULL,
      'created' => gmmktime(0, 0, 0, 1, 2, 2000),
      'status' => 1,
    );
    return $data_set;
  }

  /**
   * Allow {views_test_data}.job to be NULL.
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();
    unset($schema['views_test_data']['fields']['job']['not null']);
    return $schema;
  }

}
