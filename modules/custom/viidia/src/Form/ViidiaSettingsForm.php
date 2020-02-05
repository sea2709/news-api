<?php

namespace Drupal\viidia\Form;

/**
 * @file
 * Contains \Drupal\viidia\Form\ViidiaSettingsForm.
 */
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Administration settings form.
 */
class ViidiaSettingsForm extends ConfigFormBase
{

    /**
     * Implements \Drupal\Core\Form\FormInterface::getFormID().
     */
    public function getFormId()
    {
        return 'viidia_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'viidia.settings',
        ];
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface::buildForm().
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('viidia.settings');
        $settings = $config->get();
        $importToElasticSearch = false;
        $host = '';
        $port = '';
        $schema = '';
        $user = '';
        $password = '';
        $index = '';
        $confOpenCatWithin = '12';
        $confSpecialGroupInDay = '';

        if (isset($settings['viidia_elasticsearch_import'])) {
            $importToElasticSearch = $settings['viidia_elasticsearch_import'];
        }

        if (isset($settings['viidia_elasticsearch_host']) && trim($settings['viidia_elasticsearch_host']) != '') {
            $host = $settings['viidia_elasticsearch_host'];
        }

        if (isset($settings['viidia_elasticsearch_port']) && trim($settings['viidia_elasticsearch_port']) != '') {
            $port = $settings['viidia_elasticsearch_port'];
        }

        if (isset($settings['viidia_elasticsearch_schema']) && trim($settings['viidia_elasticsearch_schema']) != '') {
            $schema = $settings['viidia_elasticsearch_schema'];
        }

        if (isset($settings['viidia_elasticsearch_user']) && trim($settings['viidia_elasticsearch_user']) != '') {
            $user = $settings['viidia_elasticsearch_user'];
        }

        if (isset($settings['viidia_elasticsearch_password']) && trim($settings['viidia_elasticsearch_password']) != '') {
            $password = $settings['viidia_elasticsearch_password'];
        }

        if (isset($settings['viidia_elasticsearch_index']) && trim($settings['viidia_elasticsearch_index']) != '') {
            $index = $settings['viidia_elasticsearch_index'];
        }

        if (isset($settings['viidia_location_endpoint']) && trim($settings['viidia_location_endpoint']) != '') {
            $locationEndpoint = $settings['viidia_location_endpoint'];
        }

        if (isset($settings['viidia_configuration_open_category_within']) && trim($settings['viidia_configuration_open_category_within']) != '') {
            $confOpenCatWithin = $settings['viidia_configuration_open_category_within'];
        }

        if (isset($settings['viidia_configuration_special_group_in_day']) && trim($settings['viidia_configuration_special_group_in_day']) != '') {
            $confSpecialGroupInDay = $settings['viidia_configuration_special_group_in_day'];

            if (!empty($confSpecialGroupInDay)) {
                $confSpecialGroupInDay = Node::load($confSpecialGroupInDay);
            }
        }

        $form['elasticsearch_details'] = [
            '#type' => 'details',
            '#title' => t('ElasticSearch Details'),
            '#open' => TRUE,
        ];

        $form['elasticsearch_details']['viidia_elasticsearch_import'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Import'),
            '#default_value' => $importToElasticSearch,
            '#size' => 80,
        ];

        $form['elasticsearch_details']['viidia_elasticsearch_host'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Host'),
            '#default_value' => $host,
            '#required' => TRUE,
            '#size' => 80,
        ];

        $form['elasticsearch_details']['viidia_elasticsearch_port'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Port'),
            '#default_value' => $port,
            '#required' => TRUE,
            '#size' => 80,
        ];

        $form['elasticsearch_details']['viidia_elasticsearch_schema'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Schema'),
            '#default_value' => $schema,
            '#required' => TRUE,
            '#size' => 80,
        ];

        $form['elasticsearch_details']['viidia_elasticsearch_user'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Username'),
            '#default_value' => $user,
            '#required' => TRUE,
            '#size' => 80,
        ];

        $form['elasticsearch_details']['viidia_elasticsearch_password'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Password'),
            '#default_value' => $password,
            '#required' => TRUE,
            '#size' => 80,
        ];

        $form['elasticsearch_details']['viidia_elasticsearch_index'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Index'),
            '#default_value' => $index,
            '#required' => TRUE,
            '#size' => 80,
        ];

        $form['location_server_details'] = [
            '#type' => 'details',
            '#title' => t('Location Server Details'),
            '#open' => TRUE,
        ];

        $form['location_server_details']['viidia_location_endpoint'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Location Endpoint'),
            '#default_value' => $locationEndpoint,
            '#required' => TRUE,
            '#size' => 80,
        ];

        $form['configuration'] = [
            '#type' => 'details',
            '#title' => t('Configuration'),
            '#open' => TRUE,
        ];

        $form['configuration']['viidia_configuration_open_category_within'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Open Categories Within (h)'),
            '#default_value' => $confOpenCatWithin,
            '#required' => TRUE,
            '#size' => 80,
        ];

        $form['configuration']['viidia_configuration_special_group_in_day'] = [
            '#type' => 'entity_autocomplete',
            '#required' => TRUE,
            '#target_type' => 'node',
            '#title' => $this->t('Select group'),
            '#selection_settings' => [
                'target_bundles' => ['article_group']
            ],
            '#default_value' => $confSpecialGroupInDay,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface:submitForm()
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = \Drupal::service('config.factory')->getEditable('viidia.settings');
        $config->set('viidia_elasticsearch_import', $form_state->getValue('viidia_elasticsearch_import'))
            ->set('viidia_elasticsearch_host', $form_state->getValue('viidia_elasticsearch_host'))
            ->set('viidia_elasticsearch_port', $form_state->getValue('viidia_elasticsearch_port'))
            ->set('viidia_elasticsearch_schema', $form_state->getValue('viidia_elasticsearch_schema'))
            ->set('viidia_elasticsearch_user', $form_state->getValue('viidia_elasticsearch_user'))
            ->set('viidia_elasticsearch_password', $form_state->getValue('viidia_elasticsearch_password'))
            ->set('viidia_elasticsearch_index', $form_state->getValue('viidia_elasticsearch_index'))
            ->set('viidia_location_endpoint', $form_state->getValue('viidia_location_endpoint'))
            ->set('viidia_configuration_open_category_within', $form_state->getValue('viidia_configuration_open_category_within'))
            ->set('viidia_configuration_special_group_in_day', $form_state->getValue('viidia_configuration_special_group_in_day'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
