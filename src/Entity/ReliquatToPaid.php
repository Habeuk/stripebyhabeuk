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
 * Defines the Reliquat to paid entity.
 *
 * @ingroup stripebyhabeuk
 *
 * @ContentEntityType(
 *   id = "reliquat_to_paid",
 *   label = @Translation("Reliquat to paid"),
 *   handlers = {
 *     "storage" = "Drupal\stripebyhabeuk\ReliquatToPaidStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\stripebyhabeuk\ReliquatToPaidListBuilder",
 *     "views_data" = "Drupal\stripebyhabeuk\Entity\ReliquatToPaidViewsData",
 *     "translation" = "Drupal\stripebyhabeuk\ReliquatToPaidTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\stripebyhabeuk\Form\ReliquatToPaidForm",
 *       "add" = "Drupal\stripebyhabeuk\Form\ReliquatToPaidForm",
 *       "edit" = "Drupal\stripebyhabeuk\Form\ReliquatToPaidForm",
 *       "delete" = "Drupal\stripebyhabeuk\Form\ReliquatToPaidDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\stripebyhabeuk\ReliquatToPaidHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\stripebyhabeuk\ReliquatToPaidAccessControlHandler",
 *   },
 *   base_table = "reliquat_to_paid",
 *   data_table = "reliquat_to_paid_field_data",
 *   revision_table = "reliquat_to_paid_revision",
 *   revision_data_table = "reliquat_to_paid_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer reliquat to paid entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
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
 *     "canonical" = "/admin/structure/reliquat_to_paid/{reliquat_to_paid}",
 *     "add-form" = "/admin/structure/reliquat_to_paid/add",
 *     "edit-form" = "/admin/structure/reliquat_to_paid/{reliquat_to_paid}/edit",
 *     "delete-form" = "/admin/structure/reliquat_to_paid/{reliquat_to_paid}/delete",
 *     "version-history" = "/admin/structure/reliquat_to_paid/{reliquat_to_paid}/revisions",
 *     "revision" = "/admin/structure/reliquat_to_paid/{reliquat_to_paid}/revisions/{reliquat_to_paid_revision}/view",
 *     "revision_revert" = "/admin/structure/reliquat_to_paid/{reliquat_to_paid}/revisions/{reliquat_to_paid_revision}/revert",
 *     "revision_delete" = "/admin/structure/reliquat_to_paid/{reliquat_to_paid}/revisions/{reliquat_to_paid_revision}/delete",
 *     "translation_revert" = "/admin/structure/reliquat_to_paid/{reliquat_to_paid}/revisions/{reliquat_to_paid_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/reliquat_to_paid",
 *   },
 *   field_ui_base_route = "reliquat_to_paid.settings"
 * )
 */
