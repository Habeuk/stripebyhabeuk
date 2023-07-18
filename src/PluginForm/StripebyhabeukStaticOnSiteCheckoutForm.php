<?php

namespace Drupal\stripebyhabeuk\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\user\UserInterface;
use Stripe\SetupIntent;
use Drupal\Component\Utility\Html;
use Habeuk\Stripe\GateWay;
use Habeuk\Stripe\Exception\ExceptionStripe;
use Stripe\Exception\ApiErrorException;

/**
 * Provides payment form for Stripe.
 */
class StripebyhabeukStaticOnSiteCheckoutForm extends PaymentMethodAddForm implements TrustedCallbackInterface {
  
  /**
   * Builds the credit card form.
   *
   * @param array $element
   *        The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *        The current state of the complete form.
   *        
   * @return array The built credit card form.
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    // Set our key to settings array.
    /** @var \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripebyhabeukStaticOnSite $plugin */
    $plugin = $this->plugin;
    $GateWay = new GateWay($plugin->getSecretKey());
    
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $idHtml = Html::getUniqueId('cart-ifs-' . rand(100, 999));
    $client_secret = null;
    // Alter the form with Stripe specific needs.
    $element['#attributes']['class'][] = 'stripe-by-habeuk-form';
    
    // space to add iframe to collect cart information by StripeJs.
    $element['titre_cart'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      "#attributes" => [],
      '#value' => 'Information sur la carte'
    ];
    $element['stripe_payment_method_id'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'payment-method-id' . $idHtml
      ]
    ];
    
    // space to add iframe to collect cart information by StripeJs.
    $element['cart_information'] = [
      '#type' => 'html_tag',
      '#tag' => 'section',
      "#attributes" => [
        'id' => $idHtml,
        'class' => []
      ]
    ];
    /**
     *
     * @var \Drupal\prise_rendez_vous\Plugin\Commerce\CheckoutFlow\PriseRendezVousCheckoutflow $formObject
     */
    $formObject = $form_state->getFormObject();
    if (method_exists($formObject, 'getOrder')) {
      /**
       *
       * @var \Drupal\commerce_order\Entity\Order $order
       */
      $order = $formObject->getOrder();
      
      try {
        $montant = $order->getTotalPrice()->getNumber();
        $label = $order->label();
        // on verifie si l'intention de payer a deja ete crrer
        $paymentIntentId = $this->getPaymentIndent($order->id());
        if ($paymentIntentId) {
          // Maj des informations de la commande.
          $PaymentIntents = $GateWay->UpdatePaymentIntents($paymentIntentId, $montant, $label);
        }
        else {
          // On cree l'intention de payer au niveau de stripe.
          $PaymentIntents = $GateWay->CreatePaymentIntents($montant, $label);
          $values = [
            'paymentintents_id' => $PaymentIntents->offsetGet('id'),
            'payment_intents_status' => $PaymentIntents->offsetGet('status'),
            'commerce_order' => $order->id()
          ];
          $this->setPaymentIndent($values);
        }
        
        $client_secret = $PaymentIntents->offsetGet('client_secret');
      }
      catch (ExceptionStripe $e) {
        $this->logger->error($e->getMessage());
        \Drupal::messenger()->addError($e->getMessage());
        return [];
      }
      catch (ApiErrorException $e) {
        $this->logger->error($e->getMessage());
        \Drupal::messenger()->addError($e->getMessage());
        /**
         * il ya un probleme d'autre de validation, il se peut que
         * $PaymentIntents
         * doit etre crrer ailleurs.
         * ( ou alors on retourne un message d'erreur ).
         */
        $form_state->setError($element, $e->getMessage());
        return [];
      }
      $element['stripe_payment_intents_id'] = [
        '#type' => 'hidden',
        '#value' => $PaymentIntents->offsetGet('id')
      ];
    }
    
    // js.
    $element['#attached']['library'][] = 'stripebyhabeuk/stripejsinit';
    // pass and attach datas.
    $element['#attached']['drupalSettings']['stripebyhabeuk'] = [
      'publishableKey' => $plugin->getPublishableKey(),
      'idhtml' => $idHtml,
      'paymentindent_client_secret' => $client_secret,
      'enable_credit_card_logos' => FALSE
    ];
    
    // $dd = [
    // 'paid' => $order->getTotalPaid(),
    // 'price' => [
    // $order->getTotalPrice()->getNumber(),
    // $order->getTotalPrice()->getCurrencyCode()
    // ],
    // 'title' => $order->label(),
    // 'key' => $plugin->getPublishableKey(),
    // 'PaymentIntents' => $PaymentIntents
    // ];
    // \Stephane888\Debug\debugLog::kintDebugDrupal($dd,
    // "buildCreditCardForm--debug--", true);
    
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->entity);
    $cacheability->setCacheMaxAge(0);
    $cacheability->applyTo($element);
    
    return $element;
  }
  
  protected function getPaymentIndent($order_id) {
    $datas = $this->entityTypeManager->getStorage('payment_intents')->loadByProperties([
      'commerce_order' => $order_id
    ]);
    if ($datas) {
      /**
       *
       * @var \Drupal\stripebyhabeuk\Entity\paymentIntents $payment_intents
       */
      $payment_intents = reset($datas);
      return $payment_intents->getStripePaymentIntentsId();
    }
    return null;
  }
  
  protected function setPaymentIndent(array $values) {
    $payment_intents = $this->entityTypeManager->getStorage('payment_intents')->create($values);
    $payment_intents->save();
  }
  
  /**
   * Validates the credit card form.
   *
   * @param array $element
   *        The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *        The current state of the complete form.
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    // The JS library performs its own validation.
  }
  
  /**
   * --
   */
  public static function trustedCallbacks() {
    parent::trustedCallbacks();
  }
  
}