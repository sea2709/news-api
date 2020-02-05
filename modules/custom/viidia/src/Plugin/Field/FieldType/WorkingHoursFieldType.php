<?php

namespace Drupal\viidia\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'Working Hours' field type.
 *
 * @FieldType(
 *   id = "working_hours_field",
 *   label = @Translation("Working Hours field"),
 *   description = @Translation("This field stores Working Hours fields in the database."),
 *   default_widget = "working_hours_field_default",
 *   default_formatter = "working_hours_field_default"
 * )
 */
class WorkingHoursFieldType extends FieldItemBase
{

    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field)
    {
        return array(
            'columns' => array(
                'text' => array(
                    'type' => 'varchar',
                    'not null' => FALSE,
                    'length' => 256,
                ),
                'timezone' => array(
                    'type' => 'varchar',
                    'not null' => FALSE,
                    'length' => 32,
                ),
                'workingdays' => array(
                    'type' => 'text',
                    'not null' => FALSE,
                ),
                'is_24open' => array(
                    'type' => 'tinyint',
                    'length' => 1,
                    'not null' => FALSE,
                ),
            ),
        );
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['text'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('Working Hours'));
        $properties['timezone'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('Timezone'));
        $properties['workingdays'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('Working Days'))
            ->setSettings(array(
                'text_processing' => 0
            ));
        $properties['is_24open'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('Open 24 hours'));

        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $timezone = $this->get('timezone')->getValue();
        $workingDays = $this->get('workingdays')->getValue();

        return $timezone === NULL || $workingDays === NULL;
    }
}
