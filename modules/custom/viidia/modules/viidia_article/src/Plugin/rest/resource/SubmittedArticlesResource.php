<?php

namespace Drupal\viidia_article\Plugin\rest\resource;

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
 *   id = "viidia_submitted_articles_rest",
 *   label = @Translation("Viidia Submitted Articles List of user"),
 *   uri_paths = {
 *     "canonical" = "/viidia_article/submitted_articles/{userId}"
 *   }
 * )
 */
class SubmittedArticlesResource extends ResourceBase
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
    public function get($userId)
    {
        $pageNumber = $this->currentRequest->query->getInt('page', 1);
        $articlesPerPage = $this->currentRequest->query->getInt('articlesperPage', 20);
        $statuses = $this->currentRequest->query->get('status', []);

        $totalQuery = \Drupal::entityQuery('node');
        $totalQuery->condition('status', 1);
        $totalQuery->condition('type', 'article');

        $groupCondition = $totalQuery->orConditionGroup();
        if (in_array('curated', $statuses)) {
            $groupCondition->condition('field_curated_by', $userId);
        }
        if (in_array('published', $statuses)) {
            $groupCondition->condition('field_published_by', $userId);
        }
        if (in_array('contributed', $statuses)) {
            $groupCondition->condition('field_contributed_by', $userId);
        }

        $totalQuery->condition($groupCondition);

        $total = $totalQuery->count()->execute();

        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'article');

        $groupCondition = $query->orConditionGroup();

        if (in_array('curated', $statuses)) {
            $groupCondition->condition('field_curated_by', $userId);
        }
        if (in_array('published', $statuses)) {
            $groupCondition->condition('field_published_by', $userId);
        }
        if (in_array('contributed', $statuses)) {
            $groupCondition->condition('field_contributed_by', $userId);
        }

        $query->condition($groupCondition);

        $query->sort('created', 'DESC');
        $query->range($articlesPerPage * ($pageNumber - 1), $articlesPerPage);

        $articleIds = $query->execute();
        $articles = \Drupal\node\Entity\Node::loadMultiple($articleIds);
        $articleSourceIds = [];
        foreach ($articles as $article) {
            if (!empty($article->field_article_source)) {
                array_push($articleSourceIds, $article->field_article_source->target_id);
            }
        }

        $articleSources = \Drupal\node\Entity\Node::loadMultiple($articleSourceIds);

        // Return the articles.
        return new ModifiedResourceResponse(
            ['total' => $total, 'articles' => array_values($articles), 'articleSources' => $articleSources]
        );
    }
}