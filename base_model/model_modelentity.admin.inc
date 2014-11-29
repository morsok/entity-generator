<?php

/**
 * @file
 * Modelentity editing UI.
 *
 * We make very little use of the EntityAPI interface for this - preferring
 * instead to use views. That offers more flexibility to change a UI that
 * will, more often than not, be end-user facing.
 */

/**
 * UI controller.
 */
class ModelentityUIController extends EntityDefaultUIController {

  /**
   * Overrides hook_menu() defaults. Main reason for doing this is that
   * parent class hook_menu() is optimized for entity type administration.
   */
  public function hook_menu() {

    $items = array();
    $id_count = count(explode('/', $this->path));
    $wildcard = isset($this->entityInfo['admin ui']['menu wildcard']) ? $this->entityInfo['admin ui']['menu wildcard'] : '%' . $this->entityType;

    $items[$this->path] = array(
      'title'            => 'ELabels',
      'description'      => 'Add edit and update ELabels.',
      'page callback'    => 'system_admin_menu_block_page',
      'access arguments' => array('access administration pages'),
      'file path'        => drupal_get_path('module', 'system'),
      'file'             => 'system.admin.inc',
    );

    // Change the overview menu type for the list of modelentities.
    $items[$this->path]['type'] = MENU_LOCAL_TASK;

    // Change the add page menu to multiple types of entities.
    $items[$this->path . '/add'] = array(
      'title'            => 'Add a ELabel',
      'description'      => 'Add a new ELabel',
      'page callback'    => 'modelentity_add_page',
      'access callback'  => 'modelentity_access',
      'access arguments' => array('edit'),
      'type'             => MENU_NORMAL_ITEM,
      'weight'           => 20,
      'file'             => 'model_modelentity.admin.inc',
      'file path'        => drupal_get_path('module', $this->entityInfo['module']),
    );

    // Add menu items to add each different type of entity.
    foreach (modelentity_get_types() as $type) {
      $items[$this->path . '/add/' . $type->type] = array(
        'title'            => 'Add ' . $type->label,
        'page callback'    => 'modelentity_form_wrapper',
        'page arguments'   => array(modelentity_create(array('type' => $type->type))),
        'access callback'  => 'modelentity_access',
        'access arguments' => array('edit', 'edit ' . $type->type),
        'file'             => 'model_modelentity.admin.inc',
        'file path'        => drupal_get_path('module', $this->entityInfo['module']),
      );
    }

    // Loading and editing modelentity entities.
    $items[$this->path . '/modelentity/' . $wildcard] = array(
      'page callback'    => 'modelentity_form_wrapper',
      'page arguments'   => array($id_count + 1),
      'access callback'  => 'modelentity_access',
      'access arguments' => array('edit', $id_count + 1),
      'weight'           => 0,
      'context'          => MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE,
      'file'             => 'model_modelentity.admin.inc',
      'file path'        => drupal_get_path('module', $this->entityInfo['module']),
    );
    $items[$this->path . '/modelentity/' . $wildcard . '/edit'] = array(
      'title'   => 'Edit',
      'type'    => MENU_DEFAULT_LOCAL_TASK,
      'weight'  => -10,
      'context' => MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE,
    );

    $items[$this->path . '/modelentity/' . $wildcard . '/delete'] = array(
      'title'            => 'Delete',
      'page callback'    => 'modelentity_delete_form_wrapper',
      'page arguments'   => array($id_count + 1),
      'access callback'  => 'modelentity_access',
      'access arguments' => array('edit', $id_count + 1),
      'type'             => MENU_LOCAL_TASK,
      'context'          => MENU_CONTEXT_INLINE,
      'weight'           => 10,
      'file'             => 'model_modelentity.admin.inc',
      'file path'        => drupal_get_path('module', $this->entityInfo['module']),
    );

    // Menu item for viewing modelentities.
    $items['modelentity/' . $wildcard] = array(
      // 'title' => 'Title',
      'title callback'   => 'modelentity_page_title',
      'title arguments'  => array(1),
      'page callback'    => 'modelentity_page_view',
      'page arguments'   => array(1),
      'access callback'  => 'modelentity_access',
      'access arguments' => array('view', 1),
      'type'             => MENU_CALLBACK,
    );

    return $items;
  }

  /**
   * Create the markup for the add Modelentity Entities page within the class
   * so it can easily be extended/overriden.
   */
  public function addPage() {
    $item = menu_get_item();
    $content = system_admin_menu_block($item);

    if (count($content) == 1) {
      $item = array_shift($content);
      drupal_goto($item['href']);
    }

    return theme('modelentity_add_list', array('content' => $content));
  }
}

/**
 * Form callback wrapper: create or edit a modelentity.
 *
 * @param object $modelentity
 *   The modelentity object being edited by this form.
 *
 * @return array
 *   Form.
 *
 * @see modelentity_edit_form()
 */
function modelentity_form_wrapper($modelentity) {
  // Add the breadcrumb for the form's location.
  modelentity_set_breadcrumb();

  return drupal_get_form('modelentity_edit_form', $modelentity);
}

/**
 * Form callback wrapper: delete a modelentity.
 *
 * @param object $modelentity
 *   The modelentity object being edited by this form.
 *
 * @return array
 *   Form.
 *
 * @see modelentity_edit_form()
 */
function modelentity_delete_form_wrapper($modelentity) {
  // Add the breadcrumb for the form's location.
  // modelentity_set_breadcrumb();
  return drupal_get_form('modelentity_delete_form', $modelentity);
}

/**
 * Form callback: create or edit a modelentity.
 *
 * @param object $modelentity
 *   The modelentity object to edit or for a create form an empty
 *     modelentity object with only a modelentity type defined.
 */
