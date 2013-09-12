<?php
/**
  * Documentation generator for Panthera Framework
  *
  * @package Panthera\modules\mdocs
  * @author Damian Kęska
  * @author Mateusz Warzyński
  * @license GNU Affero General Public License 3, see license.txt
  */

require SITE_DIR. '/content/share/php-markdown/Michelf/Markdown.php';
require SITE_DIR. '/content/share/php-markdown/Michelf/MarkdownExtra.php';
global $panthera;
$panthera -> importModule('filesystem');
$panthera -> importModule('phpquery');
use \Michelf\Markdown;

class mdocs
{
    // directory or github/bitbucket repository where docs are placed
    public $src = 'https://github.com/Panthera-Framework/panthera-docs';
    public $destination = '';
    public $branch = 'master';
    public $templateURL = '{$PANTHERA_URL}/?display=docs&section={$section}&title={$title}&language={$language}'; // {$name}, {$title}, {$section}, {$sectionLong}
    public $buildTarget = '{$SITE_DIR}/content/templates/_docs';
    
    // database
    protected $manifests = array();
    protected $index = array();
    public $warnings = array();
    
    // cache for static functions
    public static $database = array();
    
    /**
      * Download sources from remote server
      *
      * @return bool 
      * @author Damian Kęska
      */
    
    protected function download()
    {
        // clone sources
        if (!is_dir($this->src))
        {
            $this->destination = SITE_DIR. '/content/tmp/mdocs-' .hash('md4', $this->src);
            
            if (is_dir($this->destination))
            {
                @deleteDirectory($this->destination);
            }
            
            @mkdir($this->destination);
            
            scm::cloneBranch($this->src, $this->destination, $this->branch);
            return True;
        }
        
        // if its a dir
        $this->destination = pantheraUrl($this->src, False, 'system');
        return True;
    }
    
    /**
      * Set template of navigation URL's placed inside of documentation
      *
      * @param string $url Template
      * @return void 
      * @author Damian Kęska
      */
    
    public function setTemplateURL($url)
    {
        if (strpos($url, '{$section}') === False or strpos($url, '{$title}') === False)
        {
            throw new Exception('{$section} or {$title} variables are missing in input template');
        }
        
        $this->templateURL = $url;
    }
    
    /**
      * Set source directory or remote repository
      *
      * @param string $src URL or directory
      * @param string $branch Optional branch if using remote repository
      * @return mixed 
      * @author Damian Kęska
      */
    
    public function setSource($src, $branch='master')
    {
        $this->src = $src;
        $this->branch = $branch;
    }
    
    /**
      * Set a target directory where to place generated documentation HTML files
      *
      * @param string $targetDirectory
      * @return void 
      * @author Damian Kęska
      */
    
    public function setDestination($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }
    
    /**
      * Get page title from manifest file
      *
      * @param string $dir Directory where is placed manifest.json to load
      * @param string $file File to get title for
      * @return mixed 
      * @author Damian Kęska
      */
    
    public function getPageTitle($dir, $file)
    {
        $dir = str_ireplace('/manifest.json', '', $dir);
    
        if (!isset($this->manifests[$dir]))
        {
            if (is_file($dir. '/manifest.json'))
            {
                $this->manifests[$dir] = json_decode(file_get_contents($dir. '/manifest.json'), true);
            }
        }
        
        if (!isset($this->manifests[$dir]['files'][$file]))
        {
            return '';
        }
        
        return $this->manifests[$dir]['files'][$file]['title'];
    }
    
    /**
      * Replace all links in output HTML code
      *
      * @param string $html Input HTML
      * @param object $phpQuery Object
      * @return mixed 
      * @author Damian Kęska
      */
    
    public function filterLinks($html, $phpQuery)
    {
        foreach ($phpQuery->find('a') as $tag)
        {
            $originalLink = pq($tag)->attr('href');
            $link = parse_url($originalLink, PHP_URL_PATH);
            $link = substr($link, strpos($link, 'src/')+4, strlen($link));

            $exp = explode('/', $link);
            
            // get language
            $language = $exp[0];
            unset($exp[0]);
            $link = implode('/', $exp);
            
            // get section and name
            $pathinfo = pathinfo($link);
            $cExp = explode('/', $pathinfo['dirname']);
            $section = seoUrl($cExp[count($cExp)-1]);
            $name = $pathinfo['basename'];
            
            if (!$section)
            {
                $section = 'index';
                $pathinfo['dirname'] = 'index';
            }
            
            // get title
            $title = seoUrl($this->getPageTitle($this->destination. '/src/' .$language. '/' .$pathinfo['dirname'], $pathinfo['filename']));
            
            // parse template
            $link = str_replace('{$title}', $title, $this->templateURL);
            $link = str_replace('{$section}', $section, $link);
            $link = str_replace('{$name}', $pathinfo['filename'], $link);
            $link = str_replace('{$sectionLong}', str_replace('/', '-', seoUrl($pathinfo['dirname'])), $link);
            $link = str_replace('{$language}', $language, $link);
            $link = pantheraUrl($link, False, 'frontend');

            $html = str_replace($originalLink, $link, $html);            
        }
        
        return $html;
    }
    
    /**
      * Colorize PHP syntax
      *
      * @param string name
      * @return mixed 
      * @author Damian Kęska
      */
    
    public function colorizePHPSyntax($html, $phpQuery)
    {
        // <code>php
        
        foreach ($phpQuery->find('code') as $tag)
        {
            $contents = pq($tag)->html();
            
            if (strtolower(substr($contents, 0, 3)) != 'php')
            {
                continue;
            }
            
            $highlighted = highlight_string($contents, true);
            $highlighted = substr($highlighted, 9, strlen($highlighted)-7); // remove <code>php and </code>
            $html = str_replace($contents, $highlighted, $html);
        }
        
        return $html;
    }
    
