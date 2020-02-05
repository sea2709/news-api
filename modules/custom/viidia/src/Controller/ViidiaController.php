<?php

namespace Drupal\viidia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\viidia\SearchClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;

class ViidiaController extends ControllerBase
{
    protected $_articlesHelper;
    protected $_imagesHelper;

    private function _getLocationById($locationId)
    {
        $config = \Drupal::config('viidia.settings');
        $settings = $config->get();

        $client = \Drupal::httpClient();
        $response = $client->get($settings['viidia_location_endpoint'] . '/viidia/api/getLocationInfoById/' . $locationId . '/0',
            array('headers' => array('Accept' => 'application/json')));

        $responseData = Json::decode($response->getBody());

        return $responseData['data'];
    }

    private function _getLocationByProperty($propertyName, $propertyValue)
    {
        $config = \Drupal::config('viidia.settings');
        $settings = $config->get();

        $client = \Drupal::httpClient();
        $response = $client->get($settings['viidia_location_endpoint'] . '/viidia/api/getLocationByProperty/' . $propertyName . '/' . $propertyValue,
            array('headers' => array('Accept' => 'application/json')));

        $responseData = Json::decode($response->getBody());

        return $responseData['data'];
    }

    public function getArticlesHelper() {
        return $this->_articlesHelper ?: \Drupal::service('viidia.articles_helper');
    }

    public function getImagesHelper() {
        return $this->_imagesHelper ?: \Drupal::service('viidia.images_helper');
    }

    public function adminConfig()
    {
        return array(
            '#type' => 'markup',
            '#markup' => $this->t('Hello, World!'),
        );
    }

    public function getArticleCategories()
    {
        $con = Database::getConnection();

        $subQuery = $con->select('node_field_data', 'fd');
        $subQuery->innerJoin('node__field_category', 'fc', 'fd.nid = fc.entity_id');
        $subQuery->where('fc.field_category_target_id = c.field_category_target_id');
        $subQuery->where('status = 1');
        $subQuery->fields('fd', ['nid']);
        $subQuery->range(0, 1);
        $subQuery->orderBy('fd.created', 'DESC');

        $query = $con->select('node__field_category', 'c');
        $query->innerJoin('node', 'n', 'c.entity_id = n.nid');
        $query->innerJoin('node_field_data', 'nd', 'nd.nid = n.nid');
        $query->condition('nd.status', 1);
        $query->condition('n.type', 'article');
        $query->groupBy('c.field_category_target_id');
        $query->addExpression('count(n.nid)', 'count');
        $query->addExpression('(' . $subQuery . ')', 'nid');
        $query->fields('c', ['field_category_target_id']);
        $data = $query->execute()->fetchAllAssoc('field_category_target_id');

        $nids = [];
        foreach ($data as $key => $termData) {
            array_push($nids, $termData->nid);
        }
        $articles = Node::loadMultiple($nids);

        $categories = Term::loadMultiple(array_keys($data));
        $responseCats = [];
        foreach ($categories as $term) {
            $responseCats[$term->id()] = [
                'tid' => $term->id(),
                'name' => $term->getName(),
                'description' => $term->getDescription(),
                'isPromotedToMobileHomePage' => $term->field_mobile_home_page->value,
                'color' => $term->field_color->value,
                'groupLayout' => $term->field_group_layout->value,
                'thumbnail' => $this->getImagesHelper()->getImageUrl($term->field_thumbnail->first()),
                'whiteThumbnail' => $this->getImagesHelper()->getImageUrl($term->field_white_thumbnail->first())
            ];

            if (isset($articles[$data[$term->id()]->nid])) {
                $article = $articles[$data[$term->id()]->nid];
                if ($article) {
                    $imageUrl = $this->getImagesHelper()->getImageUrl($article->field_image->first());
                    if (!empty($imageUrl)) {
                        $responseCats[$term->id()]['latestArticleImage'] = $imageUrl;
                    }
                    $responseCats[$term->id()]['latestArticleCreated'] = $article->getCreatedTime();
                }
            }
        }

        return new JsonResponse($responseCats, 200);
    }

