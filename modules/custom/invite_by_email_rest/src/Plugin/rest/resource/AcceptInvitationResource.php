<?php

namespace Drupal\invite_by_email_rest\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
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
 *   id = "invite_by_email_accept_invitation_rest",
 *   label = @Translation("Invite By Email - Accept Invitation REST"),
 *   uri_paths = {
 *     "canonical" = "/invite_by_email/accept_invitation/{regCode}",
 *     "https://www.drupal.org/link-relations/create" = "/invite_by_email/accept_invitation"
 *   }
 * )
 */
class AcceptInvitationResource extends ResourceBase
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
     * Check registeration code
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   HTTP response object containing webform submission.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws HttpException in case of error.
     */
    public function get($regCode)
    {
        $invite = \Drupal::entityTypeManager()->getStorage('invite')->loadByProperties(['reg_code' => $regCode]);
        if ($invite) {
            $invite = reset($invite);
            if ($invite->getStatus() == INVITE_USED) {
                throw new BadRequestHttpException('Invitation Code has been used.');
            }
        } else {
            throw new BadRequestHttpException('Invitation Code does not exist.');
        }

        if ($this->currentUser->id()) {
            return new ModifiedResourceResponse(['result' => '0', 'message' => 'Please log out to register new user!']);
        } elseif ($invite->getStatus() == INVITE_USED) {
            return new ModifiedResourceResponse(['result' => '0', 'message' => 'Sorry this invitation has already been used.']);
        } else {
            return new ModifiedResourceResponse(['result' => '1']);
        }
    }
}