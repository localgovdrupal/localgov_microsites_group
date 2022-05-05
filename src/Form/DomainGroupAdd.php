<?php

namespace Drupal\localgov_microsites_group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Form\GroupForm;

class DomainGroupAdd extends GroupForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->getEntity()->getGroupType();
    $replace = ['@group_type' => $group_type->label()];

    if ($this->operation == 'new_domain') {
      $actions['submit']['#submit'] = ['::submitForm', '::store'];
      
      if ($group_type->creatorMustCompleteMembership())
        $actions['submit']['#value'] = $this->t('Create @group_type and complete owner membership', $replace);
      // Update the label if we are not using the wizard, but the group creator
      // still gets a membership upon group creation.
      elseif ($group_type->creatorGetsMembership()) {
        $actions['submit']['#value'] = $this->t('Create @group_type add owner member and configure domain', $replace);
      }
      // Use a simple submit label if none of the above applies.
      else {
        $actions['submit']['#value'] = $this->t('Create @group_type and configure domain', $replace);
      }

      // Add a cancel button to clear the private temp store. This exits the
      // wizard without saving.
      $actions['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#submit' => ['::cancel'],
        '#limit_validation_errors' => [],
      ];
    }

    return $actions;
  }

}
