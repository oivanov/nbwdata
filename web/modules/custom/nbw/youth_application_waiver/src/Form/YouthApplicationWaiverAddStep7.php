<?php

namespace Drupal\youth_application_waiver\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the youth application waiver entity edit forms.
 */
class YouthApplicationWaiverAddStep7 extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Redirect to step 8.
    $entity = $this->getEntity();
    $entity->save();
    $id = $entity->id();
    $form_state->setRedirect('youth_application_waiver.step8', ['youth_application_waiver' => $id]);
/*    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New youth application waiver %label has been created.', $message_arguments));
        $this->logger('youth_application_waiver')->notice('Created new youth application waiver %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The youth application waiver %label has been updated.', $message_arguments));
        $this->logger('youth_application_waiver')->notice('Updated youth application waiver %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.youth_application_waiver.canonical', ['youth_application_waiver' => $entity->id()]);

    return $result;*/
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\youth_application_waiver\Entity\YouthApplicationWaiver */
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = t('Save and proceed');

    // Add a custom HTML element to the form.
    $waver_text = 'waver text from DB';
    $form['waver_element'] = array(
      '#markup' => '<br><div class="custom-form-header">The Liability Waver Text goes here: '.$waver_text. '</div>',
      '#weight' => -10,  // Adjust the weight to control the header's position.
    );
    return $form;
  }
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['go_back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back to step 6'),
      '#submit' => ['::goBack'],
      '#weight' => 90,
      '#limit_validation_errors' => []
    ];
    if (array_key_exists('delete', $actions)) {
      unset($actions['delete']);
    }
    $actions['#prefix'] = '<i>Step 7 of 9</i>';
    return $actions;
  }

  public function goBack(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $id = $entity->id();
    $form_state->setRedirect('youth_application_waiver.step6', ['youth_application_waiver' => $id]);
  }

}
