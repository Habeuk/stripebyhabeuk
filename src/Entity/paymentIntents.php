<?php

namespace Drupal\stripebyhabeuk\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Payment intents entity.
 *
 * @ingroup stripebyhabeuk
 *
 * @ContentEntityType(
 *   id = "payment_intents",
 *   label = @Translation("Payment intents"),
 *   handlers = {
 *     "storage" = "Drupal\stripebyhabeuk\paymentIntentsStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\stripebyhabeuk\paymentIntentsListBuilder",
 *     "views_data" = "Drupal\stripebyhabeuk\Entity\paymentIntentsViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\stripebyhabeuk\Form\paymentIntentsForm",
 *       "add" = "Drupal\stripebyhabeuk\Form\paymentIntentsForm",
 *       "edit" = "Drupal\stripebyhabeuk\Form\paymentIntentsForm",
 *       "delete" = "Drupal\stripebyhabeuk\Form\paymentIntentsDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\stripebyhabeuk\paymentIntentsHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\stripebyhabeuk\paymentIntentsAccessControlHandler",
 *   },
 *   base_table = "payment_intents",
 *   revision_table = "payment_intents_revision",
 *   revision_data_table = "payment_intents_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = FALSE,
 *   admin_permission = "administer payment intents entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "paymentintents_id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/payment_intents/{payment_intents}",
 *     "add-form" = "/admin/structure/payment_intents/add",
 *     "edit-form" = "/admin/structure/payment_intents/{payment_intents}/edit",
 *     "delete-form" = "/admin/structure/payment_intents/{payment_intents}/delete",
 *     "version-history" = "/admin/structure/payment_intents/{payment_intents}/revisions",
 *     "revision" = "/admin/structure/payment_intents/{payment_intents}/revisions/{payment_intents_revision}/view",
 *     "revision_revert" = "/admin/structure/payment_intents/{payment_intents}/revisions/{payment_intents_revision}/revert",
 *     "revision_delete" = "/admin/structure/payment_intents/{payment_intents}/revisions/{payment_intents_revision}/delete",
 *     "collection" = "/admin/structure/payment_intents",
 *   },
 *   field_ui_base_route = "payment_intents.settings"
 * )
 */
class paymentIntents extends EditorialContentEntityBase implements paymentIntentsInterface {
  
  use EntityChangedTrait;
  use EntityPublishedTrait;
  
  /**
   *
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id()
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    
    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    
    return $uri_route_parameters;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    
    // If no revision author has been set explicitly,
    // make the payment_intents owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }
  
  public function getStripePaymentIntentsId() {
    return $this->get('paymentintents_id')->value;
  }
  
  public function setStripePaymentIntentsId($paymentintents_id) {
    $this->set('paymentintents_id', $paymentintents_id);
    return $this;
  }
  
  /**
   * Name is use for save paymentintents_id
   *
   * {@inheritdoc}
   */
  public function getName() {
    return $this->getStripePaymentIntentsId();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setName($name) {
    return $this->setStripePaymentIntentsId($name);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    
    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('Authored by'))->setDescription(t('The user ID of author of the Payment intents entity.'))->setRevisionable(TRUE)->setSetting('target_type', 'user')->setSetting('handler', 'default')->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'author',
      'weight' => 0
    ])->setDisplayOptions('form', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 5,
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => '60',
        'autocomplete_type' => 'tags',
        'placeholder' => ''
      ]
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setConstraints([
      'UniqueField' => []
    ]);
    
    $fields['paymentintents_id'] = BaseFieldDefinition::create('string')->setLabel(t('Payment intent id'))->setDescription(t('the paymentintents_id from Stripe'))->setRevisionable(TRUE)->setSettings([
      'max_length' => 100,
      'text_processing' => 0
    ])->setDefaultValue('')->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
      'weight' => -4
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRequired(TRUE);
    
    $fields['commerce_order'] = BaseFieldDefinition::create('entity_reference')->setLabel(t(' commerce order '))->setSetting('target_type', 'commerce_order')->setSetting('handler', 'default')->setDisplayOptions('form', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 5
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);
    
    $fields['payment_intents_status'] = BaseFieldDefinition::create('string')->setLabel(" Espace interne ")->setDisplayOptions('form', [
      'type' => 'number'
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRequired(TRUE);
    
    $fields['status']->setDescription(t('A boolean indicating whether the Payment intents is published.'))->setDisplayOptions('form', [
      'type' => 'boolean_checkbox',
      'weight' => -3
    ]);
    
    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t('The time that the entity was created.'));
    
    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setDescription(t('The time that the entity was last edited.'));
    
    return $fields;
  }
  
}
