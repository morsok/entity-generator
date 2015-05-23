<?php

require 'generator/functions.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['machine_name']) ||
      empty($_POST['module_name']) ||
      empty($_POST['module_desc']) ||
      empty($_POST['entity_name']) ||
      empty($_POST['entity_label']) ||
      empty($_POST['entity_label_plural']) ||
      !preg_match('/^[A-Z0-9_]+$/i', $_POST['machine_name'])
    ) {
        echo 'An error happened, one field is empty or contains invalid value';
        exit;
    }

    define('BASE_SRC', realpath('./base_model'));
    $dir = $_POST['machine_name'];

    // Change to temp directory.
    chdir(sys_get_temp_dir());

    if (is_dir($dir)) {
        foreach (new RecursiveIteratorIterator(
                   new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                   RecursiveIteratorIterator::CHILD_FIRST
                 ) as $path) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }

        rmdir($dir);

        if (file_exists($dir . '.zip')) {
            unlink($dir . '.zip');
        }
    }

    mkdir($dir);
    mkdir($dir . '/views');
    mkdir($dir . '/templates');

    // model.info
    $data = file_get_contents(BASE_SRC . '/model.info');
    $data = str_replace('%NAME%', $_POST['module_name'], $data);
    $data = str_replace('%DESC%', $_POST['module_desc'], $data);
    $data = str_replace('%MD_NAME%', $_POST['machine_name'], $data);
    $data = str_replace('%ENTITY_NAME%', $_POST['entity_name'], $data);
    file_put_contents($dir . '/' . $_POST['machine_name'] . '.info', $data);

    // Create smart class names.
    $classname_entity = str_replace(' ', '', ucwords(str_replace('_', ' ', $_POST['entity_name'])));
    $classname_model = str_replace(' ', '', ucwords(str_replace('_', ' ', $_POST['machine_name'])));

    // Create replace mapping.
    $replace_tokens = array(
      'modelentity'   => $_POST['entity_name'],
      'modelentities' => $_POST['entity_name'] . 's',
      'model'         => $_POST['machine_name'],
      'Modelentity'   => $classname_entity,
      'Modelentities' => $classname_entity . 's',
      'Model'         => $classname_model,
      'ELabels'       => ucfirst($_POST['entity_label_plural']),
      'ELabel'        => ucfirst($_POST['entity_label']),
    );

    // model.module
    $destination = parseTemplate('model.module', $dir, $replace_tokens);
    $data = file_get_contents($destination);

    // Hook supported.
    $base_hook = array(
      'hook_entity_presave' => 'entity_presave',
      'hook_entity_update'  => 'entity_update',
      'hook_entity_view'    => 'entity_view',
      'hook_node_load'      => 'node_load',
      'hook_node_presave'   => 'node_presave',
      'hook_node_update'    => 'node_update',
      'hook_node_insert'    => 'node_insert',
      'hook_node_view'      => 'node_view',
      'hook_user_insert'    => 'user_insert',
      'hook_user_presave'   => 'user_presave',
      'hook_user_update'    => 'user_update',
      'hook_menu'           => 'menu',
      'hook_action_info'    => 'action_info',
    );

    // Special case for 'model.module' file.
    if (!isset($_POST['hooks'])) {
        $data = str_replace('%HOOKS%', '', $data);
    } else {
        $hook_info = '';

        foreach ($_POST['hooks'] as $key => $hook) {
            $hook_info .= "module_load_include('inc', '" . $_POST['machine_name'] . "', '" . $_POST['machine_name'] . "." . $base_hook[$hook] . "');" . PHP_EOL;
            parseTemplate('model.' . $base_hook[$hook] . '.inc', $dir, $replace_tokens);
        }

        $data = str_replace('%HOOKS%', $hook_info, $data);
    }

    file_put_contents($destination, $data);

    $files = array(
      'model.install',
      'model.features.inc',
      'model.views_default.inc',
      'model_modelentity.admin.inc',
      'model_modelentity_type.admin.inc',
      'templates/model.tpl.php',
      'views/model.views.inc',
      'views/modelentity_handler_link_field.inc',
      'views/modelentity_handler_modelentity_operations_field.inc',
      'views/modelentity_handler_edit_link_field.inc',
      'views/modelentity_handler_delete_link_field.inc',
    );

    foreach ($files as $file) {
        parseTemplate($file, $dir, $replace_tokens);
    }

    // Create and send zip file.
    $file = createZipFile($dir, $dir . '.zip', true);
    $file_name = basename($dir . '.zip');

    header('Content-Type: application/zip');
    header("Content-Disposition: attachment; filename=$file_name");
    header('Content-Length: ' . filesize($dir . '.zip'));
    readfile($dir . '.zip');
    exit;
}

// Detect language.
$language = detectLanguage(array('en', 'fr'), 'en');

// Display form generator.
include 'generator/index.html';
