<?php

namespace Drupal\spotify_lookup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a form to accept and map Spotify search results.
 */
class SpotifyAcceptForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spotify_accept_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $results = $_SESSION['spotify_results'] ?? [];

    if (empty($results)) {
      $form['no_results'] = [
        '#markup' => $this->t('No results to display.'),
      ];
      return $form;
    }

    $form['items'] = [
      '#type' => 'table',
      '#header' => [$this->t('Select'), $this->t('Name'), $this->t('Cover Image'), $this->t('Type')],
    ];

    foreach ($results as $id => $item) {
      $form['items'][$id]['select'] = [
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      ];

      $form['items'][$id]['name'] = [
        '#markup' => $item['name'],
      ];

      $form['items'][$id]['cover_image'] = [
        '#markup' => '<img src="' . $item['images'][0]['url'] . '" width="50">',
      ];

      $form['items'][$id]['type'] = [
        '#markup' => $item['type'] ?? 'Unknown',
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Selected'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('items');
    $results = $_SESSION['spotify_results'] ?? [];

    foreach ($values as $id => $item) {
      if ($item['select']) {
        $type = $results[$id]['type'];
        $content_type = $type === 'album' ? 'plata' : ($type === 'artist' ? 'listamadur' : 'lag');
        
        $node = Node::create([
          'type' => $content_type,
          'title' => $data['name'],
        ]);
        $node->save();
        
        \Drupal::messenger()->addMessage($this->t('Imported: @name', ['@name' => $data['name']]));
      }
    }

    unset($_SESSION['spotify_results']);
  }

}