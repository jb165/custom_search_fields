<?php

namespace Drupal\custom_search_fields\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Configure custom settings for Dependent Dropdown Class.
 */
class DependentDropdownForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dependent_dropdown_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dependent_dropdown.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL, string $contentType = NULL) {

    $dependent_dropdown_config = $this->config('dependent_dropdown.settings')->get('dependent_dropdown_settings');

    //dpm($dependent_dropdown_config->get('dependent_dropdown_settings'));

          // Get the definitions

          // Iterate through the definitions
          $entity_type_id = 'node';
          $bundle = $contentType;
          $form_mode = 'default';
          $counter = 0;
          $order_list = [];
          //$order_list['_none'] = '- None -';
      
          foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
            if (!empty($field_definition->getTargetBundle())) {
              
              $form_display = \Drupal::entityTypeManager()
                ->getStorage('entity_form_display')
                ->load($entity_type_id . '.' . $bundle . '.' . $form_mode);
              
              $specific_widget_type = $form_display->getComponent($field_name);
              if($specific_widget_type['type'] == 'options_select') {
                $bundleFields[$entity_type_id][$field_name]['type'] = $field_definition->getType();
                $bundleFields[$entity_type_id][$field_name]['label'] = $field_definition->getLabel();
                $order_list[$field_name] = $bundleFields[$entity_type_id][$field_name]['label'];
                $counter++;
              }
            }
          }

          $types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
          $contentTypeName = '';

          foreach($types as $type) {
            if ($type->get('type') == $bundle) {
              $contentTypeName = $type->get('name');
            }
          }

          $form['#prefix'] = '<h1>Content type: ' . $contentTypeName . '</h1>';

          $form['dependent_dropdown_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('JSON Url'),
            '#default_value' => $dependent_dropdown_config[$bundle]['dependent_dropdown_url'] ?: '',
            '#required' => TRUE,
            '#description' => $this->t('Please provide an absolute url.'),
          ];

          $form['dependent_dropdown_content_type'] = array(
            '#type' => 'hidden',
            '#value' => $bundle,
          );
      
          $form['dependent_dropdown_dependent'] = [
            '#type' => 'select',
            '#title' => $this
              ->t('Dependent'),
            '#options' => $order_list,
            '#default_value' => $dependent_dropdown_config[$bundle]['dependent_dropdown_dependent'] ?: '',
            '#ajax' => array(
              'callback' => [$this, 'dependent_dropdown_dependent_ajax'],
              'event' => 'change',
              'wrapper'  => 'depends_on_wrapper',
            ),
            '#prefix' => '<div id="dependent_wrapper">',
            '#suffix' => '</div>',
            '#required' => TRUE,
          ];

          $form['dependent_dropdown_depends_on'] = [
            '#type' => 'select',
            '#title' => $this
              ->t('Depends On'),
            '#options' => $order_list,
            '#default_value' => $dependent_dropdown_config[$bundle]['dependent_dropdown_depends_on'] ?: '',
            '#prefix' => '<div id="depends_on_wrapper">',
            '#suffix' => '</div>',
            '#ajax' => array(
              'callback' => [$this, 'dependent_dropdown_depends_on_ajax'],
              'event' => 'change',
              'wrapper'  => 'dependent_wrapper',
            ),
            '#required' => TRUE,
          ];
      
          if ($counter == 0) {
            return [
              '#markup' => '<h1>Content type: ' . $contentTypeName . '</h1><br>' . 'No select fields available, please add atleast two select fields to this content type.',
            ];
          } elseif ($counter == 1) {
            return [
              '#markup' => '<h1>Content type: ' . $contentTypeName . '</h1><br>' . 'Only one select field available, there should be at least two select fields to this content type.',
            ];
          } else {
            return parent::buildForm($form, $form_state);
          }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $dependent_dropdown_config = $this->config('dependent_dropdown.settings')->get('dependent_dropdown_settings');

    $dependent_dropdown_config[$values['dependent_dropdown_content_type']] = ['dependent_dropdown_url' => $values['dependent_dropdown_url'],'dependent_dropdown_dependent' => $values['dependent_dropdown_dependent'], 'dependent_dropdown_depends_on' => $values['dependent_dropdown_depends_on']];

    $this->config('dependent_dropdown.settings')
      ->set('dependent_dropdown_settings', $dependent_dropdown_config)
      ->save();

    //drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function dependent_dropdown_dependent_ajax(array $form, FormStateInterface $form_state) {

              $values = $form_state->getValues();

              // Iterate through the definitions
              $entity_type_id = 'node';
              $bundle = $values['dependent_dropdown_content_type'];
              $form_mode = 'default';
              $counter = 0;
              $order_list = [];
              $order_list[''] = '- Select -';

              foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
                if (!empty($field_definition->getTargetBundle())) {
                  
                  $form_display = \Drupal::entityTypeManager()
                    ->getStorage('entity_form_display')
                    ->load($entity_type_id . '.' . $bundle . '.' . $form_mode);
                  
                  $specific_widget_type = $form_display->getComponent($field_name);
                  if($specific_widget_type['type'] == 'options_select') {
                    $bundleFields[$entity_type_id][$field_name]['type'] = $field_definition->getType();
                    $bundleFields[$entity_type_id][$field_name]['label'] = $field_definition->getLabel();
                    $order_list[$field_name] = $bundleFields[$entity_type_id][$field_name]['label'];
                    $counter++;
                  }
                }
              }

              $remove_field = $values['dependent_dropdown_dependent'];

              unset($order_list[$remove_field]);
              
              $form['dependent_dropdown_depends_on']['#options'] = $order_list;
            
              $form_state->setRebuild(TRUE);
            
              $ajaxResponse = new ajaxResponse();
              
              $ajaxResponse->addCommand(new ReplaceCommand("#depends_on_wrapper", $form['dependent_dropdown_depends_on']));
              return $ajaxResponse;
  }

  /**
   * {@inheritdoc}
   */
  public function dependent_dropdown_depends_on_ajax(array $form, FormStateInterface $form_state) {
              
                  $values = $form_state->getValues();

                  // Iterate through the definitions
                  $entity_type_id = 'node';
                  $bundle = $values['dependent_dropdown_content_type'];
                  $form_mode = 'default';
                  $counter = 0;
                  $order_list = [];
                  $order_list[''] = '- Select -';
    
    
                  foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
                    if (!empty($field_definition->getTargetBundle())) {
                      
                      $form_display = \Drupal::entityTypeManager()
                        ->getStorage('entity_form_display')
                        ->load($entity_type_id . '.' . $bundle . '.' . $form_mode);
                      
                      $specific_widget_type = $form_display->getComponent($field_name);
                      if($specific_widget_type['type'] == 'options_select') {
                        $bundleFields[$entity_type_id][$field_name]['type'] = $field_definition->getType();
                        $bundleFields[$entity_type_id][$field_name]['label'] = $field_definition->getLabel();
                        $order_list[$field_name] = $bundleFields[$entity_type_id][$field_name]['label'];
                        $counter++;
                      }
                    }
                  }
    
                  $remove_field = $values['dependent_dropdown_depends_on'];
    
                  unset($order_list[$remove_field]);
                  
                  $form['dependent_dropdown_dependent']['#options'] = $order_list;
                
                  $form_state->setRebuild(TRUE);
                
                  $ajaxResponse = new ajaxResponse();
                  
                  $ajaxResponse->addCommand(new ReplaceCommand("#dependent_wrapper", $form['dependent_dropdown_dependent']));
                  return $ajaxResponse;
  }

}
