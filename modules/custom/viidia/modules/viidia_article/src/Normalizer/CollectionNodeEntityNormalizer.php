<?php

namespace Drupal\viidia_article\Normalizer;

use Drupal\node\NodeInterface;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;

class CollectionNodeEntityNormalizer extends ContentEntityNormalizer
{
    /**
     * The interface or class that this Normalizer supports.
     *
     * @var string
     */
    protected $supportedInterfaceOrClass = 'Drupal\node\NodeInterface';

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = NULL) {
        if (!is_object($data) || !$this->checkFormat($format)) {
            return FALSE;
        }

        if ($data instanceof NodeInterface && $data->getType() == 'collection') {
            return TRUE;
        }

        return FALSE;
    }
    /**
     * {@inheritdoc}
     */
    public function normalize($entity, $format = NULL, array $context = array()) {
        $attributes = parent::normalize($entity, $format, $context);

        if ($entity->getType() === 'collection') {
            // The link to the node entity.
            $attributes['n_articles'] = count($entity->get('field_articles')->getValue());

            // Re-sort the array after our new additions.
            ksort($attributes);
        }

        // Return the $attributes with our new values.
        return $attributes;
    }
}