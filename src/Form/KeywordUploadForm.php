<?php

namespace Drupal\brand_safety_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class KeywordUploadForm extends FormBase {

  public function getFormId(): string {
    return 'brand_safety_manager_keyword_upload';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attributes']['enctype'] = 'multipart/form-data';

    $form['csv_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload CSV File'),
      '#description' => $this->t('Must be a .csv file. Format: keyword,language,severity'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!isset($_FILES['files']['tmp_name']['csv_upload'])) {
      $form_state->setErrorByName('csv_upload', $this->t('Please upload a CSV file.'));
      return;
    }

    $filename = $_FILES['files']['name']['csv_upload'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
      $form_state->setErrorByName('csv_upload', $this->t('Only .csv files are allowed.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!isset($_FILES['files']['tmp_name']['csv_upload'])) {
      $this->messenger()->addError($this->t('CSV file not found.'));
      return;
    }

    $file_path = $_FILES['files']['tmp_name']['csv_upload'];
    $timestamp = \Drupal::time()->getCurrentTime();
    $count = 0;
    $skipped = 0;

    if (($handle = fopen($file_path, 'r')) !== FALSE) {
      while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) >= 3) {
          [$kw, $lang, $sev] = $data;
          $kw = trim($kw);
          if (empty($kw)) continue;

          $exists = \Drupal::database()->select('brand_safety_keywords', 'k')
            ->fields('k', ['id'])
            ->where('LOWER(k.keyword) = :keyword', [':keyword' => strtolower($kw)])
            ->condition('language', trim($lang))
            ->condition('severity', trim($sev))
            ->execute()
            ->fetchField();

          if ($exists) {
            $skipped++;
            continue;
          }

          \Drupal::database()->insert('brand_safety_keywords')
            ->fields([
              'keyword' => $kw,
              'language' => trim($lang),
              'severity' => trim($sev),
              'created' => $timestamp,
              'updated' => $timestamp,
            ])
            ->execute();

          $count++;
        }
      }
      fclose($handle);
    }

    $this->messenger()->addStatus($this->t('@count keywords imported. @skipped duplicates skipped.', [
      '@count' => $count,
      '@skipped' => $skipped,
    ]));

    $form_state->setRedirect('brand_safety_manager.keyword_list');
  }

}
