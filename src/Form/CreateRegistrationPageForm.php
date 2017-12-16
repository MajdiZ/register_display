<?php

namespace Drupal\register_display\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\register_display\RegisterDisplayServices;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CreateRegistrationPageForm.
 *
 * @package Drupal\register_display\Form
 */
class CreateRegistrationPageForm extends ConfigFormBase {

  protected $services;
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(RegisterDisplayServices $services,
    EntityDisplayRepositoryInterface $entityDisplayRepository,
    ConfigFactoryInterface $config_factory) {

    parent::__construct($config_factory);
    $this->services = $services;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('register_display.services'),
      $container->get('entity_display.repository'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_display_admin_create_registration_page';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'register_display.settings',
    ];
  }

  /**
   * Building form to add registration page.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form_state object.
   * @param null|string $roleId
   *   String of role id.
   *
   * @return array|bool
   *   Form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $roleId = NULL) {
    // Get available user roles for register.
    $availableUserRolesToRegister = $this->services->getAvailableUserRolesToRegister();

    $registerPageUrl = $this->services::REGISTER_DISPLAY_BASE_REGISTER_PATH . '/' . $roleId;

    // @Todo Put some text here.
    if (!isset($availableUserRolesToRegister[$roleId])) {
      return FALSE;
    }

    // Load configuration.
    $config = $this->config('register_display.settings.pages')->get($roleId);

    $form['roleId'] = [
      '#type' => 'value',
      '#value' => $roleId,
    ];

    $form['registerPageUrl'] = [
      '#type' => 'value',
      '#value' => $registerPageUrl,
    ];

    $form['registerPageAlias'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration page alias'),
      '#description' => $this->t('Register page url for this role is @url', ['@url' => $registerPageUrl]),
      '#default_value' => $config['registerPageAlias'],
      '#required' => TRUE,
    ];

    // Load display modes.
    $userFormDisplaysOptions = $this->entityDisplayRepository->getFormModeOptions('user');

    $form['displayId'] = [
      '#type' => 'select',
      '#title' => $this->t('Select display'),
      '#options' => $userFormDisplaysOptions,
      '#default_value' => $config['displayId'],
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // We need to check the alias.
    // Ignore check if this is form submitted for the first time.
    $roleId = $form_state->getValue('roleId');
    $config = $this->config('register_display.settings')->get($roleId);
    if ($config) {
      if ($config['registerPageAlias'] != $form_state->getValue('registerPageAlias')) {
        // This is creation for new alis, we need to be sure its not exist.
        if ($this->services->isAliasExist($form_state->getValue('registerPageAlias'))) {
          // Here we set error that alias is exist.
          $form_state->setErrorByName('registerPageAlias', $this->t('Alias already exist.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Load display modes.
    $userFormDisplaysOptions = $this->entityDisplayRepository->getFormModeOptions('user');
    $userRoleName = user_role_names();
    $formValues = [
      'roleId' => $form_state->getValue('roleId'),
      'roleName' => $userRoleName[$form_state->getValue('roleId')],
      'displayId' => $form_state->getValue('displayId'),
      'displayName' => $userFormDisplaysOptions[$form_state->getValue('displayId')],
      'registerPageUrl' => $form_state->getValue('registerPageUrl'),
      'registerPageAlias' => $form_state->getValue('registerPageAlias'),
    ];

    $this->configFactory->getEditable('register_display.settings.pages')
      ->set($formValues['roleId'], $formValues)
      ->save();
  }

}
