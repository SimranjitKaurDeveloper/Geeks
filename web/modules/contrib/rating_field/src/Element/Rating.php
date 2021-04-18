<?php

namespace Drupal\rating_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Component\Utility\Html as HtmlUtility;

/**
 * Provides a render element for a rating grid table.
 *
 * Properties:
 * - #header: An array of table header labels.
 * - #options: An array of each of the options to be displayed, one per row.
 *   Each row is an array of properties as described in rating.html.twig.
 * - #responsive: Indicates whether to add the drupal.responsive_table library
 *   providing responsive tables. Defaults to TRUE.
 *
 * Usage example:
 * @code
 *
 * $questions = [
 *   1 => 'How much cool is drupal?',
 *   2 => 'How much easy is Drupal?',
 *   3 => 'How much do you support Drupal?'
 * ];
 * $eval = ['n/a', '1', '2', '3', '4'];
 *
 * $form['survey'] = array(
 *   '#type' => 'rating',
 *   '#description' => $this->t('Sample Rating grid'),
 *   '#header' => '',
 *   '#id' => 'drupal_survey',
 *   '#smart_description' => FALSE,
 * );
 * $form['survey']['#questions']   = $questions;
 * $form['survey']['#rating_vals'] = $eval;
 *
 * @endcode
 *
 * @FormElement("rating")
 */
class Rating extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#header' => [],
      '#rows' => [],
      '#empty' => '',
      '#title' => '',
      '#description' => '',
      '#input' => TRUE,
      '#tree' => TRUE,
      '#default_value' => NULL,
      '#responsive' => TRUE,
      '#show_label_field' => FALSE,
      '#process' => [
        // Process the table altering some elements from the values given.
        [$class, 'processTable'],
      ],
      '#pre_render' => [
        // Pre-renders the table for the twig template.
        [$class, 'preRenderTable'],
      ],
      '#attached' => [
        // Custom library to alter the position of some elements.
        'library' => ['rating_field/rating_grid'],
      ],
      // Twig file that will be rendered.
      '#theme' => 'rating',
    ];
  }

  /**
   * Process callback for #type 'rating' to create the rows and header.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   rating element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed elements created from the settings or other values.
   */
  public static function processTable(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $field_name = $element['#id'];

    // Merge the header with the different values we have for the rating grid.
    $element['#header'] = (
      is_array($element['#header'])
      ? array_merge($element['#header'], $element['#rating_vals'])
      : array_merge([$element['#header']], $element['#rating_vals'])
    );
    $selected_values = $element['#values'];

    // Now we have to process each of the row of the grid.
    foreach ($element['#questions'] as $i => $option) {
      // Each row is made by one question from the storageSettings.
      // First, the classes for the first element, the decription.
      $classes_desc = ['question_row'];
      // Just for the cases the field is required and the label is hidden.
      if ($element['#required'] && !$element['#show_label_field']) {
        $classes_desc[] = 'js-form-required';
        $classes_desc[] = 'form-required';
      }

      $element['#options'][$i]['description'] = [
        '#markup' => '<div class="' . implode(' ', $classes_desc) . '">' . $option . '</div>',
        '#type' => 'markup',
      ];
      // And by multiple cells as radio_buttons, one cell per value from the
      // rating_values gotten from the storageSettings as well.
      foreach ($element['#rating_vals'] as $rid => $r) {

        $checked = ((isset($selected_values[$i]) && (int) $selected_values[$i] === $rid) ? TRUE : FALSE);
        $parents_for_id = array_merge($element['#parents'], [$i], [$rid]);

        $element['#options'][$i][$rid] = [
          // This is hidden.
          '#title' => $r . ': ' . $rid . ' : ' . $i,
          '#title_display' => 'invisible',
          '#type' => 'radio',
          '#parents' => $element['#parents'],
          '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
          '#attributes' => [
            'class' => [
              'rid-' . $rid,
              'js-rid-' . $rid,
            ],
            // Name has to be a string, we cannot group inputs in arrays.
            'name' => $field_name . '-row-' . $i,
            'value' => $rid,
          ],
        ];
        if ($checked) {
          // Set the default value of this radio.
          $element['#options'][$i][$rid]['#attributes'] += ['checked' => 'checked'];
        }
      }
    }
    unset($element['#rating_vals']);
    unset($element['#questions']);
    unset($element['#values']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // If is non-mandatory, input is boolean and we need an array as default.
    if (isset($input) && $input === FALSE) {
      return ['value' => 0, 'question_delta' => 0];
    }
    elseif (is_array($input)) {
      // Mandatory, returns empty array.
      return $input;
    }
    else {
      return ['value' => 0, 'question_delta' => 0];
    }
  }

  /**
   * Pre_render callback to transform children of an element of #type 'rating'.
   *
   * This function converts sub-elements of an element of #type 'rating' to be
   * suitable for rating.html.twig:
   * - The first level of sub-elements are table rows. Only the #attributes
   *   property is taken into account.
   * - The second level of sub-elements is converted into columns for the
   *   corresponding first-level table row.
   *
   * @param array $element
   *   A structured array containing two sub-levels of elements.
   *
   * @return array
   *   The element pre-rendered and ready for the twig template.
   *
   * @see template_preprocess_rating()
   */
  public static function preRenderTable(array $element) {
    if (isset($element['#options'])) {
      // From the #option array, we create the #rows for the twig template.
      foreach ($element['#options'] as $option) {
        $row = [];
        foreach (Element::children($option) as $item) {
          $class = 'td-left';
          if (is_int($item)) {
            $class = 'td-center';
          }

          // Assign the element by reference, so any potential changes to the
          // original element are taken over.
          $column = ['data' => &$option[$item], 'class' => $class];
          $row[] = $column;
        }
        $element['#rows'][] = $row;
      }
    }

    // If the table has headers and should react responsively to columns hidden
    // with the classes represented by the constants RESPONSIVE_PRIORITY_MEDIUM
    // and RESPONSIVE_PRIORITY_LOW, add the tableresponsive behaviors.
    if (count($element['#header']) && $element['#responsive']) {
      $element['#attached']['library'][] = 'core/drupal.tableresponsive';
      // Add 'responsive-enabled' class to the table to identify it for JS.
      // This is needed to target tables constructed by this function.
      $element['#attributes']['class'][] = 'responsive-enabled';
    }

    return $element;
  }

}