    public function searchArticles($pageNumber, $articlesPerPage)
    {
        $config = \Drupal::config('viidia.settings');
        $settings = $config->get();

        $client = SearchClient::build();

        $query = \Drupal::request()->query->get('query');

        $params = [
            'index' => $settings['viidia_elasticsearch_index'],
            'type' => 'article',
            'size' => $articlesPerPage,
            'from' => ($pageNumber > 1) ? ($pageNumber - 1) * $articlesPerPage : 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'match' => [
                                    'name' => [
                                        'query' => $query,
                                        'boost' => 1
                                    ],
                                ]
                            ],
                            [
                                'match' => [
                                    'categoryName' => [
                                        'query' => $query,
                                        'boost' => 0.5
                                    ],
                                ]
                            ],
                            [
                                'match' => [
                                    'body' => $query
                                ]
                            ],
                            [
                                'match' => [
                                    'summary' => $query
                                ]
                            ],
                            [
                                'match' => [
                                    'featured' => [
                                        'query' => true,
                                        'boost' => 1
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $client->search($params);
        $responseInfo = $response['hits'];

        return new JsonResponse(['total' => $responseInfo['total'], 'data' => $this->_buildResponseArticlesFromSearch($responseInfo['hits'])]);
    }

    public function getArticlesByCategory($categoryId, $pageNumber, $articlesPerPage)
    {
        $totalQuery = \Drupal::entityQuery('node');
        $totalQuery->condition('status', 1);
        $totalQuery->condition('type', 'article');
        $totalQuery->condition('field_category', $categoryId);
        $total = $totalQuery->count()->execute();

        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'article');
        $query->condition('field_category', $categoryId);
        $query->sort('created', 'DESC');
        $query->range($articlesPerPage * ($pageNumber - 1), $articlesPerPage);

        $articleIds = $query->execute();

        return new JsonResponse(['total' => $total, 'data' => $this->getArticlesHelper()->buildResponseArticles($articleIds)]);
    }

    public function getPrevArticlesInCategory($articleId, $categoryId, $numberOfArticles) {
        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'article');
        $query->condition('field_category', $categoryId);
        $query->condition('nid', $articleId, '<');
        $query->sort('nid', 'ASC');
        $query->range(0, $numberOfArticles);

        $articleIds = $query->execute();

        return new JsonResponse(['data' => $this->getArticlesHelper()->buildResponseArticles($articleIds)]);
    }

    public function getNextArticlesInCategory($articleId, $categoryId, $numberOfArticles) {
        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'article');
        $query->condition('field_category', $categoryId);
        $query->condition('nid', $articleId, '>');
        $query->sort('nid', 'ASC');
        $query->range(0, $numberOfArticles);

        $articleIds = $query->execute();

        return new JsonResponse(['data' => $this->getArticlesHelper()->buildResponseArticles($articleIds)]);
    }

    public function getFeaturedArticles($limit = 5)
    {
        $query = \Drupal::entityQuery('node')->condition('status', 1)
            ->condition('type', 'article')->condition('promote', 1)->range(0, $limit);
        $articleIds = $query->execute();

        return new JsonResponse(['data' => $this->getArticlesHelper()->buildResponseArticles($articleIds)]);
    }

    public function getLatestArticles($from = 0, $limit = 5)
    {
        $totalQuery = \Drupal::entityQuery('node');
        $totalQuery->condition('status', 1);
        $totalQuery->condition('type', 'article');
        $total = $totalQuery->count()->execute();

        $query = \Drupal::entityQuery('node')->condition('status', 1)
            ->condition('type', 'article')->condition('promote', 0)
            ->sort('sticky', 'DESC')
            ->sort('nid', 'DESC')
        ;

        $excludeArticleIds = \Drupal::request()->query->get('excludeArticleIds');
        if (!empty($excludeArticleIds)) {
            $query->condition('nid', $excludeArticleIds, 'NOT IN');
        }

        $query->range($from, $limit);
        $articleIds = $query->execute();

        return new JsonResponse([
            'data' => $this->getArticlesHelper()->buildResponseArticles($articleIds),
            'total' => $total
        ]);
    }

