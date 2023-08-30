<?php

namespace Drupal\adult_patron_waver\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the bike church patron waiver entity edit forms.
 */
class AdultPatronWaverForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New bike church patron waiver %label has been created.', $message_arguments));
        $this->logger('adult_patron_waver')->notice('Created new bike church patron waiver %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The bike church patron waiver %label has been updated.', $message_arguments));
        $this->logger('adult_patron_waver')->notice('Updated bike church patron waiver %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.adult_patron_waver.canonical', ['adult_patron_waver' => $entity->id()]);

    return $result;
  }

}
