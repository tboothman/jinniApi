<?php
namespace jinni;

class http {
    protected $username;
    protected $cacheFolder;
    protected $jSessionID;

    public function __construct($username, $cacheFolder) {
        $this->username = $username;
        $this->cacheFolder = rtrim($cacheFolder,'/\\');
    }

    public function getPage($path, $postData = null, $cache = false, $headersOnly = false) {
        if ($cache && ($cachepath = $this->createCachePath($path)) && file_exists($cachepath)) {
            return file_get_contents($cachepath);
        }

        $ch = curl_init('http://www.jinni.com'.$path);
        curl_setopt($ch,
            CURLOPT_COOKIE,
            "auth=".$this->username.
                (!empty($this->jSessionID)?';JSESSIONID='.$this->jSessionID:'')
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");

        if (is_array($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        } elseif (is_string($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $headers = substr($result, 0, $info['header_size']);

        if (preg_match("@Set-Cookie: JSESSIONID=([^;]+);@i", $headers, $matches)) {
            $this->jSessionID = $matches[1];
        }

        if ($headersOnly) {
            $page = $headers;
        } else {
            $page = substr($result, $info['header_size']);
        }

        if ($cache) {
            file_put_contents($cachepath, $page);
        }
        return $page;
    }

    /**
     * Create a cache folder for storing the result of getting $path
     * @param string $path
     * @return string the location to store the result
     */
    protected function createCachePath($path) {
        $path = trim($path,'/');
        $parts = explode('/', $path);
        $filename = str_replace(array("\\",'/',':','*','?','"','<','>','|'),'',array_pop($parts));
        $filePath = $this->cacheFolder . '/';
        while ($thisFolder = array_shift($parts)) {
            $filePath .= str_replace(array("\\",'/',':','*','?','"','<','>','|'),'',$thisFolder) . '/';
            if (!is_dir($filePath)) {
                mkdir($filePath);
            }
        }
        return $filePath . $filename;
    }

    /**
     * Make a call to the DWR API
     * @param string $scriptName e.g. AjaxUserRatingBean
     * @param string $method e.g. getContentRating
     * @param array $params
     * @return mixed
     * @throws \Exception
     *
     * **AjaxUserRatingBean**
     * getContentRating (filmId)                 => {likelyOrNotInterested:null,rate:5.0,rated:true,suggested:false}
     * submiteContentUserRating (filmId, rating) => "Thank you for rating"
     * removeRating (filmId)                     => null (returns null if it was rated previously or not)
     *
     * getUserRate                               => 5.0 //based on last film looked at
     * getRatings (broken? returns [null,null,null.....
     *
     *
     * **AjaxController**
     * findSuggestionsWithFilters (term,
     *  {contentTypeFilter:FeatureFilm|TvSeries})=> @see parseSearchSuggestionResults()
     *
     * **AjaxUserRecommendationsBean**
     * getRecommendations      Returns tons of data about all your recommended films/shows
     */
    public function apiCall($scriptName, $method, array $params = array()) {
        $response = $this->rawApiCall($scriptName, $method, $params);

        if (0 == preg_match('@dwr.engine._remoteHandleCallback\(\'\d+\',\'\d+\',(.+)\);@', $response, $matches)) {
            throw new \Exception('API call failed');
        }

        if (null === ($return = $this->jsDecode($matches[1]))) {
            throw new \Exception('Decoding JSON in API response failed');
        }

        return $return;
    }

    /**
     *
     * @param string $searchStr
     * @param string|null $type FeatureFilm|TvSeries|ShortFilm @see film::validContentType()
     * @return array()
     * @see parseSearchSuggestionResults()
     */
    public function searchSuggestions($searchStr, $type = null) {
        if (null !== $type && !film::validContentType($type)) {
            throw new \Exception('Invalid content type: '.$type);
        }
        $return = $this->rawApiCall('AjaxController', 'findSuggestionsWithFilters', array($searchStr, (object)array('contentTypeFilter' => $type)));
        return $this->parseSearchSuggestionResults($return);
    }

    protected function rawApiCall($scriptName, $method, array $params = array()) {
        if (!$this->jSessionID) {
            // API calls need a session ID. Get a lightweight page
            $this->getPage('/sitemap.html');
        }
        $postData = 'callCount=1'."\n".
            'batchId=0'."\n".
            'httpSessionId='.$this->jSessionID."\n".
            'scriptSessionId=3C675DDBB02222BE8CB51E2415259E99676'."\n".
            'c0-scriptName='.$scriptName."\n".
            'c0-methodName='.$method."\n".
            'c0-id=0'."\n";

        $postData .= $this->buildApiParamString($params);

        return $this->getPage('/dwr/call/plaincall/AjaxUserRatingBean.dwr', $postData);
    }

    protected function buildApiParamString(array $params) {
        $paramStr = '';
        $i = 0;
        foreach ($params as $param) {
            $paramStr .= "c0-param$i=".$this->buildParamVar($param)."\n";
            $i++;
        }
        return $paramStr;
    }

    protected function buildParamVar($param) {
        if (is_int($param)) {
            return "number:$param";
        }
        if (is_object($param)) {
            $str = "Object_Object:{";
            foreach (get_object_vars($param) as $k => $x) {
                $str .= "$k:".$this->buildParamVar($x).',';
            }
            return rtrim($str,',').'}';
        }
        if (is_null($param)) {
            return "null:null";
        }
        if (is_string($param)) {
            return "string:$param";
        }
    }

    protected function parseSearchSuggestionResults($str) {
        if (0 == preg_match("@dwr.engine._remoteHandleCallback\(\'\d+\',\'\d+\',\{results:([^,]+),@", $str, $matches)) {
            throw new \Exception('Could not parse API result');
        }

        preg_match_all("@s\d+.categoryType=null;s\d+.entityType='Title';s\d+.id=\"(\d+)\";s\d+.name=\"([^\"]+)\";s\d+.popularity=null;s\d+.titleType=\'[A-Z,a-z]+';s\d+.year=(\d+);@", $str, $matches, PREG_SET_ORDER);

        $results = array();
        foreach ($matches as $match) {
            $results[] = array(
                'id' => $match[1],
                'name' => stripslashes($match[2]),
                'year' => $match[3]
            );
        }
        return $results;
    }

    protected function jsDecode($str) {
        $str = preg_replace('@\b([a-z0-9]+)(\s*:)@i', "\"$1\"$2", $str);
        return json_decode($str);
    }
}