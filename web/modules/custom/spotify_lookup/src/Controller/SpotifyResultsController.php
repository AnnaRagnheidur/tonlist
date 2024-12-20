<?php

namespace Drupal\spotify_lookup\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for displaying Spotify search results.
 */
class SpotifyResultsController extends ControllerBase {

  /**
   * Displays the results stored in temporary storage.
   */
  public function results() {
    $temp_store = \Drupal::service('tempstore.private')->get('spotify_results');
    $results = $temp_store->get('spotify_results');
  
    if (empty($results)) {
      return [
        '#markup' => $this->t('No results found. Please try again.'),
      ];
    }
  
    $type = $temp_store->get('search_type') ?? 'album';
  
    return \Drupal::formBuilder()->getForm('Drupal\spotify_lookup\Form\SpotifySelectForm', $results, $type);
  }  
}