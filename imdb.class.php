<?php
/**
 * PHP IMDb.com Grabber
 *
 * This PHP library enables you to scrap data from IMDB.com.
 *
 *
 * If you want to thank me for this library, please buy me something at Amazon:
 * http://www.amazon.de/gp/registry/wishlist/8840JITISN9L/ - thank you in
 * advance! :)
 *
 *
 * @author  Fabian Beiner <fb@fabianbeiner.de>
 * @license http://opensource.org/licenses/MIT The MIT License
 * @link    https://github.com/FabianBeiner/PHP-IMDB-Grabber GitHub Repository
 * @version 6.0.0
 */

class IMDB {
    /**
     * Set this to true if you run into problems.
     */
    const IMDB_DEBUG = false;

    /**
     * Set the prefered language for the User Agent.
     */
    const IMDB_LANG = 'en, en-US;q=0.8';

    /**
     * Define the timeout for cURL requests.
     */
    const IMDB_TIMEOUT = 15;

    /**
     * @var int Maximum cache time.
     */
    private $iCache = 1440;

    /**
     * @var null|string The root of the script.
     */
    private $sRoot = null;

    /**
     * @var null|string Holds the source.
     */
    private $sSource = null;

    /**
     * @var null|int The ID of the movie.
     */
    private $iId = null;

    /**
     * @var string What to search for?
     */
    private $sSearchFor = 'all';

    /**
     * @var bool Is the content ready?
     */
    public $isReady = false;

    /**
     * @var string The string returned, if nothing is found.
     */
    public $sNotFound = 'n/A';

    /**
     * @var string Char that separates multiple entries.
     */
    public $sSeparator = ' / ';

    /**
     * @var null|string The URL to the movie.
     */
    public $sUrl = null;

    /**
     * @var bool Return reponses eclosed in array
     */
    public $bArrayOutput = false;

    /**
     * These are the regular expressions used to extract the data.
     * If you don’t know what you’re doing, you shouldn’t touch them.
     */
    const IMDB_AKA           = '~<h5>Also Known As:<\/h5>(?:\s*)<div class="info-content">(?:\s*)"(.*)"~Ui';
    const IMDB_CAST          = '~<td class="nm"><a href="\/name\/(.*)\/"(?:.*)>(.*)<\/a><\/td>~Ui';
    const IMDB_COMPANY       = '~<h5>Company:<\/h5>(?:\s*)<div class="info-content"><a href="\/company\/(.*)\/">(.*)</a>(?:.*)<\/div>~Ui';
    const IMDB_COUNTRY       = '~<a href="/country/(\w+)">(.*)</a>~Ui';
    const IMDB_DIRECTOR      = '~<h5>(?:Director|Directors):<\/h5>(?:\s*)<div class="info-content">(.*)<\/div>~Ui';
    const IMDB_GENRE         = '~<a href="\/Sections\/Genres\/(\w+)\/">(.*)<\/a>~Ui';
    const IMDB_ID            = '~((?:tt\d{6,})|(?:itle\?\d{6,}))~';
    const IMDB_LANGUAGE      = '~<a href="\/language\/(\w+)">(.*)<\/a>~Ui';
    const IMDB_LOCATION      = '~href="\/search\/title\?locations=(.*)">(.*)<\/a>~Ui';
    const IMDB_NAME          = '~href="\/name\/(.*)\/"(?:.*)>(.*)<\/a>~Ui';
    const IMDB_NOT_FOUND     = '~<h1 class="findHeader">No results found for ~Ui';
    const IMDB_RELEASE_DATE  = '~<h5>Release Date:<\/h5>(?:\s*)<div class="info-content">(.*)<\/div>~Ui';
    const IMDB_RUNTIME       = '~<h5>Runtime:<\/h5>(?:\s*)<div class="info-content">(.*)<\/div>~Ui';
    const IMDB_SEARCH        = '~<td class="result_text"> <a href="\/title\/(tt\d{6,})\/(?:.*)"(?:\s*)>(?:.*)<\/a>~Ui';
    const IMDB_SEASONS       = '~(?:episodes\?season=(\d+))~Ui';
    const IMDB_TITLE         = '~property="og:title" content="(.*)"~Ui';
    const IMDB_TITLE_ORIG    = '~<span class="title-extra">(.*) <i>\(original title\)<\/i></span>~Ui';
    const IMDB_TRAILER       = '~data-video="(.*)"~Ui';
    const IMDB_URL           = '~http://(?:.*\.|.*)imdb.com/(?:t|T)itle(?:\?|/)(..\d+)~i';
    const IMDB_VOTES         = '~<a href="ratings" class="tn15more">(.*) votes<\/a>~Ui';
    const IMDB_WRITER        = '~<h5>(?:Writer|Writers):<\/h5>(?:\s*)<div class="info-content">(.*)<\/div>~Ui';
    const IMDB_YEAR          = '~<a href="\/year\/(?:\d{4})\/">(.*)<\/a>~Ui';
    const IMDB_MEDIA_TYPE    = '~<div class="infobar">(.*)<~Ui';
    const IMDB_DESCRIPTION  = '~<p itemprop="description">(.*)(?:<a|<\/p>)~Ui';




