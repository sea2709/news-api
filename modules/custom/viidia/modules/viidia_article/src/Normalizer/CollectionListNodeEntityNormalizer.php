<?php

namespace Drupal\viidia_article\Normalizer;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\serialization\Normalizer\ListNormalizer;

class CollectionListNodeEntityNormalizer extends ListNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = NULL) {
        if (is_array($data)) {
            $isCollectionArr = true;
            foreach ($data as $node) {
                if (!$node instanceof NodeInterface || !$node->getType() === 'collection') {
                    $isCollectionArr = false;
                    break;
                }
            }

            return $isCollectionArr;
        }

        return false;
    }
    /**
     * {@inheritdoc}
     */
    public function normalize($entity, $format = NULL, array $context = array()) {
        $attributes = [];
        foreach ($entity as $key => $fieldItem) {
            $attributes[$key] = $this->serializer->normalize($fieldItem, $format, $context);
        }

        $articleIds = [];
        foreach ($entity as $node) {
            if ($node->getType() == 'collection') {
                $listArticles = array_slice($node->get('field_articles')->getValue(), 0, 4);
                foreach ($listArticles as $a) {
                    array_push($articleIds, $a['target_id']);
                }
            }
        }
        if (!empty($articleIds)) {
            $articles = Node::loadMultiple($articleIds);
        } else {
            $articles = [];
        }
        foreach ($attributes as $key => $collection) {
            if (!empty($collection['field_articles'])) {
                $listArticles = array_slice($collection['field_articles'], 0, 4);
                $attributes[$key]['latestArticles'] = [];
                foreach ($listArticles as $articleObj) {
                    if ($articles[$articleObj['target_id']]) {
                        array_push($attributes[$key]['latestArticles'], $this->serializer->normalize($articles[$articleObj['target_id']], 'json'));
                    }
                }
            }
        }

        // Return the $attributes with our new value.
        return $attributes;
    }
}