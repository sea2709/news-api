<?php

namespace Drupal\invite_by_email_rest\Plugin\Invite;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\invite\InvitePluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\Role;

/**
 * Class for Invite by Email.
 *
 * @Plugin(
 *   id = "invite_by_email2",
 *   label = @Translation("Invite By Email 2")
 * )
 */
class InviteByEmail2 implements InvitePluginInterface
{

    use StringTranslationTrait;

    /**
     * {@inheritdoc}
     */
    public function send($invite)
    {
        /*
         * @var $token \Drupal\token\Token
         * @var $mail \Drupal\Core\Mail\MailManager
         */
        $bubbleable_metadata = new BubbleableMetadata();
        $token = \Drupal::service('token');
        $mail = \Drupal::service('plugin.manager.mail');
        $mail_key = $invite->get('type')->value;
        $inviteType = \Drupal::config('invite.invite_type.' . $invite->get('type')->value);
        $inviteTypeData = unserialize($inviteType->get('data'));
        // Prepare message.
        $message = $mail->mail('invite_by_email_rest', $mail_key, $invite->get('field_invite_email_address')->value,
            $invite->activeLangcode, [], $invite->getOwner()->getEmail(), FALSE);

        $inviteData = $invite->get('data')->value;
        if ($inviteData) {
            $inviteData = \GuzzleHttp\json_decode($inviteData);
        }
        $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
        $message['subject'] = $token->replace($invite->get('field_invite_email_subject')->value, ['invite' => $invite], [], $bubbleable_metadata);
        $owner = $invite->getOwner();
        $ownerRoles = Role::loadMultiple($owner->getRoles());
        $ownerRoleTokens = [];
        foreach ($ownerRoles as $role) {
            array_push($ownerRoleTokens, $role->get('label'));
        }
        $body = [
            '#theme' => 'invite_by_email_' . $mail_key,
            '#body' => $token->replace($invite->get('field_invite_email_body')->value, ['invite' => $invite], [], $bubbleable_metadata),
            '#invitee_name' => $inviteData->name ? $inviteData->name : '',
            '#inviter_image' => $owner->user_picture ? $this->getImageUrl($owner->user_picture) : NULL,
            '#inviter_roles' => implode($ownerRoleTokens, ', '),
            '#inviter_name' => $owner->get('field_name')->value,
            '#inviter_url' => $token->replace($inviteTypeData['inviter_profile_url'], ['invite' => $invite]),
            '#inviter_description' => $inviteData->message ? $inviteData->message : $owner->field_description->value,
            '#accept_link' => $token->replace($inviteTypeData['register_url'], ['reg_code' => $invite->getRegCode()])
        ];
        $message['body'] = \Drupal::service('renderer')
            ->render($body)
            ->__toString();
        // Send.
        $system = $mail->getInstance([
            'module' => 'invite_by_email_rest',
            'key' => $mail_key,
        ]);

        $result = $system->mail($message);

        if ($result) {

            drupal_set_message($this->t('Invitation has been sent.'));

            $mail_user = $message['to'];

            \Drupal::logger('invite')->notice('Invitation has been sent for: @mail_user.', [
                '@mail_user' => $mail_user,
            ]);
        } else {

            drupal_set_message($this->t('Failed to send a message.'), 'error');

            \Drupal::logger('invite')->error('Failed to send a message.');
        }

    }

    public function getImageUrl($imageField, $size = 'thumbnail')
    {
        if (!empty($imageField) && !empty($imageField->entity)) {
            $imageUri = $imageField->entity->getFileUri();
            return $imageUri ? \Drupal\image\Entity\ImageStyle::load($size)->buildUrl($imageUri) : '';
        }

        return '';
    }
}
