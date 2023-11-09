<?php

namespace Drupal\stripebyhabeuk\Services;

use Stephane888\Debug\Repositories\ConfigDrupal;
use Stripe\StripeClient;
use Stripe\PaymentIntent;
use Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices;

/**
 *
 * @author stephane
 *        
 */
class PasserelleStripe {
  
  /**
   * true si on doit utilser : sk_live_, sinon false pour sk_test_
   *
   * @var boolean
   */
  protected $prod_mode = false;
  
  /**
   *
   * @var array
   */
  protected $config;
  
  /**
   *
   * @var \Stripe\StripeClient
   */
  protected $stripeApi;
  
  /**
   * --
   */
  protected function initApp() {
    $this->config = ConfigDrupal::config('stripebyhabeuk.settings');
    if ($this->prod_mode) {
      $api_key = $this->config['api_key_live'];
    }
    else
      $api_key = $this->config['secret_key_test'];
    $this->stripeApi = new StripeClient($api_key);
  }
  
  /**
   *
   * @param string $id
   * @return \Stripe\PaymentIntent
   */
  public function getPaymentIndent($id) {
    try {
      $this->initApp();
      return $this->stripeApi->paymentIntents->retrieve($id);
    }
    catch (\Error $e) {
      $errors = [
        'msg' => $e->getMessage(),
        'trace' => $e->getTrace()
      ];
      \Stephane888\Debug\debugLog::kintDebugDrupal($errors, 'getPaymentIndent', true);
    }
  }
  
  /**
   *
   * @param float $montant
   *        //montant de la commande.
   * @param String $titre
   *        //titre de la commande.
   */
  public function paidInvoice(float $montant = 1, String $titre = 'null') {
    try {
      $montant = $montant * 100;
      $this->initApp();
      $paymentIntents = $this->stripeApi->paymentIntents->create([
        'amount' => $montant,
        'currency' => 'eur',
        'payment_method_types' => [
          'card'
        ]
      ]);
      $result = $paymentIntents->toArray();
      return $result;
    }
    catch (\Stripe\Exception\CardException $e) {
      $errors = [
        'Status is:' => $e->getHttpStatus(),
        'Type is:' => $e->getError()->type,
        'Code is:' => $e->getError()->code,
        'Param is:' => $e->getError()->param,
        'Message is:' => $e->getError()->message
      ];
      \Stephane888\Debug\debugLog::kintDebugDrupal($errors, 'paidInvoiceCardException', true);
    }
    catch (\Stripe\Exception\RateLimitException $e) {
      $errors = [
        'msg' => $e->getMessage(),
        'trace' => $e->getTrace()
      ];
      \Stephane888\Debug\debugLog::kintDebugDrupal($errors, 'paidInvoiceRateLimitException', true);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
      $errors = [
        'msg' => $e->getMessage(),
        'trace' => $e->getTrace()
      ];
      \Stephane888\Debug\debugLog::kintDebugDrupal($errors, 'paidInvoiceInvalidRequestException', true);
    }
    catch (\Stripe\Exception\AuthenticationException $e) {
      // Authentication with Stripe's API failed
      // (maybe you changed API keys recently)
      $errors = [
        'msg' => $e->getMessage(),
        'trace' => $e->getTrace()
      ];
      \Stephane888\Debug\debugLog::kintDebugDrupal($errors, 'paidInvoiceAuthenticationException', true);
    }
    catch (\Stripe\Exception\ApiConnectionException $e) {
      // Network communication with Stripe failed
      $errors = [
        'msg' => $e->getMessage(),
        'trace' => $e->getTrace()
      ];
      \Stephane888\Debug\debugLog::kintDebugDrupal($errors, 'paidInvoiceApiConnectionException', true);
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
      // Display a very generic error to the user, and maybe send
      // yourself an email
      $errors = [
        'msg' => $e->getMessage(),
        'trace' => $e->getTrace()
      ];
      \Stephane888\Debug\debugLog::kintDebugDrupal($errors, 'paidInvoiceApiErrorException', true);
    }
    catch (\Exception $e) {
      $errors = [
        'msg' => $e->getMessage(),
        'trace' => $e->getTrace()
      ];
      \Stephane888\Debug\debugLog::kintDebugDrupal($errors, 'paidInvoiceException', true);
    }
  }
  
}