<?php

namespace Drupal\viidia_user\Plugin\rest\resource;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResourceAccessTrait;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Represents user forgot password as a resource.
 *
 * @RestResource(
 *   id = "viidia_user_forgot_password_rest",
 *   label = @Translation("Viidia User forgot password REST"),
 *   uri_paths = {
 *     "canonical" = "/viidia_user/forgot-password",
 *     "https://www.drupal.org/link-relations/create" = "/viidia_user/forgot-password",
 *   },
 * )
 */
class UserForgotPasswordResource extends ResourceBase
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
     * @param Symfony\Component\HttpFoundation\Request $current_request
     *   The current request
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats,
        LoggerInterface $logger, ImmutableConfig $user_settings, Request $current_request)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->userSettings = $user_settings;
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
            $container->get('logger.factory')->get('rest'),
            $container->get('config.factory')->get('user.settings'),
            $container->get('request_stack')->getCurrentRequest()
        );
    }

    /**
     * Responds to user forgot password POST request.
     *
     * @param $data
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     */
    public function post($data)
    {
        if (empty($data['username'])) {
            throw new BadRequestHttpException('Please input username / email');
        }

        $username = $data['username'];
        $user = user_load_by_name($username);
        if (empty($user)) {
            $user = user_load_by_mail($username);
        }

        if (empty($user)) {
            throw new BadRequestHttpException('This username/email does not exist !');
        }

        $mail = \Drupal::service('plugin.manager.mail');

        $site_mail = \Drupal::config('system.site')->get('mail_notification');
        // If the custom site notification email has not been set, we use the site
        // default for this.
        if (empty($site_mail)) {
            $site_mail = \Drupal::config('system.site')->get('mail');
        }
        if (empty($site_mail)) {
            $site_mail = ini_get('sendmail_from');
        }

        $config = \Drupal::config('viidia_user.settings');
        $settings = $config->get();
        $token = \Drupal::service('token');

        $resetLink = $token->replace($settings['viidia_user_forgot_pass_reset_link'],
            ['user' => $user, 'timestamp' => \Drupal::time()->getRequestTime()]);

        // Prepare message.
        $message = $mail->mail('viidia_user', 'forgot_password', $user->getEmail(),
            $user->getPreferredLangcode(), [], $site_mail, FALSE);
        $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
        $message['subject'] = $settings['viidia_user_forgot_pass_subject'];
        $message['body'] = $token->replace($settings['viidia_user_forgot_pass_body'],
            ['user' => $user, 'reset_link' => $resetLink]);

        // Send.
        $system = $mail->getInstance([
            'module' => 'viidia_user',
            'key' => 'forgot_password',
        ]);

        $result = $system->mail($message);

        return new ModifiedResourceResponse(['result' => $result]);
    }
}
