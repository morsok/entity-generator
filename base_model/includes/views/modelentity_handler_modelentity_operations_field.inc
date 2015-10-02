<?php

/**
 * @file
 * This field handler aggregates operations that can be done on a modelentity
 * under a single field providing a more flexible way to present them in a view
 */

/**
 * Class modelentity_handler_modelentity_operations_field.
 */
class modelentity_handler_modelentity_operations_field extends views_handler_field {
  /**
   * Constructor.
   */
  public function construct() {
    parent::construct();

    $this->additional_fields['modelentity_id'] = 'modelentity_id';
  }

  /**
   * Update query.
   */
  public function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  /**
   * Build link.
   *
   * @param array $values
   *   Values.
   *
   * @return string
   *   Link.
   *
   * @throws \Exception
   */
  public function render($values) {
    $links = menu_contextual_links(
      'modelentity',
      'admin/content/modelentities/modelentity',
      array($this->get_value($values, 'modelentity_id'))
    );

    if (!empty($links)) {
      return theme(
        'links',
        array(
          'links' => $links,
          'attributes' => array(
            'class' => array('links', 'inline', 'operations'),
          ),
        )
      );
    }
  }
}
