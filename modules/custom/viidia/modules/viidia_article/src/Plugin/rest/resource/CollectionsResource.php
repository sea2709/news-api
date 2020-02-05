<?php

namespace Drupal\viidia_article\Plugin\rest\resource;

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;

/**
 * Creates a resource for retrieving article.
 *
 * @RestResource(
 *   id = "viidia_article_collections_rest",
 *   label = @Translation("Viidia Article Collections"),
 *   uri_paths = {
 *     "canonical" = "/viidia_article/collections"
 *   }
 * )
 */
class CollectionsResource extends ResourceBase
{
    /**
     * Retrieve article.
     *
     * @param string $articleId
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   HTTP response object containing article data.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function get()
    {
        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'collection');
        $query->sort('created', 'DESC');
        $collectionIds = $query->execute();

        $collections = Node::loadMultiple($collectionIds);

        return new ModifiedResourceResponse(array_values($collections));
    }
}