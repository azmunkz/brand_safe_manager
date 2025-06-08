<?php

namespace Drupal\brand_safety_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class KeywordBulkForm extends FormBase {

  public function getFormId(): string {
    return 'brand_safety_manager_keyword_bulk';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
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

    $form['bulk_keywords'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Multiple Keywords (one per line)'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Keywords'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $lang = $form_state->getValue('language');
    $sev = $form_state->getValue('severity');
    $lines = preg_split("/\r\n|\n|\r/", $form_state->getValue('bulk_keywords'));
    $timestamp = \Drupal::time()->getCurrentTime();

    $count = 0;
    $skipped = 0;

    foreach ($lines as $kw) {
      $kw = trim($kw);
      if (empty($kw)) continue;

      $exists = \Drupal::database()->select('brand_safety_keywords', 'k')
        ->fields('k', ['id'])
        ->where('LOWER(k.keyword) = :keyword', [':keyword' => strtolower($kw)])
        ->condition('language', $lang)
        ->condition('severity', $sev)
        ->execute()
        ->fetchField();

      if ($exists) {
        $skipped++;
        continue;
      }

      \Drupal::database()->insert('brand_safety_keywords')
        ->fields([
          'keyword' => $kw,
          'language' => $lang,
          'severity' => $sev,
          'created' => $timestamp,
          'updated' => $timestamp,
        ])
        ->execute();

      $count++;
    }

    $this->messenger()->addStatus($this->t('@count keywords added. @skipped duplicates skipped.', [
      '@count' => $count,
      '@skipped' => $skipped,
    ]));

    $form_state->setRedirect('brand_safety_manager.keyword_list');
  }

}
