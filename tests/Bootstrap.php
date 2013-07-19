<?php
/**
 * Set error reporting to the level to which Zend Framework code must comply.
 */
error_reporting( E_ALL | E_STRICT );

if (class_exists('PHPUnit_Runner_Version', true)) {
    $phpUnitVersion = PHPUnit_Runner_Version::id();
    if ('@package_version@' !== $phpUnitVersion && version_compare($phpUnitVersion, '3.7.0', '<')) {
        echo 'This version of PHPUnit (' .
            PHPUnit_Runner_Version::id() .
            ') is not supported for ThinkscapeTest unit tests - use v 3.7.0 or higher.'
            . PHP_EOL
            . PHP_EOL
        ;
        exit(1);
    }
    unset($phpUnitVersion);
}

/**
 * Make sure we're running a recent PHP version
 */
if (version_compare(PHP_VERSION, '5.4.3', '<')) {
    echo 'This component requires PHP version 5.4.3 or newer.\n';
    exit(1);
}

/**
 * Setup autoloading
 */
// Try to use Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include_once __DIR__ . '/../vendor/autoload.php';
}
// If composer autoloader is missing, try to use ZF2 loader from zend-loader package.
elseif (false && file_exists( __DIR__ . '/../vendor/zendframework/zend-loader/Zend/Loader/StandardAutoloader.php')) {
    require_once __DIR__ . '/../vendor/zendframework/zend-loader/Zend/Loader/StandardAutoloader.php';
    $loader = new Zend\Loader\StandardAutoloader(array(
        Zend\Loader\StandardAutoloader::LOAD_NS => array(
            'Thinkscape'     => __DIR__ . '/../src/Thinkscape',
            'ThinkscapeTest' => __DIR__ . '/ThinkscapeTest',
        ),
    ));
    $loader->register();
}

// ... or main zendframework package.
elseif (file_exists( __DIR__ . '/../vendor/zendframework/zendframework/library/Zend/Loader/StandardAutoloader.php')) {
    require_once __DIR__ . '/../vendor/zendframework/zendframework/library/Zend/Loader/StandardAutoloader.php';
    $loader = new Zend\Loader\StandardAutoloader(array(
        Zend\Loader\StandardAutoloader::LOAD_NS => array(
            'Thinkscape'     => __DIR__ . '/../src/Thinkscape',
            'ThinkscapeTest' => __DIR__ . '/ThinkscapeTest',
        ),
    ));
    $loader->register();
}

// ... or use a simple SPL autoloader
else{

    // update include path
    set_include_path(implode(PATH_SEPARATOR, array(
        __DIR__.'/../src',
        __DIR__,
        get_include_path()
    )));

    /**
     * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md#example-implementation
     */
    spl_autoload_register(function($className){
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        var_dump($fileName);
        require $fileName;
    });

}

/**
 * Code coverage option
 */
if (defined('TESTS_GENERATE_REPORT') && TESTS_GENERATE_REPORT === true) {
    $codeCoverageFilter = new PHP_CodeCoverage_Filter();

    $lastArg = end($_SERVER['argv']);
    if (is_dir($zfCoreTests . '/' . $lastArg)) {
        $codeCoverageFilter->addDirectoryToWhitelist($zfCoreLibrary . '/' . $lastArg);
    } elseif (is_file($zfCoreTests . '/' . $lastArg)) {
        $codeCoverageFilter->addDirectoryToWhitelist(dirname($zfCoreLibrary . '/' . $lastArg));
    } else {
        $codeCoverageFilter->addDirectoryToWhitelist($zfCoreLibrary);
    }

    /*
     * Omit from code coverage reports the contents of the tests directory
     */
    $codeCoverageFilter->addDirectoryToBlacklist($zfCoreTests, '');
    $codeCoverageFilter->addDirectoryToBlacklist(PEAR_INSTALL_DIR, '');
    $codeCoverageFilter->addDirectoryToBlacklist(PHP_LIBDIR, '');

    unset($codeCoverageFilter);
}

/*
 * Unset global variables that are no longer needed.
 */
unset($phpUnitVersion);