    /**
     * @param string $sSearch IMDb URL or movie title to search for.
     * @param null   $iCache  Custom cache time in minutes.
     *
     * @throws \IMDBException
     */
    public function __construct($sSearch, $iCache = null, $sSearchFor = 'all') {
        $this->sRoot = dirname(__FILE__);
        if (!is_writable($this->sRoot . '/posters') && !mkdir($this->sRoot . '/posters')) {
            throw new IMDBException('The directory “' . $this->sRoot . '/posters” isn’t writable.');
        }
        if (!is_writable($this->sRoot . '/cache') && !mkdir($this->sRoot . '/cache')) {
            throw new IMDBException('The directory “' . $this->sRoot . '/cache” isn’t writable.');
        }
        if (!function_exists('curl_init')) {
            throw new IMDBException('You need to enable the PHP cURL extension.');
        }
        if (in_array($sSearchFor, array(
            'movie',
            'tv',
            'episode',
            'game',
            'all'
        ))) {
            $this->sSearchFor = $sSearchFor;
        }
        if (true === self::IMDB_DEBUG) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(-1);
            echo '<pre><b>Running:</b> fetchUrl("' . $sSearch . '")</pre>';
        }
        if (null !== $iCache && (int)$iCache > 0) {
            $this->iCache = (int)$iCache;
        }
        $this->fetchUrl($sSearch);
    }

    /**
     * @param string $sSearch IMDb URL or movie title to search for.
     *
     * @return bool True on success, false on failure.
     */
    private function fetchUrl($sSearch) {
        $sSearch = trim($sSearch);

        // Try to find a valid URL.
        $sId = IMDBHelper::matchRegex($sSearch, self::IMDB_ID, 1);
        if (false !== $sId) {
            $this->iId  = preg_replace('~[\D]~', '', $sId);
            $this->sUrl = 'http://www.imdb.com/title/tt' . $this->iId . '/combined';
            $bSearch    = false;
        }
        else {
            switch (strtolower($this->sSearchFor)) {
                case 'movie':
                    $sParameters = '&s=tt&ttype=ft';
                    break;
                case 'tv':
                    $sParameters = '&s=tt&ttype=tv';
                    break;
                case 'episode':
                    $sParameters = '&s=tt&ttype=ep';
                    break;
                case 'game':
                    $sParameters = '&s=tt&ttype=vg';
                    break;
                default:
                    $sParameters = '&s=tt';
            }

            $this->sUrl = 'http://www.imdb.com/find?q=' . str_replace(' ', '+', $sSearch) . $sParameters;
            $bSearch    = true;

            // Was this search already performed and cached?
            $sRedirectFile = $this->sRoot . '/cache/' . md5($this->sUrl) . '.redir';
            if (is_readable($sRedirectFile)) {
                if (self::IMDB_DEBUG) {
                    echo '<pre><b>Using redirect:</b> ' . basename($sRedirectFile) . '</pre>';
                }
                $sRedirect  = file_get_contents($sRedirectFile);
                $this->sUrl = trim($sRedirect);
                $this->iId  = preg_replace('~[\D]~', '', IMDBHelper::matchRegex($sRedirect, self::IMDB_ID, 1));
                $bSearch    = false;
            }
        }

        // Does a cache of this movie exist?
        $sCacheFile = $this->sRoot . '/cache/' . md5($this->iId) . '.cache';
        if (is_readable($sCacheFile)) {
            $iDiff = round(abs(time() - filemtime($sCacheFile)) / 60);
            if ($iDiff < $this->iCache) {
                if (true === self::IMDB_DEBUG) {
                    echo '<pre><b>Using cache:</b> ' . basename($sCacheFile) . '</pre>';
                }
                $this->sSource = file_get_contents($sCacheFile);
                $this->isReady = true;

                return true;
            }
        }

        // Run cURL on the URL.
        if (true === self::IMDB_DEBUG) {
            echo '<pre><b>Running cURL:</b> ' . $this->sUrl . '</pre>';
        }

        $aCurlInfo = IMDBHelper::runCurl($this->sUrl);
        $sSource   = $aCurlInfo['contents'];

        if (false === $sSource) {
            if (true === self::IMDB_DEBUG) {
                echo '<pre><b>cURL error:</b> ' . var_dump($aCurlInfo) . '</pre>';
            }

            return false;
        }

        // Was the movie found?
        $sMatch = IMDBHelper::matchRegex($sSource, self::IMDB_SEARCH, 1);
        if (false !== $sMatch) {
            $sUrl = 'http://www.imdb.com/title/' . $sMatch . '/combined';
            if (true === self::IMDB_DEBUG) {
                echo '<pre><b>New redirect saved:</b> ' . basename($sRedirectFile) . ' => ' . $sUrl . '</pre>';
            }
            file_put_contents($sRedirectFile, $sUrl);
            $this->sSource = null;
            self::fetchUrl($sUrl);

            return true;
        }

        $sMatch = IMDBHelper::matchRegex($sSource, self::IMDB_NOT_FOUND, 0);
        if (false !== $sMatch) {
            if (true === self::IMDB_DEBUG) {
                echo '<pre><b>Movie not found:</b> ' . $sSearch . '</pre>';
            }

            return false;
        }

        $this->sSource = str_replace(array(
                                         "\n",
                                         "\r\n",
                                         "\r"
                                     ), '', $sSource);
        $this->isReady = true;

        // Save cache.
        if (false === $bSearch) {
            if (true === self::IMDB_DEBUG) {
                echo '<pre><b>Cache created:</b> ' . basename($sCacheFile) . '</pre>';
            }
            file_put_contents($sCacheFile, $this->sSource);
        }

        return true;
    }

    /**
     * @return string “Also Known As” or $sNotFound.
     */
    public function getAka() {
        if (true === $this->isReady) {
            $sMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_AKA, 1);
            if (false !== $sMatch) {
                return IMDBHelper::cleanString($sMatch);
            }
        }

        return $this->sNotFound;
    }


    /**
     * Returns all local names
     *
     * @return string The aka name.
     */
    public function getAkas() {


        if (true === $this->isReady) {
           // Does a cache of this movie exist?
            $sCacheFile = $this->sRoot . '/cache/' . md5($this->iId) . '_akas.cache';
            $bUseCache = false;


            if (is_readable($sCacheFile)) {
        
                $iDiff = round(abs(time() - filemtime($sCacheFile)) / 60);
                if ($iDiff < $this->iCache || false) {
                    if (true === self::IMDB_DEBUG) {
                        echo '<pre><b>Using cache:</b> ' . basename($sCacheFile) . '</pre>';
                    }
                    $bUseCache = true;
                    $sSource = file_get_contents($sCacheFile);
                }
            }

            if ($bUseCache) {
                if (IMDB::IMDB_DEBUG) {
                    echo '<b>- Using cache for Akas from ' . $sCacheFile . '</b><br>';
                }
                $aRawReturn = file_get_contents($sCacheFile);
                $aReturn = unserialize($aRawReturn);
                return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound, $aReturn);

            } else {
                $fullAkas = sprintf('http://www.imdb.com/title/tt%s/releaseinfo', $this->iId);
                $aCurlInfo = IMDBHelper::runCurl($fullAkas);
                $sSource   = $aCurlInfo['contents'];

                if (false === $sSource) {
                    if (true === self::IMDB_DEBUG) {
                        echo '<pre><b>cURL error:</b> ' . var_dump($aCurlInfo) . '</pre>';
                    }

                    return false;
                }

                $aReturned = IMDBHelper::matchRegex($sSource, "~<td>(.*?)<\/td>\s+<td>(.*?)<\/td>~");

                if ($aReturned) {
                    $aReturn = array();
                    foreach ($aReturned[1] as $i => $strName) {
                          if (strpos($strName,'(')===false){
                            $aReturn[] = array('title'=>IMDBHelper::cleanString($aReturned[2][$i]), 'country'=> IMDBHelper::cleanString($strName));
                        }
                    }

                    file_put_contents($sCacheFile, serialize($aReturn));
                    return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound, $aReturn);
                }
            }
        }


        return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound);
    }


    
    /**
    * Returns the type of the imdb media
    *
    * @return string Type of the imdb media
    */
    public function getType() {
        if (true === $this->isReady) {
            if ($sMatch = IMDBHelper::matchRegex($this->sSource, IMDB::IMDB_MEDIA_TYPE, 1)) {
            // some cases there's no info in that place
                if (is_string($sMatch)) {
                    // if we use onle trim, it strips useful characters
                    $sMatch = str_replace("&nbsp;-&nbsp;", '', $sMatch);
                    return trim($sMatch, " ");
                }
            }
        }
        return false;
    }


    /**
     * @param int    $iLimit  How many cast members should be returned?
     * @param bool   $bMore   Add … if there are more cast members than printed.
     * @param string $sTarget Add a target to the links?
     *
     * @return string A list with linked cast members or $sNotFound.
     */
    public function getCast($iLimit = 0, $bMore = true) {
        if (true === $this->isReady) {
            $aMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_CAST);
            if (count($aMatch[2])) {
                foreach ($aMatch[2] as $i => $sName) {
                    if (0 !== $iLimit && $i >= $iLimit) {
                        break;
                    }
                    $aReturn[] = IMDBHelper::cleanString($sName);
                }

                $bHaveMore = ($bMore && (count($aMatch[2]) > $iLimit));
                return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound, $aReturn, $bHaveMore);
            }
        }

        return $this->sNotFound;
    }



    /**
     * Returns the cast and character as URL .
     *
     * @return array The movie cast and character as URL (default limited to 20).
     */
    public function getCastAndCharacter($intLimit = 20) {
        if (true === $this->isReady) {
            $arrReturned = $this->matchRegex($this->sSource, IMDB::IMDB_CAST);
            $arrChar     = $this->matchRegex($this->sSource, IMDB::IMDB_CHAR);
            if (count($arrReturned[2])) {
                foreach ($arrReturned[2] as $i => $strName) {
                    if ($i >= $intLimit) {
                        break;
                    }
                    $arrChar[1][$i] = trim(preg_replace('~\((.*)\)~Ui', '', $arrChar[1][$i]));
                    preg_match_all('~<a href="/character/ch(\d+)/">(.*)</a>~Ui', $arrChar[1][$i], $arrMatches);
                    if (isset($arrMatches[1][0]) && isset($arrMatches[2][0])) {
                        $arrReturn[] = array('name'=> trim($strName),'imdb'=>$arrReturned[1][$i],'role'=>  IMDBHelper::cleanString($arrMatches[2][0]) );
                    } else {
                        if ($arrChar[1][$i]) {
                            $role = preg_replace("/&#?[a-z0-9]{2,8};/i","",$arrChar[1][$i]);
                            $aReturn[] = array('name'=> IMDBHelper::cleanString($strName),'imdb'=>$arrReturned[1][$i],'role'=> IMDBHelper::cleanString($role));
                        } else {
                            $aReturn[] = array('name'=> IMDBHelper::cleanString($strName),'imdb'=>$arrReturned[1][$i],'role'=> '-' );
                        }
                    }
                }
                return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound, $aReturn);
            }
        }
        return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound);
    }  


    /**
     * @param string $sTarget Add a target to the links?
     *
     * @return string The linked company producing the movie or $sNotFound.
     */
    public function getCompany() {
        if (true === $this->isReady) {
            $aMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_COMPANY);
            if (isset($aMatch[2][0])) {
                return IMDBHelper::cleanString($aMatch[2][0]);
            }
        }

        return $this->sNotFound;
    }

    
    /**
     * @param string $sTarget Add a target to the links?
     *
     * @return string A list with linked countries or $sNotFound.
     */
    public function getCountry() {
        if (true === $this->isReady) {
            $aMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_COUNTRY);
            if (count($aMatch[2])) {
                foreach ($aMatch[2] as $i => $sName) {
                    $aReturn[] = IMDBHelper::cleanString($sName);
                }

                return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound, $aReturn);
            }
        }

        return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound);
    }


   
    /**
     * @param string $sTarget Add a target to the links?
     *
     * @return string A list with the linked directors or $sNotFound.
     */
    public function getDirector() {
        if (true === $this->isReady) {
            $sMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_DIRECTOR, 1);
            $aMatch = IMDBHelper::matchRegex($sMatch, self::IMDB_NAME);
            if (count($aMatch[2])) {
                foreach ($aMatch[2] as $i => $sName) {
                    $aReturn[] = IMDBHelper::cleanString($sName);
                }

                return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound, $aReturn);
            }
        }

        return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound);
    }



    /**
     * @param string $sTarget Add a target to the links?
     *
     * @return string A list with the linked genres or $sNotFound.
     */
    public function getGenre() {
        if (true === $this->isReady) {
            $aMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_GENRE);
            if (count($aMatch[2])) {
                foreach ($aMatch[2] as $i => $sName) {
                    $aReturn[] = IMDBHelper::cleanString($sName);
                }

                return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound, $aReturn);
            }
        }

        return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound);
    }

   
   
    /**
     * @return string The release date of the movie or $sNotFound.
     */
    public function getReleaseDate() {
        if (true === $this->isReady) {
            $sMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_RELEASE_DATE, 1);
            if (false !== $sMatch) {
                return IMDBHelper::cleanString($sMatch);
            }
        }

        return $this->sNotFound;
    }


    /**
    * Release date doesn't contain all the information we need to create a media and 
    * we need this function that checks if users can vote target media (if can, it's released).
    *
    * @return  true If the media is released
    */
    public function isReleased() {
        $strReturn = $this->getReleaseDate();
        if ($strReturn == $this->sNotFound || $strReturn == 'Not yet released') {
            return false;
        }
        return true;
    }


    /**
     * @return string The runtime of the movie or $sNotFound.
     */
    public function getRuntime() {
        if (true === $this->isReady) {
            $sMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_RUNTIME, 1);
            if (false !== $sMatch) {
                return IMDBHelper::cleanString($sMatch);
            }
        }

        return $this->sNotFound;
    }

    /**
     * @return string A list with the seasons or $sNotFound.
     */
    public function getSeasonsCount() {
        if (true === $this->isReady) {
            $sMatch = $this->getSeasons();
            if (is_array($sMatch)){
                return sizeof($sMatch);
            }
            else if ($this->sNotFound !== $sMatch) {
                return sizeof( explode($this->sSeparator,IMDBHelper::cleanString($sMatch));
            }
        }

        return $this->sNotFound;
    }

    /**
     * @param string $sTarget Add a target to the links?
     *
     * @return string A list with the linked seasons or $sNotFound.
     */
    public function getSeasons() {
        if (true === $this->isReady) {
            $aMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_SEASONS);
            if (count($aMatch[1])) {
                foreach ($aMatch[1] as $i => $sName) {
                    $aReturn[] =  $sName;
                }

                return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound, $aReturn);
            }
        }

        return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound);
    }

    /**
     * @return string The sound mix of the movie or $sNotFound.
     */
    public function getSoundMix() {
        if (true === $this->isReady) {
            $sMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_SOUND_MIX, 1);
            if (false !== $sMatch) {
                return IMDBHelper::cleanString($sMatch);
            }
        }

        return $this->sNotFound;
    }

  
    /**
     * @param bool $bForceLocal Try to return the original name of the movie.
     *
     * @return string The title of the movie or $sNotFound.
     */
    public function getTitle($bForceLocal = false) {
        if (true === $this->isReady) {
            if (true === $bForceLocal) {
                $sMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_TITLE_ORIG, 1);
                if (false !== $sMatch && "" !== $sMatch) {
                    return IMDBHelper::cleanString($sMatch);
                }
            }

            $sMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_TITLE, 1);
            $sMatch = preg_replace('~\(\d{4}\)$~Ui', '', $sMatch);
            if (false !== $sMatch && "" !== $sMatch) {
                return IMDBHelper::cleanString($sMatch);
            }
        }

        return $this->sNotFound;
    }

  

    /**
     * @return string The IMDb URL.
     */
    public function getUrl() {
        if (true === $this->isReady) {
            return IMDBHelper::cleanString(str_replace('combined', '', $this->sUrl));
        }

        return $this->sNotFound;
    }

  
    /**
     * @param string $sTarget Add a target to the links?
     *
     * @return string A list with the linked writers or $sNotFound.
     */
    public function getWriter() {
        if (true === $this->isReady) {
            $sMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_WRITER, 1);
            $aMatch = IMDBHelper::matchRegex($sMatch, self::IMDB_NAME);
            if (count($aMatch[2])) {
                foreach ($aMatch[2] as $i => $sName) {
                    $aReturn[] = IMDBHelper::cleanString($sName);
                }

                return IMDBHelper::arrayOutput($this->bArrayOutput, $this->sSeparator, $this->sNotFound, $aReturn);
            }
        }

        return $this->sNotFound;
    }


    /**
     * Returns the description.
     *
     * @return string The movie description.
     */
    public function getDescription() {
       if (true === $this->isReady) 
            if ($sMatch = $this->matchRegex($this->sSource, IMDB::IMDB_DESCRIPTION, 1)) {
                return trim($sMatch);
            }
        }
        return $this->strNotFound;
    }

    /**
     * @return string The year of the movie or $sNotFound.
     */
    public function getYear() {
        if (true === $this->isReady) {
            $sMatch = IMDBHelper::matchRegex($this->sSource, self::IMDB_YEAR, 1);
            if (false !== $sMatch) {
                return IMDBHelper::cleanString($sMatch);
            }
        }

        return $this->sNotFound;
    }

}

