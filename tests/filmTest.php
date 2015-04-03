<?php
require_once "../film.php";
require_once "../http.php";

class http extends \jinni\http {
    public function setSessionId($id) {
        $this->jSessionID = $id;
    }
}

class filmtest extends PHPUnit_Framework_TestCase {
    public static $film;
    public static $http;

    public static $boundSearch = "HTTP/1.1 302 Moved Temporarily
Date: Tue, 29 May 2012 20:15:24 GMT
Server: Apache/2.2.3 (Red Hat)
X-Powered-By: Servlet 2.4; JBoss-4.2.2.GA (build: SVNTag=JBoss_4_2_2_GA date=200710221139)/Tomcat-5.5
Set-Cookie: JSESSIONID=D3C5EC989A4E086F9611018F65CBEC5B.web1_node1; Path=/
X-Powered-By: JSF/1.2
Location: http://www.jinni.com/movies/bound/
Vary: Accept-Encoding
Content-Encoding: gzip
Content-Length: 20
Connection: close
Content-Type: text/html; charset=UTF-8";

    public static $boundApiRating = "//#DWR-INSERT
//#DWR-REPLY
dwr.engine._remoteHandleCallback('0','0',{likelyOrNotInterested:null,rate:9.0,rated:true,suggested:false});
";

    public static function setUpBeforeClass() {
        static::$http = new \jinni\http('DJMcTom', realpath(dirname(__FILE__).'/testcache'));
    }

    public function testFilmFromRatingsStart() {
        $film = new \jinni\film(static::$http);
        $film->setUrlName('bound');
        $film->setName('Bound');
        $film->setRating(9);
        $film->setFilmId('5285');

        // start with the stuff we know
        $this->assertEquals('bound', $film->getUrlName());
        $this->assertEquals('Bound', $film->getName());
        $this->assertEquals(9, $film->getRating());
        $this->assertEquals(5285, $film->getFilmId());

        // stuff we didn't set
        $this->assertEquals(1996, $film->getYear());
        $this->assertEquals(115736, $film->getImdb());
    }

    public function testFilmFromSearchStart() {
        $http = $this->getMock('\jinni\http', array('getPage'), array('test', realpath(dirname(__FILE__).'/testcache')));

        $http->expects($this->any())
             ->method('getPage')
                                           // get jSessionId, api call,    search with /discovery.html, get bound page
             ->will($this->onConsecutiveCalls('', static::$boundApiRating, static::$boundSearch, file_get_contents(realpath(dirname(__FILE__).'/testcache').'/movies/bound')));

        $film = new \jinni\film($http);
        $film->setFilmId('5285');
        $film->setName('Bound');
        $film->setYear(1996);
        $film->setContentType(\jinni\film::CONTENT_FILM);

        // start with the stuff we know
        $this->assertEquals(5285, $film->getFilmId());
        $this->assertEquals('Bound', $film->getName());
        $this->assertEquals(1996, $film->getYear());

        // stuff we didn't set
        $this->assertEquals(9, $film->getRating());
        $this->assertEquals(115736, $film->getImdb()); // automatically call getUrlName, then get main page
        $this->assertEquals('bound', $film->getUrlName());
    }

    public function testRate() {
        $http = $this->getMock('http', array('getPage'), array('test', realpath(dirname(__FILE__).'/testcache')));
        $http->setSessionId('9C7337856936A4C7F9EBC3FB1EF542E0.web1_node1');
        $http->expects($this->any())
             ->method('getPage')
             ->will($this->returnValueMap( array(
                    array('/sitemap.html', null, false, false, ''),
                    array('/dwr/call/plaincall/AjaxUserRatingBean.dwr', 'callCount=1'."\n".
'batchId=0'."\n".
'httpSessionId=9C7337856936A4C7F9EBC3FB1EF542E0.web1_node1'."\n".
'scriptSessionId=3C675DDBB02222BE8CB51E2415259E99676'."\n".
'c0-scriptName=AjaxUserRatingBean'."\n".
'c0-methodName=submiteContentUserRating'."\n".
'c0-id=0'."\n".
'c0-param0=number:37588'."\n".
'c0-param1=number:7'."\n", false, false, '//#DWR-INSERT
//#DWR-REPLY
dwr.engine._remoteHandleCallback(\'0\',\'0\',"Thank you for rating");
')
                     )));

        $film = new \jinni\film($http);
        $film->setFilmId(37588);
        $film->rate(7);
    }
}
