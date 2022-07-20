<?php

namespace Drupal\stripebyhabeuk\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for stripe by habeuk routes.
 */
class StripebyhabeukController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
