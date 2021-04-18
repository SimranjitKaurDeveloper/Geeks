<?php

namespace Drupal\Tests\rating_field\Kernel\Formatter;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Tests the address_plain formatter.
 *
 * @group rating_field
 */
abstract class RatingFormatterTestBase extends KernelTestBase {

  /**
   * The entity display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * The generated field name.
   *
   * @var string
   */
  protected $fieldName = 'test_rating';

  /**
   * The field storage definition used to created the field storage.
   *
   * @var array
   */
  protected $fieldStorageDefinition;

  /**
   * The list field storage used in the test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The list field used in the test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The array of modules.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'field',
    'entity_test',
    'user',
    'rating_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installConfig(['rating_field']);
    $this->installEntitySchema('entity_test');
  }

  /**
   * Creates a field with certain parameters to be tested.
   *
   * @param int $option
   *   The option 'chosen' in the storageSettings.
   * @param int $num_quest
   *   The number of questions we will have in the storageSettings.
   * @param int $max_option_1
   *   If option 'chosen' is 1, then this is the max. value for the options.
   */
  protected function createField($option, $num_quest = 2, $max_option_1 = 3) {

    $questions = [];
    $j = 'A';
    for ($i = 1; $i <= $num_quest; $i++) {
      $questions[] = 'Question ' . $j;
      $j++;
    }

    $this->fieldStorageDefinition = [
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'rating_field',
      'settings' => [
        'group_1_response' => [
          'input_radios' => $option,
          'option_1' => [
            'max_value_rating' => $max_option_1,
            'numeric_include_zero' => FALSE,
          ],
          'option_2' => [
            'custom_scale_values' => [
              0 => 'Value 1',
              1 => 'Value 2',
              2 => 'Value 3',
            ],
          ],
          'include_na_option' => FALSE,
        ],
        'question_values' => $questions,
      ],
    ];
    $this->fieldStorage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName(),
    ]);
    $this->field->save();

    $this->display = EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $this->display->setComponent($this->fieldName, [
      'type' => 'rating_formatter',
      'settings' => [],
    ])->save();

  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   *
   * @return string
   *   The rendered entity fields.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity, EntityViewDisplayInterface $display) {
    $content = $display->build($entity);
    $content = $this->render($content);
    return $content;
  }

}
