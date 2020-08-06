<?php

namespace Drupal\ucsc_profiles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ProfilesConfigForm extends ConfigFormBase {

  public function getFormId() {
    return 'ucsc_profiles_configform';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ucsc_profiles.settings');

    $form['ucsc_profiles_server'] = [
      '#type' => 'url',
      '#title' => $this->t('Directory Proxy URI'),
      '#description' => $this->t('URI of the LDAP proxy server to use'),
      '#default_value' => $config->get('ucsc_profiles_server'),
    ];
    $form['ucsc_profiles_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy API Key'),
      '#description' => $this->t('Value to pass in the x-api-key header'),
      '#default_value' => $config->get('ucsc_profiles_key'),
    ];
    $form['ucsc_profiles_directory_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Full Profile Page URL'),
      '#description' => $this->t('URL to the campus directory profile site'),
      '#default_value' => $config->get('ucsc_profiles_directory_link'),
    ];
    $form['ucsc_profiles_cache_ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('Profile TTL'),
      '#description' => $this->t('Amount of minutes to keep cached profile data'),
      '#default_value' => $config->get('ucsc_profiles_cache_ttl'),
    ];

    return parent::buildForm($form, $form_state);
  }

  protected function getEditableConfigNames() {
    return [
      'ucsc_profiles.settings',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('ucsc_profiles.settings');

    $config->set('ucsc_profiles_server', $form_state->getValue('ucsc_profiles_server'))->save();
    $config->set('ucsc_profiles_key', $form_state->getValue('ucsc_profiles_key'))->save();
    $config->set('ucsc_profiles_directory_link', $form_state->getValue('ucsc_profiles_directory_link'))->save();
    $config->set('ucsc_profiles_cache_ttl', $form_state->getValue('ucsc_profiles_cache_ttl'))->save();
    parent::submitForm($form, $form_state);
  }
}
