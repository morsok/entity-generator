<?php

/**
 * The class used for modelentity type entities
 *
 * Class ModelentityType
 */
class ModelentityType extends Entity {
  /**
   * @var string
   */
  public $type;

  /**
   * @var string
   */
  public $label;

  /**
   * Constructor.
   *
   * @param array $values
   *   Values.
   *
   * @throws \Exception
   */
  public function __construct($values = array()) {
    parent::__construct($values, 'modelentity_type');
  }
}