<?php

namespace Drupal\viidia_user\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\Entity\User;

/**
 * Creates a resource for retrieving roles of a user.
 *
 * @RestResource(
 *   id = "viidia_user_roles_rest",
 *   label = @Translation("Viidia User Roles REST"),
 *   uri_paths = {
 *     "canonical" = "/viidia_user/roles/{userId}"
 *   }
 * )
 */
class UserRoleResource extends ResourceBase
{
    /**
     * Retrieve roles of a user.
     *
     * @param string $userId
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   HTTP response object containing user roles.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function get($userId)
    {
        $user = User::load($userId);

        // Return the submission.
        return new ModifiedResourceResponse($user->getRoles(true));
    }
}