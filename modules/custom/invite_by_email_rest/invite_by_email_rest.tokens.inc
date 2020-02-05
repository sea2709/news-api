<?php

/**
 * @file
 * Invite tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Link;

/**
 * Implements hook_token_info().
 */
function invite_by_email_rest_token_info()
{
    $info = [];
    $info['types']['invite_by_email_rest'] = [
        'name' => t('Invite by email REST'),
        'description' => ('Invite by email REST tokens.'),
    ];

    $info['tokens']['invite_by_email_rest']['reg-code'] = [
        'name' => t('Register Code'),
        'description' => t('Register Code'),
        'dynamic' => TRUE,
    ];

    return $info;
}

/**
 * Implements hook_tokens().
 */
function invite_by_email_rest_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata)
{
    $replacements = [];
    if ($type == 'invite_by_email_rest') {
        foreach ($tokens as $name => $original) {
            switch ($name) {
                case 'reg-code':
                    $replacements[$original] = $data['reg_code'];
                    break;

                case 'inviter:name':
                    $replacements[$original] = $data['invite']->getOwner()->get('field_name')->value;
                    break;

                case 'inviter:username':
                    $replacements[$original] = $data['invite']->getOwner()->getUsername();
                    break;
                default:
            }
        }
    }

    return $replacements;
}
