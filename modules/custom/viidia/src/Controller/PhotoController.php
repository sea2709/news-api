<?php

namespace Drupal\viidia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;

class PhotoController extends ControllerBase
{
    protected $_imagesHelper;

    public function getImagesHelper() {
        return $this->_imagesHelper ?: \Drupal::service('viidia.images_helper');
    }

    private function _buildResponsePhotoSets($nids, $firstItemOnly = false, $fetchImages = true)
    {
        $entries = \Drupal\node\Entity\Node::loadMultiple($nids);
        $photoSets = [];
        foreach ($entries as $entry) {
            $images = [];

            if ($fetchImages) {
                foreach ($entry->field_images as $imageItem) {
                    $images[] = [
                        'src' => $this->getImagesHelper()->getImageUrl($imageItem, 'large'),
                        'title' => html_entity_decode($imageItem->title),
                        'alt' => html_entity_decode(html_entity_decode($imageItem->alt))
                    ];
                }
            }

            $photoSets[] = [
                'id' => $entry->id(),
                'name' => $entry->getTitle(),
                'images' => $images
            ];
        }

        if ($firstItemOnly) {
            if (count($photoSets) > 0) {
                return $photoSets[0];
            }

            return null;
        }

        return $photoSets;
    }

    public function getLatestPhotoSets($from = 0, $limit = 5)
    {
        $query = \Drupal::entityQuery('node')->condition('status', 1)->condition('type', 'photo_set')
            ->sort('created', 'DESC');
        $query->range($from, $limit);
        $photoSetIds = $query->execute();

        $totalQuery = \Drupal::entityQuery('node');
        $totalQuery->condition('status', 1);
        $totalQuery->condition('type', 'photo_set');
        $total = $totalQuery->count()->execute();

        return new JsonResponse(['data' => $this->_buildResponsePhotoSets($photoSetIds), 'total' => $total]);
    }

    public function getPhotoSetById($id) {
        $photoSet = \Drupal\node\Entity\Node::load($id);
        $images = [];
        if ($photoSet) {
            foreach ($photoSet->field_images as $imageItem) {
                $images[] = [
                    'src' => $this->getImagesHelper()->getImageUrl($imageItem, 'large'),
                    'title' => html_entity_decode($imageItem->title),
                    'alt' => html_entity_decode(html_entity_decode($imageItem->alt))
                ];
            }

            if (!empty($photoSet->field_source_article)) {
                $article = reset($photoSet->field_source_article->referencedEntities());
            }
        }

        $responseObj = [
            'id' => $photoSet->id(),
            'name' => $photoSet->getTitle(),
            'images' => $images
        ];

        if (!empty($article)) {
            $responseObj['article'] = [
                'id' => $article->id(),
                'name' => $article->getTitle()
            ];
        }

        return new JsonResponse(['data' => $responseObj]);
    }

    public function getPrevAndNextPhotoSet($id) {
        $photoSet = Node::load($id);

        $prevPhotoSetId = null;
        $nextPhotoSetId = null;

        if ($photoSet) {
            $prevQuery = \Drupal::entityQuery('node')->condition('status', 1)
                ->condition('type', 'photo_set')->condition('created', $photoSet->getCreatedTime(), '>')
                ->sort('created', 'ASC');
            $prevQuery->range(0, 1);
            $prevPhotoSetId = $prevQuery->execute();

            $nextQuery = \Drupal::entityQuery('node')->condition('status', 1)
                ->condition('type', 'photo_set')->condition('created', $photoSet->getCreatedTime(), '<')
                ->sort('created', 'DESC');
            $nextQuery->range(0, 1);
            $nextPhotoSetId = $nextQuery->execute();
        }

        return new JsonResponse(['data' => [
            'prev' => $prevPhotoSetId ? $this->_buildResponsePhotoSets($prevPhotoSetId, true, false) : null,
            'next' => $nextPhotoSetId ? $this->_buildResponsePhotoSets($nextPhotoSetId, true, false) : null
        ]]);
    }
}