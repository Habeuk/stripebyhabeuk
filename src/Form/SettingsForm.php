<?php

namespace Drupal\stripebyhabeuk\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure stripe by habeuk settings for this site.
 */
class SettingsForm extends ConfigFormBase {
  
  /**
   *
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stripebyhabeuk_settings';
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'stripebyhabeuk.settings'
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('stripebyhabeuk.settings');
    //
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t(' Selectionner le mode '),
      '#default_value' => $config->get('mode'),
      '#options' => [
        'prod' => 'Prod',
        'dev' => 'Dev'
      ]
    ];
    //
    $form['api_key_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t(' Clé publique test '),
      '#default_value' => $config->get('api_key_test'),
      '#description' => 'pk_test_***'
    ];
    
    $form['secret_key_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t(' Clé secrète test '),
      '#default_value' => $config->get('secret_key_test'),
      '#description' => 'sk_test_***'
    ];
    
    //
    $form['api_key_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t(' Api key live '),
      '#default_value' => $config->get('api_key_live'),
      '#description' => 'pk_live_***'
    ];
    return parent::buildForm($form, $form_state);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // if ($form_state->getValue('example') != 'example') {
    // $form_state->setErrorByName('example', $this->t('The value is not
    // correct.'));
    // }
    parent::validateForm($form, $form_state);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('stripebyhabeuk.settings');
    $config->set('mode', $form_state->getValue('mode'));
    $config->set('api_key_test', $form_state->getValue('api_key_test'));
    $config->set('secret_key_test', $form_state->getValue('secret_key_test'));
    $config->set('api_key_live', $form_state->getValue('api_key_live'));
    $config->save();
    parent::submitForm($form, $form_state);
  }
  
}
