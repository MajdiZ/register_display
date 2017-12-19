<?php

namespace Drupal\register_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\register_display\RegisterDisplayServices;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Class AdminPagesController.
 *
 * @package Drupal\register_display\Controller
 */
class AdminPagesController extends ControllerBase {
  protected $services;

  /**
   * {@inheritdoc}
   */
  public function __construct(RegisterDisplayServices $services) {
    $this->services = $services;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('register_display.services')
    );
  }

  /**
   * Register display.
   */
  public function indexAdmin() {
    // Prepare table.
    $header = [
      t('Role'),
      t('Display'),
      t('Path'),
      t('Operations'),
    ];

    $output = [
      '#theme' => 'table',
      '#header' => $header,
      '#empty' => $this->t(
        "You don't have valid role to create
       registration page. Please notice that you won't be able to create any
        registration page for role marked as admin."
      ),
      '#attributes' => ['id' => 'user-roles-reg-pages'],
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];

    // Get valid available user roles.
    $availableUserRoles = $this->services->getAvailableUserRolesToRegister();
    if (empty($availableUserRoles)) {
      return $output;
    }

    // Get registration pages.
    $pages = $this->services->getRegistrationPages();

    $output['#rows'] = [];
    foreach ($availableUserRoles as $roleId => $roleDisplayName) {
      if ($pages && array_key_exists($roleId, $pages)) {
        // Prepare operations.
        $operations = [
          '#type' => 'dropbutton',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit register page'),
              'url' => Url::fromRoute('register_display.edit_registration_page_form', ['roleId' => $roleId]),
            ],
            'delete' => [
              'title' => $this->t('Delete register page'),
              'url' => Url::fromRoute('register_display.delete_registration_page_form', ['roleId' => $roleId]),
            ],
          ],
        ];

        $operationsRendered = \Drupal::service('renderer')->render($operations);
        // Prepare row.
        $output['#rows'][] = [
          $roleDisplayName,
          $pages[$roleId]['displayName'],
          $this->t('<strong>Path:</strong> @path <br/> <strong>Alias</strong>: @alias', [
            '@path' => $pages[$roleId]['registerPageUrl'],
            '@alias' => $pages[$roleId]['registerPageAlias'],
          ]),
          $operationsRendered,
        ];

      }
      else {
        // Prepare operations.
        $operations = [
          '#type' => 'dropbutton',
          '#links' => [
            'create' => [
              'title' => $this->t('Create register page'),
              'url' => Url::fromRoute('register_display.create_registration_page_form', ['roleId' => $roleId]),
            ],
          ],
        ];

        $operationsRendered = \Drupal::service('renderer')->render($operations);

        // Prepare row.
        $output['#rows'][] = [
          $roleDisplayName,
          '--',
          '--',
          $operationsRendered,
        ];
      }

    }

    return $output;
  }

}
