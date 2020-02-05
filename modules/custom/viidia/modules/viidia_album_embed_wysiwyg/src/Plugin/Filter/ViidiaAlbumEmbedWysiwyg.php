<?php

namespace Drupal\viidia_album_embed_wysiwyg\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * The filter to turn tokens inserted into the WYSIWYG into videos.
 *
 * @Filter(
 *   title = @Translation("Viidia Album Embed WYSIWYG"),
 *   id = "viidia_album_embed_wysiwyg",
 *   description = @Translation("Enables the use of viidia_album_embed_wysiwyg."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class ViidiaAlbumEmbedWysiwyg extends FilterBase implements ContainerFactoryPluginInterface
{

    /**
     * The renderer.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected $renderer;

    /**
     * VideoEmbedWysiwyg constructor.
     *
     * @param array $configuration
     *   Plugin configuration.
     * @param string $plugin_id
     *   Plugin ID.
     * @param mixed $plugin_definition
     *   Plugin definition.
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *   The renderer.
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static($configuration, $plugin_id, $plugin_definition, $container->get('renderer'));
    }

    /**
     * {@inheritdoc}
     */
    public function process($text, $langcode)
    {
        $response = new FilterProcessResult($text);

        preg_match_all('/(<p>)?(?<json>{(?=.*photoset\b)(?=.*count\b)(?=.*thumbnail\b)(?=.*title\b)(.*)})(<\/p>)?/', $text, $matches);
        foreach ($matches['json'] as $delta => $match) {
            $embed_data = json_decode($match, TRUE);
//            $html = "<div class='viidia-album-embed-widget'
//                        data-photoset-id='{$embed_data['photoset']}'
//                        data-count='{$embed_data['count']}'
//                        data-thumbnail='{$embed_data['thumbnail']}'
//                        data-title='{$embed_data['title']}'>
//                    </div>";
            $html = "<photoset [photoSetId]=\"{$embed_data['photoset']}\" [count]=\"{$embed_data['count']}\" thumbnail=\"{$embed_data['thumbnail']}\" title=\"{$embed_data['title']}\"></photoset>";
            $text = str_replace($match, $html, $text);
        }

        $response->setProcessedText($text);
        return $response;
    }

    /**
     * Get all valid matches in the WYSIWYG.
     *
     * @param string $text
     *   The text to check for WYSIWYG matches.
     *
     * @return array
     *   An array of data from the text keyed by the text content.
     */
    protected function getValidMatches($text)
    {
        // Use a look ahead to match the capture groups in any order.
        if (!preg_match_all('/(<p>)?(?<json>{(?=.*photoset\b)(?=.*count\b)(?=.*thumbnail\b)(?=.*title\b)(.*)})(<\/p>)?/', $text, $matches)) {
            return [];
        }
        $valid_matches = [];
        foreach ($matches['json'] as $delta => $match) {
            // Ensure the JSON string is valid.
            $embed_data = json_decode($match, TRUE);
            if (!$embed_data || !is_array($embed_data)) {
                continue;
            }
            if ($this->isValidSettings($embed_data['settings'])) {
                $valid_matches[$matches[0][$delta]] = $embed_data;
            }
        }
        return $valid_matches;
    }
}
