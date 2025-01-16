<?php

namespace Drupal\stripebyhabeuk\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a Reliquat to paid revision.
 *
 * @ingroup stripebyhabeuk
 */
class ReliquatToPaidRevisionRevertForm extends ConfirmFormBase {

  /**
   * The Reliquat to paid revision.
   *
   * @var \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface
   */
  protected $revision;

  /**
   * The Reliquat to paid storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $reliquatToPaidStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;
  
  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->reliquatToPaidStorage = $container->get('entity_type.manager')->getStorage('reliquat_to_paid');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->time = $container->get('datetime.time');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reliquat_to_paid_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert to the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.reliquat_to_paid.version_history', ['reliquat_to_paid' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $reliquat_to_paid_revision = NULL) {
    $this->revision = $this->ReliquatToPaidStorage->loadRevision($reliquat_to_paid_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = $this->t('Copy of the revision from %date.', [
      '%date' => $this->dateFormatter->format($original_revision_timestamp),
    ]);
    $this->revision->save();

    $this->logger('content')->notice('Reliquat to paid: reverted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Reliquat to paid %title has been reverted to the revision from %revision-date.', ['%title' => $this->revision->label(), '%revision-date' => $this->dateFormatter->format($original_revision_timestamp)]));
    $form_state->setRedirect(
      'entity.reliquat_to_paid.version_history',
      ['reliquat_to_paid' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(ReliquatToPaidInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    $revision->setRevisionCreationTime($this->time->getRequestTime());

    return $revision;
  }

}
