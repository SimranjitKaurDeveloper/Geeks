<?php

namespace Drupal\Tests\rating_field\Kernel\Formatter;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the address_plain formatter.
 *
 * @group rating_field
 */
class RatingCustomOptionTest extends RatingFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set storageSetting to custom option.
    $this->createField(2);
  }

  /**
   * Tests the rendered output.
   *
   * The custom options for this test are:
   * 'custom_scale_values' => [
   *    0 => 'Value 1',
   *    1 => 'Value 2',
   *    2 => 'Value 3',
   *  ],
   */
  public function testRatingFormatter() {

    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      ['value' => 1, 'question_delta' => 0],
      ['value' => 2, 'question_delta' => 1],
    ];

    $this->renderEntityFields($entity, $this->display);

    $expected_elements = [
      "<strong>Question:</strong> Question A<br><strong>Selected option:</strong> Value 2",
      "<strong>Question:</strong> Question B<br><strong>Selected option:</strong> Value 3",
    ];
    foreach ($expected_elements as $expected_element) {
      $this->assertRaw($expected_element);
    }
  }

}
