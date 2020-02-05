<?php

namespace Drupal\invite_by_email_rest\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\invite\Entity\Invite;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Creates a resource for inviting friends via email
 *
 * @RestResource(
 *   id = "invite_by_email_rest",
 *   label = @Translation("Invite By Email REST"),
 *   uri_paths = {
 *     "canonical" = "/invite_by_email/submit",
 *     "https://www.drupal.org/link-relations/create" = "/invite_by_email/submit"
 *   }
 * )
 */
class InviteByEmailResource extends ResourceBase
{
    /**
     *  A curent user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $currentRequest;

    /**
     * Constructs a Drupal\rest\Plugin\ResourceBase object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   The current user instance.
     * @param Symfony\Component\HttpFoundation\Request $current_request
     *   The current request
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats,
                                LoggerInterface $logger, AccountProxyInterface $current_user, Request $current_request)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->currentUser = $current_user;
        $this->currentRequest = $current_request;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('example_rest'),
            $container->get('current_user'),
            $container->get('request_stack')->getCurrentRequest()
        );
    }

    /**
     * Responds to invite friends by email POST requests.
     *
     * @param $email
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function post($data)
    {
        $user = user_load_by_mail($data['email']);
        if (!empty($user)) {
            throw new BadRequestHttpException('There is a user registered this email.');
        }

        if (!empty($data['type'])) {
            $inviteType = \Drupal::config('invite.invite_type.' . $data['type']);
            if (!empty($inviteType)) {
                $inviteTypeData = unserialize($inviteType->get('data'));
                $invite = Invite::create(['type' => $data['type'], 'data' => \GuzzleHttp\json_encode($data)]);
                $invite->field_invite_email_address->value = $data['email'];
                $invite->field_invite_email_subject->value = $inviteTypeData['subject'];
                $invite->field_invite_email_body->value = $inviteTypeData['body'];
                $invite->setPlugin('invite_by_email2');
                $invite->save();

                return new ModifiedResourceResponse($invite);
            }
        }
    }
}