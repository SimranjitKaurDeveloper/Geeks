<?php

namespace Drupal\Tests\rating_field\Kernel\Formatter;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the address_plain formatter.
 *
 * @group rating_field
 */
class RatingNumericOptionTest extends RatingFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set storageSetting to option 1, 3 questions and a max.value of 5.
    $this->createField(1, 3, 5);
  }

  /**
   * Tests the rendered output.
   *
   * The custom options for this test are:
   * 'custom_scale_values' => [
   *    0 => '1',
   *    1 => '2',
   *    2 => '3',
   *    3 => '4',
   *    4 => '5',
   *  ],
   */
  public function testRatingFormatter() {

    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      ['value' => 0, 'question_delta' => 0],
      ['value' => 2, 'question_delta' => 1],
      ['value' => 4, 'question_delta' => 2],
    ];

    $this->renderEntityFields($entity, $this->display);

    $expected_elements = [
      "<strong>Question:</strong> Question A<br><strong>Selected option:</strong> 1",
      "<strong>Question:</strong> Question B<br><strong>Selected option:</strong> 3",
      "<strong>Question:</strong> Question C<br><strong>Selected option:</strong> 5",
    ];
    foreach ($expected_elements as $expected_element) {
      $this->assertRaw($expected_element);
    }
  }

}
