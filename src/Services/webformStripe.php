<?php

namespace Drupal\stripebyhabeuk\Services;

use Stripe\StripeClient;

/**
 *
 * @author stephane
 *        
 */
class webformStripe {
  /**
   *
   * @var null
   */
  protected $endpointSecret;
  
  /**
   *
   * @var null
   */
  protected $event;
  
  /**
   * --
   */
  function getResponse() {
    $payload = file_get_contents('php://input');
    $event = null;
    $this->endpointSecret = '';
    
    try {
      $this->event = \Stripe\Event::constructFrom(json_decode($payload, true));
    }
    catch (\UnexpectedValueException $e) {
      // Invalid payload
      echo '⚠️  Webhook error while parsing basic request.';
      http_response_code(400);
      exit();
    }
    
    //
    if ($this->endpointSecret) {
      // Only verify the event if there is an endpoint secret defined
      // Otherwise use the basic decoded event
      $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
      try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $this->endpointSecret);
      }
      catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        echo '⚠️  Webhook error while validating signature.';
        http_response_code(400);
        exit();
      }
    }
    
    // Handle the event
    switch ($event->type) {
      case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object;
        // contains a
        // \Stripe\PaymentIntent
        // Then define and call a method
        // to handle the successful
        // payment intent.
        // handlePaymentIntentSucceeded($paymentIntent);
        break;
      case 'payment_method.attached':
        $paymentMethod = $event->data->object;
        // contains a
        // \Stripe\PaymentMethod
        // Then define and call a method
        // to handle the successful
        // attachment of a PaymentMethod.
        // handlePaymentMethodAttached($paymentMethod);
        break;
      default:
        error_log(' Received unknown event type ');
    }
  }
  
}