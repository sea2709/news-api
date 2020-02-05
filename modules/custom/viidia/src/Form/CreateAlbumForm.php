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
class CreateAlbumForm extends FormBase
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
                '#markup' => $this->_stringTranslationManager->translate('<p>Do you want to create an album from this article <em>@title</em>?</p>', [
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
            $body = $this->_entity->body->getValue();
            if (!empty($body[0]['value'])) {
                $photos = [];

                $results = [];
                preg_match_all('/<img[^>]+>/i', $body[0]['value'], $results);
                foreach ($results as $images) {
                    foreach ($images as $image) {
                        $tags = [];
                        preg_match_all('/(alt|data-caption|src|data-entity-uuid)=("[^"]*")/i', $image, $tags);
                        $photoInfo = [];
                        foreach ($tags[1] as $key => $tag) {
                            $photoInfo[$tag] = trim($tags[2][$key], '"');
                        }
                        $photos[] = $photoInfo;
                    }
                }
                if (!empty($photos)) {
                    $fieldImages = [];
                    foreach ($photos as $photo) {
                        if (isset($photo['src'])) {
                            if (!empty($photo['data-entity-uuid'])) {
                                $fileEntity = \Drupal::getContainer()->get('entity.repository')->loadEntityByUuid('file', $photo['data-entity-uuid']);
                            } else {
                                if (substr($photo['src'], 0, 7) != 'http://' && substr($photo['src'], 0, 8) != 'https://') {
                                    $data = file_get_contents(DRUPAL_ROOT . $photo['src']);
                                } else {
                                    $data = file_get_contents($photo['src']);
                                }
                                if ($data) {
                                    $fileName = basename($photo['src']);
                                    $fileEntity = file_save_data($data, "public://photosets/" . $fileName, FILE_EXISTS_RENAME);
                                }
                            }
                            if (!empty($fileEntity)) {
                                $fieldImages[] = [
                                    'target_id' => $fileEntity->id(),
                                    'title' => !empty($photo['alt']) ? $photo['alt'] : '',
                                    'alt' => !empty($photo['data-caption']) ? $photo['data-caption'] : ''
                                ];
                            }
                        }
                    }
                    $node = Node::create([
                        'type' => 'photo_set',
                        'title' => $this->_entity->getTitle(),
                        'field_images' => $fieldImages,
                        'field_source_article' => [
                            'target_id' => $this->_entity->id()
                        ]
                    ]);
                    $node->save();
                }
            }
        }
    }
}
