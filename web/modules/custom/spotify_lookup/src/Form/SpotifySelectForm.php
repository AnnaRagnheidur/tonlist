<?php

namespace Drupal\spotify_lookup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for selecting a Spotify album.
 */
class SpotifySelectForm extends FormBase {

  protected $results;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spotify_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $results = [], $type = 'album') {
    if (empty($results[$type . 's']['items'])) {
      return ['#markup' => $this->t('No @type found. Please try again.', ['@type' => ucfirst($type)])];
    }
  
    $options = [];
    foreach ($results[$type . 's']['items'] as $index => $item) {
      $label = match ($type) {
        'album' => $item['name'] . ' by ' . ($item['artists'][0]['name'] ?? 'Unknown Artist'),
        'artist' => $item['name'],
        'track' => $item['name'] . ' by ' . ($item['artists'][0]['name'] ?? 'Unknown Artist'),
        default => 'Unknown',
      };
      $options[$index] = $label;
    }
  
    $form['item'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select a @type to save', ['@type' => ucfirst($type)]),
      '#options' => $options,
      '#required' => TRUE,
    ];
  
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
    ];
  
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $item_index = $form_state->getValue('item');
    $temp_store = \Drupal::service('tempstore.private')->get('spotify_results');
  
    $type = $temp_store->get('search_type');
    $selected_item = $temp_store->get('spotify_results')[$type . 's']['items'][$item_index];
  
    $temp_store->set('selected_item', $selected_item);
    $temp_store->set('selected_type', $type);
  
    $form_state->setRedirect('spotify_lookup.detail_form');
  }
}