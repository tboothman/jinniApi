<?php
require_once "../jinni.php";

class testJinni extends \jinni\jinni {
    function _getFilmsFromRatingsPage($page) {
        return $this->getFilmsFromRatingsPage($page);
    }
    function _getNextRatingPageNumber($page) {
        return $this->getNextRatingPageNumber($page);
    }
    function _getFilmsFromSearchResults($page) {
        return $this->getFilmsFromSearchResults($page);
    }
}

class jinnitest extends PHPUnit_Framework_TestCase {
    public static $jinni;
    public static $ratingsPage;
    public static $finalRatingsPage;
    public static $testCache;
    public static $searchResultsPage;

    public static function setUpBeforeClass() {
        self::$testCache = realpath(dirname(__FILE__).'/testcache');
        self::$jinni = new testJinni('username', self::$testCache);
        self::$ratingsPage = file_get_contents("ratings.htm", true);
        self::$finalRatingsPage = file_get_contents("finalratings.htm", true);
        self::$searchResultsPage = file_get_contents(self::$testCache.'/discovery.htmlquery=the+matrix&content=Movies', true);
    }

    public function testRatingPageParsing() {
        
        $films = self::$jinni->_getFilmsFromRatingsPage(self::$ratingsPage);

        //print_r($films);die();

        $this->assertTrue(count($films) == 20, "Only ".count($films)." were matched");

        // long name (has ... in title on page)
        $this->assertEquals("the-greatest-movie-ever-sold", $films[3]->urlName);
        $this->assertEquals("The Greatest Movie Ever Sold", $films[3]->getName());
        $this->assertEquals(7, $films[3]->getRating());
        $this->assertEquals(34470, $films[3]->getFilmId());
        $this->assertEquals('FeatureFilm', $films[3]->getContentType());

        //no Icon / good / punctuation
        $this->assertEquals("lifes-too-short", $films[10]->urlName);
        $this->assertEquals("Life's Too Short", $films[10]->getName());
        $this->assertEquals(7, $films[10]->getRating());
        $this->assertEquals(37588, $films[10]->getFilmId());
        $this->assertEquals('TvSeries', $films[10]->getContentType());

        // trash icon / Poor
        $this->assertEquals("john-carter", $films[17]->urlName);
        $this->assertEquals("John Carter", $films[17]->getName());
        $this->assertEquals(3, $films[17]->getRating());
        $this->assertEquals(42469, $films[17]->getFilmId());
        $this->assertEquals('FeatureFilm', $films[17]->getContentType());

        // oscar icon / Great
        $this->assertEquals("chronicle", $films[18]->urlName);
        $this->assertEquals("Chronicle", $films[18]->getName());
        $this->assertEquals(8, $films[18]->getRating());
        $this->assertEquals(42750, $films[18]->getFilmId());
        $this->assertEquals('FeatureFilm', $films[18]->getContentType());

        // Odd url / tv show
        $this->assertEquals("5-2010", $films[19]->urlName);
        $this->assertEquals("V", $films[19]->getName());
        $this->assertEquals(6, $films[19]->getRating());
        $this->assertEquals(21877, $films[19]->getFilmId());
        $this->assertEquals('TvSeries', $films[19]->getContentType());
    }

    public function testgetNextRatingPageNumber() {
        $pageNum = self::$jinni->_getNextRatingPageNumber(self::$ratingsPage);
        $this->assertEquals(6, $pageNum);
    }

    public function testFinalRatingPagePostNumber() {
        $pageNum = self::$jinni->_getNextRatingPageNumber(self::$finalRatingsPage);
        $this->assertEquals(false, $pageNum);
    }

    public function testSearch2() {
        $films = self::$jinni->search2('the matrix', \jinni\film::CONTENT_FILM);

        $this->assertTrue(count($films) == 22);

        $this->assertEquals("the-matrix", $films[0]->urlName);
        $this->assertEquals("The Matrix", $films[0]->getName());
        $this->assertEquals(191, $films[0]->getFilmId());
        $this->assertEquals(133093, $films[0]->getImdb());
        $this->assertEquals(1999, $films[0]->getYear());
    }

    public function testGetFilmsFromSearchResults() {
        $result = self::$jinni->_getFilmsFromSearchResults(self::$searchResultsPage);

        $this->assertTrue(count($result) == 22);

        $this->assertEquals($result[0]['extendedTitle'], 'The Matrix');
    }
}
