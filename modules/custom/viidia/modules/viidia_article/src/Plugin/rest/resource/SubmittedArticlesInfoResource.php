<?php

namespace Drupal\viidia_article\Plugin\rest\resource;

use Drupal\Core\Database\Database;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates a resource for retrieving terms of a taxonomy.
 *
 * @RestResource(
 *   id = "viidia_submitted_articles_info_user_rest",
 *   label = @Translation("Viidia Submitted Articles Info of user"),
 *   uri_paths = {
 *     "canonical" = "/viidia_article/submitted_articles_info/{webformId}/{userId}"
 *   }
 * )
 */
class SubmittedArticlesInfoResource extends ResourceBase
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
     *
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   HTTP response object containing webform submission.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function get($webformId, $userId)
    {
        $totalQuery = \Drupal::entityQuery('node');
        $totalQuery->condition('status', 1);
        $totalQuery->condition('type', 'article');
        $totalQuery->condition('field_curated_by', $userId);
        $totalCuratedArticles = $totalQuery->count()->execute();

        $totalQuery = \Drupal::entityQuery('node');
        $totalQuery->condition('status', 1);
        $totalQuery->condition('type', 'article');
        $totalQuery->condition('field_published_by', $userId);
        $totalPublishedArticles = $totalQuery->count()->execute();

        $totalQuery = \Drupal::entityQuery('node');
        $totalQuery->condition('status', 1);
        $totalQuery->condition('type', 'article');
        $totalQuery->condition('field_contributed_by', $userId);
        $totalContributedArticles = $totalQuery->count()->execute();

        $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
        $totalQuery = $storage->getQuery();
        $totalQuery->condition('webform_id', $webformId);
        $totalQuery->condition('in_draft', 0);
        $totalQuery->condition('uid', $userId);
        $totalAwaitingArticles = $totalQuery->count()->execute();

        $query = Database::getConnection()->select('webform_submission', 's');
        $query->innerJoin('webform_submission_data', 'sd', 'sd.sid = s.sid');
        $query->where('s.in_draft = 1');
        $query->where('sd.name = :name', ['name' => 'is_waiting_for_user']);
        $query->where('sd.value = :value', ['value' => 1]);
        $query->where('s.uid = :uid', ['uid' => $userId]);
        $query->fields('s', ['sid']);
        $query->distinct('s.sid');
        $totalWaitingForUsers = $query->countQuery()->execute()->fetchField();

        // Return the articles.
        return new ModifiedResourceResponse([
            'totalCuratedArticles' => $totalCuratedArticles,
            'totalPublishedArticles' => $totalPublishedArticles,
            'totalContributedArticles' => $totalContributedArticles,
            'totalAwaitingArticles' => intval($totalAwaitingArticles, 10) + intval($totalWaitingForUsers, 10)
        ]);
    }
}