    public function getTrendingArticles($pageNumber, $articlePerPage = 20)
    {
        $articleHelper = $this->getArticlesHelper();

        return new JsonResponse([
            'total' => $articleHelper->getTotalTrendingArticles(),
            'data' => $articleHelper->getTrendingArticles($pageNumber, $articlePerPage)
        ]);
    }

    public function hitArticle($articleId)
    {
        return new JsonResponse(['data' => \Drupal::service('statistics.storage.node')->recordView($articleId)]);
    }

    public function getArticleById($articleId)
    {
        $article = \Drupal\node\Entity\Node::load($articleId);
        if ($article) {
            $articleHelper = $this->getArticlesHelper();
            $articleResponse = $articleHelper->buildResponseArticle($article);
            $body = $articleResponse['body'];
            if (!empty($body)) {
                $body = $articleHelper->urlsToAbsolute(\Drupal::request()->getUriForPath('/'), $body);
                $articleResponse['body'] = $body;
            }
        }
        return new JsonResponse(['data' => $articleResponse]);
    }

    public function getNextArticlesById($articleId, $numberOfArticles)
    {
        $query = $this->_buildGetDifferentArticlesQuery($articleId, '>', $numberOfArticles);
        $articleIds = $query->execute();

        return new JsonResponse(['data' => !empty($articleIds) ? $this->getArticlesHelper()->buildResponseArticles($articleIds) : null]);
    }

    public function getPreviousArticlesById($articleId, $numberOfArticles)
    {
        $query = $this->_buildGetDifferentArticlesQuery($articleId, '<', $numberOfArticles);
        $articleIds = $query->execute();

        return new JsonResponse(['data' => !empty($articleIds) ? $this->getArticlesHelper()->buildResponseArticles($articleIds) : null]);
    }

    public function getLocationInfoByName($name)
    {
        $cityLocation = $this->_getLocationByProperty('alt_name', $name);

        return new JsonResponse(['data' => $cityLocation], 200);
    }

    public function getLocationInfoById($locationId)
    {
        return new JsonResponse(['data' => $this->_getLocationById($locationId)]);
    }

    public function updateExternalReferral($categoryId)
    {
        $articles = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(
            array('type' => 'article', 'field_category' => $categoryId)
        );

        foreach ($articles as $article) {
            $body = $article->body->getValue();
            if (!empty($body[0]['value'])) {
                $article->field_external_referral->setValue(0);
            } else {
                $article->field_external_referral->setValue(1);
            }
            $article->save();
        }

        return array(
            '#type' => 'markup',
            '#markup' => count($articles) . ' ' . $this->t('Done!'),
        );
    }

    public function updateCategory($fromCatId, $toCatId)
    {
        $articles = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(
            array('type' => 'article', 'field_category' => $fromCatId)
        );

        foreach ($articles as $article) {
            $catValues = $article->field_category->getValue();
            foreach ($catValues as $key => $value) {
                if ($value['target_id'] == $fromCatId) {
                    $catValues[$key]['target_id'] = $toCatId;
                    $article->field_category->setValue($catValues);
                    $article->save();
                    break;
                }
            }
        }

        return array(
            '#type' => 'markup',
            '#markup' => count($articles) . ' ' . $this->t('Done!'),
        );
    }