function modelentity_edit_form($form, &$form_state, $modelentity) {
  // Add the default field elements.
  $form['name'] = array(
    '#type'          => 'textfield',
    '#title'         => t('ELabel Name'),
    '#default_value' => isset($modelentity->name) ? $modelentity->name : '',
    '#maxlength'     => 255,
    '#required'      => TRUE,
    '#weight'        => -5,
  );

  // Add the field related form elements.
  $form_state['modelentity'] = $modelentity;
  field_attach_form('modelentity', $modelentity, $form, $form_state);

  $form['actions'] = array(
    '#type'       => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight'     => 400,
  );

  // We add the form's #submit array to this button along with the actual submit
  // handler to preserve any submit handlers added by a form callback_wrapper.
  $submit = array();

  if (!empty($form['#submit'])) {
    $submit += $form['#submit'];
  }

  $form['actions']['submit'] = array(
    '#type'   => 'submit',
    '#value'  => t('Save'),
    '#submit' => $submit + array('modelentity_edit_form_submit'),
  );

  if (!empty($modelentity->name)) {
    $form['actions']['delete'] = array(
      '#type'   => 'submit',
      '#value'  => t('Delete'),
      '#suffix' => l(t('Cancel'), 'admin/content/modelentities'),
      '#submit' => $submit + array('modelentity_form_submit_delete'),
      '#weight' => 45,
    );
  }

  // We append the validate handler to #validate in case a form callback_wrapper
  // is used to add validate handlers earlier.
  $form['#validate'][] = 'modelentity_edit_form_validate';

  return $form;
}

/**
 * Implements hook_form_validate().
 */
function modelentity_edit_form_validate(&$form, &$form_state) {
  $modelentity = $form_state['modelentity'];

  // Notify field widgets to validate their data.
  field_attach_form_validate('modelentity', $modelentity, $form, $form_state);
}

/**
 * Implements hook_form_submit().
 *
 * @todo remove hard-coded link
 */
function modelentity_edit_form_submit(&$form, &$form_state) {

  $modelentity = entity_ui_controller('modelentity')->entityFormSubmitBuildEntity($form, $form_state);
  // Save the modelentity and go back to the list of modelentities.

  // Add in created and changed times.
  if ($modelentity->is_new = isset($modelentity->is_new) ? $modelentity->is_new : 0) {
    $modelentity->created = REQUEST_TIME;
  }

  $modelentity->changed = REQUEST_TIME;

  $modelentity->save();
  $form_state['redirect'] = 'admin/content/modelentities';
}

/**
 * Form API submit callback for the delete button.
 * @todo Remove hard-coded path
 *
 * @param array $form
 *   Form.
 * @param array $form_state
 *   Form state.
 */
function modelentity_form_submit_delete(&$form, &$form_state) {
  $form_state['redirect'] = 'admin/content/modelentities/modelentity/' . $form_state['modelentity']->modelentity_id . '/delete';
}

/**
 * Form callback: confirmation form for deleting a modelentity.
 *
 * @param object $modelentity
 *   The modelentity to delete
 *
 * @return array
 *   Form.
 *
 * @see confirm_form()
 */
function modelentity_delete_form($form, &$form_state, $modelentity) {
  $form_state['modelentity'] = $modelentity;

  $form['#submit'][] = 'modelentity_delete_form_submit';

  $form = confirm_form(
    $form,
    t('Are you sure you want to delete ELabel %name?', array('%name' => $modelentity->name)),
    'admin/content/modelentities/modelentity',
    '<p>' . t('This action cannot be undone.') . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

/**
 * Implements hook_form_submit().
 */
function modelentity_delete_form_submit($form, &$form_state) {
  $modelentity = $form_state['modelentity'];

  modelentity_delete($modelentity);

  drupal_set_message(t('The ELabel %name has been deleted.', array('%name' => $modelentity->name)));
  watchdog('model', 'Deleted ELabel %name.', array('%name' => $modelentity->name));

  $form_state['redirect'] = 'admin/content/modelentities';
}


/**
 * Page to add Modelentity Entities.
 * @todo Pass this through a proper theme function
 *
 * @return mixed
 *   Page.
 */
function modelentity_add_page() {
  $controller = entity_ui_controller('modelentity');

  return $controller->addPage();
}


/**
 * Displays the list of available modelentity types for modelentity creation.
 *
 * @ingroup themeable
 */
function theme_modelentity_add_list($variables) {
  $content = $variables['content'];

  if ($content) {
    $output = '<dl class="modelentity-type-list">';

    foreach ($content as $item) {
      $output .= '<dt>' . l($item['title'], $item['href']) . '</dt>';
      $output .= '<dd>' . filter_xss_admin($item['description']) . '</dd>';
    }
    $output .= '</dl>';
  }
  else {
    if (user_access('administer modelentity types')) {
      $output = '<p>' . t(
          'ELabel Entities cannot be added because you have not created any ELabel types yet. Go to the <a href="@create-modelentity-type">ELabel type creation page</a> to add a new ELabel type.',
          array('@create-modelentity-type' => url('admin/structure/modelentity_types/add'))
        ) . '</p>';
    }
    else {
      $output = '<p>' . t('No ELabel types have been created yet for you to use.') . '</p>';
    }
  }

  return $output;
}

/**
 * Sets the breadcrumb for administrative modelentity pages.
 */
function modelentity_set_breadcrumb() {
  $breadcrumb = array(
    l(t('Home'), '<front>'),
    l(t('Administration'), 'admin'),
    l(t('Content'), 'admin/content'),
    l(t('Modelentities'), 'admin/content/modelentities'),
  );

  drupal_set_breadcrumb($breadcrumb);
}
