<?php

namespace Drupal\Tests\rating_field\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the rating_field field.
 *
 * @group rating_field
 */
class RatingTest extends EntityKernelTestBase {

  /**
   * The array of modules.
   *
   * @var array
   */
  public static $modules = [
    'rating_field',
  ];

  /**
   * The test entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $testEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_rating',
      'entity_type' => 'entity_test',
      'type' => 'rating_field',
      'cardinality' => -1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_rating',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    $entity = EntityTest::create([
      'name' => 'Test',
    ]);
    $entity->save();
    $this->testEntity = $entity;
  }

  /**
   * Tests storing and retrieving a rating from the field.
   */
  public function testRating() {

    $rating = [
      ['value' => 1, 'question_delta' => 0],
      ['value' => 2, 'question_delta' => 1],
      ['value' => 3, 'question_delta' => 2],
    ];

    $this->testEntity->field_rating = $rating;
    $this->testEntity->save();

    $this->testEntity = $this->reloadEntity($this->testEntity);
    $this->assertEquals($rating, $this->testEntity->field_rating->getValue());
  }

}
