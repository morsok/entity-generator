<?php

/**
 * @file
 * Contains a Views field handler to take care of displaying edit links
 * as fields
 */


class modelentity_handler_edit_link_field extends modelentity_handler_link_field {
  function construct() {
    parent::construct();
    $this->additional_fields['type'] = 'type';
  }


  function render($values) {
    $type = $values->{$this->aliases['type']};
    
    //Creating a dummy modelentity to check access against
    $dummy_modelentity = (object) array('type' => $type);
    if (!modelentity_access('edit', $dummy_modelentity)) {
      return;
    }
    
    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
    $modelentity_id = $values->{$this->aliases['modelentity_id']};
    
    return l($text, 'admin/content/modelentities/modelentity/' . $modelentity_id . '/edit');
  }
}
