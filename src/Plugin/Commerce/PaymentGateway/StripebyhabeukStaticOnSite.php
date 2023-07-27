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
use Drupal\stripebyhabeuk\ErrorHelper;
use Stripe\PaymentIntent;

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
    // Delete the remote record.
    $payment_method_remote_id = $payment_method->getRemoteId();
    try {
      $GateWay = new GateWay($this->getSecretKey());
      /**
       *
       * @var \Stripe\StripeClient $stribeLib
       */
      $stribeLib = $GateWay->getInstance();
      $remote_payment_method = $stribeLib->paymentMethods->retrieve($payment_method_remote_id);
      if ($remote_payment_method->customer) {
        $remote_payment_method->detach();
      }
    }
    catch (ApiErrorException $e) {
      ErrorHelper::handleException($e);
    }
    $payment_method->delete();
  }
  
  /**
   * Elle se charge d'effectuer le paiement via l'API et on cree l'entite
   * payement si le paiement a reussi ou est en attente.
   * à ce stade le paymentintent a reussi.
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
    $payment_method = $payment->getPaymentMethod();
    assert($payment_method instanceof PaymentMethodInterface);
    $this->assertPaymentMethod($payment_method);
    $order = $payment->getOrder();
    assert($order instanceof OrderInterface);
    $paymentIntents = $this->createPaymentIntent($order);
    // On se rassure que le paiement a effectivement reussi.
    if ($paymentIntents->status != PaymentIntent::STATUS_SUCCEEDED) {
      \Stephane888\Debug\debugLog::kintDebugDrupal($paymentIntents, 'error-createPayment--', true);
      $this->messenger()->addWarning("Votre paiement a echoue, veillez utliser une autre methode de paiement");
      $order->set('payment_method', NULL);
      $this->deletePaymentMethod($payment_method);
      if (is_object($paymentIntents->last_payment_error)) {
        $error = $paymentIntents->last_payment_error;
        $decline_message = sprintf('%s: %s', $error->type, $error->message ?? '');
      }
      else {
        $decline_message = $paymentIntents->last_payment_error;
      }
      throw new HardDeclineException($decline_message);
    }
    // \Stephane888\Debug\debugLog::kintDebugDrupal($paymentIntents,
    // 'createPayment--', true);
    $next_state = $capture ? 'completed' : 'authorization';
    $payment->setState($next_state);
    $payment->setRemoteId($paymentIntents->id);
    $payment->save();
    //
    $order->unsetData('stripebyhabeuk_payment_intent_id');
    $order->save();
  }
  
  /**
   * Permet de creer ou de mettre à jour PaymentIntent.
   * S'assure egalement que pour une commande on a un unique paymentIntent.
   */
  public function createPaymentIntent(OrderInterface $order) {
    
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $order->get('payment_method')->entity;
    /** @var \Drupal\commerce_price\Price */
    $amount = $order->getTotalPrice();
    $intent_id = $order->getData('stripebyhabeuk_payment_intent_id');
    $intent_array = [
      'amount' => $this->minorUnitsConverter->toMinorUnits($amount),
      'currency' => strtolower($amount->getCurrencyCode()),
      'payment_method_types' => [
        'card'
      ],
      'metadata' => [
        'order_id' => $order->id(),
        'store_id' => $order->getStoreId()
      ],
      'payment_method' => $payment_method->getRemoteId(),
      'capture_method' => 'automatic'
    ];
    
    $customer_remote_id = $this->getRemoteCustomerId($order->getCustomer());
    if (!empty($customer_remote_id)) {
      $intent_array['customer'] = $customer_remote_id;
    }
    $GateWay = new GateWay($this->getSecretKey());
    /**
     *
     * @var \Stripe\StripeClient $stribeLib
     */
    $stribeLib = $GateWay->getInstance();
    if (!$intent_id) {
      // le paiement se ferra lorsque l'utilisateur va cliquer sur le bouton
      // "paiyer la facture".
      $intent_array['confirm'] = false;
      $paymentIntents = $stribeLib->paymentIntents->create($intent_array);
      $order->setData('stripebyhabeuk_payment_intent_id', $paymentIntents->id)->save();
    }
    else {
      /**
       * On verifie si l'utilisateur a deja payé ou pas.
       */
      $paymentIntents = $stribeLib->paymentIntents->retrieve($intent_id);
      if ($paymentIntents->status == PaymentIntent::STATUS_SUCCEEDED) {
        return $paymentIntents;
      }
      // On maj les données.
      $paymentIntents = $stribeLib->paymentIntents->update($intent_id, $intent_array);
    }
    return $paymentIntents;
  }
  
  /**
   * Cette methode est executé apres la validation qui qui presente les methodes
   * de paiements.
   * $payment_details : represente les données fournit par le formulaire
   * "add-payment-method".
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsCreatingPaymentMethodsInterface::createPaymentMethod()
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $required_keys = [
      // The expected keys are payment gateway specific and usually match
      // the PaymentMethodAddForm form elements. They are expected to be valid.
      'stripebyhabeuk_payment_method_id'
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        
        throw new InvalidRequestException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }
    
    $remote_payment_method = $this->UpdatePaymentMethods($payment_method, $payment_details);
    $payment_method->card_type = $this->mapCreditCardType($remote_payment_method['brand']);
    $payment_method->card_number = $remote_payment_method['last4'];
    $payment_method->card_exp_month = $remote_payment_method['exp_month'];
    $payment_method->card_exp_year = $remote_payment_method['exp_year'];
    $expires = CreditCard::calculateExpirationTimestamp($remote_payment_method['exp_month'], $remote_payment_method['exp_year']);
    $payment_method->setRemoteId($payment_details['stripebyhabeuk_payment_method_id']);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }
  
  /**
   * Permet de completer les informations sur la methode de paiment (ajouter le
   * client, ajouter le billing information).
   *
   * @param PaymentMethodInterface $payment_method
   * @param array $payment_details
   */
  protected function UpdatePaymentMethods(PaymentMethodInterface $payment_method, array $payment_details) {
    $stripe_payment_method_id = $payment_details['stripebyhabeuk_payment_method_id'];
    $owner = $payment_method->getOwner();
    $customer_id = NULL;
    if ($owner && $owner->isAuthenticated()) {
      $customer_id = $this->getRemoteCustomerId($owner);
    }
    try {
      $GateWay = new GateWay($this->getSecretKey());
      /**
       *
       * @var \Stripe\StripeClient $stripe
       */
      $stripeLib = $GateWay->getInstance();
      $stripe_payment_method = $stripeLib->paymentMethods->retrieve($stripe_payment_method_id);
      if ($customer_id) {
        $stripe_payment_method->attach([
          'customer' => $customer_id
        ]);
        $email = $owner->getEmail();
      }
      // If the user is authenticated, created a Stripe customer to attach the
      // payment method to.
      elseif ($owner && $owner->isAuthenticated()) {
        $email = $owner->getEmail();
        $customer = $stripeLib->customers->create([
          'email' => $email,
          'description' => $this->t('Customer for :mail', [
            ':mail' => $email
          ]),
          'payment_method' => $stripe_payment_method_id
        ]);
        $customer_id = $customer->id;
        $this->setRemoteCustomerId($owner, $customer_id);
        $owner->save();
      }
      else {
        $email = NULL;
      }
      
      if ($customer_id && $email) {
        $payment_method_data = [
          'email' => $email
        ];
        if ($billing_profile = $payment_method->getBillingProfile()) {
          $billing_address = $billing_profile->get('address')->first()->toArray();
          $payment_method_data['address'] = [
            'city' => $billing_address['locality'],
            'country' => $billing_address['country_code'],
            'line1' => $billing_address['address_line1'],
            'line2' => $billing_address['address_line2'],
            'postal_code' => $billing_address['postal_code'],
            'state' => $billing_address['administrative_area']
          ];
          $payment_method_data['name'] = $billing_address['given_name'] . ' ' . $billing_address['family_name'];
        }
        $stripeLib->paymentMethods->update($stripe_payment_method_id, [
          'billing_details' => $payment_method_data
        ]);
      }
      return $stripe_payment_method->card;
    }
    catch (ApiErrorException $e) {
      ErrorHelper::handleException($e);
    }
  }
  
  /**
   * Attach la methode payment à l'intention de payment.
   *
   * @param PaymentMethodInterface $payment_method
   * @param array $payment_details
   * @deprecated probleme de logique.
   */
  protected function addPaymentMethodToPaymentIntents(PaymentMethodInterface $payment_method, array $payment_details) {
    $stripe_payment_method_id = $payment_details['stripebyhabeuk_payment_method_id'];
    $stripe_payment_intents_id = $payment_details['stripebyhabeuk_payment_method_id'];
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