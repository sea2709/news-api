<?php

namespace Drupal\viidia_user\Plugin\rest\resource;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ImmutableConfig;
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
 *   id = "viidia_user_update_password_rest",
 *   label = @Translation("Viidia User update password REST"),
 *   uri_paths = {
 *     "canonical" = "/viidia_user/update/reset-password",
 *     "https://www.drupal.org/link-relations/create" = "/viidia_user/update/reset-password",
 *   },
 * )
 */
class UserUpdatePasswordResource extends ResourceBase
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
     * @param UserAuthInterface $user_auth
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats,
        LoggerInterface $logger, ImmutableConfig $user_settings, Request $current_request, UserAuthInterface $user_auth)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->userSettings = $user_settings;
        $this->currentRequest = $current_request;
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
        if (empty($data['user_id']) || empty($data['timestamp']) || empty($data['hash'])) {
            throw new BadRequestHttpException('Invalid data submission.');
        }

        $current = \Drupal::time()->getRequestTime();

        $timestamp = intval($data['timestamp'], 10);
        $user = User::load($data['user_id']);
        if ($user === NULL || !$user->isActive()) {
            throw new AccessDeniedHttpException('User not found');
        }

        // Time out, in seconds, until login URL expires.
        $timeout = $this->userSettings->get('password_reset_timeout');

        if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
            throw new PreconditionFailedHttpException('This reset password URL is not valid !');
        }
        elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current)
            && Crypt::hashEquals($data['hash'], user_pass_rehash($user, $timestamp))) {

            $uid = $this->userAuth->authenticate($user->getUsername(), $data['password']);
            if (!empty($uid)) {
                throw new PreconditionFailedHttpException('Please do not use your old password !');
            }

            $user->setPassword($data['password']);
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

        throw new PreconditionFailedHttpException('Update password failed !');
    }
}
