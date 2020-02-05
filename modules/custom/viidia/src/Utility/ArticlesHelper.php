<?php

namespace Drupal\viidia\Utility;

use Drupal\node\Entity\Node;
use Drupal\Core\Database\Database;
use Drupal\rel_to_abs\Plugin\Filter\RelToAbs;

class ArticlesHelper
{
    public function __construct()
    {
    }

    public function buildResponseArticle(Node $entry)
    {
        $sourceUrl = !empty($entry->field_source_url) ? $entry->field_source_url->getValue(true) : '';
        $videoUrl = !empty($entry->field_video_source) ? $entry->field_video_source->getValue(true) : '';
        if (!empty($videoUrl[0]['uri'])) {
            $videoUrl = $videoUrl[0]['uri'];
        }

        $body = $entry->body->getValue();
        $imageUri = !empty($entry->field_image->entity) ? $entry->field_image->entity->getFileUri() : '';
        $teaserImageUri = !empty($entry->field_teaser_image->entity) ? $entry->field_teaser_image->entity->getFileUri() : '';

        $filter = new RelToAbs(array(), 'rel_to_abs', array('provider' => 'rel_to_abs'));

        $articleSource = $entry->field_article_source->referencedEntities();
        if (!empty($articleSource)) {
            $articleSource = reset($articleSource);
        }

        $article = [
            'id' => $entry->id(),
            'name' => $entry->getTitle(),
            'body' => $body[0]['value'] ? $filter->process($body[0]['value'], NULL)->getProcessedText() : '',
            'summary' => $body[0]['summary'],
            'categoryId' => !empty($entry->field_category) ? $entry->field_category->getString() : '',
            'image' => $imageUri ? \Drupal\image\Entity\ImageStyle::load('large')->buildUrl($imageUri) : '',
            'teaserImage' => $imageUri ? \Drupal\image\Entity\ImageStyle::load('large')->buildUrl($teaserImageUri) : '',
            'sourceUrl' => !empty($sourceUrl[0]['uri']) ? $sourceUrl[0]['uri'] : '',
            'videoUrl' => $videoUrl,
            'videoHtml' => $entry->field_video_html->value,
            'createdDate' => \Drupal::service('date.formatter')->format($entry->getCreatedTime(), 'custom', DATE_ISO8601),
            'externalReferral' => $entry->field_external_referral->value,
            'articleSource' => !empty($articleSource)?['id' => $articleSource->id(), 'name' => $articleSource->getTitle(), 'redirect' => $articleSource->field_redirect->value]:null
        ];

        return $article;
    }

    public function getTotalTrendingArticles() {
        $con = Database::getConnection();
        return $con->query("SELECT count(*) FROM {node_counter}")->fetchField();
    }

    public function getTrendingArticles($pageNumber, $articlesPerPage) {
        $con = Database::getConnection();
        $query = $con->select('node', 'n');
        $query->leftJoin('node_counter', 'nc', 'n.nid = nc.nid');
        $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
        $query->fields('n', ['nid']);
        $query->range($articlesPerPage * ($pageNumber - 1), $articlesPerPage);
        $query->where('n.type LIKE \'article\'');
        $query->where('nfd.status = 1');
        $query->orderBy('nc.totalcount', 'DESC');
        $query->orderBy('nc.daycount', 'DESC');
        $query->orderBy('n.nid', 'DESC');

        $articleIds = $query->execute()->fetchCol();

        return $this->buildResponseArticles($articleIds);
    }

    public function getPrevAndNextTredingArticles($articleId) {
        $con = Database::getConnection();
        $result = $con->query("SELECT * FROM {node_counter} WHERE nid = :nid", [
            ':nid' => $articleId
        ])->fetchObject();

        $query = $con->select('node_counter', 'nc');
        $query->innerJoin('node', 'n', 'n.nid = nc.nid');
        $query->fields('nc', ['nid']);
        $query->where('n.type LIKE \'article\'');
        $query->where('nc.totalcount = :tc', [':tc' => $result->totalcount]);
        $query->orderBy('nc.daycount', 'DESC');
        $query->orderBy('nc.nid', 'DESC');

        $records = $query->execute()->fetchCol();

        $prevId = null;
        $nextId = null;

        for ($i = 0; $i < count($records); $i++) {
            $id = $records[$i];
            if ($id === $articleId) {
                if ($i > 0) {
                    $prevId = $records[$i - 1];
                }

                if ($i < count($records) - 1) {
                    $nextId = $records[$i + 1];
                }
            }
        }

        if ($prevId === null) {
            $preQuery = $con->select('node_counter', 'nc');
            $preQuery->innerJoin('node', 'n', 'n.nid = nc.nid');
            $preQuery->fields('nc', ['nid']);
            $preQuery->where('n.type LIKE \'article\'');
            $preQuery->where('nc.totalcount > :tc', [':tc' => $result->totalcount]);
            $preQuery->orderBy('nc.totalcount', 'ASC');
            $preQuery->orderBy('nc.daycount', 'ASC');
            $preQuery->orderBy('nc.nid', 'ASC');
            $preQuery->range(0, 1);

            $prevId = $preQuery->execute()->fetchField();

            if (!$prevId) {
                $preQuery = $con->select('node', 'n');
                $preQuery->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
                $preQuery->fields('n', ['nid']);
                $preQuery->range(0, 1);
                $preQuery->where('n.type LIKE \'article\'');
                $preQuery->where('nfd.status = 1');
                $preQuery->where('n.nid > :nid', [':nid' => $articleId]);
                $preQuery->orderBy('n.nid', 'ASC');

                $prevId = $preQuery->execute()->fetchField();
            }
        }

        if ($nextId === null) {
            $nextQuery = $con->select('node_counter', 'nc');
            $nextQuery->innerJoin('node', 'n', 'n.nid = nc.nid');
            $nextQuery->fields('nc', ['nid']);
            $nextQuery->where('n.type LIKE \'article\'');
            $nextQuery->where('nc.totalcount < :tc', [':tc' => $result->totalcount]);
            $nextQuery->orderBy('nc.totalcount', 'DESC');
            $nextQuery->orderBy('nc.daycount', 'DESC');
            $nextQuery->orderBy('nc.nid', 'DESC');
            $nextQuery->range(0, 1);

            $nextId = $nextQuery->execute()->fetchField();

            if (!$nextId) {
                $nextQuery = $con->select('node', 'n');
                $nextQuery->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
                $nextQuery->fields('n', ['nid']);
                $nextQuery->range(0, 1);
                $nextQuery->where('n.type LIKE \'article\'');
                $nextQuery->where('nfd.status = 1');
                $nextQuery->where('n.nid < :nid', [':nid' => $articleId]);
                $nextQuery->orderBy('n.nid', 'DESC');

                $nextId = $nextQuery->execute()->fetchField();
            }
        }

        return [
            'prev' => $prevId ? $this->buildResponseArticle(Node::load($prevId)) : null,
            'next' => $nextId ? $this->buildResponseArticle(Node::load($nextId)) : null
        ];
    }

