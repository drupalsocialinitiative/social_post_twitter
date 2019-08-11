<?php

namespace Drupal\social_post_twitter;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Defines an interface for the Twitter Post Manager.
 */
interface TwitterPostManagerInterface {

  /**
   * Sets the token.
   *
   * @param string $oauth_token
   *   The oauth token.
   * @param string $oauth_token_secret
   *   The oauth token secret.
   */
  public function setOauthToken($oauth_token, $oauth_token_secret);

  /**
   * Sets the Twitter client.
   *
   * @param Abraham\TwitterOAuth\TwitterOAuth $client
   *   The API client.
   */
  public function setClient(TwitterOAuth $client);

  /**
   * Wrapper for post method.
   *
   * @param string|array $tweet
   *   The tweet text (with optional media paths).
   */
  public function doPost($tweet);

  /**
   * Uploads files by path.
   *
   * @param array $paths
   *   The paths for media to upload.
   */
  public function uploadMedia(array $paths);

}
