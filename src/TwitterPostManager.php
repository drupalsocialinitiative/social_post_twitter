<?php

namespace Drupal\social_post_twitter;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Manages the authorization process before getting a long lived access token.
 */
class TwitterPostManager implements TwitterPostManagerInterface {

  /**
   * The Twitter client.
   *
   * @var \Abraham\TwitterOAuth\TwitterOAuth
   */
  protected $client;

  /**
   * The tweet text (with optional media ids).
   *
   * @var array
   */
  protected $tweet;

  /**
   * {@inheritdoc}
   */
  public function setClient(TwitterOAuth $client) {
    $this->client = $client;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOauthToken($oauth_token, $oauth_token_secret) {
    $this->client->setOauthToken($oauth_token, $oauth_token_secret);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function doPost($tweet) {

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
  public function uploadMedia(array $paths) {
    $media_ids = [];

    foreach ($paths as $path) {
      // Upload the media from the path.
      $media = $this->client->upload('media/upload', ['media' => $path]);

      // The response contains the media_ids to attach the media to the post.
      $media_ids[] = $media->media_id_string;
    }

    return $media_ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function post() {

    $post = $this->client->post('statuses/update', $this->tweet);

    if (isset($post->errors)) {
      $this->getLogger('social_post_twitter')->error($post->errors[0]->message);

      return FALSE;
    }

    return TRUE;
  }

}
