<?php

namespace Drupal\stripebyhabeuk\Handler;

use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentInformation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use function Drupal\Component\Datetime\time;

/**
 * Provides the payment information pane.
 * Cette classe est automatiquement injecter dans le PaymentInformation via
 * hook_commerce_checkout_pane_info_alter, car on ne peut pas desactivé le pane
 * "payment_information".
 * ( C'est un plugin et cest interessant de savoir qu'on peut changer les
 * classes qui gere les plugins, cela permet d'etendre facilement le
 * fonctionnement des plugins ).
 */
class StripebyHabeukPaymentInformation extends PaymentInformation {
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);
    // add ajax command.
    if (!empty($pane_form['payment_method'])) {
      // dump($pane_form);
    }
    return $pane_form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $payment_method_id = $form_state->getValue([
      'payment_information',
      'payment_method'
    ]);
    // \Stephane888\Debug\debugLog::$max_depth = 7;
    // $db = [
    // 'payment_method' => $payment_method_id,
    // 'pane_form' => $pane_form
    // ];
    /**
     * à ce stade la methode de paiement choisit n'est pas encore sauvegarder,
     * donc on va mettre la methode choisit par l'utilisateur dans les datas de
     * la commande.
     * ( Ainsi "stripebyhabeuk.late_order_processor" pourrait ajouter les
     * informations necessaire par rapport à l'accompte ).
     */
    
    if (!empty($pane_form['#payment_options']) && $pane_form['#payment_options'][$payment_method_id]) {
      /**
       *
       * @var \Drupal\commerce_payment\PaymentOption $PaymentOption
       */
      $PaymentOption = $pane_form['#payment_options'][$payment_method_id];
      $this->order->setData('stripebyhabeuk_select_payment_method', $PaymentOption->toArray());
      $this->order->save();
      // $db['PaymentOption'] = $PaymentOption->toArray();
    }
    // \Stephane888\Debug\debugLog::kintDebugDrupal($db, 'validatePaneForm',
    // true);
  }
  
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Refesah the paiment method.
    if (!empty($form['payment_information'])) {
      $payment_information = $form['payment_information'];
      $selector = sprintf('[data-drupal-selector="%s"]', $payment_information['#attributes']['data-drupal-selector']);
      $response->addCommand(new ReplaceCommand($selector, $payment_information));
    }
    // Refresh the order summary if present.
    $order_summary = $form['sidebar']['order_summary'] ?? $form['order_summary'] ?? NULL;
    if (isset($order_summary)) {
      // $order_summary['test_data'] = [
      // '#markup' => 'new title by AJAX ' . \time()
      // ];
      $selector = sprintf('[data-drupal-selector="%s"]', $order_summary['#attributes']['data-drupal-selector']);
      $response->addCommand(new ReplaceCommand($selector, $order_summary));
    }
    return $response;
  }
  
  /**
   * Ajax callback.
   */
  public static function ajaxRefreshOld(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    $newValue = NestedArray::getValue($form, $parents);
    \Stephane888\Debug\debugLog::$max_depth = 7;
    $db = [
      'newValue' => $newValue,
      'form' => $form,
      'getTriggeringElement' => $form_state->getTriggeringElement()
    ];
    \Stephane888\Debug\debugLog::kintDebugDrupal($db, 'ajaxRefresh', true);
    return $newValue;
  }
  
  /**
   * Ajax callback.
   */
  public static function ajaxRefreshOld2(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    
    // Refresh the order summary if present.
    $order_summary = $form['sidebar']['order_summary'] ?? $form['order_summary'] ?? NULL;
    if (isset($order_summary)) {
      $selector = sprintf('[data-drupal-selector="%s"]', $order_summary['#attributes']['data-drupal-selector']);
      $response->addCommand(new ReplaceCommand($selector, $order_summary));
    }
    if (isset($form['shipping_information']['shipments'])) {
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['shipping_information']['shipments']['#attributes']['data-drupal-selector'] . '"]', $form['shipping_information']['shipments']));
    }
    
    return $response;
  }
  
}