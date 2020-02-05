<?php

namespace Drupal\viidia\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\viidia\Plugin\Field\FieldType\EventDateTimeItem;

/**
 * Base class for the 'eventdatetime_*' widgets.
 */
class EventDateTimeWidgetBase extends WidgetBase
{

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element['start'] = [
            '#title' => $this->t('Start'),
            '#type' => 'datetime',
            '#default_value' => NULL,
            '#date_increment' => 1,
            '#date_timezone' => drupal_get_user_timezone(),
            '#required' => $element['#required'],
        ];

        $element['end'] = [
            '#title' => $this->t('End'),
            '#type' => 'datetime',
            '#default_value' => NULL,
            '#date_increment' => 1,
            '#date_timezone' => drupal_get_user_timezone(),
            '#required' => $element['#required'],
        ];

        if ($this->getFieldSetting('datetime_type') == EventDateTimeItem::DATETIME_TYPE_DATE) {
            // A date-only field should have no timezone conversion performed, so
            // use the same timezone as for storage.
            $element['start']['#date_timezone'] = DATETIME_STORAGE_TIMEZONE;
            $element['end']['#date_timezone'] = DATETIME_STORAGE_TIMEZONE;
        }

        if ($items[$delta]->start_date) {
            $date = $items[$delta]->start_date;
            // The date was created and verified during field_load(), so it is safe to
            // use without further inspection.
            if ($this->getFieldSetting('datetime_type') == EventDateTimeItem::DATETIME_TYPE_DATE) {
                // A date without time will pick up the current time, use the default
                // time.
                datetime_date_default_time($date);
            }

            $date->setTimezone(new \DateTimeZone($element['start']['#date_timezone']));
            $element['start']['#default_value'] = $date;
        }

        if ($items[$delta]->end_date) {
            $date = $items[$delta]->end_date;
            // The date was created and verified during field_load(), so it is safe to
            // use without further inspection.
            if ($this->getFieldSetting('datetime_type') == EventDateTimeItem::DATETIME_TYPE_DATE) {
                // A date without time will pick up the current time, use the default
                // time.
                datetime_date_default_time($date);
            }
            $date->setTimezone(new \DateTimeZone($element['end']['#date_timezone']));
            $element['end']['#default_value'] = $date;
        }

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function massageFormValues(array $values, array $form, FormStateInterface $form_state)
    {
        // The widget form element type has transformed the value to a
        // DrupalDateTime object at this point. We need to convert it back to the
        // storage timezone and format.
        foreach ($values as &$item) {
            if (!empty($item['start']) && $item['start'] instanceof DrupalDateTime) {
                $date = $item['start'];
                switch ($this->getFieldSetting('datetime_type')) {
                    case EventDateTimeItem::DATETIME_TYPE_DATE:
                        // If this is a date-only field, set it to the default time so the
                        // timezone conversion can be reversed.
                        datetime_date_default_time($date);
                        $format = DATETIME_DATE_STORAGE_FORMAT;
                        break;

                    default:
                        $format = DATETIME_DATETIME_STORAGE_FORMAT;
                        break;
                }
                // Adjust the date for storage.
                $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
                $item['start'] = $date->format($format);
            }

            if (!empty($item['end']) && $item['end'] instanceof DrupalDateTime) {
                $date = $item['end'];
                switch ($this->getFieldSetting('datetime_type')) {
                    case EventDateTimeItem::DATETIME_TYPE_DATE:
                        // If this is a date-only field, set it to the default time so the
                        // timezone conversion can be reversed.
                        datetime_date_default_time($date);
                        $format = DATETIME_DATE_STORAGE_FORMAT;
                        break;

                    default:
                        $format = DATETIME_DATETIME_STORAGE_FORMAT;
                        break;
                }
                // Adjust the date for storage.
                $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
                $item['end'] = $date->format($format);
            }
        }
        return $values;
    }

}
