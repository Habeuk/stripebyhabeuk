<?php

namespace Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Habeuk\Stripe\GateWay;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the example for payement on commece_stripe.
 *
 * @CommercePaymentGateway(
 *   id = "stripebyhabeuk_acompte",
 *   label = @Translation("stripe by habeuk acompte"),
 *   display_label = @Translation("stripe by habeuk acompte"),
 *    forms = {
 *     "add-payment-method" = "Drupal\stripebyhabeuk\PluginForm\StripebyhabeukStaticOnSiteCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class StripeAcompte extends StripebyhabeukStaticOnSite {
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase::defaultConfiguration()
   */
  public function defaultConfiguration() {
    return [
      'min_value_paid' => 3,
      'percent_value' => 10
    ] + parent::defaultConfiguration();
  }
  
  /**
   * une fois le paiement validé, on cree l'entité pour sauvegarder l'id du
   * paiementIntent.
   *
   * {@inheritdoc}
   * @see \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripebyhabeukStaticOnSite::createPayment()
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $paymentIntents = parent::createPayment($payment, $capture);
    $order = $payment->getOrder();
    $intent_id = $order->getData('stripebyhabeuk_payment_intent_id');
    $GateWay = new GateWay($this->getSecretKey());
    /**
     *
     * @var \Stripe\StripeClient $stribeLib
     */
    $stribeLib = $GateWay->getInstance();
    $metadatas = [
      'stripebyhabeuk_acompte_price_paid' => $order->getData('stripebyhabeuk_acompte_price_paid'),
      'stripebyhabeuk_acompte_price_remainder' => $order->getData('stripebyhabeuk_acompte_price_remainder')
    ] + $paymentIntents->metadata->toArray();
    $stribeLib->paymentIntents->update($intent_id, [
      'metadata' => $metadatas
    ]);
    //
    $order->unsetData('stripebyhabeuk_acompte_price_paid');
    $order->unsetData('stripebyhabeuk_acompte_price_remainder');
    $order->save();
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripebyhabeukStaticOnSite::acompte()
   */
  public function acompte(Price $amount, OrderInterface $order) {
    $price = $this->toMinorUnits($amount);
    $min_value_paid = $this->getMinValuePaid();
    $percent_value = $this->getPercentValue();
    $price_reduce = 0;
    if ($min_value_paid >= 1) {
      $pm = new Price($min_value_paid, $amount->getCurrencyCode());
      $price_reduce = $this->toMinorUnits($pm);
    }
    if ($percent_value >= 1) {
      $reduce_percent = \ceil($price * $percent_value / 100);
      if ($reduce_percent > $price_reduce) {
        $price_reduce = $reduce_percent;
      }
    }
    if ($price_reduce) {
      // on sauvegarde la difference, en tempon.
      $order->setData('stripebyhabeuk_acompte_price_paid', $price_reduce);
      $order->setData('stripebyhabeuk_acompte_price_remainder', $price - $price_reduce);
      $order->save();
      return $price_reduce;
    }
    
    return $price;
  }
  
  public function getPercentValue() {
    return $this->configuration['percent_value'];
  }
  
  public function getMinValuePaid() {
    return $this->configuration['min_value_paid'];
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase::buildConfigurationForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    $form['percent_value'] = [
      '#type' => 'number',
      '#title' => $this->t('percent_value ') . " %",
      '#description' => $this->t('Percentage deposit based on the total amount to be paid'),
      '#default_value' => $this->configuration['percent_value'],
      '#required' => TRUE,
      '#min' => 0
    ];
    
    $form['min_value_paid'] = [
      '#type' => 'number',
      '#title' => $this->t('min_value_paid'),
      '#description' => $this->t('Minimum deposit amount depending on the currency'),
      '#default_value' => $this->configuration['min_value_paid'],
      '#required' => TRUE,
      '#min' => 0
    ];
    
    return $form;
  }
  
}