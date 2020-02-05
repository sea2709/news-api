<?php

namespace Drupal\viidia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;

class CollectionController extends ControllerBase
{
    protected $_articlesHelper;

    private function _getImageUrl($imageField)
    {
        if (!empty($imageField) && !empty($imageField->entity)) {
            $imageUri = $imageField->entity->getFileUri();
            return $imageUri ? \Drupal\image\Entity\ImageStyle::load('large')->buildUrl($imageUri) : '';
        }

        return '';
    }

    public function getArticlesHelper() {
        return $this->_articlesHelper ?: \Drupal::service('viidia.articles_helper');
    }

    public function getCollections() {
        $con = Database::getConnection();

        $subQuery = $con->select('node__field_articles', 'a');
        $subQuery->where('a.entity_id = n.nid');
        $subQuery->fields('a', ['field_articles_target_id']);
        $subQuery->range(0, 1);
        $subQuery->orderBy('a.delta', 'DESC');

        $query = $con->select('node', 'n');
        $query->innerJoin('node_field_data', 'nd', 'nd.nid = n.nid');
        $query->condition('nd.status', 1);
        $query->condition('n.type', 'collection');
        $query->addExpression('(' . $subQuery . ')', 'article_id');
        $query->fields('n', ['nid']);

        $data = $query->execute()->fetchAllAssoc('nid');

        $articleIds = [];
        foreach ($data as $key => $value) {
            $articleIds[] = $value->article_id;
        }

        $collections = Node::loadMultiple(array_keys($data));
        $articles = Node::loadMultiple($articleIds);

        $objs = [];
        foreach ($collections as $collection) {
            $obj = [
                'id' => $collection->id(),
                'name' => $collection->getTitle()
            ];
            if (isset($articles[$data[$collection->id()]->article_id])) {
                $article = $articles[$data[$collection->id()]->article_id];
                $obj['image'] = !empty($article->field_image) ? $this->_getImageUrl($article->field_image, 'large') : '';
            }
            $objs[] = $obj;
        }

        return new JsonResponse(['data' => $objs]);
    }

    private function _buildResponseArticles($entries)
    {
        $articles = [];
        foreach ($entries as $entry) {
            $articles[] = $this->getArticlesHelper()->buildResponseArticle($entry);
        }

        return $articles;
    }

    public function getCollectionById($collectionId) {
        $collection = Node::load($collectionId);

        return new JsonResponse([
            'data' => [
                'id' => $collection->id(),
                'name' => $collection->getTitle(),
                'image' => !empty($collection->field_image->entity) ? $this->_getImageUrl($collection->field_image, 'large') : '',
                'articles' => $this->_buildResponseArticles($collection->field_articles->referencedEntities())
            ]
        ]);
    }
}