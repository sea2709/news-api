<?php

namespace Drupal\viidia_zoho_creator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class UnapproveForm extends FormBase
{
    protected $_entity;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'unapprove_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
    {
        if (!empty($id)) {
            $this->_entity = Node::load($id);
        }

        if ($this->_entity) {
            $form['note'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Note'),
                '#size' => 256,
                '#required' => TRUE,
            ];

            $form['clone'] = [
                '#type' => 'submit',
                '#value' => 'Unapprove',
            ];

            $form['abort'] = [
                '#type' => 'submit',
                '#value' => 'Abort',
                '#submit' => '::cancelForm',
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        if (!empty($this->_entity)) {
            $submissionId = $this->_entity->field_submission_id->value;
            $submission = \Drupal::entityTypeManager()->getStorage('webform_submission')->load($submissionId);
            if ($submission) {
                $submission->setSticky(0);
                $submission->set('notes', $form_state->getValue('note'));
                $submission->set('in_draft', 1);
                $submission->save();
            }
        }
    }
}
