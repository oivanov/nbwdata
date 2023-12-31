<?php

namespace Drupal\Tests\download_statistics\Functional;

/**
 * Generates text using placeholders for dummy content to check statistics token
 * replacement.
 *
 * @group statistics
 */
class DownloadStatisticsTokenReplaceTest extends DownloadStatisticsTestBase {
  /**
   * Creates a node, then tests the statistics tokens generated from it.
   */
  public function testStatisticsTokenReplacement() {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Create user and node.
    $user = $this->drupalCreateUser(['create page content']);
    $this->drupalLogin($user);
    $node = $this->drupalCreateNode(['type' => 'page', 'uid' => $user->id()]);

    // Hit the node.
    $this->drupalGet('node/' . $node->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $node->id();
    $post = http_build_query(['nid' => $nid]);
    $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
    global $base_url;
    $stats_path = $base_url . '/' . \Drupal::service('extension.list.module')->getPath('statistics') . '/statistics.php';
    $client = \Drupal::httpClient();
    $client->post($stats_path, ['headers' => $headers, 'body' => $post]);
    $statistics = statistics_get($node->id());

    // Generate and test tokens.
    $tests = [];
    $tests['[node:total-count]'] = 1;
    $tests['[node:day-count]'] = 1;
    $tests['[node:last-view]'] = \Drupal::service('date.formatter')->format($statistics['timestamp']);
    $tests['[node:last-view:short]'] = \Drupal::service('date.formatter')->format($statistics['timestamp'], 'short');

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = \Drupal::token()->replace($input, ['node' => $node], ['langcode' => $language_interface->getId()]);
      $this->assertEquals($output, $expected, format_string('Statistics token %token replaced.', ['%token' => $input]));
    }
  }

}
