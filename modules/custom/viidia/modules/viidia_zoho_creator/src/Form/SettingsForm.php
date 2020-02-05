<?php

namespace Drupal\viidia_zoho_creator\Form;

/**
 * @file
 * Contains \Drupal\viidia\Form\ViidiaSettingsForm.
 */
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Administration settings form.
 */
class SettingsForm extends ConfigFormBase
{

    /**
     * Implements \Drupal\Core\Form\FormInterface::getFormID().
     */
    public function getFormId()
    {
        return 'viidia_zoho_creator_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['viidia_zoho_creator.settings'];
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface::buildForm().
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('viidia_zoho_creator.settings');
        $settings = $config->get();

        $syncZohoCreator = false;
        $authToken = '';
        $zohoCreator = '';
        $zohoApplicationName = '';
        $curatorsForm = '';
        $articleInputForm = '';

        if (isset($settings['viidia_zoho_creator_sync'])) {
            $syncZohoCreator = $settings['viidia_zoho_creator_sync'];
        }

        if (isset($settings['viidia_zoho_creator_auth_token'])) {
            $authToken = $settings['viidia_zoho_creator_auth_token'];
        }

        if (isset($settings['viidia_zoho_creator_zoho_creator'])) {
            $zohoCreator = $settings['viidia_zoho_creator_zoho_creator'];
        }

        if (isset($settings['viidia_zoho_creator_application_name'])) {
            $zohoApplicationName = $settings['viidia_zoho_creator_application_name'];
        }

        if (isset($settings['viidia_zoho_creator_curators_form_name'])) {
            $curatorsForm = $settings['viidia_zoho_creator_curators_form_name'];
        }

        if (isset($settings['viidia_zoho_creator_articles_form_name'])) {
            $articleInputForm = $settings['viidia_zoho_creator_articles_form_name'];
        }

        $form['zoho_creator_details'] = [
            '#type' => 'details',
            '#title' => t('Zoho Creator Details'),
            '#open' => TRUE,
        ];

        $form['zoho_creator_details']['viidia_zoho_creator_sync'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Sync'),
            '#default_value' => $syncZohoCreator,
            '#size' => 80,
        ];

        $form['zoho_creator_details']['viidia_zoho_creator_auth_token'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Authentication Token'),
            '#default_value' => $authToken,
            '#required' => TRUE,
            '#size' => 80
        ];

        $form['zoho_creator_details']['viidia_zoho_creator_zoho_creator'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Zoho Creator'),
            '#default_value' => $zohoCreator,
            '#required' => TRUE,
            '#size' => 80
        ];

        $form['zoho_creator_details']['viidia_zoho_creator_application_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Application Name'),
            '#default_value' => $zohoApplicationName,
            '#required' => TRUE,
            '#size' => 80
        ];

        $form['zoho_creator_curators'] = [
            '#type' => 'details',
            '#title' => t('Curators'),
            '#open' => TRUE,
        ];

        $form['zoho_creator_curators']['viidia_zoho_creator_curators_form_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Form Name'),
            '#default_value' => $curatorsForm,
            '#required' => TRUE,
            '#size' => 80
        ];

        $form['zoho_creator_articles'] = [
            '#type' => 'details',
            '#title' => t('Input Articles'),
            '#open' => TRUE,
        ];

        $form['zoho_creator_articles']['viidia_zoho_creator_articles_form_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Form Name'),
            '#default_value' => $articleInputForm,
            '#required' => TRUE,
            '#size' => 80
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface:submitForm()
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = \Drupal::service('config.factory')->getEditable('viidia_zoho_creator.settings');
        $config
            ->set('viidia_zoho_creator_sync', $form_state->getValue('viidia_zoho_creator_sync'))
            ->set('viidia_zoho_creator_auth_token', $form_state->getValue('viidia_zoho_creator_auth_token'))
            ->set('viidia_zoho_creator_zoho_creator', $form_state->getValue('viidia_zoho_creator_zoho_creator'))
            ->set('viidia_zoho_creator_application_name', $form_state->getValue('viidia_zoho_creator_application_name'))
            ->set('viidia_zoho_creator_curators_form_name', $form_state->getValue('viidia_zoho_creator_curators_form_name'))
            ->set('viidia_zoho_creator_articles_form_name', $form_state->getValue('viidia_zoho_creator_articles_form_name'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
