<?php

namespace Drupal\viidia_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Administration settings form.
 */
class SettingsForm extends ConfigFormBase {

    /**
     * Gets the configuration names that will be editable.
     *
     * @return array
     *   An array of configuration object names that are editable if called in
     *   conjunction with the trait's config() method.
     */
    protected function getEditableConfigNames()
    {
        return ['viidia_user.settings'];
    }

    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId()
    {
        return 'viidia_user_settings';
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface::buildForm().
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('viidia_user.settings');
        $settings = $config->get();

        $forgotPassSubject = '';
        $forgotPassBody = '';
        $forgotPassResetLink = '';

        $updatePassSubject = '';
        $updatePassBody = '';

        if (isset($settings['viidia_user_forgot_pass_subject'])) {
            $forgotPassSubject = $settings['viidia_user_forgot_pass_subject'];
        }
        if (isset($settings['viidia_user_forgot_pass_body'])) {
            $forgotPassBody = $settings['viidia_user_forgot_pass_body'];
        }
        if (isset($settings['viidia_user_forgot_pass_reset_link'])) {
            $forgotPassResetLink = $settings['viidia_user_forgot_pass_reset_link'];
        }

        if (isset($settings['viidia_user_update_pass_subject'])) {
            $updatePassSubject = $settings['viidia_user_update_pass_subject'];
        }
        if (isset($settings['viidia_user_update_pass_body'])) {
            $updatePassBody = $settings['viidia_user_update_pass_body'];
        }

        $form['forgot_pass_details'] = [
            '#type' => 'details',
            '#title' => t('Forgot Password Email Details'),
            '#open' => TRUE,
        ];
        $form['forgot_pass_details']['viidia_user_forgot_pass_subject'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Forgot Password Email Subject'),
            '#default_value' => $forgotPassSubject,
            '#required' => TRUE,
            '#size' => 80
        ];
        $form['forgot_pass_details']['viidia_user_forgot_pass_body'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Forgot Password Email Body'),
            '#default_value' => $forgotPassBody,
            '#required' => TRUE
        ];
        $form['forgot_pass_details']['viidia_user_forgot_pass_reset_link'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Forgot Password Email Reset Link'),
            '#default_value' => $forgotPassResetLink,
            '#required' => TRUE,
            '#size' => 80,
            '#maxlength' => 256
        ];

        $form['update_pass_details'] = [
            '#type' => 'details',
            '#title' => t('Update Password Email Details'),
            '#open' => TRUE,
        ];
        $form['update_pass_details']['viidia_user_update_pass_subject'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Update Password Email Subject'),
            '#default_value' => $updatePassSubject,
            '#required' => TRUE,
            '#size' => 80
        ];
        $form['update_pass_details']['viidia_user_update_pass_body'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Update Password Email Body'),
            '#default_value' => $updatePassBody,
            '#required' => TRUE
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface:submitForm()
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = \Drupal::service('config.factory')->getEditable('viidia_user.settings');
        $config->set('viidia_user_forgot_pass_subject', $form_state->getValue('viidia_user_forgot_pass_subject'))
            ->set('viidia_user_forgot_pass_body', $form_state->getValue('viidia_user_forgot_pass_body'))
            ->set('viidia_user_forgot_pass_reset_link', $form_state->getValue('viidia_user_forgot_pass_reset_link'))
            ->set('viidia_user_update_pass_subject', $form_state->getValue('viidia_user_update_pass_subject'))
            ->set('viidia_user_update_pass_body', $form_state->getValue('viidia_user_update_pass_body'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
