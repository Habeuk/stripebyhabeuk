<?php

namespace Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the example for payement on commece_stripe.
 *
 * @CommercePaymentGateway(
 *   id = "stripebyhabeuk_static_offsite",
 *   label = @Translation("stripe by habeuk static Offsite"),
 *   display_label = @Translation("stripe by habeuk static"),
 *    forms = {
 *     "offsite-payment" = "Drupal\stripebyhabeuk\PluginForm\StripebyhabeukStaticOffSiteCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class StripebyhabeukStaticOffSite extends OffsitePaymentGatewayBase {
  
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
    
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secrete key'),
      '#description' => $this->t('This is the private key from the Quickpay manager.'),
      '#default_value' => $this->configuration['secret_key'],
      '#required' => TRUE
    ];
    
    $form['publishable_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('publishable key'),
      '#description' => $this->t('The API key for the same user as used in Agreement ID.'),
      '#default_value' => $this->configuration['publishable_key'],
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
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase::submitConfigurationForm()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['secret_key'] = $values['secret_key'];
    $this->configuration['publishable_key'] = $values['publishable_key'];
    $this->configuration['enable_credit_card_icons'] = $values['enable_credit_card_icons'];
  }
  
}