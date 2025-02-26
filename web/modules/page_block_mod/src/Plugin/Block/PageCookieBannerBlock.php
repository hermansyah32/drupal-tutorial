<?php

namespace Drupal\page_block_mod\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides a 'Page Cookie Banner' Block.
 *
 * @Block(
 *   id = "page_block_mod_cookie_banner",
 *   admin_label = @Translation("Cookie Banner Block")
 * )
 */
class PageCookieBannerBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $categories = json_decode($config['categories'] ?? '[]', true);

    return [
      '#theme' => 'cookie_banner_block',
      '#title' => $config['title'] ?? '',
      '#logo' => $config['logo'] ?? '',
      '#content' => $config['content'] ?? '',
      '#script' => $config['script'] ?? '',
      '#categories' => $categories,
      '#buttonAccept' => $config['buttonAccept'] ?? '',
      '#buttonDecline' => $config['buttonDecline'] ?? '',
      '#attached' => [
        'library' => ['page_block_mod/cookie_banner_block'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['#attributes']['enctype'] = 'multipart/form-data';

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Banner Title'),
      '#default_value' => $config['title'] ?? 'Cookie Consent',
    ];

    $form['Logo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Logo'),
      '#upload_location' => 'public://page_block_mod/',
      '#default_value' => $config['Logo'],
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
      ],
      '#description' => $this->t('Allowed file types: png, jpg, jpeg.'),
    ];

    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Banner Content'),
      '#default_value' => $config['content'] ?? 'We use cookies to improve your experience. By continuing, you accept our use of cookies.',
    ];

    $this->buildCategoriesFieldGroup($form, $form_state);

    $form['script'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Banner Script'),
      '#default_value' => $config['script'] ?? '',
    ];

    $form['buttonAccept'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accept Button'),
      '#default_value' => $config['buttonAccept'] ?? 'Accept',
    ];

    $form['buttonDecline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Decline Button'),
      '#default_value' => $config['buttonDecline'] ?? 'Decline',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $categories = $form_state->getValue('categories') ?? [];
    $logo = $form_state->getValue('logo');

    // save logo
    if (!empty($values['logo'])) {
      $file = File::load($logo[0]);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    $this->setConfigurationValue('title', $form_state->getValue('title'));
    $this->setConfigurationValue('logo', $logo);
    $this->setConfigurationValue('content', $form_state->getValue('content'));
    $this->setConfigurationValue('script', $form_state->getValue('script'));
    $this->setConfigurationValue('categories', json_encode($categories));
    $this->setConfigurationValue('buttonAccept', $form_state->getValue('buttonAccept'));
    $this->setConfigurationValue('buttonDecline', $form_state->getValue('buttonDecline'));
  }

  /**
   * Builds the categories field group.
   */
  private function buildCategoriesFieldGroup(array &$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $categories = json_decode($config['categories'] ?? '[]', true);
    $categoryFields = $categories['fields'] ?? [];

    if (empty($categoryFields)) {
      $categoryFields = [['label' => '', 'type' => '', 'value' => '']];
    }

    $form['categories'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cookie Categories'),
      '#prefix' => '<div id="cookie-categories-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['categories']['fields'] = [
      '#type' => 'container',
      '#prefix' => '<div id="cookie-categories-fields">',
      '#suffix' => '</div>',
    ];
    foreach ($categoryFields as $key => $category) {
      $this->buildCategoriesField($key, $form['categories']['fields'], $category);
    }

    $form['categories']['add_category'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Category'),
      '#submit' => [[$this, 'addCategory']],
      '#ajax' => [
        'callback' => [$this, 'addCategoryCallback'],
        'wrapper' => 'cookie-categories-fields',
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Builds the categories field.
   */
  private function buildCategoriesField(int $key, array &$form, $category) {
    $form[$key] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Category') . ' ' . ($key + 1),
    ];

    $form[$key]['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category Label'),
      '#default_value' => $category['label'] ?? '',
    ];

    $form[$key]['type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category Type'),
      '#default_value' => $category['type'] ?? '',
    ];

    $form[$key]['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category Value'),
      '#default_value' => $category['value'] ?? '',
    ];
  }

  /**
   * AJAX callback for adding a new category.
   */
  public function addCategoryCallback(array &$form, FormStateInterface $form_state) {
    $this->buildCategoriesFieldGroup($form, $form_state);

    return $form['categories']['fields'];
  }

  /**
   * Custom submit handler for adding a category.
   */
  public function addCategory(array &$form, FormStateInterface $form_state) {

    $categories = $form_state->get('categories') ?? [];
    $categoryFields = $categories['fields'] ?? [];
    $categoryFields[] = ['label' => '', 'type' => '', 'value' => ''];
    $categories['fields'] = $categoryFields;

    $form_state->set('categories', $categories);
    $form_state->setRebuild();
  }

}
