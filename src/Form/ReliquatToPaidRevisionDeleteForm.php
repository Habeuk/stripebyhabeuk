<?php

namespace Drupal\stripebyhabeuk\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Reliquat to paid revision.
 *
 * @ingroup stripebyhabeuk
 */
class ReliquatToPaidRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->reliquatToPaidStorage = $container->get('entity_type.manager')->getStorage('reliquat_to_paid');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reliquat_to_paid_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
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
    return $this->t('Delete');
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
    $this->ReliquatToPaidStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Reliquat to paid: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Reliquat to paid %title has been deleted.', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.reliquat_to_paid.canonical',
       ['reliquat_to_paid' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {reliquat_to_paid_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.reliquat_to_paid.version_history',
         ['reliquat_to_paid' => $this->revision->id()]
      );
    }
  }

}
