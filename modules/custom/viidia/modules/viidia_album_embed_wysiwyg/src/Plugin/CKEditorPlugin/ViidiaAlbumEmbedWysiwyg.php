<?php

namespace Drupal\viidia_album_embed_wysiwyg\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * The media_entity plugin for viidia_album_embed_wysiwyg.
 *
 * @CKEditorPlugin(
 *   id = "viidia_album_embed",
 *   label = @Translation("Viidia Album Embed WYSIWYG")
 * )
 */
class ViidiaAlbumEmbedWysiwyg extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'viidia_album_embed_wysiwyg') . '/plugin/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'viidia_album_embed' => [
        'label' => $this->t('Viidia Album Embed'),
        'image' => drupal_get_path('module', 'viidia_album_embed_wysiwyg') . '/plugin/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

    /**
     * Returns a settings form to configure this CKEditor plugin.
     *
     * If the plugin's behavior depends on extensive options and/or external data,
     * then the implementing module can choose to provide a separate, global
     * configuration page rather than per-text-editor settings. In that case, this
     * form should provide a link to the separate settings page.
     *
     * @param array $form
     *   An empty form array to be populated with a configuration form, if any.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The state of the entire filter administration form.
     * @param \Drupal\editor\Entity\Editor $editor
     *   A configured text editor object.
     *
     * @return array
     *   A render array for the settings form.
     */
    public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor)
    {
        // TODO: Implement settingsForm() method.
        return [];
    }
}
