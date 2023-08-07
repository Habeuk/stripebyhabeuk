<?php

namespace Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Habeuk\Stripe\GateWay;
use Drupal\Core\Form\FormStateInterface;
use Google\Service\ShoppingContent\Amount;

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
class StripeAcompte extends StripebyhabeukStaticOnSite implements StripebyHabeukAcompteInterface {
  /**
   * Permet de mettre en cache la valeur de l'acompte aucours d'un cycle.
   *
   * @var number
   */
  protected $UnitsAcompteAmount = NULL;
  
  /**
   * Permet de mettre en cache la valeur qui va etre payer par rapport au
   * montant du sous total.
   *
   * @var number
   */
  protected $UnitsSubtotalAmount = NULL;
  
  /**
   * Permet de mettre en cache la valeur du montant restant aucours d'un cycle.
   *
   * @var number
   */
  protected $UnitsBalanceAmount = NULL;
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase::defaultConfiguration()
   */
  public function defaultConfiguration() {
    return [
      'min_value_paid' => 3,
      'percent_value' => 10,
      'apply_on_subtotal' => true
    ] + parent::defaultConfiguration();
  }
  
  public function getDisplayLabel() {
    return " Payer  " . $this->getPercentValue() . "%  maintenant et le reste plus tard ";
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
    $stribeLib->paymentIntents->update($paymentIntents->id, [
      'metadata' => $metadatas
    ]);
    // il faut modifier le status du paiement, car il n'est pas complet.
    // $next_state = $capture ? 'completed' : 'authorization';
    // $payment->setState($next_state);
    // en attendant de bien comprendre d'avance l'ecommerce, on le fait via une
    // entite custom.
    $this->storeProcessPeriodiquePaiement($order, $payment, $paymentIntents);
    $order->unsetData('stripebyhabeuk_acompte_price_paid');
    $order->unsetData('stripebyhabeuk_acompte_price_remainder');
    $order->save();
    //
  }
  
  /**
   * Permet de payer plus tard le reste de la commande.
   */
  protected function storeProcessPeriodiquePaiement(OrderInterface $order, PaymentInterface $payment) {
  }
  
  public function AcompteApplyOnSubtotal() {
    if ($this->configuration['apply_on_subtotal'])
      return true;
    return false;
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripebyhabeukStaticOnSite::acompte()
   * @return number
   */
  public function acompte(OrderInterface $order) {
    if ($this->UnitsAcompteAmount === NULL) {
      if ($this->configuration['apply_on_subtotal']) {
        $amount = $order->getSubtotalPrice();
      }
      else {
        $amount = $order->getTotalPrice();
      }
      $price = $this->toMinorUnits($amount);
      $this->UnitsAcompteAmount = $price;
      $this->UnitsBalanceAmount = 0;
      $this->UnitsSubtotalAmount = 0;
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
        $this->UnitsSubtotalAmount = $price_reduce;
        // Dans la mesure ou est sur un reduction par rapport au sous-total, on
        // complete les autres montant à la somme de base à payer.
        if ($this->configuration['apply_on_subtotal']) {
          // Ne change pas, car le montant restant à payer provient uniquement
          // du sous total.
          $this->UnitsBalanceAmount = $price - $price_reduce;
          $totalPrice = $order->getTotalPrice();
          $UnitstotalPrice = $this->toMinorUnits($totalPrice);
          $this->UnitsAcompteAmount = $UnitstotalPrice - $this->UnitsBalanceAmount;
        }
        else {
          $this->UnitsBalanceAmount = $price - $price_reduce;
          $this->UnitsAcompteAmount = $price_reduce;
        }
      }
    }
    return $this->UnitsAcompteAmount;
  }
  
  /**
   *
   * {@inheritdoc} amount
   * @see \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripebyhabeukStaticOnSite::amount()
   */
  public function amount(Price $amount, OrderInterface $order) {
    $acompteAmont = $this->acompte($order);
    // On met la difference en tampon, on ferra la sauvegarde plus tard,( par
    // createPaymentIntent si tout se passe bien ).
    $order->setData('stripebyhabeuk_acompte_price_paid', $this->UnitsAcompteAmount);
    $order->setData('stripebyhabeuk_acompte_price_remainder', $this->UnitsBalanceAmount);
    return $acompteAmont;
  }
  
  public function getPercentValue() {
    return $this->configuration['percent_value'];
  }
  
  public function getMinValuePaid() {
    return $this->configuration['min_value_paid'];
  }
  
  /**
   * Recupere le montant à payer
   *
   * @param OrderInterface $order
   * @param boolean $positive
   *        // permet de retouner un prix positif si à true
   * @return \Drupal\commerce_price\Price
   */
  public function getAcompteAmount(OrderInterface $order, $positive = true) {
    $UnitsAcompteAmount = $this->acompte($order);
    if (!$positive)
      $UnitsAcompteAmount = -$UnitsAcompteAmount;
    return $this->minorUnitsConverter->fromMinorUnits($UnitsAcompteAmount, $this->getCurrencyCode($order));
  }
  
  /**
   * Recupere le montant restant à payer.
   *
   * @param OrderInterface $order
   * @param boolean $positive
   *        // permet de retouner un prix positif si à true
   * @return \Drupal\commerce_price\Price
   */
  public function getBalanceToPay(OrderInterface $order, $positive = true) {
    // must run acompte() to get ::UnitsBalanceAmount.
    $this->acompte($order);
    $val = $this->UnitsBalanceAmount;
    if (!$positive)
      $val = -$val;
    return $this->minorUnitsConverter->fromMinorUnits($val, $this->getCurrencyCode($order));
  }
  
  /**
   * Recupere le montant à payer
   *
   * @param OrderInterface $order
   * @param boolean $positive
   *        // permet de retouner un prix positif si à true
   * @return \Drupal\commerce_price\Price
   */
  public function getSubToatlaAmount(OrderInterface $order, $positive = true) {
    $this->acompte($order);
    $val = $this->UnitsSubtotalAmount;
    if (!$positive)
      $val = -$val;
    return $this->minorUnitsConverter->fromMinorUnits($val, $this->getCurrencyCode($order));
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