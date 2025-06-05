<?php

namespace Drupal\brand_safety_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class KeywordAdminForm extends FormBase {

  protected Connection $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database')
    );
  }

  public function getFormId(): string {
    return 'brand_safety_manager_keywords_form';
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
      '#options' => ['en' => 'English', 'bm' => 'Bahasa Melayu', 'cn' => 'Chinese'],
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

    // Add keyword table listing.
    $header = [
      'keyword' => $this->t('Keyword'),
      'language' => $this->t('Language'),
      'severity' => $this->t('Severity'),
      'created' => $this->t('Created'),
      'operations' => $this->t('Operations'),
    ];

    $rows = [];
    $query = $this->database->select('brand_safety_keywords', 'k')
      ->fields('k', ['id', 'keyword', 'language', 'severity', 'created'])
      ->orderBy('created', 'DESC')
      ->execute();

    foreach ($query as $record) {
      $rows[] = [
        'data' => [
          $record->keyword,
          strtoupper($record->language),
          ucfirst($record->severity),
          \Drupal::service('date.formatter')->format($record->created, 'short'),
          $this->t('<a href=":url">Delete</a>', [
            ':url' => '/admin/config/brand-safety/keywords/delete/' . $record->id,
          ]),
        ],
      ];
    }

    $form['keyword_list'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No keywords found.'),
      '#caption' => $this->t('Existing Keywords'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $keyword = $form_state->getValue('keyword');
    $language = $form_state->getValue('language');
    $severity = $form_state->getValue('severity');
    $timestamp = \Drupal::time()->getCurrentTime();

    $this->database->insert('brand_safety_keywords')
      ->fields([
        'keyword' => $keyword,
        'language' => $language,
        'severity' => $severity,
        'created' => $timestamp,
        'updated' => $timestamp,
      ])
      ->execute();

    $this->messenger()->addMessage($this->t('Keyword added successfully.'));
  }
}
