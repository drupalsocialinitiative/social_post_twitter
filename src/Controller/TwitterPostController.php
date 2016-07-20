<?php

namespace Drupal\social_post_twitter\Controller;

use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_post_twitter\Plugin\Network\TwitterPost;
use Drupal\social_post_twitter\TwitterPostManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Manages requests to Twitter
 */
class TwitterPostController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $networkManager;

  /**
   * The twitter post manager.
   *
   * @var \Drupal\social_post_twitter\TwitterPostManager
   */
  protected $twitterManager;

  /**
   * TwitterPostController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   The network plugin manager.
   * @param \Drupal\social_post_twitter\TwitterPostManager $twitter_manager
   *   The Twitter post manager.
   */
  public function __construct(NetworkManager $network_manager, TwitterPostManager $twitter_manager) {
    $this->networkManager = $network_manager;
    $this->twitterManager = $twitter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('twitter_post.manager')
    );
  }

  /**
   * Redirects user to Twitter for authentication.
   *
   * @return \Zend\Diactoros\Response\RedirectResponse
   *   Redirects to Twitter.
   *
   * @throws \Abraham\TwitterOAuth\TwitterOAuthException
   */
  public function redirectToTwitter() {
    /* @var TwitterPost $network_plugin */
    $network_plugin = $this->networkManager->createInstance('social_post_twitter');

    /* @var TwitterOAuth $connection */
    $connection = $network_plugin->getSdk();

    $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $network_plugin->getOauthCallback()));

    // Saves the request token values in session.
    $this->twitterManager->setOauthToken($request_token['oauth_token']);
    $this->twitterManager->setOauthTokenSecret($request_token['oauth_token_secret']);

    // Generates url for authentication.
    $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

    return new RedirectResponse($url);
  }

  /**
   * Callback function for the authentication process.
   *
   * @throws \Abraham\TwitterOAuth\TwitterOAuthException
   */
  public function callback() {
    $oauth_token = $this->twitterManager->getOauthToken();
    $oauth_token_secret = $this->twitterManager->getOauthTokenSecret();

    /* @var TwitterOAuth $connection */
    $connection = $this->networkManager->createInstance('social_post_twitter')->getSdk2($oauth_token, $oauth_token_secret);

    // Gets the permanent access token.
    $access_token = $connection->oauth('oauth/access_token', array('oauth_verifier' => $this->twitterManager->getOauthVerifier()));

    var_dump($access_token);
  }

}
