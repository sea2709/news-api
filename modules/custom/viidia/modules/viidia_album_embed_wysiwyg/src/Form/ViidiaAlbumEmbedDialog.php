<?php

namespace Drupal\viidia_album_embed_wysiwyg\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Video;
use Drupal\video_embed_field\ProviderManager;
use Drupal\video_embed_field\ProviderPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class for a video embed dialog.
 */
class ViidiaAlbumEmbedDialog extends FormBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * VideoEmbedDialog constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->render = $renderer;
  }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static($container->get('renderer'));
    }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // Add AJAX support.
    $form['#prefix'] = '<div id="viidia-album-embed-dialog-form">';
    $form['#suffix'] = '</div>';
    // Ensure relevant dialog libraries are attached.
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['photoset'] = [
        '#type' => 'entity_autocomplete',
        '#required' => TRUE,
        '#target_type' => 'node',
        '#title' => $this->t('Select Photo Set'),
        '#selection_settings' => [
            'target_bundles' => ['photo_set']
        ],
    ];


    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
        'wrapper' => 'video-embed-dialog-form',
      ],
    ];

    return $form;
  }

    /**
     * Get the values from the form and provider required for the client.
     *
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state from the dialog submission.
     * @param \Drupal\video_embed_field\ProviderPluginInterface $provider
     *   The provider loaded from the user input.
     *
     * @return array
     *   An array of values sent to the client for use in the WYSIWYG.
     */
    protected function getClientValues(FormStateInterface $form_state) {
        $photoSet = $form_state->getValue('photoset');
        $photoSetNode = Node::load($photoSet);
        if ($photoSetNode) {
            if ($photoSetNode->field_images->count() > 0) {
                $fileUri = $photoSetNode->field_images->get(0)->entity->getFileUri();
                if ($fileUri) {
                    $thumbnail = \Drupal\image\Entity\ImageStyle::load('large')->buildUrl($fileUri);
                }
            }
            return [
                'photoset' => $photoSet,
                'count' => $photoSetNode->field_images->count(),
                'thumbnail' => $thumbnail ? $thumbnail : '',
                'title' => $photoSetNode->getTitle()
            ];
        }

        return [];
    }

  /**
   * An AJAX submit callback to validate the WYSIWYG modal.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!$form_state->getErrors()) {
      // Load the provider and get the information needed for the client.
      $response->addCommand(new EditorDialogSave($this->getClientValues($form_state)));
      $response->addCommand(new CloseModalDialogCommand());
    }
    else {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand(NULL, $form));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The AJAX commands were already added in the AJAX callback. Do nothing in
    // the submit form.
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'viidia_album_embed_dialog';
  }

}
