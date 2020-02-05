<?php

namespace Drupal\viidia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class VideoController extends ControllerBase
{
    protected $_imagesHelper;

    public function getImagesHelper() {
        return $this->_imagesHelper ?: \Drupal::service('viidia.images_helper');
    }

    private function _buildResponseVideos($nids)
    {
        $entries = \Drupal\node\Entity\Node::loadMultiple($nids);
        $videos = [];
        foreach ($entries as $entry) {
            $videoUrl = !empty($entry->field_video_source) ? $entry->field_video_source->getValue(true) : '';
            if (!empty($videoUrl[0]['uri'])) {
                $videoUrl = $videoUrl[0]['uri'];
            }

            $videos[] = [
                'id' => $entry->id(),
                'name' => $entry->getTitle(),
                'image' => !empty($entry->field_image->entity) ? $this->getImagesHelper()->getImageUrl($entry->field_image, 'large') : '',
                'videoHtml' => $entry->field_video_html->value,
                'videoUrl' => $videoUrl
            ];
        }

        return $videos;
    }

    public function getLatestVideos($limit = 5, $pageNumber = 1, $videosPerPage = 20)
    {
        $totalQuery = \Drupal::entityQuery('node')->condition('status', 1)->condition('type', 'video');
        $total = $totalQuery->count()->execute();

        $query = \Drupal::entityQuery('node')->condition('status', 1)->condition('type', 'video')
            ->sort('created', 'DESC');
        if ($limit != 0) {
            $query->range(0, $limit);
        } else {
            $query->range($videosPerPage * ($pageNumber - 1), $videosPerPage);
        }
        $videoIds = $query->execute();

        return new JsonResponse(['total' => $total, 'data' => $this->_buildResponseVideos($videoIds)]);
    }
}