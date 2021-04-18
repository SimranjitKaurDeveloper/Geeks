<?php

namespace Drupal\rating_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rating_field\Plugin\Field\FieldType\RatingSettingsTrait;

/**
 * Plugin implementation of the 'rating' field widget.
 *
 * @FieldWidget(
 *   id = "rating_widget",
 *   label = @Translation("Rating widget"),
 *   field_types = {
 *     "rating_field"
 *   },
 *   multiple_values = TRUE
 * )
 */
class RatingWidgetBase extends WidgetBase {

  use RatingSettingsTrait;

  /**
   * The name of the field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The storageSettings.
   *
   * @var array
   */
  protected $storageSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    // Define vars.
    $this->fieldName = $this->fieldDefinition->getName();
    $this->storageSettings = $this->fieldDefinition->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_label_field' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['show_label_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the rating field label'),
      '#description' => $this->t('Fields that are mandatory and have the label hidden will have instead the * mark added in each row.'),
      '#default_value' => $this->getSetting('show_label_field'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary['show_label_field'] = $this->t('Rating field label: @status', [
      '@status' => $this->getSetting('show_label_field') ? $this->t('Shown') : $this->t('Hidden'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Get the rating options and the questions from the settings.
    $questions      = $this->getQuestionsRating($this->storageSettings);
    $rating_options = $this->getRatingOptions($this->storageSettings);

    // Add our custom validator.
    $element['#element_validate'][] = [$this, 'validateElement'];
    // Prepare the main part of the element.
    $element += [
      '#type' => 'rating',
      '#header' => '',
      '#id' => $this->fieldName,
      '#description' => $this->fieldDefinition->getDescription(),
      // Making tooltip disabled in each row of the rendered table.
      '#smart_description' => FALSE,
      // Default value is to show the flag (FALSE).
      '#show_label_field' => $this->getSetting('show_label_field'),
    ];
    // Adding the questions for the rows.
    $element['#questions'] = $questions;
    // And the rating options for the header and cells.
    $element['#rating_vals'] = $rating_options;

    // Check if we are editing the field or is new.
    $is_new = $this->isNewValue($form_state);
    // And get therefore the values depending also if the submit has failed.
    $selected_values = $this->getSelectedOptions($form_state->getUserInput(), $items, $rating_options, $is_new);
    // And add to the list.
    $element['#values'] = $selected_values;

    return $element;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The form element.
   */
  public function validateElement(array $element, FormStateInterface $form_state, array $form) {
    if (isset($form[$this->fieldName])) {
      // Let's update the values submitted.
      $form_state->setValue($this->fieldName, $this->getSubmittedValues($form_state, $form[$this->fieldName]['widget']));

      $all_filled = $this->checkAllRowsBeenFilled($form_state, $form[$this->fieldName]['widget']);
      // Get the status of this field, and we display a message if error.
      if ($this->fieldDefinition->isRequired() && !$all_filled) {
        // Only of is mandatory field and not all the rows have been filled.
        // @todo: style css error in element somehow.
        $form_state->setErrorByName($this->fieldName, $this->t('@name field is required and not all the questions have been filled.', ['@name' => $element['#title']]));
      }
    }
  }

  /**
   * Custom function to check the inputs of our rating grid field.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $widget_element
   *   The element array to check, we want the widget index.
   *
   * @return bool
   *   TRUE if any radio from all the rows have been chosen. FALSE otherwise.
   */
  protected function checkAllRowsBeenFilled(FormStateInterface $form_state, array $widget_element) {
    // We get the #options array from the widget.
    $options = $widget_element['#options'];
    // And the user inputs from the form_state.
    $user_input = $form_state->getUserInput();

    $all_filled = TRUE;
    foreach ($options as $key => $option) {
      // We just check if the field exists, meaning any radio has been selected.
      $all_filled &= isset($user_input[$this->fieldName . '-row-' . $key]);
    }
    return $all_filled;
  }

  /**
   * Get the values of the field when the form is submitted.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $widget_element
   *   The element array to check, we want the widget index.
   *
   * @return array
   *   Formatted array with the inputs of the radios on each row.
   */
  protected function getSubmittedValues(FormStateInterface $form_state, array $widget_element) {
    // We get the #options array from the widget.
    $options = $widget_element['#options'];

    // And the user inputs from the form_state.
    $user_input = $form_state->getUserInput();
    $values = [];
    foreach ($options as $key => $option) {
      // We just check if the field exists, meaning any radio has been selected.
      if (isset($user_input[$this->fieldName . '-row-' . $key])) {
        // We create a full array as this is what we need for storing the info.
        $values[$key] = ['value' => $user_input[$this->fieldName . '-row-' . $key], 'question_delta' => $key];
      }
    }
    return $values;
  }

  /**
   * Get the values of the field from the user input (or the database).
   *
   * If the field is being submitted, after an error, we have the values in the
   * user input. If the field otherwise is being edited, we have the values in
   * the items field. If the field is new, we dont care about the values there.
   *
   * @param array $user_input
   *   Array of all the inputs of the user from the form_state.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   * @param array $rating_options
   *   The rating options of the field from the element.
   * @param bool $is_new
   *   Flaf to indicate if the field is being edited or not.
   *
   * @return array
   *   Normal array with the inputs of the radios on each row.
   */
  protected function getSelectedOptions(array $user_input, FieldItemListInterface $items, array $rating_options, $is_new) {
    $values = [];
    // First we check if the field has been submitted and failed.
    // It has the latest value of the field.
    foreach ($rating_options as $key => $option) {
      // We just check if the field exists, meaning any radio has been selected.
      if (isset($user_input[$this->fieldName . '-row-' . $key])) {
        // Yes, so we get the value from the input array.
        $values[$key] = $user_input[$this->fieldName . '-row-' . $key];
      }
    }
    // Ok, no input. Can be either a new field or one that is being edited.
    if (empty($values)) {
      if (!$is_new) {
        // Being edited, therefore already exists and has values we need.
        foreach ($items as $item) {
          $row = $item->getValue();
          $values[$row['question_delta']] = $row['value'];
        }
      }
      // The case 'new' we dont care as it has some default garbage values.
    }
    return $values;
  }

  /**
   * Checks if the field is new, has been sumbitted or is being set.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if is new, FALSE if already been submitted or being set.
   */
  protected function isNewValue(FormStateInterface $form_state) {

    if (!$form_state->getformObject()->getEntity()->id()) {
      // Is the first time the field is gonna be submited.
      return TRUE;
    }
    else {
      // Field already exists (it has a node id), so we get the type.
      $type = $form_state->getformObject()->getEntity()->getEntityType()->getLabel()->__toString();
      if ($type === 'Content') {
        // We are editing a node, is fine as we have real values.
        return FALSE;
      }
      else {
        // This is the field config form, delete input as is garbage.
        return TRUE;
      }
    }
  }

}
