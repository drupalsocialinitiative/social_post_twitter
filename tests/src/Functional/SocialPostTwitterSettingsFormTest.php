<?php

namespace Drupal\Tests\social_post_twitter\Functional;

use Drupal\Tests\social_post\Functional\SocialPostTestBase;

/**
 * Test Social Post Twitter settings form.
 *
 * @group social_post
 *
 * @ingroup social_post_twitter
 */
class SocialPostTwitterSettingsFormTest extends SocialPostTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['social_post_twitter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->module = 'social_post_twitter';
    $this->provider = 'twitter';

    parent::setUp();
  }

  /**
   * Test if implementer is shown in the integration list.
   */
  public function testIsAvailableInIntegrationList() {
    $this->fields = ['consumer_key', 'consumer_secret'];

    $this->checkIsAvailableInIntegrationList();
  }

  /**
   * Test if permissions are set correctly for settings page.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testPermissionForSettingsPage() {
    $this->checkPermissionForSettingsPage();
  }

  /**
   * Test settings form submission.
   */
  public function testSettingsFormSubmission() {
    $this->edit = [
      'consumer_key' => $this->randomString(10),
      'consumer_secret' => $this->randomString(10),
    ];

    $this->checkSettingsFormSubmission();
  }

}
