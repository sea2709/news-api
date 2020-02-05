<?php

namespace Drupal\viidia\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'working_hours_field_default' widget.
 *
 * @FieldWidget(
 *   id = "working_hours_field_default",
 *   label = @Translation("Working Hours Field default"),
 *   field_types = {
 *     "working_hours_field"
 *   }
 * )
 */
class WorkingHoursFieldDefaultWidget extends WidgetBase
{

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element['#attached']['library'][] = 'viidia/working-hours-field-widget-renderer';

        $element['text'] = array(
            '#title' => $this->t('Working Hours'),
            '#type' => 'textarea',
            '#default_value' => isset($items[$delta]->text) ? $items[$delta]->text : NULL,
        );

        $element['timezone'] = array(
            '#title' => $this->t('Timezone'),
            '#type' => 'select',
            '#options' => [
                'America/New_York' => 'Eastern Time',
                'America/Chicago' => 'Central Time',
                'America/Denver' => 'Mountain Time',
                'America/Phoenix' => 'Mountain Time (no DST)',
                'America/Los_Angeles' => 'Pacific Time',
                'America/Adak' => 'Hawaii-Aleutian',
                'Pacific/Honolulu' => 'Hawaii-Aleutian Time (no DST)',
                'Asia/Hanoi' => 'Vietnam'
            ],
            '#default_value' => isset($items[$delta]->timezone) ? $items[$delta]->timezone : 'America/Los_Angeles',
        );

        $element['is_24open'] = array(
            '#title' => $this->t('Open 24 hours'),
            '#type' => 'checkbox',
            '#default_value' => isset($items[$delta]->is_24open) ? $items[$delta]->is_24open : NULL,
            '#return_value' => 1
        );

        $selectedRows = [];
        $workingDaysValues = json_decode($items[$delta]->workingdays, true);
        if (!empty($workingDaysValues)) {
            $names = $workingDaysValues['name'];
            $starts = $workingDaysValues['start'];
            $ends = $workingDaysValues['end'];

            if (count($names) == count($starts) && count($starts) == count($ends)) {
                $count = count($names);
                for ($i = 0; $i < $count; $i++) {
                    if (($names[$i] !== '') && ($starts[$i] !== '') && ($ends[$i] !== '')) {
                        $selectedRows[] = [
                            'name' => $names[$i],
                            'start' => $starts[$i],
                            'end' => $ends[$i],
                        ];
                    }
                }
            }
        }

        $element['workingdays'] = array(
            '#title' => $this->t('Working Days'),
            '#theme' => 'workingdays',
            '#days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            '#startFrames' => [
                '0.0' => '12:00 am (midnight)',
                '0.5' => '12:30 am',
                '1.0' => '1:00 am',
                '1.5' => '1:30 am',
                '2.0' => '2:00 am',
                '2.5' => '2:30 am',
                '3.0' => '3:00 am',
                '3.5' => '3:30 am',
                '4.0' => '4:00 am',
                '4.5' => '4:30 am',
                '5.0' => '5:00 am',
                '5.5' => '5:30 am',
                '6.0' => '6:00 am',
                '6.5' => '6:30 am',
                '7.0' => '7:00 am',
                '7.5' => '7:30 am',
                '8.0' => '8:00 am',
                '8.5' => '8:30 am',
                '9.0' => '9:00 am',
                '9.5' => '9:30 am',
                '10.0' => '10:00 am',
                '10.5' => '10:30 am',
                '11.0' => '11:00 am',
                '11.5' => '11:30 am',
                '12.0' => '12:00 pm (noon)',
                '12.5' => '12:30 pm',
                '13.0' => '1:00 pm',
                '13.5' => '1:30 pm',
                '14.0' => '2:00 pm',
                '14.5' => '2:30 pm',
                '15.0' => '3:00 pm',
                '15.5' => '3:30 pm',
                '16.0' => '4:00 pm',
                '16.5' => '4:30 pm',
                '17.0' => '5:00 pm',
                '17.5' => '5:30 pm',
                '18.0' => '6:00 pm',
                '18.5' => '6:30 pm',
                '19.0' => '7:00 pm',
                '19.5' => '7:30 pm',
                '20.0' => '8:00 pm',
                '20.5' => '8:30 pm',
                '21.0' => '9:00 pm',
                '21.5' => '9:30 pm',
                '22.0' => '10:00 pm',
                '22.5' => '10:30 pm',
                '23.0' => '11:00 pm',
                '23.5' => '11:30 pm',
                '24.0' => '12:00 am (midnight)',
            ],
            '#endFrames' => [
                '0.5' => '12:30 am',
                '1.0' => '1:00 am',
                '1.5' => '1:30 am',
                '2.0' => '2:00 am',
                '2.5' => '2:30 am',
                '3.0' => '3:00 am',
                '3.5' => '3:30 am',
                '4.0' => '4:00 am',
                '4.5' => '4:30 am',
                '5.0' => '5:00 am',
                '5.5' => '5:30 am',
                '6.0' => '6:00 am',
                '6.5' => '6:30 am',
                '7.0' => '7:00 am',
                '7.5' => '7:30 am',
                '8.0' => '8:00 am',
                '8.5' => '8:30 am',
                '9.0' => '9:00 am',
                '9.5' => '9:30 am',
                '10.0' => '10:00 am',
                '10.5' => '10:30 am',
                '11.0' => '11:00 am',
                '11.5' => '11:30 am',
                '12.0' => '12:00 pm (noon)',
                '12.5' => '12:30 pm',
                '13.0' => '1:00 pm',
                '13.5' => '1:30 pm',
                '14.0' => '2:00 pm',
                '14.5' => '2:30 pm',
                '15.0' => '3:00 pm',
                '15.5' => '3:30 pm',
                '16.0' => '4:00 pm',
                '16.5' => '4:30 pm',
                '17.0' => '5:00 pm',
                '17.5' => '5:30 pm',
                '18.0' => '6:00 pm',
                '18.5' => '6:30 pm',
                '19.0' => '7:00 pm',
                '19.5' => '7:30 pm',
                '20.0' => '8:00 pm',
                '20.5' => '8:30 pm',
                '21.0' => '9:00 pm',
                '21.5' => '9:30 pm',
                '22.0' => '10:00 pm',
                '22.5' => '10:30 pm',
                '23.0' => '11:00 pm',
                '23.5' => '11:30 pm',
                '24.0' => '12:00 am (mid night next day)',
                '24.5' => '12:30 am (next day)',
                '25.0' => '1:00 am (next day)',
                '25.5' => '1:30 am (next day)',
                '26.0' => '2:00 am (next day)',
                '26.5' => '2:30 am (next day)',
                '27.0' => '3:00 am (next day)',
                '27.5' => '3:30 am (next day)',
                '28.0' => '4:00 am (next day)',
                '28.5' => '4:30 am (next day)',
                '29.0' => '5:00 am (next day)',
                '29.5' => '5:30 am (next day)',
                '30.0' => '6:00 am (next day)',
            ],
            '#type' => 'hidden',
            '#selectedRows' => $selectedRows,
        );

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
        $name = $this->fieldDefinition->getName();
        $userInputs = $form_state->getUserInput();

        if (!empty($userInputs[$name])) {
            foreach ($userInputs[$name] as $key => $userInput) {
                if (!empty($userInput['day'])) {
                    $values[$key]['workingdays'] = json_encode($userInput['day']);
                }
            }
        }

        return $values;
    }
}
