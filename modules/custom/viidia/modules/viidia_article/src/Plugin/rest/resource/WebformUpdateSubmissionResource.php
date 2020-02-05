<?php

namespace Drupal\viidia_article\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\webform\Entity\WebformSubmission;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates a resource for updating status of a submission.
 *
 * @RestResource(
 *   id = "viidia_webform_update_submission_rest",
 *   label = @Translation("Viidia Webform Update Submission"),
 *   uri_paths = {
 *     "canonical" = "/viidia_article/webform_update_submission",
 *     "https://www.drupal.org/link-relations/create" = "/viidia_article/webform_update_submission"
 *   }
 * )
 */
class WebformUpdateSubmissionResource extends ResourceBase
{
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
     * @param Symfony\Component\HttpFoundation\Request $current_request
     *   The current request
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, Request $current_request)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->currentRequest = $current_request;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('example_rest'),
            $container->get('request_stack')->getCurrentRequest()
        );
    }

    /**
     * Responds to update awaiting submission POST requests.
     *
     * @param array $data
     *
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function post($data)
    {
        if (empty($data['submission_ids'])) {
            $errors = [
                'error' => [
                    'message' => 'Both webform ID and submission ID are required.'
                ]
            ];

            return new ModifiedResourceResponse($errors);
        }
        $submissionIds = $data['submission_ids'];

        // Load the webform submission.
        $webformSubmissions = WebformSubmission::loadMultiple($submissionIds);

        if (empty($webformSubmissions)) {
            throw new HttpException(t("Can't load webform submission."));
        }

        $sIds = [];
        foreach ($webformSubmissions as $webformSubmission) {
            if ($webformSubmission) {
                $status = $data['status'];
                if (!empty($status)) {
                    if ($status === 'approve') {
                        $webformSubmission->setSticky(1);
                    } elseif ($status === 'reject') {
                        $webformSubmission->set('in_draft', 1);
                        $webformSubmission->setElementData('is_waiting_for_user', 0);
                    } elseif ($status === 'note') {
                        $webformSubmission->set('notes', $data['note']);
                    }
                } else {
                    $webformSubmission->setData($data);
                    $webformSubmission->set('in_draft', 0);
                    $webformSubmission->set('sticky', 0);
                }

                if ($webformSubmission->save()) {
                    $sIds[] = $webformSubmission->id();
                }
            }
        }

        // Return the submission.
        return new ModifiedResourceResponse(['sid' => $sIds]);
    }
}