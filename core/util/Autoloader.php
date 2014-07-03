<?php
/**
 * Autoloader is a class scanner with caching. i.e. it'll try and discover class files in the
 * given list of directories, and if it finds matche(s) it'll save them in a cache for later use (E.g. APC via Zend_Cache)
 *
 *
 *
 * @see http://anthonybush.com/projects/autoloader/
 * DG re-coded it to not be all static, as that seems stupid.
 *
 *  Sample Usage:
 *  include_once('Autoloader.php');
 *  $autoloader = Autoloader::getInstance();
 *  $autoloader->addClassPath('path/to/classes');
 *  $autoloader->addClassPath(explode(':', ini_get('include_path'));
 *  $autoloader->setCache(Zend_Cache_Frontend $x); // APC?
 *  $autoloader->excludeFolderNamesMatchingRegex('/^CVS|^\./');
 *  $autoloader->ignoreClassNamesRegexp('/Zend_/'); // use e.g. Zend_Loader instead.
 *  $autoloader->setCacheKey('whatever');
 *  $autoloader->register(); // register with SPL.
 *
 * @author Original: Anthony Bush, Wayne Wight
 * @author David Goodwin
 * @copyright 2006-2008 Academic Superstore. This software is open source protected by the FreeBSD License.
 * @version 2011-04-18
 **/
 
class Autoloader {
    /* @var Autoloader singleton static thing */
    private static $instance = null;
 
    /* @var string cache key; should be unique per project using this,
      if multiple projects on the same server. */
    protected $cacheKey = 'autoloader_cache';
    /* @var array */
    protected $classPaths = array();
    /* @var string */
    protected $classFileSuffix = '.php';
    /* @var Zend_Cache instance */
    protected $cacheBackend = null;
    /* @var array list of directories to rummage through */
    protected $cachedPaths = null;
    /* @var string regexp to use to exclude folders we do not wish to search */
    protected $excludeFolderNames = '/^CVS|\..*$/'; // CVS directories and directories starting with a dot (.).
    /* @var string regexp for class names to not even bother looking for - e.g. Zend_ */
    protected $ignoreClassNamesRegexp = '/Zend_/';
 
   /* explicitly here so you are aware that it doesn't have to be a singleton */
    public function __construct() {
    }
 
    /**
      * do not consider a file name matching this regexp; e.g. anything with Zend_ - don't bother
      * trying, as we'll always have a 'miss', as we'll use Zend_Loader for them.
      * @param string $regexp
      */
    public function ignoreClassesMatching($regexp) {
        $this->ignoreClassNamesRegexp = $regexp;
    }
 
    /**
     * Well, if you want, you can use it as a singleton....
     * @return Autoloader $singleton instance
     */
    public static function getInstance() {
        if(self::$instance == null) {
            self::$instance = new Autoloader();
        }
        return self::$instance;
    }
 
    /**
      * You might need this if running the same code base twice on the same server but
      * under different directories - e.g. dev and live. In which case, specify a local per project key.
      */
    public function setCacheKey($string) {
        $this->cacheKey = $string;
    }
 
    /**
     * Sets the paths to search in when looking for a class.
     *
     * @param array $paths
     * @return void
     **/
    public  function setClassPaths($paths) {
        $this->classPaths = $paths;
    }
 
    /**
     * Adds a path to search in when looking for a class.
     *
     * @param string $path
     * @return void
     **/
    public  function addClassPath($path) {
        if(!is_array($path)) {
            $path = array($path);
        }
        foreach($path as $item) {
            $this->classPaths[] = $item;
        }
    }
 
    /**
     * Set the Zend_Cache instance to use.
     *
     *     <code>
     *     Autoloader->setCache($cache);
     *     </code>
     *
     * @param Zend_Cache_Frontend $cache
     * @return void
     **/
    public function setCache($cache) {
        $this->cacheBackend = $cache;
    }
 
    /**
     * Return caching backend used; or create new one if none already defined
     * (Zend_Cache_Frontend_Core with APC backend).
     */
    public function getCache() {
        if($this->cacheBackend == null) {
            $this->cacheBackend = Zend_Cache::factory('Core', 'Apc', array('automatic_serialization' => true));
        }
        return $this->cacheBackend;
    }
 
    /**
     * Sets the suffix to append to a class name in order to get a file name
     * to look for
     *
     * @param string $suffix - $className . $suffix = filename.
     * @return void
     **/
    public static function setClassFileSuffix($suffix) {
        $this->classFileSuffix = $suffix;
    }
 
    /**
     * When searching the {@link $classPaths} recursively for a matching class
     * file, folder names matching $regex will not be searched.
     *
     * Example:
     *     <code>
     *     Autoloader::excludeFolderNamesMatchingRegex('/^CVS|\..*$/');
     *     </code>
     * @param string $regex - regexp to exclude files/dirs from being selected for possible candidates.
     **/
    public function excludeFolderNamesMatchingRegex($regex) {
        $this->excludeFolderNames = $regex;
    }
 
    /**
     * Given a map of class names to absolute file paths, try and load the class.
     * If we get to including the file, we ensure that the class_exists after doing
     * so, otherwise we'll return false.
     * Also return false if we don't know where the class ($name) could be.
     * @param string $name the name of the class we wish to load.
     * @param array $list - map of known classnames to absolute file paths.
     */
    protected function tryAndLoadClass($name, $list) {
        if(in_array($name, array_keys($list))) {
            include_once $list[$name];
            if(class_exists($name) || interface_exists($name)) {
                //echo "Successfully loaded $name\n";
                return true;
            }
        }
        return false;
    }
 
    /**
     * Returns true if the class file was found and included, false if not.
     *
     * @return boolean
     **/
    public function loadClass($className) {
        $cache = $this->getCache();
        $entries = $cache->load($this->cacheKey);
        if($entries == false) {
            $entries = array();
        }
        $status = $this->tryAndLoadClass($className, $entries);
        if($status) {
            // class loaded
            return true;
        }
        if(preg_match($this->ignoreClassNamesRegexp, $className)) {
            return false;
        }
 
        foreach($this->classPaths as $path) {
            // Scan for file
            $realPath = $this->searchForClassFile($className, $path);
            if($realPath !== false) {
                $entries[$className] = $realPath;
                break;
            }
        }
        $cache->save($entries, $this->cacheKey);
        $status = $this->tryAndLoadClass($className, $entries);
        return $status;
    }
 
    /**
     * Rummage recursively through all directories found within $directory for a
     * file matching ClassName.$suffix
     * @return string class name if one found; otherwise false.
     */
    protected function searchForClassFile($className, $directory) {
        if(is_dir($directory) && is_readable($directory)) {
            $d = dir($directory);
            while($f = $d->read()) {
                $subPath = realpath($directory . DIRECTORY_SEPARATOR . $f);
                //echo "Looking in $subPath for $className{$this->classFileSuffix}\n";
                if(is_dir($subPath)) {
                    // Found a subdirectory
                    if(!preg_match($this->excludeFolderNames, $f)) {
                        if($filePath = $this->searchForClassFile($className, $subPath . '/')) {
                            return $filePath;
                        }
                    }
                }
                else { // it's a file... so does the name match ?
                    if($f == $className . $this->classFileSuffix) {
                        return $subPath;
                    }
                }
            }
        }
        return false;
    }
 
    /**
     * Register this class with SPL as an autoloader.
     * @return void
     */
    public function register() {
        spl_autoload_register(array('Autoloader', 'loadClass'));
    }
}

