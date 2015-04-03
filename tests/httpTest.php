<?php
require_once "../http.php";

class testHttp extends \jinni\http {
    function _parseSearcSuggestionhResults($result) {
        return $this->parseSearchSuggestionResults($result);
    }
    function _buildApiParamString($params) {
        return $this->buildApiParamString($params);
    }
    function _buildParamVar($var) {
        return $this->buildParamVar($var);
    }
    function _createCachePath($url) {
        return $this->createCachePath($url);
    }
    function _jsDecode($str) {
        return $this->jsDecode($str);
    }
}

class fakedApiHttp extends testHttp {
    public $apiResponse;
    protected function rawApiCall($scriptName, $method, array $params = array()) {
        return $this->apiResponse;
    }
}

class httptest extends PHPUnit_Framework_TestCase {
    protected static $http;
    protected static $testCache;
    public static function setUpBeforeClass() {
        self::$testCache = realpath(dirname(__FILE__).'/testcache');
        self::$http = new testHttp('test', self::$testCache);
    }

    public function testBuildApiParamString() {
        $this->assertEquals("c0-param0=string:the matrix\n".
                            "c0-param1=Object_Object:{contentTypeFilter:null:null}\n", self::$http->_buildApiParamString(array('the matrix', (object)array('contentTypeFilter' => null))));
        $this->assertEquals("c0-param0=string:the matrix\n".
                            "c0-param1=Object_Object:{contentTypeFilter:string:TvSeries}\n", self::$http->_buildApiParamString(array('the matrix', (object)array('contentTypeFilter' => 'TvSeries'))));
    }

    public function testBuildParamVar() {
        $this->assertEquals("Object_Object:{contentTypeFilter:null:null}", self::$http->_buildParamVar((object)array('contentTypeFilter' => null)));
        $this->assertEquals("string:test", self::$http->_buildParamVar("test"));
    }

