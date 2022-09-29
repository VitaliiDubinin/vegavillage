<?php

namespace Drupal\custom_admin_menu\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_admin_menu\Service\CustomAdminMenuSettings;
use Drupal\custom_admin_menu\Service\CustomAdminMenuShortcuts;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Custom Admin Menu settings for this site.
 */
class SettingsForm extends FormBase {

  /**
   * Settings.
   *
   * @var \Drupal\custom_admin_menu\Service\CustomAdminMenuSettings
   */
  protected $settings;

  /**
   * ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\custom_admin_menu\Service\CustomAdminMenuSettings $settings
   *   The settings manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(CustomAdminMenuSettings $settings, ModuleHandlerInterface $moduleHandler) {
    $this->settings = $settings;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(CustomAdminMenuSettings::SERVICE_NAME),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_admin_menu_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Enable input.
    $form[CustomAdminMenuSettings::FIELD_ENABLE] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable custom admin menu.'),
      '#default_value' => $this->settings->get(CustomAdminMenuSettings::FIELD_ENABLE),
    ];

    // Insertion type.
    $this->initInsertionType($form, $form_state);

    // Shortcuts.
    $this->initShortcuts($form, $form_state);

    $form['actions']['save'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->settings
      ->set(CustomAdminMenuSettings::FIELD_ENABLE, $form_state->getValue(CustomAdminMenuSettings::FIELD_ENABLE))
      ->set(CustomAdminMenuSettings::FIELD_INSERTION_TYPE, $form_state->getValue(CustomAdminMenuSettings::FIELD_INCLUDED_IN_ADMIN) ? $form_state->getValue(CustomAdminMenuSettings::FIELD_INSERTION_TYPE) : NULL)
      ->set(CustomAdminMenuSettings::FIELD_INCLUDED_IN_ADMIN, $form_state->getValue(CustomAdminMenuSettings::FIELD_INCLUDED_IN_ADMIN))
      ->set(CustomAdminMenuSettings::FIELD_WRAP_DEFAULT_ADMIN, $form_state->getValue(CustomAdminMenuSettings::FIELD_WRAP_DEFAULT_ADMIN))
      ->set(CustomAdminMenuSettings::FIELD_SHORTCUTS_REGION, $form_state->getValue(CustomAdminMenuSettings::FIELD_SHORTCUTS_REGION))
      ->save();

    // Flush all persistent caches.
    // This is executed based on old/previously known information, which is
    // sufficient, since new extensions cannot have any primed caches yet.
    $this->moduleHandler->invokeAll('cache_flush');
    foreach (Cache::getBins() as $cache_backend) {
      $cache_backend->deleteAll();
    }

  }

  /**
   * Init insertion form part.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  protected function initInsertionType(array &$form, FormStateInterface $form_state) {
    $form['insertion'] = [
      '#type'  => 'details',
      '#title' => $this->t('Insertion'),
      '#open'  => TRUE,
    ];

    $form['insertion'][CustomAdminMenuSettings::FIELD_INCLUDED_IN_ADMIN] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Insert custom items in admin toolbar'),
      '#default_value' => $this->settings->get(CustomAdminMenuSettings::FIELD_INCLUDED_IN_ADMIN),
      '#required'      => FALSE,
    ];

    $form['insertion'][CustomAdminMenuSettings::FIELD_INSERTION_TYPE] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Insertion type'),
      '#default_value' => $this->settings->get(CustomAdminMenuSettings::FIELD_INSERTION_TYPE) ?: CustomAdminMenuSettings::INSERTION_TYPE_PREPEND,
      '#options'       => [
        CustomAdminMenuSettings::INSERTION_TYPE_PREPEND => $this->t('Prepend'),
        CustomAdminMenuSettings::INSERTION_TYPE_APPEND  => $this->t('Append'),
      ],
      '#states'        => [
        'required' => [
          ':input[name="' . CustomAdminMenuSettings::FIELD_INCLUDED_IN_ADMIN . '"]' => ['checked' => TRUE],
        ],
        'visible'  => [
          ':input[name="' . CustomAdminMenuSettings::FIELD_INCLUDED_IN_ADMIN . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['insertion'][CustomAdminMenuSettings::FIELD_WRAP_DEFAULT_ADMIN] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Wrap default admin menu in a single root menu item'),
      '#default_value' => $this->settings->get(CustomAdminMenuSettings::FIELD_WRAP_DEFAULT_ADMIN),
      '#states'        => [
        'visible' => [
          ':input[name="' . CustomAdminMenuSettings::FIELD_INCLUDED_IN_ADMIN . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Init Shortcuts form part.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formState.
   */
  protected function initShortcuts(array &$form, FormStateInterface $form_state) {
    $form['shortcuts_wrapper'] = [
      '#type'  => 'details',
      '#title' => $this->t('Shorcuts'),
      '#open'  => TRUE,
    ];

    $regions = CustomAdminMenuShortcuts::me()->getShortcutRegions();
    $form['shortcuts_wrapper'][CustomAdminMenuSettings::FIELD_SHORTCUTS_REGION] = [
      '#type'          => 'select',
      '#title'         => $this->t('Shortcuts region'),
      '#options'       =>
        [NULL => '-- None --'] + array_map(function ($item) {
          return $item . '';
        }, $regions),
      '#default_value' => $this->settings->get(CustomAdminMenuSettings::FIELD_SHORTCUTS_REGION),
    ];
  }

}
