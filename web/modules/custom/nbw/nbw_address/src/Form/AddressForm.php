<?php

namespace Drupal\nbw_address\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the address entity edit forms.
 */
class AddressForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New address %label has been created.', $message_arguments));
        $this->logger('nbw_address')->notice('Created new address %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The address %label has been updated.', $message_arguments));
        $this->logger('nbw_address')->notice('Updated address %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.address.canonical', ['address' => $entity->id()]);

    return $result;
  }

}
