<?php

/**
 * @file
 * Providing extra functionality for the Modelentity UI via views.
 */


/**
 * Implements hook_views_data()
 */
function model_views_data_alter(&$data) {

  $data['modelentity']['table']['join'] = array(
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );

  $data['modelentity']['uid'] = array(
    'title' => t('User ID'),
    'help' => t('The author ID'),
    'relationship' => array(
      'base' => 'users', // The name of the table to join with.
      'base field' => 'uid', // The name of the field on the joined table.
      'handler' => 'views_handler_relationship',
      'label' => t('Author ID of the modelentity (relationship)'),
      'title' => t('Author ID'),
      'help' => t('Author of the modelentity (relationship)'),
    ),
    'field' => array('handler' => 'views_handler_field_numeric', 'click sortable' => TRUE),
    'filter' => array('handler' => 'views_handler_filter_numeric'),
    'sort' => array('handler' => 'views_handler_sort'),
    'argument' => array( 'handler' => 'views_handler_argument_numeric', ),
  );

  $data['modelentity']['link_modelentity'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Provide a link to the modelentity.'),
      'handler' => 'modelentity_handler_link_field',
    ),
  );
  $data['modelentity']['edit_modelentity'] = array(
    'field' => array(
      'title' => t('Edit Link'),
      'help' => t('Provide a link to the edit form for the modelentity.'),
      'handler' => 'modelentity_handler_edit_link_field',
    ),
  );
  $data['modelentity']['delete_modelentity'] = array(
    'field' => array(
      'title' => t('Delete Link'),
      'help' => t('Provide a link to delete the modelentity.'),
      'handler' => 'modelentity_handler_delete_link_field',
    ),
  );
  // This content of this field are decided based on the menu structure that
  // follows modelentities/modelentity/%modelentity_id/op
  $data['modelentity']['operations'] = array(
    'field' => array(
      'title' => t('Operations links'),
      'help' => t('Display all operations available for this modelentity.'),
      'handler' => 'modelentity_handler_modelentity_operations_field',
    ),
  );

  $data['modelentity']['created'] = array(
    'title' => t('Created'),
    'help' => t('Creation date'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  $data['modelentity']['changed'] = array(
    'title' => t('Changed'),
    'help' => t('Last modification date'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );
}


/**
 * Implements hook_views_default_views().
 */
function model_views_default_views() {
  static $views;

  if (isset($views)) {
    return $views;
  }

  $files = file_scan_directory(drupal_get_path('module', 'model').'/views', '/\.view$/');

  foreach($files as $filepath => $file) {
    include $filepath;
    if (isset($view)) {
      $views[$view->name] = $view;
    }
  }

  return $views;
}
