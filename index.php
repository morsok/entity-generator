<?php 

	function Zip($source, $destination, $include_dir = false)
	{
	    if (!extension_loaded('zip') || !file_exists($source)) {
	        return false;
	    }
	    if (file_exists($destination)) {
	        unlink ($destination);
	    }
	    $zip = new ZipArchive();
	    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
	        return false;
	    }
	    $source = str_replace('\\', '/', realpath($source));

	    if (is_dir($source) === true)
	    {

	        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

	        if ($include_dir) {

	            $arr = explode("/",$source);
	            $maindir = $arr[count($arr)- 1];

	            $source = "";
	            for ($i=0; $i < count($arr) - 1; $i++) { 
	                $source .= '/' . $arr[$i];
	            }

	            $source = substr($source, 1);

	            $zip->addEmptyDir($maindir);

	        }

	        foreach ($files as $file)
	        {
	            $file = str_replace('\\', '/', $file);

	            // Ignore "." and ".." folders
	            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
	                continue;

	            $file = realpath($file);

	            if (is_dir($file) === true)
	            {
	                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
	            }
	            else if (is_file($file) === true)
	            {
	                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
	            }
	        }
	    }
	    else if (is_file($source) === true)
	    {
	        $zip->addFromString(basename($source), file_get_contents($source));
	    }

	    return $zip->close();
	}

	if (!empty($_POST))
	{
		if (empty($_POST['machine_name']) ||
			empty($_POST['module_name']) ||
			empty($_POST['module_desc']) ||
			empty($_POST['entity_name']) ||
			empty($_POST['entity_label']) ||
			empty($_POST['entity_label_plural']) ||
			strstr($_POST['machine_name'], "../") ||
			$_POST['machine_name'][0] == '/')
		{
			echo "An error happened, one field is empty or contains invalid value";
			exit;
		}
		define("BASE_SRC", "../base_model/");
		$dest = false;
		$dir = $_POST['machine_name'];
		$base_hook = array(
			"hook_entity_presave" => "entity_presave",
			"hook_entity_update"  => "entity_update",
			"hook_entity_view"    => "entity_view",
			"hook_node_load"      => "node_load",
			"hook_node_presave"   => "node_presave",
			"hook_node_update"    => "node_update",
			"hook_node_insert"    => "node_insert",
			"hook_node_view"      => "node_view",
			"hook_user_insert"    => "user_insert",
			"hook_user_presave"   => "user_presave",
			"hook_user_update"    => "user_update",
			"hook_menu"			  => "menu",
			"hook_action_info"	  => "action_info",
			);

		chdir("tmp");
		if (is_dir($dir))
		{
			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path)
			{
    			$path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
			}
			rmdir($dir);
			if (file_exists($dir.".zip"))
				unlink($dir.".zip");
		}
		mkdir($dir);
		mkdir($dir."/views");

		// model.info
		$data = file_get_contents(BASE_SRC."model.info");
		$data = str_replace("%NAME%", $_POST['module_name'], $data);
		$data = str_replace("%DESC%", $_POST['module_desc'], $data);
		$data = str_replace("%MD_NAME%", $_POST['machine_name'], $data);
		$data = str_replace("%ENTITY_NAME%", $_POST['entity_name'], $data);
		$dest = file_put_contents($dir."/".$_POST['machine_name'].".info", $data);
		// model.install
		$data = file_get_contents(BASE_SRC."model.install");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/".$_POST['machine_name'].".install", $data);
		// model.module
		$data = file_get_contents(BASE_SRC."model.module");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		if (!isset($_POST['hooks']))
			$data = str_replace('%HOOKS%', '', $data);
		else
		{
			$hook_info = '';
			foreach ($_POST['hooks'] as $key => $hook)
			{
				$hook_info .= "module_load_include('inc', '".$_POST['machine_name']."', '".$_POST['machine_name'].".".$base_hook[$hook]."');".PHP_EOL;
				$hook_data = file_get_contents(BASE_SRC."model.".$base_hook[$hook].".inc");
				$hook_data = str_replace('model', $_POST['machine_name'], $hook_data);
				file_put_contents($dir."/".$_POST['machine_name'].".".$base_hook[$hook].".inc", $hook_data);
			}
			$data = str_replace('%HOOKS%', $hook_info, $data);
		}
		$dest = file_put_contents($dir."/".$_POST['machine_name'].".module", $data);
		// model_modelentity.admin.inc
		$data = file_get_contents(BASE_SRC."model_modelentity.admin.inc");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/".$_POST['machine_name']."_".$_POST['entity_name'].".admin.inc", $data);
		// model_modelentity_type.admin.inc
		$data = file_get_contents(BASE_SRC."model_modelentity_type.admin.inc");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/".$_POST['machine_name']."_".$_POST['entity_name']."_type.admin.inc", $data);
		// model.tpl.php
		$data = file_get_contents(BASE_SRC."model.tpl.php");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/".$_POST['entity_name'].".tpl.php", $data);
		// model.views.inc
		$data = file_get_contents(BASE_SRC."views/model.views.inc");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('EELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/views/".$_POST['machine_name'].".views.inc", $data);
		// modelentity_handler_link_field.inc
		$data = file_get_contents(BASE_SRC."views/modelentity_handler_link_field.inc");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/views/".$_POST['entity_name']."_handler_link_field.inc", $data);
		// modelentity_handler_modelentity_operations_field.inc
		$data = file_get_contents(BASE_SRC."views/modelentity_handler_modelentity_operations_field.inc");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/views/".$_POST['entity_name']."_handler_".$_POST['entity_name']."_operations_field.inc", $data);
		// modelentity_handler_edit_link_field.inc
		$data = file_get_contents(BASE_SRC."views/modelentity_handler_edit_link_field.inc");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/views/".$_POST['entity_name']."_handler_edit_link_field.inc", $data);
		// modelentity_handler_delete_link_field.inc
		$data = file_get_contents(BASE_SRC."views/modelentity_handler_delete_link_field.inc");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/views/".$_POST['entity_name']."_handler_delete_link_field.inc", $data);
		// modelentity.admin.view
		$data = file_get_contents(BASE_SRC."views/modelentity.admin.view");
		$data = str_replace('modelentity', $_POST['entity_name'], $data);
		$data = str_replace('modelentities', $_POST['entity_name'].'s', $data);
		$data = str_replace('model', $_POST['machine_name'], $data);
		$data = str_replace('Modelentity', ucfirst($_POST['entity_name']), $data);
		$data = str_replace('Modelentities', ucfirst($_POST['entity_name'].'s'), $data);
		$data = str_replace('Model', ucfirst($_POST['machine_name']), $data);
		$data = str_replace('ELabel', ucfirst($_POST['entity_label']), $data);
		$data = str_replace('ELabels', ucfirst($_POST['entity_label_plural']), $data);
		$dest = file_put_contents($dir."/views/".$_POST['entity_name'].".admin.view", $data);

		$file = Zip($dir, $dir.'.zip', true);
		$file_name = basename($dir.'.zip');
	    header("Content-Type: application/zip");
	    header("Content-Disposition: attachment; filename=$file_name");
	    header("Content-Length: ".filesize($dir.'.zip'));
	    readfile($dir.'.zip');
	    exit;
	}

