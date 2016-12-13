<?php
use BrowscapPHP\Browscap;
use BrowscapPHP\BrowscapUpdater;
use WurflCache\Adapter\File as WurflCache;
use BrowscapPHP\Helper\IniLoader as BrowscapIniLoader;
use UserAgentParser\Provider\BrowscapLite as BrowscapLiteParser;

/* ------- UA to Test ----------------------------------------------------------------------------------------------- */

$UA_to_test = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36';

/* ------- Begin Test Ouput ----------------------------------------------------------------------------------------- */

error_reporting(-1);
ini_set('display_errors', 'yes');

require_once dirname(__FILE__, 3).'/src/vendor/autoload.php';

$cache_dir  = $_SERVER['HOME'].'/temp/browscap';

if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0775, true);
    $BrowscapUpdater = new BrowscapUpdater();
    $BrowscapUpdater->setCache(new WurflCache([WurflCache::DIR => $cache_dir]));
    $BrowscapUpdater->update(BrowscapIniLoader::PHP_INI_LITE);
}
$Browscap =  new Browscap();
$Browscap->setCache(new WurflCache([WurflCache::DIR => $cache_dir]));
$BrowscapLiteParser = new BrowscapLiteParser($Browscap);
$UserAgent          = $BrowscapLiteParser->parse($UA_to_test);

echo 'os.name = '.$UserAgent->getOperatingSystem()->getName()."\n\n";

echo 'device.type = '.$UserAgent->getDevice()->getType()."\n";
echo 'device.is_mobile = '.$UserAgent->getDevice()->getIsMobile()."\n\n";

echo 'browser.name = '.$UserAgent->getBrowser()->getName()."\n";
echo 'browser.version = '.$UserAgent->getBrowser()->getVersion()->getComplete()."\n";
