<?php

namespace Drupal\brand_safety_manager\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class KeywordDeleteForm extends ConfirmFormBase {

  protected $kid;
  protected $keyword;

  public function getFormId(): string {
    return 'brand_safety_manager_keyword_delete';
  }

  public function getQuestion(): string {
    return $this->t('Are you sure you want to delete the keyword "@kw"?', ['@kw' => $this->keyword]);
  }

  public function getCancelUrl(): Url {
    return new Url('brand_safety_manager.keyword_list');
  }

  public function getConfirmText(): string {
    return $this->t('Delete');
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array {
    $this->kid = $id;

    $record = \Drupal::database()->select('brand_safety_keywords', 'k')
      ->fields('k', ['keyword'])
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if ($record) {
      $this->keyword = $record->keyword;
    } else {
      $this->keyword = $this->t('[Unknown]');
      $this->messenger()->addError($this->t('Keyword not found.'));
      $form_state->setRedirectUrl($this->getCancelUrl());
    }

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    \Drupal::database()->delete('brand_safety_keywords')
      ->condition('id', $this->kid)
      ->execute();

    $this->messenger()->addStatus($this->t('Keyword "@kw" deleted successfully.', ['@kw' => $this->keyword]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
