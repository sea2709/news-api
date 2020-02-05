<?php

namespace Drupal\viidia_article\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;

/**
 * Creates a resource for retrieving article.
 *
 * @RestResource(
 *   id = "viidia_article_rest",
 *   label = @Translation("Viidia Get Article by ID"),
 *   uri_paths = {
 *     "canonical" = "/viidia_article/{articleId}"
 *   }
 * )
 */
class ArticleResource extends ResourceBase
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
    public function get($articleId)
    {
        $article = \Drupal\node\Entity\Node::load($articleId);
        if ($article && $article->isPublished()) {
            $curator = reset($article->field_curated_by->referencedEntities());
            $publisher = reset($article->field_published_by->referencedEntities());
            $contributor = reset($article->field_contributed_by->referencedEntities());
            $source = reset($article->field_article_source->referencedEntities());
        } else {
            throw new BadRequestHttpException('Cannot find article.');
        }
        return new ModifiedResourceResponse([
            'article' => $article,
            'curator' => $curator ? $curator : NULL,
            'publisher' => $publisher ? $publisher : NULL,
            'contributor' => $contributor ? $contributor : NULL,
            'source' => $source ? $source : NULL
        ]);
    }
}