<?php
/**
  * Mdocs module configuration
  *
  * @package Panthera\mdocs
  * @author Damian Kęska
  * @author Mateusz Warzyński
  * @license GNU Affero General Public License 3, see license.txt
  */

if (!defined('IN_PANTHERA'))
    exit;

if (!getUserRightAttribute($user, 'can_update_config_overlay') and !getUserRightAttribute($user, 'can_edit_mdocs_settings'))
{
    $noAccess = new uiNoAccess; $noAccess -> display();
    pa_exit();
}

$panthera -> locale -> loadDomain('mdocs');

// titlebar
$titlebar = new uiTitlebar(localize('Panthera Documentation Generator settings', 'mdocs'));
$titlebar -> addIcon('{$PANTHERA_URL}/images/admin/menu/documentation.png', 'left');

$config = new uiSettings('mdocs');

$config -> add('mdocs.apikey', localize('API key', 'mdocs'));
$config -> setDescription('mdocs.apikey', localize('Used to authorize external callback from eg. github.com to start re-generating docs', 'mdocs'));

$config -> add('mdocs.templateurl', localize('URL template', 'mdocs'));
$config -> setDescription('mdocs.templateurl', localize('Template of urls inside documentation to provide navigation on your page, avaliable tags:', 'mdocs'). ' {$name}, {$title}, {$section}, {$sectionLong}');

$config -> add('mdocs.src', localize('Source', 'mdocs'));
$config -> setDescription('mdocs.src', localize('Documentation source files, a github/bitbucket repository or a local directory', 'mdocs'));

$config -> add('mdocs.branch', localize('Branch', 'mdocs'));
$config -> setDescription('mdocs.branch', localize('Remote repository branch (if using a remote repository as source)', 'mdocs'));

$result = $config -> handleInput($_POST);

if (is_array($result))
{
    ajax_exit(array('status' => 'failed', 'message' => $result['message'][1], 'field' => $result['field']));
} elseif ($result === True) {
    ajax_exit(array('status' => 'success'));
}

$panthera -> template -> display('settings.generic_template.tpl');
pa_exit();
