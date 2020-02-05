<?php

namespace Drupal\viidia_user\Plugin\rest\resource;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Session\AccountInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResourceAccessTrait;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserAuthInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Represents user update password as a resource.
 *
 * @RestResource(
 *   id = "viidia_user_update_existing_password_rest",
 *   label = @Translation("Viidia User update existing password REST"),
 *   uri_paths = {
 *     "canonical" = "/viidia_user/update/password",
 *     "https://www.drupal.org/link-relations/create" = "/viidia_user/update/password",
 *   },
 * )
 */
class UserUpdateExistingPasswordResource extends ResourceBase
{

    use EntityResourceValidationTrait;
    use EntityResourceAccessTrait;

    /**
     * User settings config instance.
     *
     * @var \Drupal\Core\Config\ImmutableConfig
     */
    protected $userSettings;

    /**
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $currentRequest;

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $currentUser;

    /**
     * The user authentication.
     *
     * @var \Drupal\user\UserAuthInterface
     */
    protected $userAuth;

    /**
     * Constructs a new UserRegistrationResource instance.
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
     * @param \Drupal\Core\Config\ImmutableConfig $user_settings
     *   A user settings config instance.
     * @param Symfony\Component\HttpFoundation\Request|Request $current_request
     *   The current request
     * @param AccountInterface $current_user
     * @param UserAuthInterface $user_auth
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats,
        LoggerInterface $logger, ImmutableConfig $user_settings, Request $current_request, AccountInterface $current_user,
        UserAuthInterface $user_auth)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->userSettings = $user_settings;
        $this->currentRequest = $current_request;
        $this->currentUser = $current_user;
        $this->userAuth = $user_auth;
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
            $container->get('logger.factory')->get('rest'),
            $container->get('config.factory')->get('user.settings'),
            $container->get('request_stack')->getCurrentRequest(),
            $container->get('current_user'),
            $container->get('user.auth')
        );
    }

    /**
     * Responds to user update password POST request.
     *
     * @param $data
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     */
    public function post($data)
    {
        if ($this->currentUser->isAuthenticated()) {
            $user = \Drupal\user\Entity\User::load($this->currentUser->id());
            if (empty($data['pass'][0]['existing']) || empty($data['pass'][0]['value'])) {
                throw new BadRequestHttpException('Invalid data submission.');
            }

            $uid = $this->userAuth->authenticate($user->getAccountName(), $data['pass'][0]['existing']);
            if (empty($uid) || $uid !== $user->id()) {
                throw new PreconditionFailedHttpException('Your current password is not correct !');
            }

            if ($data['pass'][0]['existing'] === $data['pass'][0]['value']) {
                throw new PreconditionFailedHttpException('Your new password must be different from your old one !');
            }

            $user->setPassword($data['pass'][0]['value']);
            $result = $user->save();

            if ($result === SAVED_UPDATED) {
                $mail = \Drupal::service('plugin.manager.mail');

                if (empty($site_mail)) {
                    $site_mail = \Drupal::config('system.site')->get('mail');
                }
                if (empty($site_mail)) {
                    $site_mail = ini_get('sendmail_from');
                }

                $config = \Drupal::config('viidia_user.settings');
                $settings = $config->get();
                $token = \Drupal::service('token');

                // Prepare message.
                $message = $mail->mail('viidia_user', 'update_password', $user->getEmail(),
                    $user->getPreferredLangcode(), [], $site_mail, FALSE);
                $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
                $message['subject'] = $settings['viidia_user_update_pass_subject'];
                $message['body'] = $token->replace($settings['viidia_user_update_pass_body'], ['user' => $user]);

                // Send.
                $system = $mail->getInstance([
                    'module' => 'viidia_user',
                    'key' => 'update_password',
                ]);

                $result = $system->mail($message);
            }

            // Let the user's password be changed without the current password check.
            return new ModifiedResourceResponse([
                'result' => $result
            ]);
        }

        throw new BadRequestHttpException('Request not allow !');
    }
}
