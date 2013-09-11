<?php
/**
  * Documentation viewer for Panthera Framework
  *
  * @package Panthera\core\pages
  * @author Damian Kęska
  * @license GNU Affero General Public License 3, see license.txt
  */
  
$panthera -> importModule('mdocs');

if (isset($_GET['_callback_api_key']))
{
    if ($_GET['_callback_api_key'] == $panthera -> config -> getKey('mdocs.apikey', generateRandomString(32), 'string', 'mdocs'))
    {
        print("Build started\n");
        $docs = new mdocs;
        $docs -> setTemplateURL($panthera -> config -> getKey('mdocs.templateurl', '{$PANTHERA_URL}/?display=docs&section={$section}&title={$title}&language={$language}', 'string', 'mdocs'));
        $docs -> build();
        print("Done.");
        pa_exit();
    }
}

// some defaults
if (!$_GET['title'])
{
    $_GET['title'] = 'index';
}

if (!$_GET['section'])
{
    $_GET['section'] = 'index';
}

$page = mdocs::getPage($_GET['title'], $_GET['section'], $_GET['language']);

if ($page)
{
    $contents = @file_get_contents(SITE_DIR. '/content/templates/_docs/' .$page['file']);
    $panthera -> template -> push('contents', $contents);
    $panthera -> template -> display('docs.tpl');
}

pa_redirect('?404');
pa_exit();
