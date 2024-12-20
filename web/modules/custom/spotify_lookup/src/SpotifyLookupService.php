<?php

namespace Drupal\spotify_lookup;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service to interact with the Spotify API.
 */
class SpotifyLookupService {

  /**
   * The Spotify client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * The Spotify client secret.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * HTTP client for making requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Logger channel for debugging.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the SpotifyLookupService.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, LoggerInterface $logger) {
    $config = $config_factory->get('spotify_lookup.settings');
    $this->clientId = $config->get('client_id');
    $this->clientSecret = $config->get('client_secret');
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * Performs a search on the Spotify API.
   *
   * @param string $query
   *   The search query.
   * @param string $type
   *   The type of search: album, artist, or song.
   *
   * @return array
   *   An array of search results.
   */
  public function search($query, $type = 'album') {
    $accessToken = $this->getAccessToken();
    if (!$accessToken) {
      $this->logger->error('Unable to retrieve access token.');
      return [];
    }

    $url = "https://api.spotify.com/v1/search?q=" . urlencode($query) . "&type=" . $type;

    try {
      $response = $this->httpClient->get($url, [
        'headers' => ['Authorization' => 'Bearer ' . $accessToken],
      ]);
      $data = json_decode($response->getBody(), TRUE);

      $this->logger->info('Spotify API Search Response: @response', ['@response' => print_r($data, TRUE)]);
      return $data;
    } catch (\Exception $e) {
      $this->logger->error('Error calling Spotify API: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Retrieves full album details, including songs, from Spotify.
   *
   * @param string $album_id
   *   The Spotify album ID.
   *
   * @return array
   *   An array containing album details and songs.
   */
  public function getAlbumDetails($album_id) {
    $accessToken = $this->getAccessToken();
    if (!$accessToken) {
      $this->logger->error('Unable to retrieve access token for album details.');
      return [];
    }

    $url = "https://api.spotify.com/v1/albums/{$album_id}";

    try {
      $response = $this->httpClient->get($url, [
        'headers' => ['Authorization' => 'Bearer ' . $accessToken],
      ]);
      $data = json_decode($response->getBody(), TRUE);

      $this->logger->info('Spotify API Album Details Response: @response', ['@response' => print_r($data, TRUE)]);
      return $data;
    } catch (\Exception $e) {
      $this->logger->error('Error fetching album details: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Retrieves full artist details from Spotify.
   *
   * @param string $artist_id
   *   The Spotify artist ID.
   *
   * @return array
   *   An array containing artist details.
   */
  public function getArtistDetails($artist_id) {
    $accessToken = $this->getAccessToken();
    if (!$accessToken) {
      $this->logger->error('Unable to retrieve access token for artist details.');
      return [];
    }

    $url = "https://api.spotify.com/v1/artists/{$artist_id}";

    try {
      $response = $this->httpClient->get($url, [
        'headers' => ['Authorization' => 'Bearer ' . $accessToken],
      ]);
      $data = json_decode($response->getBody(), TRUE);

      $this->logger->info('Spotify API Artist Details Response: @response', ['@response' => print_r($data, TRUE)]);
      return $data;
    } catch (\Exception $e) {
      $this->logger->error('Error fetching artist details: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }


  /**
   * Fetches the Spotify access token.
   *
   * @return string|null
   *   The access token or NULL on failure.
   */
  protected function getAccessToken() {
    try {
      $response = $this->httpClient->post('https://accounts.spotify.com/api/token', [
        'form_params' => [
          'grant_type' => 'client_credentials',
        ],
        'headers' => [
          'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
        ],
      ]);
      $data = json_decode($response->getBody(), TRUE);
      return $data['access_token'] ?? NULL;
    } catch (\Exception $e) {
      $this->logger->error('Error retrieving Spotify access token: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }
}