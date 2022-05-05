<?php

namespace Drupal\localgov_microsites_group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Form\GroupContentForm;

class DomainGroupContentAdd extends GroupContentForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // If we are on step 2 of a wizard, we need to alter the actions.
    if ($form_state->get('group_wizard')) {
      $wizard_id = $form_state->get('group_wizard_id');
      $store = $this->privateTempStoreFactory->get($wizard_id);
      $store_id = $form_state->get('store_id');

      if ($wizard_id == 'domain_group_add' && $store->get("$store_id:step") === 2) {
        // Add a back button to return to step 1 with.
        $actions['back'] = [
          '#type' => 'submit',
          '#value' => $this->t('Back'),
          '#submit' => ['::back'],
          '#limit_validation_errors' => [],
        ];

        // Make the label of the save button more intuitive.
        if ($wizard_id == 'domain_group_add') {
          $actions['submit']['#value'] = $this->t('Add membership to group and configure domain');
        }

        // Make sure we complete the wizard before saving.
        $actions['submit']['#submit'] = ['::submitForm', '::store'];

        // Add a cancel button to clear the private temp store. This exits the
        // wizard without saving.
        $actions['cancel'] = [
          '#type' => 'submit',
          '#value' => $this->t('Cancel'),
          '#submit' => ['::cancel'],
          '#limit_validation_errors' => [],
        ];
      }
    }

    return $actions;
  }

  /**
   * Store entity and move to next step.
   */
  public function store(array &$form, FormStateInterface $form_state) {
    $store = $this->privateTempStoreFactory->get($form_state->get('group_wizard_id'));
    $store_id = $form_state->get('store_id');

    $store->set("$store_id:membership", $this->getEntity());
    $store->set("$store_id:step", 3);

    // Disable any URL-based redirect until the final step.
    $request = $this->getRequest();
    $form_state->setRedirect('<current>', [], ['query' => $request->query->all()]);
    $request->query->remove('destination');
  }

  /**
   * Cancels the wizard for group creator membership.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\group\Entity\Controller\GroupController::addForm()
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $store = $this->privateTempStoreFactory->get($form_state->get('group_wizard_id'));
    $store_id = $form_state->get('store_id');
    $store->delete("$store_id:entity");
    $store->delete("$store_id:membership");
    $store->delete("$store_id:step");

    // Redirect to the front page if no destination was set in the URL.
    $form_state->setRedirect('<front>');
  }

}
