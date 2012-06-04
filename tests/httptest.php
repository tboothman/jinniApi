<?php
require_once "../http.php";

class testHttp extends \jinni\http {
    function _parseSearchResults($result) {
        return $this->parseSearchResults($result);
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

    public function testParseSearchResults() {
        $resultStr = <<<EOD
//#DWR-INSERT
//#DWR-REPLY
var s0={};var s2=[];var s3=[];var s4=[];var s5=[];var s6=[];var s1=[];var s7={};var s8={};var s9={};var s10={};var s11={};var s12={};var s13={};var s14={};var s15={};var s16={};s0.cleanPhrase=s2;s0.cleanPhraseNoStem=s3;s0.cleanedConjunctions=s4;s0.cleanedVolatiles=s5;s0.metaphonePhrase=s6;s0.searchPhrase="the matrix";
s2[0]="THE";s2[1]="MATRIX";
s3[0]="THE";s3[1]="MATRIX";


s6[0]="THE";s6[1]="MTRKS";
s1[0]=s7;s1[1]=s8;s1[2]=s9;s1[3]=s10;s1[4]=s11;s1[5]=s12;s1[6]=s13;s1[7]=s14;s1[8]=s15;s1[9]=s16;
s7.categoryType=null;s7.contentType='FeatureFilm';s7.entityType='Title';s7.id=191;s7.name="The Matrix";s7.popularity=null;s7.year=1999;
s8.categoryType=null;s8.contentType='FeatureFilm';s8.entityType='Title';s8.id=192;s8.name="The Matrix Reloaded";s8.popularity=null;s8.year=2003;
s9.categoryType=null;s9.contentType='FeatureFilm';s9.entityType='Title';s9.id=484;s9.name="The Matrix Revolutions";s9.popularity=null;s9.year=2003;
s10.categoryType=null;s10.contentType='FeatureFilm';s10.entityType='Title';s10.id=8656;s10.name="The Matrix Revisited";s10.popularity=null;s10.year=2001;
s11.categoryType=null;s11.contentType='ShortFilm';s11.entityType='Title';s11.id=40910;s11.name="The Animatrix: Matriculated";s11.popularity=null;s11.year=2003;
s12.categoryType=null;s12.contentType='FeatureFilm';s12.entityType='Title';s12.id=7571;s12.name="The Animatrix";s12.popularity=null;s12.year=2003;
s13.categoryType=null;s13.contentType='TvSeries';s13.entityType='Title';s13.id=34040;s13.name="Threat Matrix";s13.popularity=null;s13.year=2003;
s14.categoryType=null;s14.contentType='ShortFilm';s14.entityType='Title';s14.id=40882;s14.name="The Animatrix: Beyond";s14.popularity=null;s14.year=2003;
s15.categoryType=null;s15.contentType='ShortFilm';s15.entityType='Title';s15.id=40834;s15.name="The Animatrix: Kid\'s Story";s15.popularity=null;s15.year=2003;
s16.categoryType=null;s16.contentType='ShortFilm';s16.entityType='Title';s16.id=40879;s16.name="The Animatrix: Program";s16.popularity=null;s16.year=2003;
dwr.engine._remoteHandleCallback('1','0',{criteria:s0,results:s1,suggestTime:0.080071});
EOD;
        $results = self::$http->_parseSearchResults($resultStr);
        $this->assertTrue(count($results) == 10);
        $this->assertEquals(array('contentType' => 'FeatureFilm', 'id' => 191, 'name' => 'The Matrix', 'year' => 1999), $results[0]);
        $this->assertEquals(array('contentType' => 'FeatureFilm', 'id' => 192, 'name' => 'The Matrix Reloaded', 'year' => 2003), $results[1]);
        $this->assertEquals(array('contentType' => 'TvSeries', 'id' => 34040, 'name' => 'Threat Matrix', 'year' => 2003), $results[6]);
        $this->assertEquals(array('contentType' => 'ShortFilm', 'id' => 40834, 'name' => 'The Animatrix: Kid\'s Story', 'year' => 2003), $results[8]);



        $resultStr = <<<EOD
//#DWR-INSERT
//#DWR-REPLY
var s0={};var s2=[];var s3=[];var s4=[];var s5=[];var s6=[];var s1=[];s0.cleanPhrase=s2;s0.cleanPhraseNoStem=s3;s0.cleanedConjunctions=s4;s0.cleanedVolatiles=s5;s0.metaphonePhrase=s6;s0.searchPhrase="gasdsads";
s2[0]="GASDSAD";
s3[0]="GASDSADS";


s6[0]="KSTST";

dwr.engine._remoteHandleCallback('12','0',{criteria:s0,results:s1,suggestTime:0.016569});
EOD;
        $results = self::$http->_parseSearchResults($resultStr);
        $this->assertTrue(count($results) == 0);



        $resultStr = <<<EOD
//#DWR-INSERT
//#DWR-REPLY
var s0={};var s2=[];var s3=[];var s4=[];var s5=[];var s6=[];var s1=[];var s7={};var s8={};var s9={};var s10={};var s11={};var s12={};var s13={};var s14={};var s15={};var s16={};s0.cleanPhrase=s2;s0.cleanPhraseNoStem=s3;s0.cleanedConjunctions=s4;s0.cleanedVolatiles=s5;s0.metaphonePhrase=s6;s0.searchPhrase="transformers";
s2[0]="TRANSFORMER";
s3[0]="TRANSFORMERS";


s6[0]="TRNSFRMR";
s1[0]=s7;s1[1]=s8;s1[2]=s9;s1[3]=s10;s1[4]=s11;s1[5]=s12;s1[6]=s13;s1[7]=s14;s1[8]=s15;s1[9]=s16;
s7.categoryType=null;s7.contentType='FeatureFilm';s7.entityType='Title';s7.id=655;s7.name="Transformers";s7.popularity=null;s7.year=2007;
s8.categoryType=null;s8.contentType='FeatureFilm';s8.entityType='Title';s8.id=2861;s8.name="The Transformers: The Movie";s8.popularity=null;s8.year=1986;
s9.categoryType=null;s9.contentType='FeatureFilm';s9.entityType='Title';s9.id=33717;s9.name="Transformers: Dark of the Moon";s9.popularity=null;s9.year=2011;
s10.categoryType=null;s10.contentType='FeatureFilm';s10.entityType='Title';s10.id=13040;s10.name="Transformers: Revenge of the Fallen";s10.popularity=null;s10.year=2009;
s11.categoryType=null;s11.contentType='FeatureFilm';s11.entityType='Title';s11.id=33226;s11.name="Transformer";s11.popularity=null;s11.year=1984;
s12.categoryType="Keywords";s12.contentType=null;s12.entityType='Category';s12.id=2064;s12.name="Transformation";s12.popularity=null;s12.year=0;
s13.categoryType=null;s13.contentType='FeatureFilm';s13.entityType='Title';s13.id=12178;s13.name="The Informers";s13.popularity=null;s13.year=2008;
s14.categoryType=null;s14.contentType='FeatureFilm';s14.entityType='Title';s14.id=20839;s14.name="Transformation: The Life and Legacy of Werner Erhard";s14.popularity=null;s14.year=2006;
s15.categoryType=null;s15.contentType='FeatureFilm';s15.entityType='Title';s15.id=8727;s15.name="The Informer";s15.popularity=null;s15.year=1935;
s16.categoryType=null;s16.contentType=null;s16.entityType='Person';s16.id=31480;s16.name="Maurice Ransford";s16.popularity=null;s16.year=0;
dwr.engine._remoteHandleCallback('3','0',{criteria:s0,results:s1,suggestTime:0.047661});
EOD;
        $results = self::$http->_parseSearchResults($resultStr);
        $this->assertTrue(count($results) == 8);
        $this->assertEquals(array('contentType' => 'FeatureFilm', 'id' => 8727, 'name' => 'The Informer', 'year' => 1935), $results[7]);
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
    }

    public function testJsDecode() {
        $this->assertEquals(
            (object)array('likelyOrNotInterested' => null, 'rate' => '9.0', 'rated' => true, 'suggested' => false),
            static::$http->_jsDecode("{likelyOrNotInterested:null,rate:9.0,rated:true,suggested:false}"));
    }
}

