<?php

namespace Drupal\rating_field\Plugin\Field\FieldType;

/**
 * Allow to render some values from different classes from the storageSettings.
 */
trait RatingSettingsTrait {

  /**
   * Returns the array of questions for the field.
   *
   * @return array
   *   The array of questions for the field rows.
   */
  public function getQuestionsRating(array $storageSettings) {

    // The questions from our field that we use in each row.
    if (isset($storageSettings['question_values'])) {
      return $storageSettings['question_values'];
    }
    return [];
  }

  /**
   * Returns a rendered array of options to choose from the storageSettings.
   *
   * @return array
   *   The array of values that the user can choose in the radio buttons.
   *
   * @todo: indexes (n/a -> -1?) ???
   */
  public function getRatingOptions(array $storageSettings) {

    // The option values for the radio buttons.
    if (isset($storageSettings['scale_config'])) {
      // The option values.
      $option = (isset($storageSettings['scale_config']['input_radios']) ? $storageSettings['scale_config']['input_radios'] : 0);
      // If we want a N/A field also or not.
      $na_value = (isset($storageSettings['scale_config']['include_na_option']) ? $storageSettings['scale_config']['include_na_option'] : 0);

      $array_options = ($na_value ? [$this->t('N/A')] : []);
      switch ($option) {
        default:
        case 0:
          // Likert-style, default values.
          $array_default = [
            $this->t('Strongly disagree'),
            $this->t('Disagree'),
            $this->t('Neutral'),
            $this->t('Agree'),
            $this->t('Strongly agree'),
          ];
          $array_options = array_merge($array_options, $array_default);
          break;

        case 1:
          // Numeric style, a bit configurable.
          // We need the max. value for the array and if we must start with 0.
          $max_value    = $storageSettings['scale_config']['option_1']['max_value_rating'];
          $include_zero = $storageSettings['scale_config']['option_1']['numeric_include_zero'];
          // Depending on the value, array will start with 0 or 1.
          $array_numeric = ($include_zero ? [$this->t('0')] : []);
          for ($i = 1; $i <= $max_value; $i++) {
            $array_numeric[] = $this->t('@i', ['@i' => $i]);
          }
          $array_options = array_merge($array_options, $array_numeric);
          break;

        case 2:
          // Custom values in the header, strings.
          $array_options = array_merge($array_options, $storageSettings['scale_config']['option_2']['custom_scale_values']);
          break;
      }
      // Composed array for the rating values according to settings created.
      return $array_options;
    }
    // In case there is nothing configured, return empty array.
    return [];
  }

}
