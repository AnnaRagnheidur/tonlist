<?php

namespace Drupal\spotify_lookup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

/**
 * Form to save selected album details from Spotify.
 */
class SpotifyDetailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spotify_detail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $temp_store = \Drupal::service('tempstore.private')->get('spotify_results');
    $type = $temp_store->get('selected_type');
    $item = $temp_store->get('selected_item');

    if (!$item) {
        return ['#markup' => '<p>No item selected.</p>'];
    }

    $form['details'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Selected @type: @name', [
            '@type' => ucfirst($type),
            '@name' => $item['name'] ?? 'Unknown',
        ]),
    ];

    if ($type === 'track') {
        $form['save_song_title'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Save Song Title: @title', ['@title' => $item['name'] ?? 'Unknown']),
            '#default_value' => TRUE,
        ];

        $form['save_song_length'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Save Song Length: @length', [
                '@length' => $this->formatSongLength($item['duration_ms'] ?? 0),
            ]),
            '#default_value' => TRUE,
        ];

        $spotify_id = $item['id'] ?? '';
        $spotify_link = $spotify_id ? 'https://open.spotify.com/track/' . $spotify_id : '';
        $form['save_song_id'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Save Spotify Link', [
                '@link' => $spotify_link ?: 'Unknown',
            ]),
            '#default_value' => TRUE,
        ];
    } elseif ($type === 'album') {
        $form['save_album_title'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Save Album Title: @title', ['@title' => $item['name'] ?? 'Unknown']),
            '#default_value' => TRUE,
        ];

        $form['save_album_artist'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Save Artist: @artist', ['@artist' => $item['artists'][0]['name'] ?? 'Unknown']),
            '#default_value' => TRUE,
        ];

        if (!empty($item['images'][0]['url'])) {
            $form['save_album_image'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Save Album Cover'),
                '#default_value' => TRUE,
            ];
        }

        $form['save_album_release_date'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Save Release Year: @year', [
                '@year' => substr($item['release_date'] ?? 'Unknown', 0, 4),
            ]),
            '#default_value' => TRUE,
        ];

        $form['save_album_tracks'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Save All Songs from Album'),
          '#default_value' => FALSE,
        ];
        
    } elseif ($type === 'artist') {
        $form['save_artist_name'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Save Artist Name: @name', ['@name' => $item['name'] ?? 'Unknown']),
            '#default_value' => TRUE,
        ];

        if (!empty($item['images'][0]['url'])) {
            $form['save_artist_image'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Save Artist Image'),
                '#default_value' => TRUE,
            ];
        }

        $artist_genres = !empty($item['genres']) ? implode(', ', $item['genres']) : '';
        if (!empty($artist_genres)) {
            $form['save_artist_genres'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Save Genres: @genres', ['@genres' => $artist_genres]),
                '#default_value' => TRUE,
            ];
        }

        $spotify_id = $item['id'] ?? '';
        $spotify_link = $spotify_id ? 'https://open.spotify.com/artist/' . $spotify_id : '';
        $form['save_artist_spotify_link'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Save Spotify Link: <a href="@link" target="_blank">@link</a>', [
                '@link' => $spotify_link,
            ]),
            '#default_value' => FALSE,
        ];
    }

    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Details'),
    ];

    return $form;
}


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $temp_store = \Drupal::service('tempstore.private')->get('spotify_results');
    $type = $temp_store->get('selected_type');
    $item = $temp_store->get('selected_item');
  
    if (!$item) {
      \Drupal::messenger()->addMessage($this->t('No item data found.'), 'error');
      return;
    }
  
    $node_data = [
      'type' => match ($type) {
        'album' => 'plata',
        'artist' => 'listamadur',
        'track' => 'lag',
        default => 'listamadur',
      },
      'title' => $item['name'] ?? 'No Title',
    ];
  
    $existing_node = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', $node_data['type'])
      ->condition('title', $node_data['title'])
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
  
    if (!empty($existing_node)) {
      \Drupal::messenger()->addMessage($this->t('@type "@title" already exists and was not added.', [
        '@type' => ucfirst($type),
        '@title' => $node_data['title'],
      ]), 'error');
      $form_state->setRedirect('spotify_lookup.search_form');
      return;
    }
  
    if ($type === 'track') {
      $spotify_id = $item['id'] ?? '';
      $spotify_link = $spotify_id ? 'https://open.spotify.com/track/' . $spotify_id : '';
  
      $node_data['field_song_name'] = $form_state->getValue('save_song_title') ? $item['name'] : '';
      $node_data['field_song_length'] = $form_state->getValue('save_song_length')
          ? $this->formatSongLength($item['duration_ms'] ?? 0)
          : '';
      $node_data['field_song_id'] = $form_state->getValue('save_song_id') ? $spotify_link : '';
  
      try {
          $node = Node::create($node_data);
          $node->save();
          \Drupal::messenger()->addMessage($this->t('Song details saved successfully.'));
  
          $form_state->setRedirect('spotify_lookup.search_form');
          return;
      } catch (\Exception $e) {
          \Drupal::logger('spotify_lookup')->error('Failed to save song: @message', ['@message' => $e->getMessage()]);
          \Drupal::messenger()->addMessage($this->t('Failed to save song details.'), 'error');
      }
  }
  
    elseif ($type === 'album') {
      $artist_name = $item['artists'][0]['name'] ?? '';

      $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
      $artist_ids = $query
          ->condition('type', 'listamadur')
          ->condition('title', $artist_name)
          ->accessCheck(FALSE)
          ->range(0, 1)
          ->execute();

      $artist_id = null;

      if (!empty($artist_ids)) {
          $artist_id = reset($artist_ids);
      } else {
          $spotify_artist_id = $item['artists'][0]['id'] ?? '';
          $artist_details = [];
          if ($spotify_artist_id) {
              $artist_details = \Drupal::service('spotify_lookup.spotify_lookup_service')->getArtistDetails($spotify_artist_id);
          }

          $artist_data = [
              'type' => 'listamadur',
              'title' => $artist_name,
          ];

          if (!empty($artist_name)) {
              $artist_data['field_name'] = $artist_name;
          }

          $spotify_link = $spotify_artist_id ? 'https://open.spotify.com/artist/' . $spotify_artist_id : '';
          if ($spotify_link) {
              $artist_data['field_website_link'] = $spotify_link;
          }

          if (!empty($artist_details['images'][0]['url'])) {
              $file = $this->downloadImage($artist_details['images'][0]['url']);
              if ($file) {
                  $artist_data['field_artist_image'] = [
                      'target_id' => $file->id(),
                      'alt' => $artist_name,
                  ];
              }
          }

          $artist_node = Node::create($artist_data);
          $artist_node->save();
          $artist_id = $artist_node->id();
      }

      $node_data['field_album_title'] = $form_state->getValue('save_album_title') ? $item['name'] : '';
      $node_data['field_performer'] = $form_state->getValue('save_album_artist') ? ['target_id' => $artist_id] : NULL;
      $node_data['field_release_year'] = $form_state->getValue('save_album_release_date') ? substr($item['release_date'], 0, 4) : '';

      if (!empty($item['images'][0]['url']) && $form_state->getValue('save_album_image')) {
          $file = $this->downloadImage($item['images'][0]['url']);
          if ($file) {
              $node_data['field_cover_image'] = [
                  'target_id' => $file->id(),
                  'alt' => $item['name'] ?? 'Cover Image',
              ];
          }
      }

      try {
          $album_node = Node::create($node_data);
          $album_node->save();
          \Drupal::messenger()->addMessage($this->t('Album details saved successfully.'));
          $form_state->setRedirect('spotify_lookup.search_form');
      } catch (\Exception $e) {
          \Drupal::logger('spotify_lookup')->error('Failed to save album: @message', ['@message' => $e->getMessage()]);
          \Drupal::messenger()->addMessage($this->t('Failed to save album.'), 'error');
          $form_state->setRedirect('spotify_lookup.search_form');
      }

      if ($form_state->getValue('save_album_tracks')) {
          $tracks = \Drupal::service('spotify_lookup.spotify_lookup_service')->getAlbumDetails($item['id'])['tracks']['items'] ?? [];
          foreach ($tracks as $track) {
              $this->saveTrack($track, $album_node->id());
          }
          \Drupal::messenger()->addMessage($this->t('All songs from the album have been saved.'));
      }

      return;
  }
  
    elseif ($type === 'artist') {
      $spotify_id = $item['id'] ?? '';
      $spotify_link = $spotify_id ? 'https://open.spotify.com/artist/' . $spotify_id : '';
      $artist_genres = !empty($item['genres']) ? implode(', ', $item['genres']) : '';
  
      $node_data['field_name'] = $form_state->getValue('save_artist_name') ? $item['name'] : '';
      $node_data['field_artist_genre'] = $form_state->getValue('save_artist_genres') ? $artist_genres : '';
      $node_data['field_website_link'] = $form_state->getValue('save_artist_spotify_link') ? $spotify_link : '';
  
      if (!empty($item['images'][0]['url']) && $form_state->getValue('save_artist_image')) {
        $file = $this->downloadImage($item['images'][0]['url']);
        if ($file) {
          $node_data['field_artist_image'] = [
            'target_id' => $file->id(),
            'alt' => $item['name'] ?? 'Artist Image',
          ];
        }
      }
    }
  

    try {
      $node = Node::create($node_data);
      $node->save();
      \Drupal::messenger()->addMessage($this->t('@type details saved successfully.', ['@type' => ucfirst($type)]));
      $form_state->setRedirect('spotify_lookup.search_form');
    } catch (\Exception $e) {
      \Drupal::logger('spotify_lookup')->error('Failed to save node: @message', ['@message' => $e->getMessage()]);
      \Drupal::messenger()->addMessage($this->t('Failed to save @type details.', ['@type' => ucfirst($type)]), 'error');
      $form_state->setRedirect('spotify_lookup.search_form');
    }
  }
  
  

  /**
   * Downloads an image from a URL and saves it as a Drupal file entity.
   *
   * @param string $url
   *   The URL of the image.
   *
   * @return \Drupal\file\Entity\File|null
   *   The saved file entity or NULL on failure.
   */
  protected function downloadImage($url) {
    try {
      
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);
        $headers = get_headers($url, 1);
        $content_type = isset($headers['Content-Type']) ? $headers['Content-Type'] : null;
        $file_data = file_get_contents($url, false, $context);

        if ($file_data) {
            $extension = '';
            if ($content_type === 'image/jpeg') {
                $extension = '.jpg';
            } elseif ($content_type === 'image/png') {
                $extension = '.png';
            } elseif ($content_type === 'image/webp') {
                $extension = '.webp';
            }

            $directory = 'public://2024-12/';
            \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

            $file_name = $directory . basename(parse_url($url, PHP_URL_PATH)) . $extension;
            \Drupal::service('file_system')->saveData($file_data, $file_name, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);

            $file = File::create([
                'uri' => $file_name,
                'status' => 1,
            ]);
            $file->save();

            return $file;
        }
    } catch (\Exception $e) {
        \Drupal::logger('spotify_lookup')->error('Failed to download image: @message', ['@message' => $e->getMessage()]);
    }
    return NULL;
}

  protected function saveTrack(array $track) {
    $spotify_id = $track['id'] ?? '';
    $spotify_link = $spotify_id ? 'https://open.spotify.com/track/' . $spotify_id : '';

    $node = Node::create([
      'type' => 'lag',
      'title' => $track['name'] ?? 'No Title',
      'field_song_name' => $track['name'] ?? '',
      'field_song_length' => $this->formatSongLength($track['duration_ms'] ?? 0),
      'field_song_id' => $spotify_link,
    ]);
    $node->save();
  }



  /**
   * Converts milliseconds to a readable time format (minutes and seconds).
   *
   * @param int $milliseconds
   *   The song length in milliseconds.
   *
   * @return string
   *   The formatted time string (e.g., "4:23").
   */
  protected function formatSongLength($milliseconds) {
    $seconds = floor($milliseconds / 1000);
    $minutes = floor($seconds / 60);
    $remaining_seconds = $seconds % 60;
    return sprintf('%d:%02d', $minutes, $remaining_seconds);
  }
}