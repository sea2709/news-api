<?php

namespace Drupal\viidia\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an entity Clone form.
 */
class CreateVideoForm extends FormBase
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
        return 'create_album_form';
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
                '#markup' => $this->_stringTranslationManager->translate('<p>Do you want to create the video from this article <em>@title</em>?</p>', [
                    '@title' => $this->_entity->getTitle(),
                ]),
            ];

            $form['clone'] = [
                '#type' => 'submit',
                '#value' => 'Create',
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
            $node = Node::create([
                'type' => 'video',
                'title' => $this->_entity->getTitle(),
                'field_image' => $this->_entity->field_image,
                'field_video_html' => $this->_entity->field_video_html->value,
                'field_video_source' => [
                    'uri' => $this->_entity->field_video_source->uri
                ],
            ]);
            $node->save();
        }
    }
}
