<?php
function _mdocsPostInstall($package)
{
    global $panthera;
    if (!is_dir(SITE_DIR. '/content/share'))
    {
        @mkdir(SITE_DIR. '/content/share');
    }

    @mkdir(SITE_DIR. '/content/share/php-markdown');

    $panthera -> logging -> output ('Installing php-markdown library from github', 'leopard');
    scm::cloneBranch('https://github.com/michelf/php-markdown', SITE_DIR. '/content/share/php-markdown', 'lib');
    return $package;
}

function _mdocsPostRemove($input)
{
    global $panthera;
    $panthera -> logging -> output('Removing php-markdown', 'leopard');
    deleteDirectory(SITE_DIR. '/content/share/php-markdown');
    return $input;
}

$panthera -> add_option('leopard.postinstall', '_mdocsPostInstall');
$panthera -> add_option('leopard.postremove', '_mdocsPostRemove');
