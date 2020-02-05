<?php

namespace Drupal\viidia\Controller;

use Drupal\Core\Controller\ControllerBase;

class UserController extends ControllerBase {
    public function invitations() {
        $currentUser = \Drupal::currentUser();
        $form = \Drupal::formBuilder()->getForm('Drupal\invite_by_email\Form\InviteByEmailBlockForm');
        return [
            '#theme' => 'invitations',
            '#form' => $form,
            '#title' => $currentUser->getAccountName()
        ];
    }

    public function submitArticles() {
        $currentUser = \Drupal::currentUser();
        $form = \Drupal\webform\Entity\Webform::load('contribute_article');
        return [
            '#theme' => 'articles',
            '#form' => \Drupal::entityTypeManager()->getViewBuilder('webform')->view($form),
            '#title' => $currentUser->getAccountName()
        ];
    }
}