class ReliquatToPaid extends EditorialContentEntityBase implements ReliquatToPaidInterface {
  
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
    
    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);
      
      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }
    
    // If no revision author has been set explicitly,
    // make the reliquat_to_paid owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
  
  public function getAmountPaid() {
    $price = $this->get('amount_paid')->first();
    if ($price->number)
      return $price->toPrice();
  }
  
  public function getAmountToPaid() {
    $price = $this->get('amount_to_paid')->first();
    if ($price->number)
      return $price->toPrice();
  }
  
  public function getAmountTotal() {
    $price = $this->get('amount_total')->first();
    if ($price->number)
      return $price->toPrice();
  }
  
  public function getAmountRelicatPaid() {
    $price = $this->get('amount_relicat_paid')->first();
    if ($price->number)
      return $price->toPrice();
  }
  
  public function getOrderInformation() {
    if ($id = $this->get('commerce_order')->target_id) {
      $order = \Drupal\commerce_order\Entity\Order::load($id);
      /**
       *
       * @var \Drupal\Core\Datetime\DateFormatter $DateFormatter
       */
      $DateFormatter = \Drupal::service('date.formatter');
      
      $datas = $order->getItems();
      $articles = [];
      foreach ($datas as $data) {
        /**
         *
         * @var \Drupal\commerce_order\Entity\OrderItem $data
         */
        $articles[] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => ' - ' . $data->label()
        ];
      }
      $infos[] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => 'Prix total : ',
        [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $this->getAmountTotal()
        ]
      ];
      $infos[] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => 'Accompte payé : ',
        [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $this->getAmountPaid()
        ]
      ];
      $relicat = $this->getAmountRelicatPaid();
      if ($relicat)
        $infos[] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => 'Relicat payé : ',
          [
            '#type' => 'html_tag',
            '#tag' => 'strong',
            '#value' => $relicat
          ]
        ];
      // articles
      $infos[] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => 'Articles',
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          $articles
        ]
      ];
      // dates
      $infos[] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => 'Date de creation de la commande',
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $DateFormatter->format($order->getCreatedTime(), 'H:m d-m-Y')
        ]
      ];
      
      /**
       *
       * @var \Drupal\Core\Render\Renderer $render
       */
      $render = \Drupal::service('renderer');
      return $render->renderPlain($infos);
    }
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    
    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('Authored by'))->setDescription(t('The user ID of author of the Reliquat to paid entity.'))->setRevisionable(TRUE)->setSetting('target_type', 'user')->setSetting('handler', 'default')->setTranslatable(TRUE)->setDisplayOptions('view', [
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
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);
    
    $fields['name'] = BaseFieldDefinition::create('string')->setLabel(t('Name'))->setDescription(t('The name of the Reliquat to paid entity.'))->setRevisionable(TRUE)->setSettings([
      'max_length' => 50,
      'text_processing' => 0
    ])->setDefaultValue('')->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
      'weight' => -4
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRequired(TRUE);
    //
    $fields['payment_intent_id'] = BaseFieldDefinition::create('string')->setLabel('payment_intent_id')->setRevisionable(TRUE)->setSettings([
      'max_length' => 250,
      'text_processing' => 0
    ])->setDefaultValue('')->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
      'weight' => -4
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRequired(TRUE);
    //
    $fields['amount_paid'] = BaseFieldDefinition::create('commerce_price')->setLabel(t('Amount paid'))->setRequired(TRUE)->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'commerce_price_default',
      'weight' => 0
    ])->setDisplayOptions('form', [
      'type' => 'commerce_price_default',
      'weight' => 0
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRevisionable(TRUE);
    
    $fields['amount_to_paid'] = BaseFieldDefinition::create('commerce_price')->setLabel(t('Amount to paid'))->setRequired(TRUE)->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'commerce_price_default',
      'weight' => 0
    ])->setDisplayOptions('form', [
      'type' => 'commerce_price_default',
      'weight' => 0
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRevisionable(TRUE);
    
    $fields['amount_relicat_paid'] = BaseFieldDefinition::create('commerce_price')->setLabel(t('Amount relicat paid'))->setRequired(TRUE)->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'commerce_price_default',
      'weight' => 0
    ])->setDisplayOptions('form', [
      'type' => 'commerce_price_default',
      'weight' => 0
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRevisionable(TRUE);
    
    /**
     * On sauvegarde le montant total, pour des verifications.
     */
    $fields['amount_total'] = BaseFieldDefinition::create('commerce_price')->setLabel(t('Amount total'))->setRequired(TRUE)->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'commerce_price_default',
      'weight' => 0
    ])->setDisplayOptions('form', [
      'type' => 'commerce_price_default',
      'weight' => 0
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRevisionable(TRUE);
    
    $fields['commerce_order'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('commerce order'))->setRevisionable(TRUE)->setSetting('target_type', 'commerce_order')->setSetting('handler', 'default')->setTranslatable(TRUE)->setDisplayOptions('view', [
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
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);
    
    $fields['status']->setDescription(t('A boolean indicating whether the Reliquat to paid is published.'))->setDisplayOptions('form', [
      'type' => 'boolean_checkbox',
      'weight' => -3
    ]);
    
    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t('The time that the entity was created.'));
    
    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setDescription(t('The time that the entity was last edited.'));
    
    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')->setLabel(t('Revision translation affected'))->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))->setReadOnly(TRUE)->setRevisionable(TRUE)->setTranslatable(TRUE);
    
    return $fields;
  }
  
}
