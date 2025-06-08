<?php

namespace Drupal\brand_safety_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class KeywordEditForm extends FormBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database')
    );
  }

  public function getFormId(): string {
    return 'brand_safety_manager_keyword_edit';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array {
    $record = $this->database->select('brand_safety_keywords', 'k')
      ->fields('k', ['keyword', 'language', 'severity'])
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();

    if (!$record) {
      $this->messenger()->addError($this->t('Keyword not found.'));
      return [];
    }

    $form['keyword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword'),
      '#default_value' => $record['keyword'],
      '#required' => TRUE,
    ];

    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => ['en' => 'English', 'bm' => 'Bahasa Malaysia', 'cn' => 'Chinese'],
      '#default_value' => $record['language'],
      '#required' => TRUE,
    ];

    $form['severity'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'],
      '#default_value' => $record['severity'],
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Keyword'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $kid = $form_state->getValue('id');

    $this->database->update('brand_safety_keywords')
      ->fields([
        'keyword' => trim($form_state->getValue('keyword')),
        'language' => $form_state->getValue('language'),
        'severity' => $form_state->getValue('severity'),
        'updated' => \Drupal::time()->getCurrentTime(),
      ])
      ->condition('id', $kid)
      ->execute();

    $this->messenger()->addStatus($this->t('Keyword updated successfully.'));
    $form_state->setRedirect('brand_safety_manager.keyword_list');
  }

}
