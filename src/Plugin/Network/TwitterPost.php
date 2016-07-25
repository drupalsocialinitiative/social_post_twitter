<?php

namespace Drupal\social_post_twitter\Plugin\Network;


use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\social_api\SocialApiException;
use Drupal\social_post\Plugin\Network\SocialPostNetwork;
use Drupal\social_post_twitter\TwitterPostManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines Social Post Twitter Network Plugin.
 *
 * @Network(
 *   id = "social_post_twitter",
 *   social_network = "Twitter",
 *   type = "social_post",
 *   handlers = {
 *     "settings": {
 *        "class": "\Drupal\social_post_twitter\Settings\TwitterPostSettings",
 *        "config_id": "social_post_twitter.settings"
 *      }
 *   }
 * )
 */
class TwitterPost extends SocialPostNetwork {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * The Twitter manager.
   *
   * @var \Drupal\social_post_twitter\TwitterPostManager
   */
  private $twitterManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('url_generator'),
      $container->get('twitter_post.manager'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * TwitterPost constructor.
   *
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   Used to generate a absolute url for authentication.
   * @param \Drupal\social_post_twitter\TwitterPostManager $twitter_manager
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(MetadataBubblingUrlGenerator $url_generator, TwitterPostManager $twitter_manager, array $configuration, $plugin_id, $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
    $this->twitterManager = $twitter_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function initSdk() {
    $class_name = '\Abraham\TwitterOAuth\TwitterOAuth';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Twitter could not be found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_post_twitter\Settings\TwitterPostSettings $settings */
    $settings = $this->settings;

    return new TwitterOAuth($settings->getConsumerKey(), $settings->getConsumerSecret());
  }

  /**
   * {@inheritdoc}
   */
  public function doPost() {
    // TODO: Implement doPost() method.
  }

  /**
   * Gets the absolute url of the callback.
   *
   * @return string.
   *   The callback url.
   */
  public function getOauthCallback() {
    return $this->urlGenerator->generateFromRoute('social_post_twitter.callback', array(), array('absolute' => TRUE));
  }

  /**
   * Gets a TwitterOAuth token with oauth_token and oauth_token_secret.
   * This method is used in the callback method when Twitter returns a
   * temporary token and token secret which should be used to get the
   * permanent access token and access token secret.
   *
   * @param string $oauth_token
   *   The oauth token.
   * @param $oauth_token_secret
   *   The oauth token secret.
   *
   * @return \Abraham\TwitterOAuth\TwitterOAuth
   *   The twitter oauth client.
   */
  public function getSdk2($oauth_token, $oauth_token_secret) {
    /* @var \Drupal\social_post_twitter\Settings\TwitterPostSettings $settings */
    $settings = $this->settings;

    return new TwitterOAuth($settings->getConsumerKey(), $settings->getConsumerSecret(),
                $oauth_token, $oauth_token_secret);
  }

}