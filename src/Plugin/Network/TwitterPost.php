<?php

namespace Drupal\social_post_twitter\Plugin\Network;

use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\social_api\SocialApiException;
use Drupal\social_post\Plugin\Network\SocialPostNetwork;
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
class TwitterPost extends SocialPostNetwork implements TwitterPostInterface {

  use LoggerChannelTrait;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Twitter connection.
   *
   * @var \Abraham\TwitterOAuth\TwitterOAuth
   */
  protected $connection;

  /**
   * The tweet text (with optional media ids).
   *
   * @var array
   */
  protected $tweet;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('url_generator'),
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
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(MetadataBubblingUrlGenerator $url_generator,
                              array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
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
   *
   * @throws \Drupal\social_api\SocialApiException
   */
  public function post() {
    if (!$this->connection) {
      throw new SocialApiException('Call post() method from its wrapper doPost()');
    }

    $post = $this->connection->post('statuses/update', $this->tweet);

    if (isset($post->errors)) {
      $this->getLogger('social_post_twitter')->error($post->errors[0]->message);

      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function doPost($access_token, $access_token_secret, $tweet) {
    $this->connection = $this->getSdk2($access_token, $access_token_secret);

    // Make backwards-compatible if someone just posts a tweet text as a string.
    $this->tweet['status'] = is_array($tweet) && !empty($tweet['status']) ? $tweet['status'] : $tweet;

    // Check if there needs to be media uploaded. If so, upload and store ids.
    if (!empty($tweet['media_paths'])) {
      $this->tweet['media_ids'] = $this->uploadMedia($tweet['media_paths']);
    }

    try {
      return $this->post();
    }
    catch (SocialApiException $e) {
      $this->getLogger('social_post_twitter')->error($e->getMessage());

      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getOauthCallback() {
    return $this->urlGenerator->generateFromRoute('social_post_twitter.callback', [], ['absolute' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSdk2($oauth_token, $oauth_token_secret) {
    /* @var \Drupal\social_post_twitter\Settings\TwitterPostSettings $settings */
    $settings = $this->settings;

    return new TwitterOAuth($settings->getConsumerKey(), $settings->getConsumerSecret(),
      $oauth_token, $oauth_token_secret);
  }

  /**
   * {@inheritdoc}
   */
  public function uploadMedia(array $paths) {
    $media_ids = [];

    foreach ($paths as $path) {
      // Upload the media from the path.
      $media = $this->connection->upload('media/upload', ['media' => $path]);

      // The response contains the media_ids to attach the media to the post.
      $media_ids[] = $media->media_id_string;
    }

    return $media_ids;
  }

}