class IMDBHelper extends IMDB {
    /**
     * Regular expression helper.
     *
     * @param string $sContent The content to search in.
     * @param string $sPattern The regular expression.
     * @param null   $iIndex   The index to return.
     *
     * @return bool   If no match was found.
     * @return string If one match was found.
     * @return array  If more than one match was found.
     */
    public static function matchRegex($sContent, $sPattern, $iIndex = null) {
        preg_match_all($sPattern, $sContent, $aMatches);
        if ($aMatches === false) {
            return false;
        }
        if ($iIndex !== null && is_int($iIndex)) {
            if (isset($aMatches[$iIndex][0])) {
                return $aMatches[$iIndex][0];
            }

            return false;
        }

        return $aMatches;
    }

    /**
     * Prefered output in responses with multiple elements
     *
     * @param bool $bArrayOutput Native array or string wtih separators.
     * @param string $sSeparator String separator.
     * @param string $sNotFound Not found text.
     * @param array $aReturn Original input.
     * @param bool $bHaveMore Have more elements indicator.
     *
     * @return string Multiple results separeted by selected separator string.
     * @return array  Multiple results enclosed into native array.     
     */
    public static function arrayOutput($bArrayOutput, $sSeparator, $sNotFound, $aReturn = null, $bHaveMore = false) {
        if ($bArrayOutput){
          if ($aReturn == null || !is_array($aReturn)) {
              return array();
          }

          if ($bHaveMore) {
            $aReturn[] = '…';
          }

          return $aReturn;
        }
        else {
          if ($aReturn == null || !is_array($aReturn)) {
              return $sNotFound;
          }

          foreach ($aReturn as $i => $value) {
            if (is_array($value)) {
                $aReturn[$i] = implode($sSeparator, $value);
            }
          }
          
          return implode($sSeparator, $aReturn) . (($bHaveMore) ? '…' : '');
        }

    }


