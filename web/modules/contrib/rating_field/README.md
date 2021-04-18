# Rating field

## INTRODUCTION 

Provides a new field for displaying and storing a rating based on questions
and options that depends on the configuration of the field and will be offered
to the user to submit in any entity form that uses this field.

## REQUIREMENTS

This module just needs Drupal 8.7.0 or higher in order to work and the field
module enabled.
Should work as well with previous versions of Drupal 8 as well, but not tested
in any version previous to 8.7.6.

This module should be ready as well for Drupal 9 when is released, as there is
no deprecated code.

## INSTALLATION

If your site is managed via Composer, use Composer to download the module:

```sh
composer require "drupal/rating_field ~1.0"
```

Then, enable it in the "Extend" page of your Drupal installation.

Once the module is enabled, the field will be available in the dropdown list,
every time you manage the fields of your entity ("Manage fields" section) and
you want to add a new field, this one will be available as "Rating field" under
the Rating Field Module option in the dropdown list of field types.

## CONFIGURATION

Once the rating_field has been added, first you have to configure the options
in the storage settings (field settings) page.
There, you have to set the options for this field, that can be modified
meanwhile the field has no value stored in the database:

### Option settings (possible values)

- **Default likert-type style**
   
   This option will add the devault values: *Strongly disagree*, *Disagree*,
   *Neutral*, *Agree* and *Strongly agree* as available options.

- **Numeric rating scale**
   
   This option gives the user to choose a numeric value from a dropdown.  
   The value chosen will be the max. value used to render the options, from 1
   to that number included.  
   The user will have the option as well to include or not the value '0' by
   choosing a different option in a separate checkbox. If enabled, will add '0'
   before the value '1' of the different options.

- **Custom scale**

   The last option allows the user to set custom options as the possible
   options for the rating field.  
   In the textarea you can introduce one option per line up to a maximum of
   five. If there is any duplicated line or more than 5, there will be an
   error displayed.

The last checkbox available in the value settings adds an `N/A` option to
the list of options that previosly we have selected. This option **will be
rendered before** any option.


### Question settings

This textarea will allow the user to introduce the questions will be rendered
later in the field in the form. One question per line with no limit.
Later, the form will show each question in the field followed by one or more
radio buttons for each value configured in the previous step.

### Required field

If we set the field as *Required* in the Edit page, then when the user is
submitting the form which uses this field, it has to choose a valid option
for each of the questions added to this field.
If, on the oposite, the field is *not required*, then the user can skip one,
several or all the questions that are asked in the field.

## Widget settings - Hide label

The widget allows to configure basic settings about the label. There is a
checkbox available to hide the label of the field when it is rendered in
the form.

### Hidden label when required field

If this is the case, each row of the field will show instead the * that
indicates the field is mandatory and each row needs to be filled before
the form is submitted.

## Formatter

The Field Formatter offered is a basic one that will render the question/answer
in a simple div tag. It can be changed by creating a simple custom module,
creating a new FieldFormatter that supports the field, and then choosing it in
the 'Manage display' page after it has been enabled.
