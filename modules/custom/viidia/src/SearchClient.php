<?php
namespace Drupal\viidia;
use Drupal\taxonomy\Entity\Term;
use Elasticsearch\ClientBuilder;

class SearchClient {
    public static function build() {
        $config = \Drupal::config('viidia.settings');
        $settings = $config->get();

        $hosts = [
            [
                'host' => $settings['viidia_elasticsearch_host'],
//                'user' => $settings['viidia_elasticsearch_user'],
                'port' => $settings['viidia_elasticsearch_port'],
//                'pass' => $settings['viidia_elasticsearch_password'],
                'scheme' => $settings['viidia_elasticsearch_schema']
            ]
        ];
        $client = ClientBuilder::create()->setHosts($hosts)->build();

        return $client;
    }

    public static function buildArticleBody($article, $cats = []) {
        $sourceUrl = !empty($article->field_source_url) ? $article->field_source_url->getValue(true) : '';
        $videoUrl = !empty($article->field_video_source) ? $article->field_video_source->getValue(true) : '';
        if (!empty($videoUrl[0]['uri'])) {
            $videoUrl = $videoUrl[0]['uri'];
        }

        $body = $article->body->getValue();
        $imageUri = !empty($article->field_image->entity) ? $article->field_image->entity->getFileUri() : '';
        $teaserImageUri = !empty($article->field_teaser_image->entity) ? $article->field_teaser_image->entity->getFileUri() : '';

        $categoryName = '';
        $categoryId = !empty($article->field_category) ? $article->field_category->getString() : '';
        if (!empty($categoryId)) {
            if (empty($cats)) {
                $cat = Term::load($categoryId);
                $categoryName = $cat->getName();
            } else {
                if (!empty($cats[$categoryId])) {
                    $cat = $cats[$categoryId];
                    $categoryName = $cat->name;
                }
            }
        }

        $articleSource = $article->field_article_source->referencedEntities();
        if (!empty($articleSource)) {
            $articleSource = reset($articleSource);
        }

        $data = [
            'name' => $article->getTitle(),
            'body' => $body[0]['value'],
            'summary' => $body[0]['summary'],
            'image' => $imageUri ? \Drupal\image\Entity\ImageStyle::load('large')->buildUrl($imageUri) : '',
            'teaserImage' => $imageUri ? \Drupal\image\Entity\ImageStyle::load('large')->buildUrl($teaserImageUri) : '',
            'featured' => $article->isPromoted(),
            'articleSource' => !empty($articleSource)?['id' => $articleSource->id(), 'name' => $articleSource->getTitle()]:null
        ];

        if (!empty($categoryId) && !empty($categoryName)) {
            $data['categoryId'] = $categoryId;
            $data['categoryName'] = $categoryName;
        }

        if (!empty($sourceUrl[0]['uri'])) {
            $data['sourceOriginalUrl'] = $sourceUrl[0]['uri'];
        }

        if (!empty($videoUrl[0]['uri'])) {
            $data['videoUrl'] = $videoUrl;
        }

        $videoHtml = $article->field_video_html->value;
        if (!empty($videoHtml)) {
            $data['videoHtml'] = $article->field_video_html->value;
        }

        return $data;
    }
}