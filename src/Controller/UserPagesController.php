<?php

/**
 * @file
 * Contains Drupal\register_display\Controller\UserPagesController.
 */

namespace Drupal\Register_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\register_display\RegisterDisplayServices;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserPagesController extends ControllerBase {

  protected $services;
  protected $entityTypeManager;
  protected $formBuilder;


  /**
   * {@inheritdoc}
   */
  public function __construct(RegisterDisplayServices $services,
    EntityTypeManagerInterface $entityTypeManager,
    FormBuilderInterface $formBuilder) {
    $this->services = $services;
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('register_display.services'),
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Register page.
   *
   * @param string $roleId
   *    Role ID.
   *
   * @return array
   *    Array form.
   */
  public function registerPage(string $roleId) {

    $registerPageConfig = $this->services->getRegistrationPages($roleId);

    $entity = User::create();

    $form_object = $this->entityTypeManager->getFormObject($entity->getEntityTypeId(), $registerPageConfig['displayId']);
    $form_object->setEntity($entity);

    $form_state = (new FormState())->setFormState([]);
    // Add role id value for form state.
    $form_state->setValue('roleId', $roleId);

    return $this->formBuilder->buildForm($form_object, $form_state);
  }

  public function registerPageTitle(string $roleId){
    return $roleId;
  }


}
