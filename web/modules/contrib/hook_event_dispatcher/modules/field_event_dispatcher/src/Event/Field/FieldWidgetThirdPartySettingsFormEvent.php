<?php

namespace Drupal\field_event_dispatcher\Event\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_event_dispatcher\FieldHookEvents;

/**
 * Class FieldWidgetThirdPartySettingsFormEvent.
 *
 * @HookEvent(
 *   id="field_widget_third_party_settings_form"
 * )
 */
class FieldWidgetThirdPartySettingsFormEvent extends AbstractFieldThirdPartySettingsFormEvent {

  /**
   * The instantiated field widget plugin.
   *
   * @var \Drupal\Core\Field\WidgetInterface
   */
  private $plugin;

  /**
   * The entity form mode.
   *
   * @var string
   */
  private $formMode;

  /**
   * FieldWidgetThirdPartySettingsFormEvent constructor.
   *
   * @param \Drupal\Core\Field\WidgetInterface $plugin
   *   The instantiated field widget plugin.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field definition.
   * @param string $formMode
   *   The entity form mode.
   * @param array $form
   *   The (entire) configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function __construct(
    WidgetInterface $plugin,
    FieldDefinitionInterface $fieldDefinition,
    string $formMode,
    array $form,
    FormStateInterface $formState
  ) {
    $this->plugin = $plugin;
    $this->fieldDefinition = $fieldDefinition;
    $this->formMode = $formMode;
    $this->form = $form;
    $this->formState = $formState;
  }

  /**
   * Get the instantiated field widget plugin.
   *
   * @return \Drupal\Core\Field\WidgetInterface
   *   A field widget plugin.
   */
  public function getPlugin(): WidgetInterface {
    return $this->plugin;
  }

  /**
   * Get the entity form mode.
   *
   * @return string
   *   The form mode.
   */
  public function getFormMode(): string {
    return $this->formMode;
  }

  /**
   * {@inheritdoc}
   */
  public function getDispatcherType(): string {
    return FieldHookEvents::FIELD_WIDGET_THIRD_PARTY_SETTINGS_FORM;
  }

}
