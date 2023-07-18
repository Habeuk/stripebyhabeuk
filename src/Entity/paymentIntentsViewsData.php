<?php

namespace Drupal\stripebyhabeuk\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Payment intents entities.
 */
class paymentIntentsViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
