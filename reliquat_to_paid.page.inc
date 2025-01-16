<?php

/**
 * @file
 * Contains reliquat_to_paid.page.inc.
 *
 * Page callback for Reliquat to paid entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Reliquat to paid templates.
 *
 * Default template: reliquat_to_paid.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_reliquat_to_paid(array &$variables) {
  // Fetch ReliquatToPaid Entity Object.
  $reliquat_to_paid = $variables['elements']['#reliquat_to_paid'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
