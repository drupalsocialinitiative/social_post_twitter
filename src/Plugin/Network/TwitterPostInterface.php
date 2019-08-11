<?php

namespace Drupal\social_post_twitter\Plugin\Network;

use Drupal\social_post\Plugin\Network\NetworkInterface;

/**
 * Defines an interface for Twitter Post Network Plugin.
 */
interface TwitterPostInterface extends NetworkInterface {

  /**
   * Gets the absolute url of the callback.
   *
   * @return string
   *   The callback url.
   */
  public function getOauthCallback();

}
