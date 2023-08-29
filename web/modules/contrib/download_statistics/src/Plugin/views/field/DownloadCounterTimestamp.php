<?php

namespace Drupal\download_statistics\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\Core\Session\AccountInterface;

/**
 * Field handler to display the most recent time the file has been downloaded.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("download_statistics_timestamp")
 */
class DownloadCounterTimestamp extends Date {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('view file download statistics');
  }

}