    /**
     * @param string $sInput Input (eg. HTML).
     *
     * @return string Cleaned string.
     */
    public static function cleanString($sInput) {
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
     * @param string $sText   The long text.
     * @param int    $iLength The maximum length of the text.
     *
     * @return string The shortened text.
     */
    public static function getShortText($sText, $iLength = 100) {
        if (mb_strlen($sText) <= $iLength) {
            return $sText;
        }

        list($sShort) = explode("\n", wordwrap($sText, $iLength - 1));

        if (substr($sShort, -1) !== '.') {
            return $sShort . '…';
        }

        return $sShort;
    }

    /**
     * @param string $sUrl      The URL to fetch.
     * @param bool   $bDownload Download?
     *
     * @return bool|mixed Array on success, false on failure.
     */
    public static function runCurl($sUrl, $bDownload = false) {
        $oCurl = curl_init($sUrl);
        curl_setopt_array($oCurl, array(
            CURLOPT_BINARYTRANSFER => ($bDownload ? true : false),
            CURLOPT_CONNECTTIMEOUT => self::IMDB_TIMEOUT,
            CURLOPT_ENCODING       => '',
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_HEADER         => ($bDownload ? false : true),
            CURLOPT_HTTPHEADER     => array(
                'Accept-Language:' . self::IMDB_LANG,
                'Accept-Charset:' . 'utf-8, iso-8859-1;q=0.8',
            ),
            CURLOPT_REFERER        => 'http://www.google.com',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::IMDB_TIMEOUT,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            CURLOPT_VERBOSE        => false
        ));
        $sOutput   = curl_exec($oCurl);
        $aCurlInfo = curl_getinfo($oCurl);
        curl_close($oCurl);
        $aCurlInfo['contents'] = $sOutput;

        if (200 !== $aCurlInfo['http_code'] && 302 !== $aCurlInfo['http_code']) {
            if (true === self::IMDB_DEBUG) {
                echo '<pre><b>cURL returned wrong HTTP code “' . $aCurlInfo['http_code'] . '”, aborting.</b></pre>';
            }

            return false;
        }

        return $aCurlInfo;
    }

    /**
     * @param $sUrl The URL to the image to download.
     * @param $iId  The ID of the movie.
     *
     * @return string Local path.
     */
    public static function saveImage($sUrl, $iId) {
        if (preg_match('~title_addposter.jpg|imdb-share-logo.png~', $sUrl)) {
            return 'posters/not-found.jpg';
        }

        $sFilename = dirname(__FILE__) . '/posters/' . $iId . '.jpg';
        if (file_exists($sFilename)) {
            return 'posters/' . $iId . '.jpg';
        }

        $aCurlInfo = self::runCurl($sUrl, true);
        $sData     = $aCurlInfo['contents'];
        if (false === $sData) {
            return 'posters/not-found.jpg';
        }

        $oFile = fopen($sFilename, 'x');
        fwrite($oFile, $sData);
        fclose($oFile);

        return 'posters/' . $iId . '.jpg';
    }
}

class IMDBException extends Exception {
}
