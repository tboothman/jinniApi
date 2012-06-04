<?php
require_once "../jinni.php";

class testJinni extends \jinni\jinni {
    function _getFilmsFromRatingsPage($page) {
        return $this->getFilmsFromRatingsPage($page);
    }
    function _getNextRatingPagePostData($page) {
        return $this->getNextRatingPagePostData($page);
    }
}

class jinnitest extends PHPUnit_Framework_TestCase {
    public static $jinni;
    public static $ratingsPage;
    public static $finalRatingsPage;

    public static function setUpBeforeClass() {
        self::$jinni = new testJinni('DJMcTom','');
        self::$ratingsPage = file_get_contents("ratings.htm", true);
        self::$finalRatingsPage = file_get_contents("finalratings.htm", true);
    }

    public function testRatingPageParsing() {
        
        $films = self::$jinni->_getFilmsFromRatingsPage(self::$ratingsPage);

        $this->assertTrue(count($films) == 50);

        //no Icon / good / punctuation
        $this->assertEquals("lifes-too-short", $films[0]->urlName);
        $this->assertEquals("Life's Too Short", $films[0]->getName());
        $this->assertEquals(7, $films[0]->getRating());
        $this->assertEquals(37588, $films[0]->getFilmId());

        // trash icon / Poor
        $this->assertEquals("john-carter", $films[1]->urlName);
        $this->assertEquals("John Carter", $films[1]->getName());
        $this->assertEquals(3, $films[1]->getRating());
        $this->assertEquals(42469, $films[1]->getFilmId());

        // oscar icon / Great
        $this->assertEquals("chronicle", $films[2]->urlName);
        $this->assertEquals("Chronicle", $films[2]->getName());
        $this->assertEquals(8, $films[2]->getRating());
        $this->assertEquals(42750, $films[2]->getFilmId());

        // Odd url / tv show
        $this->assertEquals("5-2010", $films[3]->urlName);
        $this->assertEquals("V", $films[3]->getName());
        $this->assertEquals(6, $films[3]->getRating());
        $this->assertEquals(21877, $films[3]->getFilmId());

        // long name (has ... in title on page)
        $this->assertEquals("captain-america-the-first-avenger", $films[48]->urlName);
        $this->assertEquals("Captain America: The First Avenger", $films[48]->getName());
        $this->assertEquals(6, $films[48]->getRating());
        $this->assertEquals(30932, $films[48]->getFilmId());
    }

    public function testgetNextRatingPagePostData() {
        $postData = self::$jinni->_getNextRatingPagePostData(self::$ratingsPage);
        $this->assertEquals(array(
            'javax.faces.ViewState' => 'j_id43285:j_id43286',
            'userRatingForm'        => 'userRatingForm',
            "userRatingForm:j_id269" => "idx2",
            "userRatingForm:j_id269idx2" =>	"userRatingForm:j_id269idx2"
        ), $postData);
    }

    public function testFinalRatingPagePostData() {
        $postData = self::$jinni->_getNextRatingPagePostData(self::$finalRatingsPage);
        $this->assertEquals(false, $postData);
    }

    public function testgetRatings() {
        //$result = self::$jinni->getRatings();
        //var_export($result);
        //$page = self::$jinni->getPage('/user/DJMcTom/ratings/',"javax.faces.ViewState=j_id52260%3Aj_id52261&userRatingForm=userRatingForm&userRatingForm:j_id269=idx2&userRatingForm:j_id269idx2=userRatingForm%3Aj_id269idx2");
//        var_export(self::$jinni->getPage('/user/DJMcTom/ratings/'));
//        $page = self::$jinni->getPage('/user/DJMcTom/ratings/',array(
//            'javax.faces.ViewState' => 'j_id52260:j_id52261',
//            'userRatingForm'        => 'userRatingForm',
//            "userRatingForm:j_id269" => "idx2",
//            "userRatingForm:j_id269idx2" =>	"userRatingForm:j_id269idx2"
//        ));

        //var_dump($page);
    }
}