<?php

namespace Drupal\spotify_lookup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spotify_lookup\SpotifyLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to search the Spotify API.
 */
class SpotifySearchForm extends FormBase {

  /**
   * The Spotify lookup service.
   *
   * @var \Drupal\spotify_lookup\SpotifyLookupService
   */
  protected $spotifyLookupService;

  /**
   * Constructs the SpotifySearchForm.
   */
  public function __construct(SpotifyLookupService $spotifyLookupService) {
    $this->spotifyLookupService = $spotifyLookupService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('spotify_lookup.spotify_lookup_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spotify_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Spotify'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Enter an album, artist, or songs name'),
      ],
      '#autocomplete_route_name' => 'spotify_lookup.autocomplete',
      '#autocomplete_route_parameters' => [
        'type' => $form_state->getValue('type') ?? 'album',
      ],
    ];
    
    
  
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Search Type'),
      '#options' => [
        'album' => $this->t('Album'),
        'artist' => $this->t('Artist'),
        'track' => $this->t('Tracks'),
      ],
      '#default_value' => 'album',
    ];

    $form['#attached']['library'][] = 'spotify_lookup/spotify_autocomplete';

  
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
  
    $form['#attached']['library'][] = 'spotify_lookup/spotify_autocomplete';
  
    return $form;
  }
  
  


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $form_state->getValue('query');
    $type = $form_state->getValue('type');
    $form_state->set('type', $type);
    
    $results = $this->spotifyLookupService->search($query, $type);
    
    if (!empty($results)) {
      $temp_store = \Drupal::service('tempstore.private')->get('spotify_results');
      $temp_store->set('spotify_results', $results);
      $temp_store->set('search_type', $type);
      $form_state->setRedirect('spotify_lookup.results_page');
    } else {
      \Drupal::messenger()->addMessage($this->t('No results found.'), 'error');
    }
  }
  
}