    /**
      * Build docs
      *
      * @return void 
      * @author Damian Kęska
      */
    
    public function build()
    {
        global $panthera;
    
        if (!$this->destination)
        {
            $this->download();
        }
        
        if (!is_dir($this->destination))
        {
            throw new Exception('Temporary build directory does not exists as defined in "' .$this->destination. '"');
        }
        
        $panthera -> logging -> output('Starting build from source directory "' .$this->destination. '"', 'mdocs');
        
        // prepare variables
        $this->buildTarget = pantheraUrl($this->buildTarget, False, 'system');
        
        @deleteDirectory($this->buildTarget);
        
        if(!is_dir($this->buildTarget))
        {
            mkdir($this->buildTarget);
        }

        $files = scandirDeeply($this->destination. '/src', True);
        
        foreach ($files as $file)
        {
            $pathinfo = pathinfo($file);
            $tmp = explode('/', str_replace($this->destination. '/src/', '', $pathinfo['dirname']));
            $language = $tmp[0];
            
            if (strtolower($pathinfo['extension']) == 'md')
            {
                $panthera -> logging -> startTimer();
                $fileContents = file_get_contents($file);
                
                // title, section, file names etc.
                $title = $this->getPageTitle($pathinfo['dirname'], $pathinfo['filename']);
                $section = trim(seoUrl(str_replace('/', '-', str_ireplace($this->destination. '/src/' .$language, '', $pathinfo['dirname']))), '-');
                $lastSection = $tmp[count($tmp)-1];
               
                
                if (!$section)
                {
                    $section = 'index';
                }
                
                // transform markdown to html early to be able to get some informations from it like title if its missing in manifest.json
                $html = Markdown::defaultTransform($fileContents);
                $phpQuery = phpQuery::newDocument($html);
                        
                // get title from any <h1> or <h2> if not present in manifest.json
                if (!$title)
                {
                    $this->warnings[$section][] = 'Missing title for page "' .$pathinfo['filename']. '"';
                    $panthera->logging->output('Missing title for page ' .$language. '/' .$section. '/' .$pathinfo['filename'], 'mdocs', True);
                
                    $h1 = $phpQuery['h1'];
                    
                    if ($h1->html())
                    {
                        $panthera -> logging -> output('Found title in h1 tag', 'mdocs', True);
                        $title = $h1->html();
                    }
                    
                    if (!$title)
                    {
                        $h2 = $phpQuery['h2'];
                        
                        if ($h2->html())
                        {
                            $panthera -> logging -> output('Found title in h2 tag', 'mdocs', True);
                            $title = $h2->html();
                        }
                    }
                    
                    if (!$title)
                    {
                        $h3 = $phpQuery['h3'];
                        
                        if ($h3->html())
                        {
                            $panthera -> logging -> output('Found title in h3 tag', 'mdocs', True);
                            $title = $h3->html();
                        }
                    }
                }
                
                $html = $this->filterLinks($html, $phpQuery);
                $html = $this->colorizePHPSyntax($html, $phpQuery);

                $outputFile = $language. '-' .substr(hash('md4', $section.$fileContents), 0, 16). '-' .substr(seoUrl($title), 0, 16). '.html';
                list($html, $outputFile, $_a) = $panthera -> get_filters('mdocs.html.output', array($html, $outputFile, $phpQuery));
                
                // create a language in page index
                if (!isset($this->index[$language]))
                {
                    $this->index[$language] = array();
                }
                
                // create a section
                if (!isset($this->index[$language][$section]))
                {
                    $this->index[$language][$section] = array();
                }
                
                $this -> index[$language][$section][$pathinfo['filename']] = array(
                    'title' => $title,
                    'file' => $outputFile,
                    'originalFilePath' => str_ireplace($this->destination. '/src/' .$language, '', $file),
                    'section' => $lastSection
                );
                
                // write parsed html to file
                $fp = fopen($this->buildTarget. '/' .$outputFile, 'w');
                fwrite($fp, $html);
                fclose($fp);
                
                $panthera -> logging -> output ('Parsing: ' .$language. '/' .$section. '/' .$pathinfo['filename']. ' and saving output to "' .$this->buildTarget. '/' .$outputFile. '"', 'mdocs');
            }
        }
        
        if (defined('JSON_PRETTY_PRINT'))
        {
            $encoded = json_encode($this->index, JSON_PRETTY_PRINT);
        } else {
            $encoded = json_encode($this->index);
        }
        
        $fp = fopen($this->buildTarget. '/index.json', 'w');
        fwrite($fp, $encoded);
        fclose($fp);
    }
    
    /**
      * Search for a page
      *
      * @param string $page Name or title
      * @param string $section Section to search in
      * @param string $selectedLanguage Optional language to search
      * @return bool|array 
      * @author Damian Kęska
      */
    
    public static function getPage($page, $section, $selectedLanguage='')
    {
        if (!self::$database)
        {
            self::$database = json_decode(file_get_contents(SITE_DIR. '/content/templates/_docs/index.json'), True);
        }
        
        $found = false;
        
        foreach (self::$database as $languageName => $language)
        {
            if ($selectedLanguage)
            {
                if ($languageName != $selectedLanguage)
                {
                    continue;
                }
            }
            
            foreach ($language as $sectionName)
            {
                foreach ($sectionName as $itemName => $item)
                {
                    if (($itemName == $page or seoUrl($item['title']) == $page) and ($item['section'] == $section or $sectionName == $section))
                    {
                        $found = $item;
                        break;
                    }
                }
            } 
        }
        
        return $found;
    }
}
