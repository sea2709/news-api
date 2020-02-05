<?php

namespace Drupal\viidia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\viidia\SearchClient;

class ImportController extends ControllerBase
{
    public function index()
    {
        $csvFile = drupal_get_path('module', 'viidia') . '/assets/import.csv';
        $row = 0;
        $nodeCount = 0;
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                $row++;
                if ($row > 1) {
                    $body = $data[6];
                    if (!empty($body)) {
                        $body = htmlspecialchars_decode($body);
                    }

                    $references = $data[10];
                    if (!empty($references)) {
                        $references = htmlspecialchars_decode($references);
                    }

                    $nodeData = [
                        'type'        => 'article',
                        'title'       => $data[1],
                        'field_video_source' => $data[2],
                        'body' => [
                            'summary' => $data[5],
                            'value' => $body
                        ],
                        'field_source' => $data[7],
                        'field_source_url' => $data[8],
                        'field_external_referral' => $data[9],
                        'field_references' => $references,
                        'field_category' => [
                            'target_id' => $data[11]
                        ],
                        'field_type' => 'article',
                    ];

                    if (!empty($data[3])) {
                        // Create file object from remote URL.
                        $fileData = file_get_contents($data[3]);
                        if ($fileData) {
                            $tokens = explode('/', $data[3]);
                            $fileName = $tokens[count($tokens) - 1];
                            $file = file_save_data($fileData, 'public://2017-06/' . $fileName, FILE_EXISTS_RENAME);
                            if (!empty($file)) {
                                $nodeData['field_teaser_image'] = [
                                    'target_id' => $file->id(),
                                    'alt' => $data[1],
                                    'title' => $data[1]
                                ];
                            }
                        }
                    }

                    if (!empty($data[4])) {
                        // Create file object from remote URL.
                        $fileData = file_get_contents($data[4]);
                        if ($fileData) {
                            $tokens = explode('/', $data[4]);
                            $fileName = $tokens[count($tokens) - 1];
                            $file = file_save_data($fileData, 'public://2017-06/' . $fileName, FILE_EXISTS_RENAME);
                            if (!empty($file)) {
                                $nodeData['field_image'] = [
                                    'target_id' => $file->id(),
                                    'alt' => $data[1],
                                    'title' => $data[1]
                                ];
                            }
                        }
                    }

                    $node = Node::create($nodeData);
                    $result = $node->save();

                    if ($result === SAVED_NEW) {
                        $nodeCount++;
                    }
                }
            }
            fclose($handle);
        }

        return array(
            '#type' => 'markup',
            '#markup' => $nodeCount . ' ' . $this->t('Done!'),
        );
    }

    public function search() {
        $categories = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('categories');
        $cats = [];
        foreach ($categories as $cat) {
            $cats[$cat->tid] = $cat;
        }

        $nids = \Drupal::entityQuery('node')->condition('status', 1)
            ->condition('type', 'article')->execute();
        $articles =  \Drupal\node\Entity\Node::loadMultiple($nids);

        $nodeCount = 0;

        if (!empty($articles)) {
            $client = SearchClient::build();

            $config = \Drupal::config('viidia.settings');
            $settings = $config->get();

            foreach ($articles as $article) {
                $data = SearchClient::buildArticleBody($article, $cats);
                $createdDate = DrupalDateTime::createFromTimestamp($article->getCreatedTime());
                $changedDate = DrupalDateTime::createFromTimestamp($article->getChangedTime());
                $data['created'] = $createdDate->format("Y-m-d\TH:i:s") . 'Z';
                $data['changed'] = $changedDate->format("Y-m-d\TH:i:s") . 'Z';

                $params = [
                    'index' => $settings['viidia_elasticsearch_index'],
                    'type' => 'article',
                    'id' => $article->id(),
                    'body' => $data,
                ];

                $client->index($params);
                $nodeCount++;
            }
        }

        return array(
            '#type' => 'markup',
            '#markup' => $nodeCount . ' ' . $this->t('Done!'),
        );
    }
}