<?php

namespace Drupal\stripebyhabeuk\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\OrderSummary;

/**
 * Provides the Order summary pane.
 *
 * @CommerceCheckoutPane(
 *   id = "stripebyhabeuk_order_Summary",
 *   label = @Translation("Order summary by StripeByHabeuk"),
 *   default_step = "_sidebar",
 *   wrapper_element = "container",
 * )
 */
class StripebyhabeukOrderSummary extends OrderSummary {
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);
    
    // $pane_form['test'] = [
    // '#type' => 'html_tag',
    // '#tag' => 'h4',
    // '#value' => 'moyen de paiement : ' . time()
    // ];
    // $pane_form['hello'] = [
    // '#type' => 'html_tag',
    // '#tag' => 'p',
    // '#value' => $this->t('Hello World')
    // ];
    return $pane_form;
  }
  
  public function buildPaneSummary() {
    $summary = parent::buildPaneSummary();
    // $this->messenger()->addWarning(' Affiche de buildPaneSummary() ');
    return $summary;
  }
  
}