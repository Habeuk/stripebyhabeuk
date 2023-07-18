<?php

/**
 * @file
 * Contains payment_intents.page.inc.
 *
 * Page callback for Payment intents entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Payment intents templates.
 *
 * Default template: payment_intents.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_payment_intents(array &$variables) {
  // Fetch paymentIntents Entity Object.
  $payment_intents = $variables['elements']['#payment_intents'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
