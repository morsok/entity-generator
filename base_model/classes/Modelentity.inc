<?php

/**
 * The class used for modelentity entities
 *
 * Class Modelentity
 */
class Modelentity extends Entity {
  /**
   * Constructor.
   *
   * @param array $values
   *   Values.
   *
   * @throws \Exception
   */
  public function __construct($values = array()) {
    parent::__construct($values, 'modelentity');
  }

  /**
   * Return modelentity label.
   *
   * @return string
   *   Label.
   */
  protected function defaultLabel() {
    return $this->name;
  }

  /**
   * Return modelentity URI.
   *
   * @return array
   *   Uri.
   */
  protected function defaultUri() {
    return array('path' => 'modelentity/' . $this->modelentity_id);
  }
}