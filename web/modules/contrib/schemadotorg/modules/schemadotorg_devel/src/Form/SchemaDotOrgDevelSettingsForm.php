<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_devel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org Devel settings.
 */
class SchemaDotOrgDevelSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_devel_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_devel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('schemadotorg_devel.settings');

    $form['schemadotorg_devel'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schemadotorg_devel']['generate_property_values'] = [
      '#title' => $this->t('Schema.org devel generate property values'),
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_format' => 'propertyName|value01,value02,value03 or propertyName| or SchemaType--propertyName|value01,value02,value03 or SchemaType--propertyName|',
      '#description' => $this->t('Enter Schema.org property values to be used when generating content using the Devel generate module.')
      . ' '
      . $this->t('Leave blank to disable the generation of property values.'),
      '#description_link' => 'types',
      '#default_value' => $config->get('generate_property_values'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('schemadotorg_devel.settings');
    $values = $form_state->getValue('schemadotorg_devel');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