    public function updateSource() {
        $articles = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(
            array('type' => 'article')
        );

        foreach ($articles as $article) {
            if (!empty($article->field_source->value)) {
                $source = $article->field_source->value;
                $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(
                    array('type' => 'source', 'title' => $source)
                );
                if (!empty($nodes)) {
                    $articleSource = reset($nodes);
                } else {
                    $articleSource = Node::create([
                        'type' => 'source',
                        'title' => $source
                    ]);
                    $articleSource->save();
                }

                $article->field_article_source->setValue(['target_id' => $articleSource->id()]);
                $article->save();
            }
        }

        return array(
            '#type' => 'markup',
            '#markup' => count($articles) . ' ' . $this->t('Done!'),
        );
    }

    public function getConfigurations()
    {
        $config = \Drupal::config('viidia.settings');
        $settings = $config->get();
        return new JsonResponse(['data' => [
           'open_category_within' => $settings['viidia_configuration_open_category_within'],
           'group_special_in_day' =>  $settings['viidia_configuration_special_group_in_day']
        ]]);
    }

    public function getPreviousAndNextArticlesByIdAndPage() {
        $articleId = \Drupal::request()->get('articleId');
        $page = \Drupal::request()->get('page');

        $prevArticle = null;
        $nextArticle = null;

        if ($articleId && $page) {
            switch ($page) {
                case 'trending':
                    $prevNextArticles = $this->getArticlesHelper()->getPrevAndNextTredingArticles($articleId);
                    $prevArticle = $prevNextArticles['prev'];
                    $nextArticle = $prevNextArticles['next'];

                    break;
                case 'our picks':
                    $settings = \Drupal::config('viidia.settings')->get();

                    $prevNextArticles = $this->getArticlesHelper()->getPrevAndNextArticlesOfGroup($settings['viidia_configuration_special_group_in_day'], $articleId);
                    $prevArticle = $prevNextArticles['prev'];
                    $nextArticle = $prevNextArticles['next'];

                    break;
                case 'category':
                    $data = \Drupal::request()->get('data');
                    if (!empty($data)) {
                        $data = Json::decode($data);
                        $prevNextArticles = $this->getArticlesHelper()->getPrevAndNextArticlesOfCategory($data['categoryId'], $articleId);
                        $prevArticle = $prevNextArticles['prev'];
                        $nextArticle = $prevNextArticles['next'];

                        break;
                    }
                case 'collection':
                    $data = \Drupal::request()->get('data');
                    if (!empty($data)) {
                        $data = Json::decode($data);
                        $prevNextArticles = $this->getArticlesHelper()->getPrevAndNextArticlesOfCollection($data['collectionId'], $articleId);
                        $prevArticle = $prevNextArticles['prev'];
                        $nextArticle = $prevNextArticles['next'];

                        break;
                    }
            }

            return new JsonResponse(['data' => [
                'prev' => $prevArticle,
                'next' => $nextArticle
            ]]);
        }
    }

    private function _buildGetDifferentArticlesQuery($articleId, $operation, $numberOfArticles)
    {
        $query = \Drupal::entityQuery('node')
            ->condition('status', 1)
            ->condition('type', 'article')
            ->condition('nid', $articleId, $operation)
            ->range(0, $numberOfArticles);

        return $query;
    }

    private function _buildResponseArticlesFromSearch($data)
    {
        $articles = [];
        foreach ($data as $articleInfo) {
            $article = $articleInfo['_source'];
            if (!empty($article['sourceOriginalUrl'])) {
                $article['sourceUrl'] = $article['sourceOriginalUrl'];
            }
            $article['id'] = $articleInfo['_id'];
            $articles[] = $article;
        }

        return $articles;
    }

    private function _urlsToAbsolute($baseUrl, $html)
    {
        preg_match_all('/(img src=")([^"#]+)(")/i', $html, $matches);
        if (!empty($matches[2])) {
            foreach ($matches[2] as $num => $match) {
                if (substr($match, 4) !== 'http') {
                    $codec[$match] = $baseUrl . '/' . $match;
                }
            }
            return strtr($html, $codec);
        }

        return $html;
    }
}