<?php

namespace Drupal\file_rest\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;

/**
 * Creates a resource for submitting a file.
 *
 * @RestResource(
 *   id = "file_rest_submit",
 *   label = @Translation("File Submit"),
 *   uri_paths = {
 *     "canonical" = "/file_rest/submit",
 *     "https://www.drupal.org/link-relations/create" = "/file_rest/submit"
 *   }
 * )
 */
class FileSubmitResource extends ResourceBase
{

    /**
     * Responds to entity POST requests and saves the new entity.
     *
     * @param array $file_data
     *   File field data and value.
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function post(array $fileData)
    {
        $fileContent = base64_decode($fileData['data']);
        $fileEntity = file_save_data($fileContent, "public://user-photos/" . $fileData['filename'],
            FILE_EXISTS_RENAME);

        return new ModifiedResourceResponse(['fid' => $fileEntity->id()]);
    }
}
