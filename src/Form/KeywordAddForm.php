<?php

namespace Drupal\brand_safety_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class KeywordAddForm extends FormBase {

  public function getFormId(): string {
    return 'brand_safety_manager_keyword_add';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['keyword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword'),
      '#required' => TRUE,
    ];

    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => ['en' => 'English', 'bm' => 'Bahasa Malaysia', 'cn' => 'Chinese'],
      '#required' => TRUE,
    ];

    $form['severity'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Keyword'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $kw = trim($form_state->getValue('keyword'));
    $lang = $form_state->getValue('language');
    $sev = $form_state->getValue('severity');
    $timestamp = \Drupal::time()->getCurrentTime();

    $exists = \Drupal::database()->select('brand_safety_keywords', 'k')
      ->fields('k', ['id'])
      ->where('LOWER(k.keyword) = :keyword', [':keyword' => strtolower($kw)])
      ->condition('language', $lang)
      ->condition('severity', $sev)
      ->execute()
      ->fetchField();

    if ($exists) {
      $this->messenger()->addError($this->t('Keyword already exists.'));
    } else {
      \Drupal::database()->insert('brand_safety_keywords')
        ->fields([
          'keyword' => $kw,
          'language' => $lang,
          'severity' => $sev,
          'created' => $timestamp,
          'updated' => $timestamp,
        ])
        ->execute();

      $this->messenger()->addStatus($this->t('Keyword added: @kw', ['@kw' => $kw]));
    }

    $form_state->setRedirect('brand_safety_manager.keyword_list');
  }

}
