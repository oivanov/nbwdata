<?php

namespace Drupal\volunteer_sign_up_record\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the volunteer sign up and waver entity edit forms.
 */
class VolunteerSignUpAndWaverForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New volunteer sign up and waver %label has been created.', $message_arguments));
        $this->logger('volunteer_sign_up_record')->notice('Created new volunteer sign up and waver %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The volunteer sign up and waver %label has been updated.', $message_arguments));
        $this->logger('volunteer_sign_up_record')->notice('Updated volunteer sign up and waver %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.volunteer_sign_up_and_waver.canonical', ['volunteer_sign_up_and_waver' => $entity->id()]);

    return $result;
  }

}
