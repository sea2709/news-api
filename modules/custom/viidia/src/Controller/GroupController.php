<?php

namespace Drupal\viidia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;

class GroupController extends ControllerBase
{
    protected $_articlesHelper;

    public function getArticlesHelper() {
        return $this->_articlesHelper ?: \Drupal::service('viidia.articles_helper');
    }

    public function getGroups() {
        $groups = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(
            array('type' => 'article_group', 'status' => 1)
        );
        $objs = [];
        foreach (array_reverse($groups) as $group) {
            $articles = $group->field_articles->referencedEntities();
            $articleObjs = [];
            foreach ($articles as $article) {
                $articleObjs[] = $this->getArticlesHelper()->buildResponseArticle($article);
            }
            $objs[] = [
                'id' => $group->id(),
                'name' => $group->getTitle(),
                'isRelatedGroup' => $group->field_is_related_group->value,
                'articles' => $articleObjs
            ];
        }

        return new JsonResponse(['data' => $objs]);
    }

    public function getGroupById($groupId) {
        $group = Node::load($groupId);

        if ($group) {
            $articles = $group->field_articles->referencedEntities();
            $articleObjs = [];
            foreach ($articles as $article) {
                $articleObjs[] = $this->getArticlesHelper()->buildResponseArticle($article);
            }
            $obj = [
                'id' => $group->id(),
                'name' => $group->getTitle(),
                'isRelatedGroup' => $group->field_is_related_group->getValue(),
                'articles' => $articleObjs
            ];
        } else {
            $obj = null;
        }

        return new JsonResponse(['data' => $obj]);
    }

    public function addArticleToGroup($groupId, $articleId) {
        $group = Node::load($groupId);
        $article = Node::load($articleId);

        if (!empty($group) && !empty($article)) {
            $insert = true;
            foreach ($group->field_articles->referencedEntities() as $e) {
                if ($e->id() == $articleId) {
                    $insert = false;
                    break;
                }
            }

            if ($insert) {
                $group->field_articles->appendItem([
                    'target_id' => $articleId
                ]);
                $group->save();

                return new JsonResponse(['success' => true, 'msg' => 'Add article to our picks successfully !']);
            } else {
                return new JsonResponse(['success' => false, 'msg' => 'Article has already existed in group !']);
            }

            return new JsonResponse(['success' => false, 'msg' => 'Add article to our picks unsuccessfully !']);
        }
    }
}