<?php
namespace jinni;

class film {
    const CONTENT_FILM      = 'FeatureFilm';
    const CONTENT_TV        = 'TvSeries';
    const CONTENT_SHORTFILM = 'Short';
    /**
     * @var http
     */
    protected $http;
    /**
     * @var string i.e. /movies/$urlName/
     */
    public $urlName;

    protected $rating;
    protected $name;
    protected $filmId;
    protected $year;
    protected $contentType;
    protected $imdb;

    public function __construct(http $http) {
        $this->http = $http;
    }

    public static function validContentType($type) {
        if (in_array($type, array(static::CONTENT_FILM, static::CONTENT_TV, static::CONTENT_SHORTFILM))) {
            return true;
        }
        return false;
    }

    public function rate($rating) {
        //callCount=1 page=/movies/the-corporation/ httpSessionId=1C3514898A5FED51780407C569A6316E.web2_node3 scriptSessionId=3C675DDBB02222BE8CB51E2415259E99676 c0-scriptName=AjaxUserRatingBean c0-methodName=submiteContentUserRating c0-id=0 c0-param0=number:4060 c0-param1=number:7 batchId=0
        $response = $this->http->apiCall('AjaxUserRatingBean', 'submiteContentUserRating', array($this->getFilmId(), $rating));

        if (false === strpos($response, 'Thank you for rating')) {
            throw new \Exception('Invalid response from server');
        }

        $this->setRating($rating);
    }

    public function setRating($rating) {
        $this->rating = (int)$rating;
    }

    public function getRating() {
        if (!isset($this->rating)) {
            // can get this via the ajax api
            $rating = $this->http->apiCall('AjaxUserRatingBean', 'getContentRating', array($this->getFilmId()));
            if (is_object($rating)) {
                $this->rating = $rating->rate;
            }
        }
        return $this->rating;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        if (!isset($this->name)) {
            $this->getInfo();
        }
        return $this->name;
    }

    public function setFilmId($id) {
        $this->filmId = (int)$id;
    }

    public function getFilmId() {
        if (!isset($this->filmId)) {
            $this->getInfo();
        }
        return $this->filmId;
    }

    public function setUrlName($urlName) {
        $this->urlName = $urlName;
    }

    public function getUrlName() {
        if (!isset($this->urlName)) {
            $headers = $this->http->getPage('/discovery.html', array('search' => '@#@test-['.$this->getFilmId().']T'), false, true);

            if (0 == preg_match("@Location: http://www.jinni.com/(?:movies|tv)/([^/]+)/@i", $headers, $matches)) {
                throw new \Exception('No urlName available');
            }
            $this->urlName = $matches[1];
        }
        return $this->urlName;
    }

    public function setYear($year) {
        $this->year = (int)$year;
    }

    public function getYear() {
        if (!isset($this->year)) {
            $this->getInfo();
        }
        return $this->year;
    }

    public function setContentType($type) {
        $this->contentType = $type;
    }

    public function getContentType() {
        return $this->contentType;
    }

    public function getImdb() {
        if (!isset($this->imdb)) {
            $this->getInfo();
        }
        return $this->imdb;
    }

    public function setImdb($imdb) {
        $this->imdb = (int)$imdb;
    }

    protected function getInfo() {
        $content = $this->http->getPage('/movies/'.$this->getUrlName(), null, true);
        if (preg_match('@"http://www.imdb.com/title/tt(\d+)"@', $content, $matches)) {
            $this->imdb = (int)$matches[1];
        }
        if (preg_match("@<title>.+, (\d{4})<\/title>@", $content, $matches)) {
            $this->setYear($matches[1]);
        }
    }
}