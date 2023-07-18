<?php

namespace Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Stripe\Exception\ApiErrorException;
use Habeuk\Stripe\Exception\ExceptionStripe;
use Habeuk\Stripe\GateWay;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Exception\HardDeclineException;

/**
 * Provides the example for payement on commece_stripe.
 *
 * @CommercePaymentGateway(
 *   id = "stripebyhabeuk_static_onsite",
 *   label = @Translation("stripe by habeuk static Onsite"),
 *   display_label = @Translation("stripe by habeuk static"),
 *    forms = {
 *     "add-payment-method" = "Drupal\stripebyhabeuk\PluginForm\StripebyhabeukStaticOnSiteCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class StripebyhabeukStaticOnSite extends OnsitePaymentGatewayBase implements StripebyHabeukInterface {
  
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsCreatingPaymentMethodsInterface::createPaymentMethod()
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $required_keys = [
      // The expected keys are payment gateway specific and usually match
      // the PaymentMethodAddForm form elements. They are expected to be valid.
      'stripe_payment_method_id'
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new InvalidRequestException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }
    
    $remote_payment_method = $this->addPaymentMethodToPaymentIntents($payment_method, $payment_details);
    $payment_method->card_type = $this->mapCreditCardType($remote_payment_method['brand']);
    $payment_method->card_number = $remote_payment_method['last4'];
    $payment_method->card_exp_month = $remote_payment_method['exp_month'];
    $payment_method->card_exp_year = $remote_payment_method['exp_year'];
    $expires = CreditCard::calculateExpirationTimestamp($remote_payment_method['exp_month'], $remote_payment_method['exp_year']);
    $payment_method->setRemoteId($payment_details['stripe_payment_method_id']);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }
  
  /**
   * Attach la methode payment à l'intention de payment.
   *
   * @param PaymentMethodInterface $payment_method
   * @param array $payment_details
   */
  protected function addPaymentMethodToPaymentIntents(PaymentMethodInterface $payment_method, array $payment_details) {
    $stripe_payment_method_id = $payment_details['stripe_payment_method_id'];
    $stripe_payment_intents_id = $payment_details['stripe_payment_intents_id'];
    $GateWay = new GateWay($this->getSecretKey());
    $PaymentMethod = $GateWay->attachPaymentMethodToPaymentIntents($stripe_payment_method_id, $stripe_payment_intents_id);
    return $PaymentMethod->card;
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase::buildPaymentOperations()
   */
  public function buildPaymentOperations($payment) {
    $operations = parent::buildPaymentOperations();
    return $operations;
  }
  
  /**
   * Elle se charge d'effectuer le paiement via l'API et on cree l'entite
   * payement si le paiement a reussi ou est en attente.
   *
   * This method gets called during the checkout process, when the Payment
   * Information form is submitted.It is also called when a payment is added to
   * an order manually.
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface::createPayment()
   * @see https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/on-site-gateways/creating-payments
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    // On definie le nouveau paiement.
    $this->assertPaymentState($payment, [
      'new'
    ]);
    \Stephane888\Debug\debugLog::kintDebugDrupal($payment, "createPayment", true);
  }
  
  public function getPublishableKey() {
    return $this->configuration['publishable_key'];
  }
  
  public function getSecretKey() {
    return $this->configuration['secret_key'];
  }
  
  public function voidPayment(PaymentInterface $payment) {
  }
  
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
  }
  
  public function createPaymentIntent(OrderInterface $order, $intent_attributes = [], PaymentInterface $payment = NULL) {
  }
  
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase::defaultConfiguration()
   */
  public function defaultConfiguration() {
    return [
      'publishable_key' => '',
      'secret_key' => '',
      'enable_credit_card_icons' => true
    ] + parent::defaultConfiguration();
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase::buildConfigurationForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    $form['publishable_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('publishable key'),
      '#description' => $this->t('The API key for the same user as used in Agreement ID.'),
      '#default_value' => $this->configuration['publishable_key'],
      '#required' => TRUE
    ];
    
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secrete key'),
      '#description' => $this->t('This is the private key from the Quickpay manager.'),
      '#default_value' => $this->configuration['secret_key'],
      '#required' => TRUE
    ];
    
    $form['enable_credit_card_icons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable credit cart icons'),
      '#default_value' => $this->configuration['enable_credit_card_icons']
    ];
    
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $expected_livemode = $values['mode'] == 'live' ? TRUE : FALSE;
      try {
        $GateWayTest = new GateWay($values['secret_key'], $values['publishable_key']);
        $GateWayTest->testValidationKey($expected_livemode);
      }
      catch (ExceptionStripe $e) {
        $form_state->setError($form['secret_key'], $e->getMessage());
      }
      catch (ApiErrorException $e) {
        $form_state->setError($form['secret_key'], $e->getMessage());
      }
    }
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase::submitConfigurationForm()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['secret_key'] = $values['secret_key'];
    $this->configuration['publishable_key'] = $values['publishable_key'];
    $this->configuration['enable_credit_card_icons'] = $values['enable_credit_card_icons'];
  }
  
  /**
   * Maps the Stripe credit card type to a Commerce credit card type.
   *
   * @param string $card_type
   *        The Stripe credit card type.
   *        
   * @return string The Commerce credit card type.
   */
  protected function mapCreditCardType($card_type) {
    $map = [
      'amex' => 'amex',
      'diners' => 'dinersclub',
      'discover' => 'discover',
      'jcb' => 'jcb',
      'mastercard' => 'mastercard',
      'visa' => 'visa',
      'unionpay' => 'unionpay'
    ];
    if (!isset($map[$card_type])) {
      throw new HardDeclineException(sprintf('Unsupported credit card type "%s".', $card_type));
    }
    
    return $map[$card_type];
  }
  
}