    public function testParseSearchSuggestionResults() {
        $resultStr = <<<EOD
//#DWR-INSERT
//#DWR-REPLY
var s0=[];var s1={};var s2={};var s3={};var s4={};var s5={};var s6={};var s7={};var s8={};var s9={};var s10={};s0[0]=s1;s0[1]=s2;s0[2]=s3;s0[3]=s4;s0[4]=s5;s0[5]=s6;s0[6]=s7;s0[7]=s8;s0[8]=s9;s0[9]=s10;
s1.categoryType=null;s1.entityType='Title';s1.id="191";s1.name="The Matrix";s1.popularity=null;s1.titleType='FeatureFilm';s1.year=1999;
s2.categoryType=null;s2.entityType='Title';s2.id="192";s2.name="The Matrix Reloaded";s2.popularity=null;s2.titleType='FeatureFilm';s2.year=2003;
s3.categoryType=null;s3.entityType='Title';s3.id="484";s3.name="The Matrix Revolutions";s3.popularity=null;s3.titleType='FeatureFilm';s3.year=2003;
s4.categoryType=null;s4.entityType='Title';s4.id="8656";s4.name="The Matrix Revisited";s4.popularity=null;s4.titleType='FeatureFilm';s4.year=2001;
s5.categoryType=null;s5.entityType='Title';s5.id="87155";s5.name="The Approval Matrix";s5.popularity=null;s5.titleType='TvSeries';s5.year=2014;
s6.categoryType=null;s6.entityType='Title';s6.id="40910";s6.name="The Animatrix: Matriculated";s6.popularity=null;s6.titleType='Short';s6.year=2003;
s7.categoryType=null;s7.entityType='Title';s7.id="7571";s7.name="The Animatrix";s7.popularity=null;s7.titleType='FeatureFilm';s7.year=2003;
s8.categoryType=null;s8.entityType='Title';s8.id="34040";s8.name="Threat Matrix";s8.popularity=null;s8.titleType='TvSeries';s8.year=2003;
s9.categoryType=null;s9.entityType='Title';s9.id="40882";s9.name="The Animatrix: Beyond";s9.popularity=null;s9.titleType='Short';s9.year=2003;
s10.categoryType=null;s10.entityType='Title';s10.id="40834";s10.name="The Animatrix: Kid\'s Story";s10.popularity=null;s10.titleType='Short';s10.year=2003;
dwr.engine._remoteHandleCallback('1','0',{results:s0,searchPhrase:"the matrix",suggestTime:0.034233891});
EOD;
        $results = self::$http->_parseSearcSuggestionhResults($resultStr);
        $this->assertTrue(count($results) == 10);
        $this->assertEquals(array('contentType' => 'FeatureFilm', 'id' => 191, 'name' => 'The Matrix', 'year' => 1999), $results[0]);
        $this->assertEquals(array('contentType' => 'FeatureFilm', 'id' => 192, 'name' => 'The Matrix Reloaded', 'year' => 2003), $results[1]);
        $this->assertEquals(array('contentType' => 'TvSeries', 'id' => 34040, 'name' => 'Threat Matrix', 'year' => 2003), $results[7]);
        $this->assertEquals(array('contentType' => 'Short', 'id' => 40834, 'name' => 'The Animatrix: Kid\'s Story', 'year' => 2003), $results[9]);



        $resultStr = <<<EOD
//#DWR-INSERT
//#DWR-REPLY
var s0=[];
dwr.engine._remoteHandleCallback('6','0',{results:s0,searchPhrase:"qztwgsh",suggestTime:6.73776E-4});
EOD;
        $results = self::$http->_parseSearcSuggestionhResults($resultStr);
        $this->assertTrue(count($results) == 0);



// Test that keywords are ignored
        $resultStr = <<<EOD
//#DWR-INSERT
//#DWR-REPLY
var s0=[];var s1={};var s2={};var s3={};var s4={};var s5={};var s6={};var s7={};var s8={};var s9={};var s10={};s0[0]=s1;s0[1]=s2;s0[2]=s3;s0[3]=s4;s0[4]=s5;s0[5]=s6;s0[6]=s7;s0[7]=s8;s0[8]=s9;s0[9]=s10;
s1.categoryType="Keywords";s1.entityType='Category';s1.id="2064";s1.name="Transformation";s1.popularity=null;s1.titleType=null;s1.year=0;
s2.categoryType=null;s2.entityType='Title';s2.id="655";s2.name="Transformers";s2.popularity=null;s2.titleType='FeatureFilm';s2.year=2007;
s3.categoryType="Keywords";s3.entityType='CategorySynonym';s3.id="1548";s3.name="Moral transformation";s3.popularity=null;s3.titleType=null;s3.year=0;
s4.categoryType=null;s4.entityType='Title';s4.id="31240";s4.name="Transformers Prime";s4.popularity=null;s4.titleType='TvSeries';s4.year=2010;
s5.categoryType=null;s5.entityType='Title';s5.id="21128";s5.name="Transformers Armada";s5.popularity=null;s5.titleType='TvSeries';s5.year=2002;
s6.categoryType=null;s6.entityType='Title';s6.id="22387";s6.name="Transformers Animated";s6.popularity=null;s6.titleType='TvSeries';s6.year=2007;
s7.categoryType=null;s7.entityType='Title';s7.id="2861";s7.name="The Transformers: The Movie";s7.popularity=null;s7.titleType='FeatureFilm';s7.year=1986;
s8.categoryType=null;s8.entityType='Title';s8.id="33717";s8.name="Transformers: Dark of the Moon";s8.popularity=null;s8.titleType='FeatureFilm';s8.year=2011;
s9.categoryType=null;s9.entityType='Title';s9.id="58762";s9.name="Transformers: Energon";s9.popularity=null;s9.titleType='TvSeries';s9.year=2004;
s10.categoryType=null;s10.entityType='Title';s10.id="58000";s10.name="Transformers: Cybertron";s10.popularity=null;s10.titleType='TvSeries';s10.year=2005;
dwr.engine._remoteHandleCallback('16','0',{results:s0,searchPhrase:"transform",suggestTime:0.021716644});
EOD;
        $results = self::$http->_parseSearcSuggestionhResults($resultStr);
        $this->assertTrue(count($results) == 8);
        $this->assertEquals(array('contentType' => 'FeatureFilm', 'id' => 655, 'name' => 'Transformers', 'year' => 2007), $results[0]);
    }

    public function testApiCall() {
        

        $http = new fakedApiHttp('','');
        $http->apiResponse = "//#DWR-INSERT
//#DWR-REPLY
dwr.engine._remoteHandleCallback('0','0',{likelyOrNotInterested:null,rate:9.0,rated:true,suggested:false});
";

        $this->assertEquals(
            (object)array('likelyOrNotInterested' => null, 'rate' => '9.0', 'rated' => true, 'suggested' => false),
            $http->apiCall('', '', array()));
    }

    public function testCreateCachePath() {
        @rmdir(self::$testCache.'/test/long');
        @rmdir(self::$testCache.'/test');
        $result = static::$http->_createCachePath('/test/long/url');
        $this->assertEquals(self::$testCache.'/test/long/url', $result);
        $this->assertTrue(is_dir(self::$testCache.'/test/long'));

        $result = static::$http->_createCachePath('/test/url/');
        $this->assertEquals(self::$testCache.'/test/url', $result);

        $result = static::$http->_createCachePath('/test');
        $this->assertEquals(self::$testCache.'/test', $result);

        $result = static::$http->_createCachePath('/discovery.html?query=the%20matrix%201999&content=Movies');
        $this->assertEquals(self::$testCache.'/discovery.htmlquery=the%20matrix%201999&content=Movies', $result);
    }

    public function testJsDecode() {
        $this->assertEquals(
            (object)array('likelyOrNotInterested' => null, 'rate' => '9.0', 'rated' => true, 'suggested' => false),
            static::$http->_jsDecode("{likelyOrNotInterested:null,rate:9.0,rated:true,suggested:false}"));
    }
}

