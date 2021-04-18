<?php

namespace Drupal\rating_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'rating' field type.
 *
 * @FieldType(
 *   id = "rating_field",
 *   label = @Translation("Rating Field"),
 *   description = @Translation("This field displays and store a rating grid."),
 *   category = @Translation("Rating Field Module"),
 *   default_widget = "rating_widget",
 *   default_formatter = "rating_formatter",
 *   cardinality = -1,
 * )
 */
class RatingField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Integer value'))
      ->setRequired(TRUE);

    $properties['question_delta'] = DataDefinition::create('integer')
      ->setLabel(t('Original delta of the question'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
        ],
        'question_delta' => [
          'type' => 'int',
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription($max = FALSE) {
    $description = '<p>' . $this->t('The possible values this field can contain. Enter one value per line, no key needed.');
    $description .= '<br/>' . $this->t('The label will be used in displayed values and edit forms.');
    $description .= '<br/>' . $this->t('Duplicated lines are not valid.');
    if ($max) {
      $description .= '<br/>' . $this->t('<strong>Max. amount of values is 5.</strong>');
    }
    $description .= '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      // We need apparently this here otherwise it cannot be access laters.
      'group_1_response'  => [],
      'group_2_questions' => [],
      'question_values' => [],
      'scale_config' => [],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $scale_config = $this->getSetting('scale_config');
    $question_values = $this->getSetting('question_values');

    $element = [];
    // First group, all fields belonging to the customisation of the response.
    // This is the general fielset group for this group.
    $element['group_1_response'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Rating-scale value settings'),
    ];
    // Options for the response values of this field,
    // depending on this we show other fields.
    $element['group_1_response']['input_radios'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scale options'),
      '#options' => [
        0 => "Default Likert-type scale (Options will be: 'Strongly disagree', 'Disagree', 'Neutral', 'Agree', 'Strongly agree')",
        1 => "Numeric Rating scale",
        2 => 'Custom scale (Allows custom values)',
      ],
      '#default_value' => (isset($scale_config['input_radios']) ? $scale_config['input_radios'] : 0),
      '#disabled' => $has_data,
    ];
    // If option 1 (numeric) is selected, we show these elements in the form.
    $element['group_1_response']['option_1'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings for the numeric scale option.'),
      // When is this group of fields displayed or hidden.
      '#states' => [
        'visible' => [
          ':input[name="settings[group_1_response][input_radios]"]'  => ['value' => 1],
        ],
        'invisible' => [
          [
            ':input[name="settings[group_1_response][input_radios]"]'  => ['checked' => FALSE],
          ],
          'or',
          [
            ':input[name="settings[group_1_response][input_radios]"]'  => ['value' => 0],
          ],
          'or',
          [
            ':input[name="settings[group_1_response][input_radios]"]'  => ['value' => 2],
          ],
        ],
      ],
    ];
    // Belonging to option 1, element select requesting max. numeric value.
    $element['group_1_response']['option_1']['max_value_rating'] = [
      '#type' => 'select',
      '#title' => $this->t('Max. numeric value input'),
      '#description' => $this->t('The value chosel will be the max. value used for the rating, starting in 1.'),
      '#default_value' => ((isset($scale_config['input_radios']) && $scale_config['input_radios'] == 1) ? $scale_config['option_1']['max_value_rating'] : 3),
      '#options' => [
        1 => $this->t('1'),
        2 => $this->t('2'),
        3 => $this->t('3'),
        4 => $this->t('4'),
        5 => $this->t('5'),
      ],
      '#disabled' => $has_data,
    ];
    // Belonging to option 1, element checkbox allowing zero as option.
    $element['group_1_response']['option_1']['numeric_include_zero'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include value 0 in the numeric option'),
      '#description' => $this->t('If checked, value 0 will be added to the numeric options. Otherwise rating will start in 1.'),
      '#default_value' => ((isset($scale_config['input_radios']) && $scale_config['input_radios'] == 1) ? $scale_config['option_1']['numeric_include_zero'] : 0),
      '#disabled' => $has_data,
    ];
    // If option 2 (custom) is selected, we show this element in the form.
    $element['group_1_response']['option_2'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings for the custom scale option.'),
      // When is this group of fields displayed or hidden.
      '#states' => [
        'visible' => [
          ':input[name="settings[group_1_response][input_radios]"]'  => ['value' => 2],
        ],
        'invisible' => [
          [
            ':input[name="settings[group_1_response][input_radios]"]'  => ['checked' => FALSE],
          ],
          'or',
          [
            ':input[name="settings[group_1_response][input_radios]"]'  => ['value' => 0],
          ],
          'or',
          [
            ':input[name="settings[group_1_response][input_radios]"]'  => ['value' => 1],
          ],
        ],
      ],
    ];

    // Those are the values from the storageSettings (if they exist).
    $custom_input = (
      (isset($scale_config['input_radios']) && $scale_config['input_radios'] == 2)
      ? $scale_config['option_2']['custom_scale_values']
      : NULL
    );
    // Belonging to option 2, custom questions for the inputs.
    $element['group_1_response']['option_2']['custom_scale_values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom scale values'),
      '#default_value' => (isset($custom_input) ? $this->allowedValuesString($custom_input) : ''),
      '#rows' => 6,
      '#element_validate' => [
        [get_class($this), 'validateAllowedValues'],
        [get_class($this), 'validateQuantityValues'],
      ],
      '#field_has_data' => $has_data,
      '#description' => $this->allowedValuesDescription(TRUE),
      '#disabled' => $has_data,
      // We make also this field mandatory when selected.
      '#states' => [
        'required' => [
          ':input[name="settings[group_1_response][input_radios]"]'  => ['value' => 2],
        ],
      ],
    ];
    // Last element of this group, standalone question to include a n/a option.
    $element['group_1_response']['include_na_option'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include a n/a option also in the possible options.'),
      '#description' => $this->t('If checked, a n/a input will be added to the selected scale option.'),
      '#default_value' => (isset($scale_config['include_na_option']) ? $scale_config['include_na_option'] : 0),
      '#disabled' => $has_data,
    ];

    // Second group, all fields belonging to the customisation of the questions.
    // This is the general fielset group for this group.
    $element['group_2_questions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Question settings'),
    ];
    // Fielset for the questions we want in the field to receive input.
    $element['group_2_questions']['question_values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Requested questions'),
      '#default_value' => (isset($question_values) ? $this->allowedValuesString($question_values) : ''),
      '#rows' => 6,
      '#element_validate' => [[get_class($this), 'validateAllowedValues']],
      '#field_has_data' => $has_data,
      '#description' => $this->allowedValuesDescription(),
      '#disabled' => $has_data,
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * Element_validate callback for question and options textarea values.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   */
  public static function validateAllowedValues(array $element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value']);

    $is_unique = array_diff_key($values, array_unique($values));

    if (!is_array($values)) {
      // Case the input is empty.
      $form_state->setError($element, t('Allowed values list: input cannot be empty.'));
    }
    elseif (!empty($is_unique)) {
      // Case there are duplicates in the input.
      $form_state->setError($element, t('There are duplicated values in the input (@dups).', ['@dups' => implode(', ', $is_unique)]));
    }
    else {
      // ok. Set the value in the form state as an array.
      $form_state->setValueForElement($element, $values);
    }
  }

  /**
   * Element_validate callback only for the textarea for the options.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   */
  public static function validateQuantityValues(array $element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value']);

    if (is_array($values)) {
      // We only allow a max. number of options here.
      if (count($values) > 5) {
        $form_state->setError($element, t('The number of options exceed the max. amount allowed (5).'));
      }
    }
  }

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * @param string $string
   *   The raw string to extract values from.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   */
  protected static function extractAllowedValues($string) {
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $value = trim($matches[1]);
      }
      else {
        return;
      }

      $values[] = $value;
    }

    return $values;
  }

  /**
   * Generates a string representation of an array.
   *
   * This string format is suitable for edition in a textarea.
   *
   * @param array $values
   *   An array of values, where array values are labels.
   *
   * @return string
   *   The string representation of the $values array:
   *    - Values are separated by a carriage return.
   *    - Each value is in the format "label".
   */
  protected function allowedValuesString(array $values) {
    $lines = [];
    if (!empty($values)) {
      foreach ($values as $value) {
        $lines[] = "$value";
      }
    }
    return implode("\n", $lines);
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsToConfigData(array $settings) {
    // Rewrite the values we export to the config file.
    if (isset($settings['group_1_response'])) {
      $settings['scale_config'] = $settings['group_1_response'];
    }
    if (isset($settings['group_2_questions']) && isset($settings['group_2_questions']['question_values'])) {
      $settings['question_values'] = $settings['group_2_questions']['question_values'];
    }
    // And disable the original array settings from the form.
    unset($settings['group_1_response']);
    unset($settings['group_2_questions']);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsFromConfigData(array $settings) {
    // We can structure arrays here if we store key and value separatelly
    // in the config file, but is not the case.
    if (isset($settings['question_values'])) {
      $settings['group_2_questions']['question_values'] = $settings['question_values'];
    }
    if (isset($settings['scale_config'])) {
      $settings['group_1_response'] = $settings['scale_config'];
    }

    return $settings;
  }

  /**
   * Returns the storageSettings.
   *
   * @return array
   *   The storageSettings.
   */
  public function getStorageSettings() {
    return $this->getSettings();
  }

}