    public function getPrevAndNextArticlesOfGroup($groupId, $articleId = null) {
        $prevArticle = null;
        $nextArticle = null;

        $group = Node::load($groupId); 
        if ($group) {
            $articles = $group->field_articles->referencedEntities();
            for ($i = 0; $i < count($articles); $i++) {
                $article = $articles[$i];
                if ($article->id() === $articleId) {
                    if ($i > 0) {
                        $prevArticle = $articles[$i - 1];
                    }
                    if ($i < count($articles) - 1) {
                        $nextArticle = $articles[$i + 1];
                    }
                }
            }
        }

        return [
            'prev' => $prevArticle ? $this->buildResponseArticle($prevArticle) : null,
            'next' => $nextArticle ? $this->buildResponseArticle($nextArticle) : null
        ];
    }

    public function getPrevAndNextArticlesOfCategory($categoryId, $articleId = null) {
        $node = Node::load($articleId);

        $prevId = null;
        $nextId = null;

        if ($node) {
            $prevQuery = \Drupal::entityQuery('node');
            $prevQuery->condition('status', 1);
            $prevQuery->condition('type', 'article');
            $prevQuery->condition('field_category', $categoryId);
            $prevQuery->condition('created', $node->getCreatedTime(), '>');
            $prevQuery->sort('created', 'ASC');
            $prevQuery->range(0, 1);

            $prevId = current($prevQuery->execute());

            $nextQuery = \Drupal::entityQuery('node');
            $nextQuery->condition('status', 1);
            $nextQuery->condition('type', 'article');
            $nextQuery->condition('field_category', $categoryId);
            $nextQuery->condition('created', $node->getCreatedTime(), '<');
            $nextQuery->sort('created', 'DESC');
            $nextQuery->range(0, 1);

            $nextId = current($nextQuery->execute());
        }

        return [
            'prev' => $prevId ? $this->buildResponseArticle(Node::load($prevId)) : null,
            'next' => $nextId ? $this->buildResponseArticle(Node::load($nextId)) : null
        ];
    }

    public function getPrevAndNextArticlesOfCollection($collectionId, $articleId = null) {
        $prevArticle = null;
        $nextArticle = null;

        $collection = Node::load($collectionId);
        if ($collection) {
            $articles = $collection->field_articles->referencedEntities();
            for ($i = 0; $i < count($articles); $i++) {
                $article = $articles[$i];
                if ($article->id() === $articleId) {
                    if ($i > 0) {
                        $prevArticle = $articles[$i - 1];
                    }
                    if ($i < count($articles) - 1) {
                        $nextArticle = $articles[$i + 1];
                    }
                }
            }
        }

        return [
            'prev' => $prevArticle ? $this->buildResponseArticle($prevArticle) : null,
            'next' => $nextArticle ? $this->buildResponseArticle($nextArticle) : null
        ];
    }

    public function buildResponseArticles($nids, $firstItemOnly = false)
    {
        $entries = \Drupal\node\Entity\Node::loadMultiple($nids);
        $articles = [];
        foreach ($entries as $entry) {
            $articles[] = $this->buildResponseArticle($entry);
        }

        if ($firstItemOnly && !empty($articles)) {
            $article = $articles[0];
            $body = $article['body'];
            if (!empty($body)) {
                $body = $this->urlsToAbsolute(\Drupal::request()->getUriForPath('/'), $body);
                $article['body'] = $body;
            }
            return $article;
        }

        return ($firstItemOnly && !empty($articles)) ? $articles[0] : $articles;
    }

    public function urlsToAbsolute($baseUrl, $html)
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