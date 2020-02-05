<?php

namespace Drupal\viidia\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'eventdatetime' field type.
 *
 * @FieldType(
 *   id = "eventdatetime",
 *   label = @Translation("Event Date"),
 *   description = @Translation("Create and store date values."),
 *   default_widget = "eventdatetime_default",
 *   default_formatter = "eventdatetime_default",
 *   list_class = "\Drupal\viidia\Plugin\Field\FieldType\EventDateTimeFieldItemList"
 * )
 */
class EventDateTimeItem extends FieldItemBase
{

    /**
     * {@inheritdoc}
     */
    public static function defaultStorageSettings()
    {
        return [
                'datetime_type' => 'datetime',
            ] + parent::defaultStorageSettings();
    }

    /**
     * Value for the 'datetime_type' setting: store only a date.
     */
    const DATETIME_TYPE_DATE = 'date';

    /**
     * Value for the 'datetime_type' setting: store a date and time.
     */
    const DATETIME_TYPE_DATETIME = 'datetime';

    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['start'] = DataDefinition::create('datetime_iso8601')
            ->setLabel(t('Start Date value'))
            ->setRequired(TRUE);

        $properties['end'] = DataDefinition::create('datetime_iso8601')
            ->setLabel(t('End Date value'))
            ->setRequired(TRUE);

        $properties['start_date'] = DataDefinition::create('any')
            ->setLabel(t('Computed date'))
            ->setDescription(t('The computed DateTime object.'))
            ->setComputed(TRUE)
            ->setClass('\Drupal\datetime\DateTimeComputed')
            ->setSetting('date source', 'start');

        $properties['end_date'] = DataDefinition::create('any')
            ->setLabel(t('Computed date'))
            ->setDescription(t('The computed DateTime object.'))
            ->setComputed(TRUE)
            ->setClass('\Drupal\datetime\DateTimeComputed')
            ->setSetting('date source', 'end');

        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        return [
            'columns' => [
                'start' => [
                    'description' => 'The date value.',
                    'type' => 'varchar',
                    'length' => 20,
                ],
                'end' => [
                    'description' => 'The date value.',
                    'type' => 'varchar',
                    'length' => 20,
                ],
            ],
            'indexes' => [
                'value' => ['start', 'end'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data)
    {
        $element = [];

        $element['datetime_type'] = [
            '#type' => 'select',
            '#title' => t('Date type'),
            '#description' => t('Choose the type of date to create.'),
            '#default_value' => $this->getSetting('datetime_type'),
            '#options' => [
                static::DATETIME_TYPE_DATETIME => t('Date and time'),
                static::DATETIME_TYPE_DATE => t('Date only'),
            ],
            '#disabled' => $has_data,
        ];

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public static function generateSampleValue(FieldDefinitionInterface $field_definition)
    {
        $type = $field_definition->getSetting('datetime_type');

        // Just pick a date in the past year. No guidance is provided by this Field
        // type.
        $timestamp = REQUEST_TIME - mt_rand(0, 86400 * 365);
        if ($type == DateTimeItem::DATETIME_TYPE_DATE) {
            $values['start'] = gmdate(DATETIME_DATE_STORAGE_FORMAT, $timestamp);
            $values['end'] = gmdate(DATETIME_DATE_STORAGE_FORMAT, $timestamp);
        } else {
            $values['start'] = gmdate(DATETIME_DATETIME_STORAGE_FORMAT, $timestamp);
            $values['end'] = gmdate(DATETIME_DATETIME_STORAGE_FORMAT, $timestamp);
        }
        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $start = $this->get('start')->getValue();
        $end = $this->get('end')->getValue();
        return ($start === NULL || $start === '') && ($end === NULL || $end === '');
    }

    /**
     * {@inheritdoc}
     */
    public function onChange($property_name, $notify = TRUE) {
        // Enforce that the computed date is recalculated.
        if ($property_name == 'start') {
            $this->start = NULL;
        }
        if ($property_name == 'end') {
            $this->end = NULL;
        }
        parent::onChange($property_name, $notify);
    }
}
