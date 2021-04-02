<?php

namespace Drupal\custom_search_fields\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides route responses for the Dependent Page.
 */

class CustomSearchFieldsController extends ControllerBase {

  public function customSearchFieldsPage () {

		return [ 
      '#theme' => 'custom_search_fields_page',
      '#cache' => [ 
          'max-age' => 0
      ],
      '#attached' => [ 
        'library' => ['custom_search_fields/custom_search_fields'],
      ],
      '#search-results' => [],
    ];
  }

  public function customSearchFields () {

    $field_map = \Drupal::entityManager()->getFieldMap();
    $node_field_map = $field_map['node'];

    \Drupal::service ( 'page_cache_kill_switch' )->trigger ();

		$drupal_request = \Drupal::request ();
		$search = $drupal_request->request->get ( 'search' );


    foreach($node_field_map as $name => $details) {
      foreach ($details["bundles"] as $id => $title ) {
         $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $title);
         foreach (array_keys($definitions) as $key) {
          if (str_contains($definitions[$key]->getLabel(), $search)) { 
            $searchResults[$key] = $definitions[$key]->getLabel();
          }
        }
      }
    }

    if(isset($searchResults) && $searchResults != null && $searchResults != '') {
      $custom_search_fields =  \Drupal::getContainer()->get('config.factory')->getEditable('custom_search_fields.results');
      $custom_search_fields->set('custom_search_fields',  $searchResults);
      $custom_search_fields->save();
      
      drupal_flush_all_caches();
  
      return new JsonResponse ( [ 
          'result' => 'OK'
      ]);
    } else {
      $custom_search_fields =  \Drupal::getContainer()->get('config.factory')->getEditable('custom_search_fields.results');
      $custom_search_fields->set('custom_search_fields',  []);
      $custom_search_fields->save();

      drupal_flush_all_caches();

      return new JsonResponse ( [ 
        'result' => '404'
      ]);
    }


  }

}