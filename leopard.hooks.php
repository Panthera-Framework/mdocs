<?php
/**
  * Post install hook
  *
  * @param string $package
  * @return $package 
  * @author Damian Kęska
  */

function _mdocsPostInstall($package)
{
    global $panthera;
    if (!is_dir(SITE_DIR. '/content/share'))
    {
        @mkdir(SITE_DIR. '/content/share');
    }

    // install php-markdown library
    @mkdir(SITE_DIR. '/content/share/php-markdown');
    $panthera -> logging -> output ('Installing php-markdown-extra-extended library from github', 'leopard');
    scm::cloneBranch('https://github.com/egil/php-markdown-extra-extended', SITE_DIR. '/content/share/php-markdown-extra-extended', 'master');
    
    // updating webroot
    $panthera -> logging -> output('Updating webroot', 'leopard');
    $panthera -> template -> webrootMerge();
    
    // create default configuration
    $panthera -> logging -> output('Creating default configuration', 'leopard');
    
    $panthera -> config -> getKey('mdocs.src', 'https://github.com/Panthera-Framework/panthera-docs', 'string', 'mdocs');
    $panthera -> config -> getKey('mdocs.branch', 'master', 'string', 'mdocs');
    $panthera -> config -> getKey('mdocs.apikey', generateRandomString(32), 'string', 'mdocs');
    $panthera -> config -> getKey('mdocs.templateurl', '{$PANTHERA_URL}/?display=docs&section={$section}&title={$title}&language={$language}', 'string', 'mdocs');
    
    // adding self to menu
    $panthera -> config -> loadOverlay('settings');
    $menu = $panthera -> config -> getKey('settings.items');
    
    $menu['content']['mdocs'] = array(
        'link' => '?display=settings.mdocs&cat=admin',
        'name' => localize('Panthera Documentation Generator settings', 'mdocs'),
        'icon' => '{$PANTHERA_URL}/images/admin/menu/documentation.png',
        'linkType' => 'ajax'
    );
    
    $menu = $panthera -> config -> setKey('settings.items', $menu, 'array', 'settings');
    
    return $package;
}

/**
  * Post remove hook
  *
  * @param string $input
  * @return $input 
  * @author Damian Kęska
  */

function _mdocsPostRemove($input)
{
    global $panthera;
    
    // remove configuration
    $panthera -> logging -> output('Removing configuration', 'leopard');
    $panthera -> config -> removeKey('mdocs.key');
    $panthera -> config -> removeKey('mdocs.templateurl');
    $panthera -> config -> removeKey('mdocs.branch');
    $panthera -> config -> removeKey('mdocs.src');
    
    // remove php-markdown library
    $panthera -> logging -> output('Removing php-markdown-extra-extended', 'leopard');
    deleteDirectory(SITE_DIR. '/content/share/php-markdown-extra-extended');
    
    // removing from menu
    $panthera -> config -> loadOverlay('settings');
    $menu = $panthera -> config -> getKey('settings.items');
    unset($menu['content']['mdocs']);
    $menu = $panthera -> config -> setKey('settings.items', $menu, 'array', 'mdocs');
    
    return $input;
}

$panthera -> add_option('leopard.postinstall', '_mdocsPostInstall');
$panthera -> add_option('leopard.postremove', '_mdocsPostRemove');
