<?php

/**
 * @file
 * Contains a Views field handler to take care of displaying deletes links
 * as fields.
 */

/**
 * Class modelentity_handler_delete_link_field.
 */
class modelentity_handler_delete_link_field extends modelentity_handler_link_field {
  /**
   * Constructor.
   */
  public function construct() {
    parent::construct();
    $this->additional_fields['type'] = 'type';
  }

  /**
   * Render link.
   *
   * @param array $values
   *   Values.
   *
   * @return string
   *   Link.
   */
  public function render($values) {
    $type = $values->{$this->aliases['type']};

    // Creating a dummy modelentity to check access against.
    $dummy_modelentity = (object) array('type' => $type);
    if (!modelentity_access('edit', $dummy_modelentity)) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');
    $modelentity_id = $values->{$this->aliases['modelentity_id']};

    return l($text, 'admin/content/modelentities/modelentity/' . $modelentity_id . '/delete');
  }
}