?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
	    <title>Accommodata Drupal Entity Generator</title>
	    <!-- Bootstrap -->
	    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
	    <!-- Custom css -->
	    <link rel="stylesheet" href="css/style.css">
	    <!-- Chosen css -->
	    <link rel="stylesheet" href="css/chosen.css">
	    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	    <!--[if lt IE 9]>
	      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
	      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
	    <![endif]-->
	</head>
	<body>
		<div class="container">
			<div class="page-header clearfix">
				<div class="col-sm-4">
			  		<a class="pull-left" href="http://accommodata.fr" target="_blank">
			    		<img src="images/logo.png" class="img-responsive media-object" alt="Accommodata logo">
			  		</a>
			  	</div>
			  	<div class="col-sm-8">
			    	<h1>Drupal Entity Generator <small>Based on <a href="https://drupal.org/project/model" target="_blank">Model Entities</a></small></h1>
			  	</div>
			</div>
			<form method="post" class="form-horizontal" role="form">
				<div class="form-group">
					<label for="module_name" class="col-sm-2 control-label">Nom du module</label>
					<div class="col-sm-10">
						<input class="form-control" required type="text" name="module_name" id="module_name" placeholder="My module"/>
					</div>
				</div>
				<div class="form-group">
					<label for="machine_name" class="col-sm-2 control-label">Nom machine du module</label>
					<div class="col-sm-10">
						<input class="form-control" required type="text" name="machine_name" id="machine_name" placeholder="views / module_filter / my_module"/>
					</div>
				</div>
				<div class="form-group">
					<label for="module_desc" class="col-sm-2 control-label">Description du module</label>
					<div class="col-sm-10">
						<input class="form-control" required type="text" size="60" name="module_desc" id="module_desc" placeholder="My module description"/>
					</div>
				</div>
				<div class="form-group">
					<label for="entity_label" class="col-sm-2 control-label">Label de l'entité</label>
					<div class="col-sm-10">
						<input class="form-control" required type="text" name="entity_label" id="entity_label" placeholder="Custom Entity"/>
					</div>
				</div>
				<div class="form-group">
					<label for="entity_label_plural" class="col-sm-2 control-label">Label de l'entité au pluriel</label>
					<div class="col-sm-10">
						<input class="form-control" required type="text" name="entity_label_plural" id="entity_label_plural" placeholder="Custom Entities"/>
					</div>
				</div>
				<div class="form-group">
					<label for="entity_name" class="col-sm-2 control-label">Nom machine de l'entité</label>
					<div class="col-sm-10">
						<input class="form-control" required type="text" name="entity_name" id="entity_name" placeholder="node / users / custom_entity"/>
					</div>
				</div>
				<div class="form-group">
					<label for="hooks" class="col-sm-2 control-label">Selectionner les hooks à ajouter (optionnel)</label>
					<div class="col-sm-10">
						<select data-placeholder="Choose a hook" class="form-control chosen-select" id="hooks" name="hooks[]" multiple tabindex="4">
							<option value="hook_entity_presave">hook_entity_presave</option>
				            <option value="hook_entity_update">hook_entity_update</option>
				            <option value="hook_entity_view">hook_entity_view</option>
				            <option value="hook_node_load">hook_node_load</option>
				            <option value="hook_node_presave">hook_node_presave</option>
				            <option value="hook_node_update">hook_node_update</option>
				            <option value="hook_node_insert">hook_node_insert</option>
				            <option value="hook_node_view">hook_node_view</option>
				            <option value="hook_user_insert">hook_user_insert</option>
				            <option value="hook_user_presave">hook_user_presave</option>
				            <option value="hook_user_update">hook_user_update</option>
				            <option value="hook_menu">hook_menu</option>
				            <option value="hook_action_info">hook_action_info</option>
						</select>
					</div>
				</div>
				<div class="form-group">
    				<div class="col-sm-offset-2 col-sm-10">
						<button type="submit" class="btn btn-default">Télécharger le module zippé</button>
					</div>
				</div>
			</form>
			<div class="col-sm-6">
				<p>
					Patch note 16/06/2014:
					<ul>
						<li>Suppression du hook_hook_info remplacé par des appels à module_load_include</li>
					</ul>
				</p>
				<p>
					Patch note 13/06/2014:
					<ul>
						<li>Fix: REQUEST_TIME ajouté sur le champ created du controller à la création de l'entity</li>
						<li>Ajout du hook_action_info</li>
					</ul>
				</p>
				<p>
					Patch note 11/06/2014:
					<ul>
						<li>Ajout d'une relationship views pour le champ uid de l'entité</li>
						<li>Ajout du hook_menu dans la liste des hooks disponibles</li>
					</ul>
				</p>
				<p>
					Patch note 02/06/2014:
					<ul>
						<li>Ajout de permissions (view/edit/delete own entity type)</li>
						<li>Ajout de l'uid de l'auteur dans le schéma (hook_install) et remplissage dans le create du controller avec le global $user</li>
						<li>Ajout de la possibilité de déclarer des hooks dans des fichiers séparés</li>
					</ul>
				</p>
				<p>
					Patch note 22/05/2014:
					<ul>
						<li>Correction du nommage du fichier .tpl.php</li>
						<li>Ajout du label singulier et pluriel de l'entité traduisible ensuite dans Drupal {possiblement quelques oublis}</li>
						<li>Séparation de la view d'admin de l'entité dans un fichier distinct .view afin de simplifier les imports/exports en code</li>
						<li>Ajout d'un handler views pour les dates pour les champs created et changed</li>
					</ul>
				</p>
			</div>
			<div class="col-sm-6">
				<p>
					TODO
					<ul>
						<li>Retravailler la vue dans l'admin pour une meilleur usabilité (VBO etc...)</li>
						<li>Multilangue Francais / Anglais</li>
						<li><p class="text-muted">Des questions/Suggestions ? <a href="mailto:alexandre@accommodata.fr">Contactez-nous</a></p></li>
					</ul>
				</p>
			</div>
		</div>
		<div class="footer">
	      <div class="container">
	        <p class="text-muted">© 2014 Accommodata - Des questions/Suggestions ? <a href="mailto:alexandre@accommodata.fr">Contactez-nous</a></p>
	      </div>
	    </div>
		<!-- jQuery -->
    	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    	<!-- Bootstrap 3 -->
    	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    	<!-- Chosen -->
    	<script src="js/chosen.jquery.js" type="text/javascript"></script>
    	<script type="text/javascript">
    		$(".chosen-select").chosen();
    	</script>
	</body>
</html>