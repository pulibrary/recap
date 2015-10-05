<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\PageEditForm.
 */

namespace Drupal\page_manager\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing a page entity.
 */
class PageEditForm extends PageFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['use_admin_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use admin theme'),
      '#default_value' => $this->entity->usesAdminTheme(),
    ];
    $attributes = [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode([
        'width' => 'auto',
      ]),
    ];
    $add_button_attributes = NestedArray::mergeDeep($attributes, [
      'class' => [
        'button',
        'button--small',
        'button-action',
      ]
    ]);

    $form['context'] = [
      '#type' => 'details',
      '#title' => $this->t('Available context'),
      '#open' => TRUE,
    ];
    $form['context']['add'] = [
      '#type' => 'link',
      '#title' => $this->t('Add new static context'),
      '#url' => Url::fromRoute('page_manager.static_context_add', [
        'page' => $this->entity->id(),
      ]),
      '#attributes' => $add_button_attributes,
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
        ],
      ],
    ];
    $form['context']['available_context'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Name'),
        $this->t('Type'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There is no available context.'),
    ];
    $contexts = $this->entity->getContexts();
    foreach ($contexts as $name => $context) {
      $context_definition = $context->getContextDefinition();

      $row = [];
      $row['label'] = [
        '#markup' => $context_definition->getLabel(),
      ];
      $row['machine_name'] = [
        '#markup' => $name,
      ];
      $row['type'] = [
        '#markup' => $context_definition->getDataType(),
      ];

      // Add operation links if the context is a static context.
      $operations = [];
      if ($this->entity->getStaticContext($name)) {
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('page_manager.static_context_edit', [
            'page' => $this->entity->id(),
            'name' => $name,
          ]),
          'attributes' => $attributes,
        ];
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('page_manager.static_context_delete', [
            'page' => $this->entity->id(),
            'name' => $name,
          ]),
          'attributes' => $attributes,
        ];
      }
      $row['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];

      $form['context']['available_context'][$name] = $row;
    }

    $form['display_variant_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Display variants'),
      '#open' => TRUE,
    ];
    $form['display_variant_section']['add_new_page'] = [
      '#type' => 'link',
      '#title' => $this->t('Add new display variant'),
      '#url' => Url::fromRoute('page_manager.display_variant_select', [
        'page' => $this->entity->id(),
      ]),
      '#attributes' => $add_button_attributes,
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
        ],
      ],
    ];
    $form['display_variant_section']['display_variants'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Plugin'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There are no display variants.'),
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'display-variant-weight',
      ]],
    ];
    foreach ($this->entity->getVariants() as $display_variant_id => $display_variant) {
      $row = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
      ];
      $row['label']['#markup'] = $display_variant->label();
      $row['id']['#markup'] = $display_variant->adminLabel();
      $row['weight'] = [
        '#type' => 'weight',
        '#default_value' => $display_variant->getWeight(),
        '#title' => $this->t('Weight for @display_variant display variant', ['@display_variant' => $display_variant->label()]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['display-variant-weight'],
        ],
      ];
      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('page_manager.display_variant_edit', [
          'page' => $this->entity->id(),
          'display_variant_id' => $display_variant_id,
        ]),
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('page_manager.display_variant_delete', [
          'page' => $this->entity->id(),
          'display_variant_id' => $display_variant_id,
        ]),
      ];
      $row['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];
      $form['display_variant_section']['display_variants'][$display_variant_id] = $row;
    }

    if ($access_conditions = $this->entity->getAccessConditions()) {
      $form['access_section_section'] = [
        '#type' => 'details',
        '#title' => $this->t('Access Conditions'),
        '#open' => TRUE,
      ];
      $form['access_section_section']['add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add new access condition'),
        '#url' => Url::fromRoute('page_manager.access_condition_select', [
          'page' => $this->entity->id(),
        ]),
        '#attributes' => $add_button_attributes,
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
          ],
        ],
      ];
      $form['access_section_section']['access_section'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Label'),
          $this->t('Description'),
          $this->t('Operations'),
        ],
        '#empty' => $this->t('There are no access conditions.'),
      ];

      $form['access_section_section']['access_logic'] = [
        '#type' => 'radios',
        '#options' => [
          'and' => $this->t('All conditions must pass'),
          'or' => $this->t('Only one condition must pass'),
        ],
        '#default_value' => $this->entity->getAccessLogic(),
      ];

      $form['access_section_section']['access'] = [
        '#tree' => TRUE,
      ];
      foreach ($access_conditions as $access_id => $access_condition) {
        $row = [];
        $row['label']['#markup'] = $access_condition->getPluginDefinition()['label'];
        $row['description']['#markup'] = $access_condition->summary();
        $operations = [];
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('page_manager.access_condition_edit', [
            'page' => $this->entity->id(),
            'condition_id' => $access_id,
          ]),
          'attributes' => $attributes,
        ];
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('page_manager.access_condition_delete', [
            'page' => $this->entity->id(),
            'condition_id' => $access_id,
          ]),
          'attributes' => $attributes,
        ];
        $row['operations'] = [
          '#type' => 'operations',
          '#links' => $operations,
        ];
        $form['access_section_section']['access_section'][$access_id] = $row;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('display_variants')) {
      foreach ($form_state->getValue('display_variants') as $display_variant_id => $data) {
        if ($display_variant = $this->entity->getVariant($display_variant_id)) {
          $display_variant->setWeight($data['weight']);
        }
      }
    }
    parent::save($form, $form_state);
    drupal_set_message($this->t('The %label page has been updated.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('page_manager.page_list');
  }

}
