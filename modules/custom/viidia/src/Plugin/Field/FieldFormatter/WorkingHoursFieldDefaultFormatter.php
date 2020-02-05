<?php

namespace Drupal\viidia\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'working_hours_field' formatter.
 *
 * @FieldFormatter(
 *   id = "working_hours_field_default",
 *   label = @Translation("Working Hours Field default"),
 *   field_types = {
 *     "working_hours_field"
 *   }
 * )
 */
class WorkingHoursFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $element = array(
        '#theme' => 'working_hours_field',
        '#name' => $item->name,
      );
      $elements[] = $element;
    }

    return $elements;
  }

}
