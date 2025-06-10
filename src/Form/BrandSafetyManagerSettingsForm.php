<?php

namespace Drupal\brand_safety_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class BrandSafetyManagerSettingsForm extends ConfigFormBase {

  public function getFormId(): string {
    return 'brand_safety_manager_settings_form';
  }

  protected function getEditableConfigNames(): array {
    return ['brand_safety_manager.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('brand_safety_manager.settings');

    $form['ai_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('AI Scan Prompt'),
      '#default_value' => $config->get('ai_prompt') ?? '',
      '#description' => $this->t('Custom prompt for AI brand safety analysis.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('brand_safety_manager.settings')
      ->set('ai_prompt', $form_state->getValue('ai_prompt'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
