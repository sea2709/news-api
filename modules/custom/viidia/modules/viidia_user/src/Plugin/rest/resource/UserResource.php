<?php

namespace Drupal\viidia_user\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Creates a resource for retrieving user by username.
 *
 * @RestResource(
 *   id = "viidia_user_rest",
 *   label = @Translation("Viidia Get User by username"),
 *   uri_paths = {
 *     "canonical" = "/viidia_user/{username}"
 *   }
 * )
 */
class UserResource extends ResourceBase
{
    /**
     * Retrieve roles of a user.
     *
     * @param string $username
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   HTTP response object containing user roles.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function get($username)
    {
        if (\Drupal::service('email.validator')->isValid($username)) {
            $user = user_load_by_mail($username);
        } else {
            $user = user_load_by_name($username);
        }

        if ($user && $user->isActive()) {
            // Return the submission.
            return new ModifiedResourceResponse(
                [
                    'user' => $user,
                    'roles' => $user->getRoles()
                ]
            );
        }

        throw new BadRequestHttpException('User do not exist.');
    }
}