<?php

namespace Drupal\viidia_article\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates a resource for retrieving submissions of a user.
 *
 * @RestResource(
 *   id = "viidia_webform_submissions_rest",
 *   label = @Translation("Viidia Webform Submissions List"),
 *   uri_paths = {
 *     "canonical" = "/viidia_article/webform_submissions/{webformId}/{userId}"
 *   }
 * )
 */
class WebformSubmissionsResource extends ResourceBase
{
    /**
     *  A curent user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $currentRequest;

    /**
     * Constructs a Drupal\rest\Plugin\ResourceBase object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   The current user instance.
     * @param Symfony\Component\HttpFoundation\Request $current_request
     *   The current request
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user, Request $current_request) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->currentUser = $current_user;
        $this->currentRequest = $current_request;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('example_rest'),
            $container->get('current_user'),
            $container->get('request_stack')->getCurrentRequest()
        );
    }

    /**
     * Retrieve submitted articles of current user
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   HTTP response object containing webform submission.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function get($webformId, $userId)
    {
        $submissions = \Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(
            [
                'webform_id' => $webformId,
                'uid' => $userId
            ]
        );
        foreach ($submissions as $key => $submission) {
            if ($submission->isDraft()) {
                $isWaitingForUser = $submission->getElementData('is_waiting_for_user');
                if (empty($isWaitingForUser)) {
                    unset($submissions[$key]);
                }
            }
        }

        $responseSubmissions = [];
        foreach (array_reverse($submissions) as $submission) {
            $data = $submission->getData();
            if ($submission->isDraft()) {
                $submissionStatus = 2; // wait for user's feedback
            } else {
                if ($submission->isSticky()) {
                    $submissionStatus = 1; // approved, wait for user's input
                } else {
                    $submissionStatus = 0; // pending
                }
            }

            array_push($responseSubmissions, [
                'id' => $submission->id(),
                'data' => $data,
                'status' => $submissionStatus
            ]);
        }

        return new ModifiedResourceResponse($responseSubmissions);
    }
}