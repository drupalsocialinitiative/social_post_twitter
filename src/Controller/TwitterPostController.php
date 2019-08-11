<?php

namespace Drupal\social_post_twitter\Controller;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_post\Controller\ControllerBase;
use Drupal\social_post\Entity\Controller\SocialPostListBuilder;
use Drupal\social_post\SocialPostDataHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\social_post\User\UserManager;

/**
 * Returns responses for Social Post Twitter routes.
 */
class TwitterPostController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_post\SocialPostDataHandler
   */
  private $dataHandler;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The social post manager.
   *
   * @var \Drupal\social_post\User\UserManager
   */
  protected $userManager;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * TwitterAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_post_twitter network plugin.
   * @param \Drupal\social_post\User\UserManager $user_manager
   *   Manages user login/registration.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Used to access GET parameters.
   * @param \Drupal\social_post\SocialPostDataHandler $data_handler
   *   The Social Post data handler.
   * @param \Drupal\social_post\Entity\Controller\SocialPostListBuilder $list_builder
   *   The Social Post entity list builder.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(NetworkManager $network_manager,
                              UserManager $user_manager,
                              RequestStack $request_stack,
                              SocialPostDataHandler $data_handler,
                              SocialPostListBuilder $list_builder,
                              LoggerChannelFactoryInterface $logger_factory,
                              MessengerInterface $messenger) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->request = $request_stack->getCurrentRequest();
    $this->dataHandler = $data_handler;
    $this->listBuilder = $list_builder;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;

    $this->userManager->setPluginId('social_post_twitter');

    // Sets session prefix for data handler.
    $this->dataHandler->setSessionPrefix('social_post_twitter');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_post.user_manager'),
      $container->get('request_stack'),
      $container->get('social_post.data_handler'),
      $container->get('entity_type.manager')->getListBuilder('social_post'),
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Redirects user to Twitter for authentication.
   */
  public function redirectToProvider() {
    try {
      /** @var \Drupal\social_post_twitter\Plugin\Network\TwitterPost $network_plugin */
      $network_plugin = $this->networkManager->createInstance('social_post_twitter');

      /** @var \Abraham\TwitterOAuth\TwitterOAuth $client */
      $client = $network_plugin->getSdk();

      $request_token = $client->oauth('oauth/request_token', ['oauth_callback' => $network_plugin->getOauthCallback()]);

      // Saves the request token values in session.
      $this->dataHandler->set('oauth_token', $request_token['oauth_token']);
      $this->dataHandler->set('oauth_token_secret', $request_token['oauth_token_secret']);

      // Generates url for authentication.
      $url = $client->url('oauth/authorize', ['oauth_token' => $request_token['oauth_token']]);

      $response = new TrustedRedirectResponse($url);
      $response->send();

      // Redirects the user to grant permissions.
      return $response;
    }
    catch (\Exception $ex) {
      $this->loggerFactory->get('social_post_twitter')->error($ex->getMessage());

      $this->messenger->addError($this->t('You could not be authenticated, please contact the administrator.'));

      return $this->redirect('entity.user.edit_form', ['user' => $this->currentUser()->id()]);
    }

  }

  /**
   * Callback function for the authentication process.
   */
  public function callback() {
    // Checks if user denied authorization.
    if ($this->request->query->has('denied')) {
      $this->messenger->addError($this->t('You could not be authenticated.'));

      return $this->redirect('entity.user.edit_form', ['user' => $this->currentUser()->id()]);
    }

    try {

      $oauth_token = $this->dataHandler->get('oauth_token');
      $oauth_token_secret = $this->dataHandler->get('oauth_token_secret');

      /** @var \Abraham\TwitterOAuth\TwitterOAuth $client */
      $client = $this->networkManager->createInstance('social_post_twitter')->getSdk();
      $client->setOauthToken($oauth_token, $oauth_token_secret);

      // Gets the permanent access token.
      $access_token = $client->oauth('oauth/access_token', ['oauth_verifier' => $this->request->query->get('oauth_verifier')]);
      $client->setOauthToken($access_token['oauth_token'], $access_token['oauth_token_secret']);

      // Gets user information.
      $params = [
        'include_email' => 'true',
        'include_entities' => 'false',
        'skip_status' => 'true',
      ];

      $profile = $client->get("account/verify_credentials", $params);

      if (!$this->userManager->checkIfUserExists($profile->id)) {
        $this->userManager->addRecord($profile->name, $profile->id, json_encode($access_token));
        $this->messenger->addStatus($this->t('Account added successfully.'));
      }
      else {
        $this->messenger->addWarning($this->t('You have already authorized to post on behalf of this user.'));
      }

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('social_post_twitter')->error($e->getMessage());
    }

    return $this->redirect('entity.user.edit_form', ['user' => $this->currentUser()->id()]);
  }

}
