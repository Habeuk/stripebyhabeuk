<?php

namespace Drupal\stripebyhabeuk\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "stripebyhabeuk_example",
 *   admin_label = @Translation("Example"),
 *   category = @Translation("stripe by habeuk")
 * )
 */
class ExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

}
