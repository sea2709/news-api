<?php

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function viidia_user_token_info()
{
    $info = [];
    $info['types']['viidia_user'] = [
        'name' => t('Viidia User'),
        'description' => ('Viidia User tokens.'),
    ];

    $info['tokens']['viidia_user']['name'] = [
        'name' => t('Display Name'),
        'description' => t('Display Name'),
        'dynamic' => TRUE,
    ];

    $info['tokens']['viidia_user']['username'] = [
        'name' => t('Username'),
        'description' => t('Username'),
        'dynamic' => TRUE,
    ];

    $info['tokens']['viidia_user']['email'] = [
        'name' => t('Email'),
        'description' => t('Email'),
        'dynamic' => TRUE,
    ];

    $info['tokens']['viidia_user']['reset_link'] = [
        'name' => t('Reset URL'),
        'description' => t('The Reset URL for updating user password'),
        'dynamic' => TRUE,
    ];

    $info['tokens']['viidia_user']['reset_link:user_id'] = [
        'name' => t('User ID'),
        'description' => t('User ID'),
        'dynamic' => TRUE,
    ];

    $info['tokens']['viidia_user']['reset_link:timestamp'] = [
        'name' => t('Timestamp'),
        'description' => t('Timestamp'),
        'dynamic' => TRUE,
    ];

    $info['tokens']['viidia_user']['reset_link:hash'] = [
        'name' => t('Hash'),
        'description' => t('Hash'),
        'dynamic' => TRUE,
    ];

    return $info;
}

/**
 * Implements hook_tokens().
 */
function viidia_user_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata)
{
    $replacements = [];
    if ($type == 'viidia_user') {
        foreach ($tokens as $name => $original) {
            switch ($name) {
                case 'name':
                    $replacements[$original] = $data['user']->get('field_name')->value;
                    break;

                case 'username':
                    $replacements[$original] = $data['user']->getUsername();
                    break;

                case 'email':
                    $replacements[$original] = $data['user']->getEmail();
                    break;

                case 'reset_link:user_id':
                    $replacements[$original] = $data['user']->id();
                    break;

                case 'reset_link:timestamp':
                    $replacements[$original] = $data['timestamp'];
                    break;

                case 'reset_link:hash':
                    $replacements[$original] = user_pass_rehash($data['user'], $data['timestamp']);
                    break;

                case 'reset_link':
                    $replacements[$original] = $data['reset_link'];
                    break;

                default:
                    break;
            }
        }
    }

    return $replacements;
}
