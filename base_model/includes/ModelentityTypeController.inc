<?php

/**
 * The Controller for Modelentity entities.
 *
 * Class ModelentityTypeController
 */
class ModelentityTypeController extends EntityAPIControllerExportable {
  /**
   * Constructor.
   *
   * @param string $entity_type
   *   Entity type.
   */
  public function __construct($entity_type) {
    parent::__construct($entity_type);
  }

  /**
   * Create a modelentity type - we first set up the values that are specific
   * to our modelentity type schema but then also go through
   * the EntityAPIController function.
   *
   * @param array $values
   *   The values of the modelentity.
   *
   * @return bool
   *   A modelentity type object with all default fields initialized.
   */
  public function create(array $values = array()) {
    // Add values that are specific to our Modelentity.
    $values += array(
      'id'     => '',
      'is_new' => TRUE,
    );
    $modelentity_type = parent::create($values);

    return $modelentity_type;
  }
}
