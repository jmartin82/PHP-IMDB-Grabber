<?php
/**
 * Based on Fabian Beiner (mail@fabian-beiner.de)
 *
 * This class can be used to retrieve data from IMDb.com with PHP.
 *
 */

class IMDBException extends Exception
{
}

class IMDB
{
    // Define what to return if something is not found.
    public $strNotFound = 'n/A';
    // Define what to return if something is not found.
    public $arrNotFound = array();
    // Please set this to 'true' for debugging purposes only.
    const IMDB_DEBUG = false;
    // Define a timeout for the request of the IMDb page.
    const IMDB_TIMEOUT = 15;
    // Define the "Accept-Language" header language (so IMDb replies with decent localization settings).
    const IMDB_LANG = 'en-US, en';
    // Define the default search type (all/tvtitle/tvepisode/movie).
    const IMDB_SEARCHFOR = 'all';
    
    // Regular expressions, I would not touch them. :)
    const IMDB_AKA = '~<h5>Also Known As:<\/h5>(?:\s*)<div class="info-content">(?:\s*)"(.*)"~Ui';
    const IMDB_CAST = '~itemprop="actor"(?:.*)><a href="/name/nm(\d+)/(?:.*)"[ ]?itemprop=\'url\'> <span class="itemprop" itemprop="name">(.*)</span>~Ui';
    const IMDB_CHAR = '~<td class="character">\s+<div>(.*)</div>\s+</td~Ui';
    const IMDB_COUNTRY = '~href="/country/(\w+)\?(?:.*)"[ ]?itemprop=\'url\'>(.*)</a>~Ui';
    

    const IMDB_COMPANY      = '~Production Co:</h4>(.*)</div>~Ui';
    const IMDB_COMPANY_NAME = '~href="/company/co(\d+)(?:\?.*)"[ ]?itemprop=\'url\'>(.*)</a>~Ui';

    const IMDB_DESCRIPTION = '~<p itemprop="description">(.*)(?:<a|<\/p>)~Ui';
    const IMDB_DIRECTOR = '~(?:Director|Directors):</h4>(.*)</div>~Ui';
    
    const IMDB_GENRE        = '~href="/genre/(.*)(?:\?.*)"(?:\s+|)>(.*)</a>~Ui';

    const IMDB_ID = '~((?:tt\d{6,})|(?:itle\?\d{6,}))~';
    const IMDB_LANGUAGES = '~<a href="\/language\/(\w+)(\"|\?).*\n?.*>(\w+)<\/a~Ui';
    const IMDB_LOCATION = '~href="\/search\/title\?locations=(.*)">(.*)<\/a>~Ui';
    const IMDB_NAME = '~href="/name/nm(\d+)/(?:.*)"[ ]?itemprop=\'(?:\w+)\'><span class="itemprop" itemprop="name">(.*)</span>~Ui';
    const IMDB_PLOT = '~Storyline</h2>\s+<div class="inline canwrap" itemprop="description">\s+<p>(.*)(?:<em|<\/p>|<\/div>)~Ui';
    const IMDB_POSTER = '~"src="(.*)"itemprop="image" \/>~Ui';
    const IMDB_RATING = '~<span itemprop="ratingValue">(.*)</span>~Ui';
    const IMDB_REDIRECT = '~Location:\s(.*)~';
    const IMDB_RELEASE_DATE = '~Release Date:</h4>(.*)(?:<span|<\/div>)~Ui';
    
    const IMDB_RUNTIME      = '~Runtime:</h4>\s+<time itemprop="duration" datetime="(?:.*)">(.*)</time>~Uis';

    const IMDB_SEARCH = '~<td class="result_text"> <a href="\/title\/(tt\d{6,})\/(?:.*)"(?:\s*)>(?:.*)<\/a>~Ui';
    const IMDB_SEASONS = '~(?:episodes\?season=(\d+))~Ui';

    const IMDB_TITLE = '~meta name="title" content="(.*)(\s\(.*)?"~Ui';
    const IMDB_TITLE_ORIG = '~property=\'og:title\' content="(.*)(\s\(.*)?"~Ui';
    const IMDB_URL = '~http://(?:.*\.|.*)imdb.com/(?:t|T)itle(?:\?|/)(..\d+)~i';
    

