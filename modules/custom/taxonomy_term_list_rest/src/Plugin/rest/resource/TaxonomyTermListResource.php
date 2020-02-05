<?php

namespace Drupal\taxonomy_term_list_rest\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;

/**
 * Creates a resource for retrieving terms of a taxonomy.
 *
 * @RestResource(
 *   id = "taxonomy_terms_list_rest",
 *   label = @Translation("Taxonomy Terms List"),
 *   uri_paths = {
 *     "canonical" = "/taxonomy_term_rest/{vocabulary}/list"
 *   }
 * )
 */
class TaxonomyTermListResource extends ResourceBase
{
    /**
     * Retrieve terms of a vocabulary.
     *
     * @param string $vocabulary
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   HTTP response object containing webform submission.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function get($vocabulary)
    {
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary, 0, null, true);

        // Return the submission.
        return new ModifiedResourceResponse($terms);
    }
}