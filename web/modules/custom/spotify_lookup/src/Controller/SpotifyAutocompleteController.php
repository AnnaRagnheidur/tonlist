<?php

namespace Drupal\spotify_lookup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\spotify_lookup\SpotifyLookupService;

class SpotifyAutocompleteController extends ControllerBase {

  protected $spotifyService;

  public function __construct(SpotifyLookupService $spotifyService) {
    $this->spotifyService = $spotifyService;
  }

  public static function create($container) {
    return new static($container->get('spotify_lookup.spotify_lookup_service'));
  }

  public function autocomplete(Request $request) {
    $search_string = $request->query->get('q');
    $type = $request->query->get('type') ?? 'album'; 
  
    if (!$search_string) {
      return new JsonResponse([]);
    }
  
    $results = $this->spotifyService->search($search_string, $type);
    $suggestions = [];
  
    if (!empty($results[$type . 's']['items'])) {
      foreach ($results[$type . 's']['items'] as $item) {
        $label = match ($type) {
          'album' => $item['name'] . ' by ' . ($item['artists'][0]['name'] ?? 'Unknown Artist'),
          'artist' => $item['name'],
          'track' => $item['name'] . ' by ' . ($item['artists'][0]['name'] ?? 'Unknown Artist'),
          default => 'Unknown',
        };
  
        $suggestions[] = [
          'value' => $item['name'] ?? 'Unknown',
          'label' => $label,
          'thumbnail' => $item['images'][0]['url'] ?? '',
          'exact_match' => strtolower($item['name']) === strtolower($search_string),
        ];
      }
  
      usort($suggestions, function ($a, $b) {
        if ($a['exact_match'] === $b['exact_match']) {
          return strcasecmp($a['label'], $b['label']);
        }
        return $b['exact_match'] - $a['exact_match'];
      });
    }
  
    return new JsonResponse($suggestions);
  }
  
}