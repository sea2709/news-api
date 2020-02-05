<?php

namespace Drupal\viidia_article\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates a resource for retrieving awaiting submissions .
 *
 * @RestResource(
 *   id = "viidia_webform_awaiting_submissions_rest",
 *   label = @Translation("Viidia Webform Awaiting Submissions List"),
 *   uri_paths = {
 *     "canonical" = "/viidia_article/webform_awaiting_submissions/{webformId}"
 *   }
 * )
 */
class WebformAwaitingSubmissionsResource extends ResourceBase
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
     * @param Symfony\Component\HttpFoundation\Request $current_request
     *   The current request
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, Request $current_request) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
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
//            $container->get('current_user'),
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
    public function get($webformId)
    {
        $currentUser = \Drupal::currentUser();
        $currentUserId = $currentUser->id();

        $pageNumber = $this->currentRequest->query->getInt('page', 1);
        $submissionsPerPage = $this->currentRequest->query->getInt('submissionsPerPage', 20);

        $isAdmin = in_array('administrator', $currentUser->getRoles(), true);

        $totalQuery = \Drupal::database()->select('webform_submission', 'ws');
        $totalQuery->fields('ws', ['sid']);
        $totalQuery->condition('ws.webform_id', $webformId);
        $totalQuery->condition('ws.in_draft', 0);
        $totalQuery->condition('ws.sticky', 0);

        $selectedCatIds = [];

        if (!$isAdmin) {
            $cats =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('categories', 0, null, true);

            foreach ($cats as $cat) {
                $mods = $cat->get('field_moderator')->getValue();
                if (!empty($mods)) {
                    foreach ($mods as $mod) {
                        if ($mod['target_id'] == $currentUserId) {
                            $selectedCatIds[] = $cat->id();
                            break;
                        }
                    }
                }
            }
        }

        if (!empty($selectedCatIds)) {
            $totalQuery->leftJoin('webform_submission_data', 'wsd', 'ws.sid = wsd.sid AND wsd.name = \'category\'');
            $totalQuery->condition('wsd.value', $selectedCatIds, 'IN');
        }

        $total = $totalQuery->countQuery()->execute()->fetchField();

        $query = \Drupal::database()->select('webform_submission', 'ws');
        $query->fields('ws', ['sid']);
        $query->condition('ws.webform_id', $webformId);
        $query->condition('ws.in_draft', 0);
        $query->condition('ws.sticky', 0);

        if (!empty($selectedCatIds)) {
            $query->leftJoin('webform_submission_data', 'wsd', 'ws.sid = wsd.sid AND wsd.name = \'category\'');
            $query->condition('wsd.value', $selectedCatIds, 'IN');
        }

        $query->orderBy('ws.created', 'DESC');
        $query->range($submissionsPerPage * ($pageNumber - 1), $submissionsPerPage);
        $submissionIds = $query->execute()->fetchCol();

        $submissions = \Drupal::entityTypeManager()->getStorage('webform_submission')->loadMultiple($submissionIds);

        $responseSubmissions = [];
        foreach (array_reverse($submissions) as $submission) {
            $data = $submission->getData();
            array_push($responseSubmissions, [
                'id' => $submission->id(),
                'data' => $data,
                'user' => $submission->getOwner(),
                'note' => $submission->get('notes')->value,
                'created' => $submission->getCreatedTime()
            ]);
        }

        return new ModifiedResourceResponse(['data' => $responseSubmissions, 'total' => $total]);
    }
}