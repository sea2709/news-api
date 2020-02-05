<?php

namespace Drupal\viidia_zoho_creator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SwitchToVideoForm extends FormBase
{
    protected $_entity;

    /**
     * The string translation manager.
     *
     * @var \Drupal\Core\StringTranslation\TranslationManager
     */
    protected $_stringTranslationManager;

    /**
     * Constructs a new Sync form.
     *
     * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
     *   The string translation manager.
     *
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function __construct(TranslationManager $string_translation)
    {
        $this->_stringTranslationManager = $string_translation;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('string_translation')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'switch_to_video_form';
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
            $form['information'] = [
                '#markup' => $this->_stringTranslationManager->translate(
                    '<p>Do you want to switch this article <em>@title</em> to a video ?</p>', [
                        '@title' => $this->_entity->getTitle(),
                    ]
                ),
            ];

            $form['clone'] = [
                '#type' => 'submit',
                '#value' => 'Switch',
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
            $sourceUrl = !empty($this->_entity->field_source_url) ? $this->_entity->field_source_url->getValue(true) : '';
            if (!empty($sourceUrl)) {
                $node = \Drupal\node\Entity\Node::create([
                    'type' => 'video',
                    'title' => $this->_entity->getTitle(),
                    'status' => 0,
                    'field_video_source' => [
                        'uri' => $sourceUrl[0]['uri']
                    ],
                    'field_submission_id' => $this->_entity->field_submission_id->value
                ]);
                $node->save();

                $config = \Drupal::config('viidia_zoho_creator.settings');
                $settings = $config->get();
                $uri = 'https://creator.zoho.com/api/' . $settings['viidia_zoho_creator_zoho_creator']
                    . '/xml/' . $settings['viidia_zoho_creator_application_name']
                    . '/form/' . $settings['viidia_zoho_creator_articles_form_name'] . '/record/update';
                $client = \Drupal::httpClient();
                $client->request('POST', $uri, [
                    'form_params' => [
                        'authtoken' => $settings['viidia_zoho_creator_auth_token'],
                        'scope' => 'creatorapi',
                        'criteria' => 'Article_ID=' . $this->_entity->id(),
                        'Article_ID' => $node->id(),
                        'Article_Type' => 'Video'
                    ]
                ]);

                $this->_entity->delete();
            }
        }
    }
}
