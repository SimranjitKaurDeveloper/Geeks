<?php

namespace Drupal\rating_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\rating_field\Plugin\Field\FieldType\RatingSettingsTrait;

/**
 * Plugin implementation of the 'rating' field formatter.
 *
 * @FieldFormatter(
 *   id = "rating_formatter",
 *   label = @Translation("Rating Field Formatter"),
 *   field_types = {
 *     "rating_field"
 *   }
 * )
 */
class RatingFormatter extends FormatterBase {

  use RatingSettingsTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $itemStorageSettings = $item->getStorageSettings();

      // Item has the question and the options in the settings, we get them.
      $questions = $this->getQuestionsRating($itemStorageSettings);
      $rating_options = $this->getRatingOptions($itemStorageSettings);

      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $this->t('<strong>Question:</strong> @question<br><strong>Selected option:</strong> @reply', [
          '@question' => $questions[$item->question_delta],
          '@reply' => $rating_options[$item->value],
        ]),
        '#prefix' => '<div class="rating_selection">',
        '#suffix' => '</div>',
        '#attached' => [
          // Custom library to alter the position of some elements.
          'library' => ['rating_field/rating_grid'],
        ],
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
          ],
        ],
      ];
    }

    return $element;
  }

}