    const IMDB_YEAR         = '~<title>.*\s\((\d{4})\)\s-\sIMDb<\/title>~Ui';


    const IMDB_WRITER = '~(?:Writer|Writers):</h4>(.*)</div>~Ui';
    const IMDB_TYPE = '~<div class="infobar">(.*)<~Ui';
    const IMDB_IS_RELEASED = '~<div class="star-box giga-star">(.*)</div>~Ui';
    
    /**
     * These are the regular expressions used to extract the data.
     * If you don’t know what you’re doing, you shouldn’t touch them.
     */   
    
    // cURL cookie file.
    private $_fCookie = false;
    // IMDb url.
    private $_strUrl = null;
    // IMDb source.
    private $_strSource = null;
    // IMDb cache.
    private $_strCache = 0;
    // IMDb movie id.
    private $_strId = false;
    // Movie found?
    public $isReady = false;
    // Define root of this script.
    private $_strRoot = '';
    // Current version.
    const IMDB_VERSION = '6.0.1';
    
    /**
     * IMDB constructor.
     *
     * @param string  $strSearch The movie name / IMDb url
     * @param integer $intCache  The maximum age (in minutes) of the cache (default 1 day)
     */
    public function __construct($strSearch, $intCache = 1440)
    {
        if (!$this->_strRoot) {
            $this->_strRoot = dirname(__FILE__);
        }
        // Posters and cache directory existant?
        if (!is_writable($this->_strRoot . '/posters/') && !mkdir($this->_strRoot . '/posters/')) {
            throw new IMDBException($this->_strRoot . '/posters/ is not writable!');
        }
        if (!is_writable($this->_strRoot . '/cache/') && !mkdir($this->_strRoot . '/cache/')) {
            throw new IMDBException($this->_strRoot . '/cache/ is not writable!');
        }
        // cURL.
        if (!function_exists('curl_init')) {
            throw new IMDBException('You need PHP with cURL enabled to use this script!');
        }
        // Debug only.
        if (IMDB::IMDB_DEBUG) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(-1);
            echo '<b>- Running:</b> IMDB::fetchUrl<br>';
        }
        // Set global cache and fetch the data.
        $this->_intCache = (int) $intCache;
        IMDB::fetchUrl($strSearch);
    }
    
    /**
     * Regular expressions helper function.
     *
     * @param string  $strContent The content to search in
     * @param string  $strRegex   The regular expression
     * @param integer $intIndex   The index to return
     * @return string The match found
     * @return array  The matches found
     */
    private function matchRegex($strContent, $strRegex, $intIndex = NULL)
    {
        $arrMatches = false;
        preg_match_all($strRegex, $strContent, $arrMatches);
        if ($arrMatches === false)
            return false;
        if ($intIndex != NULL && is_int($intIndex)) {
            if ($arrMatches[$intIndex]) {
                return $arrMatches[$intIndex][0];
            }
            return false;
        }
        return $arrMatches;
    }
    
    /**
     * @param string $sInput Input (eg. HTML).
     *
     * @return string Cleaned string.
     */
    private function cleanString($sInput) {
        $aSearch  = array(
            'Full summary &raquo;',
            'Full synopsis &raquo;',
            'Add summary &raquo;',
            'Add synopsis &raquo;',
            'See more &raquo;',
            'See why on IMDbPro.'
        );
        $aReplace = array(
            '',
            '',
            '',
            '',
            '',
            ''
        );
        $sInput   = strip_tags($sInput);
        $sInput   = str_replace('&nbsp;', ' ', $sInput);
        $sInput   = str_replace($aSearch, $aReplace, $sInput);
        $sInput   = html_entity_decode($sInput, ENT_QUOTES | ENT_HTML5);
        if (mb_substr($sInput, -3) === ' | ') {
            $sInput = mb_substr($sInput, 0, -3);
        }
        return trim($sInput);
    }

    
    /**
     * Returns a shortened text.
     *
     * @param string  $strText   The text to shorten
     * @param integer $intLength The new length of the text
     */
    public function getShortText($strText, $intLength = 100)
    {
        $strText = trim($strText) . ' ';
        $strText = substr($strText, 0, $intLength);
        $strText = substr($strText, 0, strrpos($strText, ' '));
        return $strText . '…';
    }
    
    /**
     * Fetch data from the given url.
     *
     * @param string  $strSearch The movie name / IMDb url
     * @param string  $strSave   The path to the file
     * @return boolean
     */
    private function fetchUrl($strSearch)
    {
        // Remove whitespaces.
        $strSearch = trim($strSearch);
        
        // "Remote Debug" - so I can see which version you're running.
        // To due people complaing about broken functions while they're
        // using old versions. Feel free to remove this.
        if ($strSearch == '##REMOTEDEBUG##') {
            $strSearch = 'http://www.imdb.com/title/tt1022603/';
            echo '<pre>Running PHP-IMDB-Grabber v' . IMDB::IMDB_VERSION . '.</pre>';
        }
        
        // Get the ID of the movie.
        $strId = IMDB::matchRegex($strSearch, IMDB::IMDB_URL, 1);
        if (!$strId) {
            $strId = IMDB::matchRegex($strSearch, IMDB::IMDB_ID, 1);
        }
        
        // Check if we found an ID ...
        if ($strId) {
            $this->_strId  = preg_replace('~[\D]~', '', $strId);
            $this->_strUrl = 'http://www.imdb.com/title/tt' . $this->_strId . '/';
            $bolFound      = false;
            $this->isReady = true;
        }
        
        // ... otherwise try to find one.
        else {
            $strSearchFor = 'all';
            if (strtolower(IMDB::IMDB_SEARCHFOR) == 'movie') {
                $strSearchFor = 'tt&ttype=ft&ref_=fn_ft';
            } elseif (strtolower(IMDB::IMDB_SEARCHFOR) == 'tvtitle') {
                $strSearchFor = 'tt&ttype=tv&ref_=fn_tv';
            } elseif (strtolower(IMDB::IMDB_SEARCHFOR) == 'tvepisode') {
                $strSearchFor = 'tt&ttype=ep&ref_=fn_ep';
            }
            
            $this->_strUrl = 'http://www.imdb.com/find?s=' . $strSearchFor . '&q=' . str_replace(' ', '+', $strSearch);
            $bolFound      = true;
            
            // Check for cached redirects of this search.
            $fRedirect = @file_get_contents($this->_strRoot . '/cache/' . md5($this->_strUrl) . '.redir');
            if ($fRedirect) {
                if (IMDB::IMDB_DEBUG) {
                    echo '<b>- Found an old redirect:</b> ' . $fRedirect . '<br>';
                }
                $this->_strUrl = trim($fRedirect);
                $this->_strId  = preg_replace('~[\D]~', '', IMDB::matchRegex($fRedirect, IMDB::IMDB_URL, 1));
                $this->isReady = true;
                $bolFound      = false;
            }
        }
        
        // Check if there is a cache we can use.
        $fCache = $this->_strRoot . '/cache/' . md5($this->_strId) . '.cache';
        if (file_exists($fCache)) {
            $bolUseCache = true;
            $intChanged  = filemtime($fCache);
            $intNow      = time();
            $intDiff     = round(abs($intNow - $intChanged) / 60);
            if ($intDiff > $this->_intCache) {
                $bolUseCache = false;
            }
        } else {
            $bolUseCache = false;
        }
        
        if ($bolUseCache) {
            if (IMDB::IMDB_DEBUG) {
                echo '<b>- Using cache for ' . $strSearch . ' from ' . $fCache . '</b><br>';
            }
            $this->_strSource = file_get_contents($fCache);
            return true;
        } else {
            // Cookie path.
            if (function_exists('sys_get_temp_dir')) {
                $this->_fCookie = tempnam(sys_get_temp_dir(), 'imdb');
                if (IMDB::IMDB_DEBUG) {
                    echo '<b>- Path to cookie:</b> ' . $this->_fCookie . '<br>';
                }
            }
            // Initialize and run the request.
            if (IMDB::IMDB_DEBUG) {
                echo '<b>- Run cURL on:</b> ' . $this->_strUrl . '<br>';
            }
            
            $arrInfo   = $this->doCurl($this->_strUrl);
            $strOutput = $arrInfo['contents'];
            
            // Check if the request actually worked.
            if ($strOutput === false) {
                if (IMDB::IMDB_DEBUG) {
                    echo '<b>! cURL error:</b> ' . $this->_strUrl . '<br>';
                }
                $this->_strSource = file_get_contents($fCache);
                if ($this->_strSource) {
                    return true;
                }
                return false;
            }
            
            // Check if there is a redirect given (IMDb sometimes does not return 301 for this...).
            $fRedirect = $this->_strRoot . '/cache/' . md5($this->_strUrl) . '.redir';
            if ($strMatch = $this->matchRegex($strOutput, IMDB::IMDB_REDIRECT, 1)) {
                $arrExplode = explode('?fr=', $strMatch);
                $strMatch   = ($arrExplode[0] ? $arrExplode[0] : $strMatch);
                if (IMDB::IMDB_DEBUG) {
                    echo '<b>- Saved a new redirect:</b> ' . $fRedirect . '<br>';
                }
                file_put_contents($fRedirect, $strMatch);
                $this->isReady = false;
                // Run the cURL request again with the new url.
                IMDB::fetchUrl($strMatch);
                return true;
            }
            // Check if any of the search regexes is matching.
            elseif ($strMatch = $this->matchRegex($strOutput, IMDB::IMDB_SEARCH, 1)) {
                $strMatch = 'http://www.imdb.com/title/tt' . $strMatch . '/';
                if (IMDB::IMDB_DEBUG) {
                    echo '<b>- Using the first search result:</b> ' . $strMatch . '<br>';
                    echo '<b>- Saved a new redirect:</b> ' . $fRedirect . '<br>';
                }
                file_put_contents($fRedirect, $strMatch);
                // Run the cURL request again with the new url.
                $this->_strSource = null;
                $this->isReady    = false;
                IMDB::fetchUrl($strMatch);
                return true;
            }
            // If it's not a redirect and the HTTP response is not 200 or 302, abort.
                elseif ($arrInfo['http_code'] != 200 && $arrInfo['http_code'] != 302) {
                if (IMDB::IMDB_DEBUG) {
                    echo '<b>- Wrong HTTP code received, aborting:</b> ' . $arrInfo['http_code'] . '<br>';
                }
                return false;
            }
            
            $this->_strSource = $strOutput;
            
            // Set the global source.
            $this->_strSource = preg_replace('~(\r|\n|\r\n)~', '', $this->_strSource);
            
            // Save cache.
            if (!$bolFound) {
                if (IMDB::IMDB_DEBUG) {
                    echo '<b>- Saved a new cache:</b> ' . $fCache . '<br>';
                }
                file_put_contents($fCache, $this->_strSource);
            }
            
            return true;
        }
        return false;
    }
    
    /**
     * Run a cURL request.
     *
     * @param str $strUrl             URL to run curl on.
     * @param bol $bolOverWriteSource Overwrite $this->_strSource?
     *
     * @return arr Array with cURL informations.
     */
    private function doCurl($strUrl, $bolOverWriteSource = true)
    {
        $oCurl = curl_init($strUrl);
        curl_setopt_array($oCurl, array(
            CURLOPT_VERBOSE => false,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array(
                'Accept-Language:' . IMDB::IMDB_LANG . ';q=0.5'
            ),
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => IMDB::IMDB_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_REFERER => 'http://www.google.com',
            CURLOPT_USERAGENT => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
            CURLOPT_FOLLOWLOCATION => false
            //CURLOPT_COOKIEFILE => $this->_fCookie
        ));
        $strOutput = curl_exec($oCurl);
        
        // Remove cookie.
        if ($this->_fCookie) {
            @unlink($this->_fCookie);
        }
        
        // Get returned information.
        $arrInfo = curl_getinfo($oCurl);
        curl_close($oCurl);
        
        $arrInfo['contents'] = $strOutput;
        
        if ($bolOverWriteSource) {
            $this->_strSource = $strOutput;
        }
        
        // If it's not a redirect and the HTTP response is not 200 or 302, abort.
        if ($arrInfo['http_code'] != 200 && $arrInfo['http_code'] != 302) {
            if (IMDB::IMDB_DEBUG) {
                echo '<b>- Wrong HTTP code received, aborting:</b> ' . $arrInfo['http_code'] . '<br>';
            }
            return false;
        }
        
        return $arrInfo;
    }
    
    
    /**
     * Returns the "also known as" name.
     *
     * @return string The aka name.
     */
    public function getAka()
    {
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSource, IMDB::IMDB_AKA, 1)) {
                return trim($strReturn);
            }
        }
        return $this->strNotFound;
    }
    
    /**
     * Returns all local names
     *
     * @return string The aka name.
     */
    public function getAkas()
    {
        if ($this->isReady) {
            $arrReturn = array();
            $fCache = $this->_strRoot . '/cache/' . md5($this->_strId) . '.akas';
            if (file_exists($fCache)) {
                $bolUseCache = true;
                $intChanged  = filemtime($fCache);
                $intNow      = time();
                $intDiff     = round(abs($intNow - $intChanged) / 60);
                if ($intDiff > $this->_intCache) {
                    $bolUseCache = false;
                }
            } else {
                $bolUseCache = false;
            }
            
            if ($bolUseCache) {
                if (IMDB::IMDB_DEBUG) {
                    echo '<b>- Using cache for Akas from ' . $fCache . '</b><br>';
                }
                $arrReturn = @file_get_contents($fCache);
                return unserialize($arrReturn);
            } else {
                $fullAkas = sprintf('http://www.imdb.com/title/tt%s/releaseinfo', $this->_strId);
                $arrInfo  = $this->doCurl($fullAkas, false);
                if (!$arrInfo) {
                    return $this->strNotFound;
                }
                $arrReturned = $this->matchRegex($arrInfo['contents'], "~<td>(.*?)<\/td>\s+<td>(.*?)<\/td>~", 0);
                if (isset($arrReturned[1]) && isset($arrReturned[2])) {
                    
                    foreach ($arrReturned[1] as $i => $strName) {
                        
                        if (strpos($strName, '(') === false) {
                            $arrReturn[] = array(
                                'title' => trim($arrReturned[2][$i]),
                                'country' => trim($strName)
                            );
                        }
                    }
                    
                    @file_put_contents($fCache, serialize($arrReturn));
                    return $arrReturn;
                }
            }
        }
        return $this->strNotFound;
    }
    
    
    
    
    
    /**
     * Returns the cast and character as URL .
     *
     * @return array The movie cast and character as URL (default limited to 20).
     */
    public function getCastAndCharacter($intLimit = 20)
    {
        if ($this->isReady) {
            $arrReturned = $this->matchRegex($this->_strSource, IMDB::IMDB_CAST);
            $arrChar     = $this->matchRegex($this->_strSource, IMDB::IMDB_CHAR);
            if (count($arrReturned[2])) {
                foreach ($arrReturned[2] as $i => $strName) {
                    if ($i >= $intLimit) {
                        break;
                    }
                    $arrChar[1][$i] = trim(preg_replace('~\((.*)\)~Ui', '', $arrChar[1][$i]));
                    preg_match_all('~<a href="/character/ch(\d+)/">(.*)</a>~Ui', $arrChar[1][$i], $arrMatches);
                    if (isset($arrMatches[1][0]) && isset($arrMatches[2][0])) {
                        $arrReturn[] = array(
                            'name' => trim($strName),
                            'imdb' => $arrReturned[1][$i],
                            'role' => trim($arrMatches[2][0])
                        );
                    } else {
                        if ($arrChar[1][$i]) {
                            $role        = preg_replace("/&#?[a-z0-9]{2,8};/i", "", $arrChar[1][$i]);
                            $arrReturn[] = array(
                                'name' => trim($strName),
                                'imdb' => $arrReturned[1][$i],
                                'role' => trim(strip_tags($role))
                            );
                        } else {
                            $arrReturn[] = array(
                                'name' => trim($strName),
                                'imdb' => $arrReturned[1][$i],
                                'role' => '-'
                            );
                        }
                    }
                }
                return $arrReturn;
            }
        }
        return $this->arrNotFound;
    }
    
    
    /**
     * Returns the companies.
     *
     * @return array The movie companies.
     */
    
    public function getCompany()
    {
        if ($this->isReady) {
            $strContainer = $this->matchRegex($this->_strSource, IMDB::IMDB_COMPANY, 1);
            $arrReturned  = $this->matchRegex($strContainer, IMDB::IMDB_COMPANY_NAME);
            if (count($arrReturned[2])) {
                foreach ($arrReturned[2] as $i => $strName) {
                    $company     = strip_tags($strName);
                    $arrReturn[] = array(
                        'imdb' => trim($arrReturned[1][$i]),
                        'name' => trim($company)
                    );
                }
                return $arrReturn;
            }
        }
        return $this->arrNotFound;
    }
    
    /**
     * Returns the countr(y|ies).
     *
     * @return array The movie countr(y|ies).
     */
    public function getCountry()
    {
        if ($this->isReady) {
            $arrReturned = $this->matchRegex($this->_strSource, IMDB::IMDB_COUNTRY);
            if (count($arrReturned[2])) {
                foreach ($arrReturned[2] as $strName) {
                    $arrReturn[] = trim($strName);
                }
                return $arrReturn;
            }
        }
        return $this->arrNotFound;
    }
    
    
    
    /**
     * Returns the description.
     *
     * @return string The movie description.
     */
    public function getDescription()
    {
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSource, IMDB::IMDB_DESCRIPTION, 1)) {
                $strReturn = trim($strReturn);
                if(empty($strReturn)){
                    return $this->strNotFound;
                }
                return $strReturn;
            }
        }
        return $this->strNotFound;
    }
    
    /**
     * Returns the director(s) as URL.
     *
     * @return array The movie director(s) as URL.
     */
    public function getDirector()
    {
        if ($this->isReady) {
            $strContainer = $this->matchRegex($this->_strSource, IMDB::IMDB_DIRECTOR, 1);
            $arrReturned  = $this->matchRegex($strContainer, IMDB::IMDB_NAME);
            if (count($arrReturned[2])) {
                foreach ($arrReturned[2] as $i => $strName) {
                    $arrReturn[] = array(
                        'imdb' => trim($arrReturned[1][$i]),
                        'name' => trim($strName)
                    );
                }
                return $arrReturn;
            }
        }
        return $this->arrNotFound;
    }
    
    /**
     * Returns the genre(s).
     *
     * @return array The movie genre(s).
     */
    public function getGenre()
    {
        if ($this->isReady) {
            $arrReturned = $this->matchRegex($this->_strSource, IMDB::IMDB_GENRE);
            if (count($arrReturned[1])) {
                foreach ($arrReturned[1] as $strName) {
                    if ($strName != "") {
                        $arrReturn[] = trim($strName);
                    }
                }
                return array_values(array_unique($arrReturn));
            }
        }
        return $this->arrNotFound;
    }
    
    
    /**
     * Returns the language(s).
     *
     * @return string The movie language(s).
     */
    public function getLanguages()
    {
        if ($this->isReady) {
            $arrReturned = $this->matchRegex($this->_strSource, IMDB::IMDB_LANGUAGES);
          
            if (count($arrReturned[3])) {
                foreach ($arrReturned[3] as $strName) {
                    $arrReturn[] = trim($strName);
                }
                return $arrReturn;
            }
        }
        return $this->arrNotFound;
    }
    
    /**
     * Returns the release date.
     *
     * @return string The movie release date.
     */
    public function getReleaseDate()
    {
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSource, IMDB::IMDB_RELEASE_DATE, 1)) {
                return str_replace('(', ' (', trim($strReturn));
            }
        }
        return $this->strNotFound;
    }
    
    /**
     * Returns the runtime.
     *
     * @return string The movie runtime.
     */
    public function getRuntime()
    {
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSource, IMDB::IMDB_RUNTIME, 1)) {
                return trim(intval($strReturn));
            }
        }
        return 0;
    }
    


    /**
     * Returns the seasons.
     *
     * @return string The movie seasons.
     */
    public function getSeasons()
    {
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSource, IMDB::IMDB_SEASONS)) {
                if (sizeof($strReturn[1]) > 0) {
                    $strReturn = strip_tags(implode($strReturn[1]));
                    $strFind   = array(
                        '&raquo;',
                        '&nbsp;',
                        'Full episode list',
                        ' '
                    );
                    $strReturn = str_replace($strFind, '', $strReturn);
                    $arrReturn = explode('|', $strReturn);
                    return sizeof($arrReturn);
                }
            }
        }
        return 0;
    }
    
    
    


    /**
     * @param bool $bForceLocal Try to return the original name of the movie.
     *
     * @return string The title of the movie or $sNotFound.
     */
    public function getTitle($bForceLocal = false) {
         if ($this->isReady) {
            if (true === $bForceLocal) {
                $sMatch = $this->matchRegex($this->_strSource, self::IMDB_TITLE_ORIG, 1);
                $sMatch = preg_replace('~\(\d{4}\)$~Ui', '', $sMatch);
                if (false !== $sMatch && "" !== $sMatch) {
                    return $this->cleanString($sMatch);
                }
            }

            $sMatch = $this->matchRegex($this->_strSource, self::IMDB_TITLE, 1);
            $sMatch = preg_replace('~\(\d{4}\)$~Ui', '', $sMatch);
            if (false !== $sMatch && "" !== $sMatch) {
                return $this->cleanString($sMatch);
            }
        }
        throw new IMDBException("Can't get title", 1);
        
        return $this->strNotFound;  //TODO EXCEPTION
    }


    /**
     * Returns the URL.
     *
     * @return string The movie URL.
     */
    public function getUrl()
    {
        if ($this->isReady) {
            return $this->_strUrl;
        }
        return $this->strNotFound;
    }
    
    
    /**
     * Returns the writer(s) as URL.
     *
     * @return array The movie writer(s) as URL.
     */
    public function getWriter()
    {
        if ($this->isReady) {
            $strContainer = $this->matchRegex($this->_strSource, IMDB::IMDB_WRITER, 1);
            $arrReturned  = $this->matchRegex($strContainer, IMDB::IMDB_NAME);
            if (count($arrReturned[2])) {
                foreach ($arrReturned[2] as $i => $strName) {
                    $arrReturn[] = array(
                        'imdb' => trim($arrReturned[1][$i]),
                        'name' => trim($strName)
                    );
                }
                return $arrReturn;
            }
        }
        return $this->arrNotFound;
    }
    
    /**
     * Returns the movie year.
     *
     * @return string The year of the movie.
     */
    public function getYear()
    {
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSource, IMDB::IMDB_YEAR, 1)) {
                return intval($strReturn);
            }
        }
        return 0;
    }


    
    /**
     * Returns the type of the imdb media
     *
     * @return string Type of the imdb media
     */
    public function getType()
    {
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSource, IMDB::IMDB_TYPE, 1)) {
                // some cases there's no info in that place
                if (is_string($strReturn)) {
                    $strReturn = str_replace("&nbsp;-&nbsp;", '', $strReturn);
                    $type = trim($strReturn, " ");
                    if (empty($type)){
                        return "movie";
                    }
                    else {
                        return $type;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Release date doesn't contain all the information we need to create a media and 
     * we need this function that checks if users can vote target media (if can, it's released).
     *
     * @return  true If the media is released (users can vote)
     */
    public function isReleased()
    {
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSource, IMDB::IMDB_IS_RELEASED, 1)) {
                // removing the html tags and spaces
                $strReturn = trim(strip_tags($strReturn));
                // expected this string if is not released, in other cases will get voting results
                if (isset($strReturn) && $strReturn == 'Not yet released') {
                    return false;
                }
            }
        }
        return true;
    }
    
    
}