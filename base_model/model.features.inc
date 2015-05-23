<?php

/**
 * @file
 * Features for the Model Entity.
 */

/**
 * Implements hook_views_api().
 */
function model_views_api($module = NULL, $api = NULL) {
  return array("api" => "3.0");
}

/**
 * Implements hook_default_animation_type().
 */
function model_default_animation_type() {
  return array();
}

