<?php

namespace Drupal\brand_safety_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class KeywordListForm extends FormBase {

  public function getFormId(): string {
    return 'brand_safety_manager_keyword_list';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['action-links']],
    ];

    $form['actions']['add'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Single Keyword'),
      '#url' => \Drupal\Core\Url::fromRoute('brand_safety_manager.keyword_add'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];
    $form['actions']['bulk'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Multiple'),
      '#url' => \Drupal\Core\Url::fromRoute('brand_safety_manager.keyword_bulk'),
      '#attributes' => ['class' => ['button']],
    ];
    $form['actions']['upload'] = [
      '#type' => 'link',
      '#title' => $this->t('Upload CSV'),
      '#url' => \Drupal\Core\Url::fromRoute('brand_safety_manager.keyword_upload'),
      '#attributes' => ['class' => ['button']],
    ];

    $header = ['Keyword', 'Language', 'Severity', 'Created', 'Actions'];
    $rows = [];

    $query = \Drupal::database()->select('brand_safety_keywords', 'k')
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
          [
            'data' => [
              '#type' => 'operations',
              '#links' => [
                'edit' => [
                  'title' => $this->t('Edit'),
                  'url' => Url::fromRoute('brand_safety_manager.keyword_edit', ['id' => $record->id]),
                ],
                'delete' => [
                  'title' => $this->t('Delete'),
                  'url' => Url::fromRoute('brand_safety_manager.keyword_delete', ['id' => $record->id]),
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $form['table'] = [
      '#type' => 'table',
      '#title' => $this->t('Keywords List'),
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'keyword-table'],
      '#empty' => $this->t('No keywords found.'),
    ];

    // Attach DataTables
    $form['#attached']['library'][] = 'brand_safety_manager/datatables';

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Not used.
  }
}
