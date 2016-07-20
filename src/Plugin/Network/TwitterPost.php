<?php

namespace Drupal\social_post_twitter\Plugin\Network;


use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\social_api\Plugin\NetworkBase;
use Drupal\social_api\SocialApiException;

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
class TwitterPost extends NetworkBase {

  /**
   * {@inheritdoc}
   */
  protected function initSdk() {
    $class_name = '\Google_Client';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Twitter could not be found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_post_twitter\Settings\TwitterPostSettings $settings */
    $settings = $this->settings;

    return new TwitterOAuth($settings->getConsumerKey(), $settings->getConsumerSecret());
  }

}
