<?php
namespace jinni;

require_once "film.php";
require_once "http.php";

class jinni {
    /**
     * @var http
     */
    public $http;

    public function __construct($username, $cacheFolder) {
        $this->http = new http($username, $cacheFolder);
    }

    /**
     * Get every rating on $this->username's account
     * @return array of \jinni\film
     */
    public function getRatings() {
        $films = array();
        //get ratings pages
        $page = $this->http->getPage('/user/'.urlencode($this->username).'/ratings/');
        //create film classes for each one, give it its rating
        $films = $this->getFilmsFromRatingsPage($page);

        while (false !== ($postData = $this->getNextRatingPagePostData($page))) {
            $page = $this->http->getPage('/user/'.urlencode($this->username).'/ratings/', $postData);
            $films = array_merge($films, $this->getFilmsFromRatingsPage($page));
        }
        return $films;
    }

    /**
     * Search for a string on jinni optionally fitered by $type
     * @param str $searchStr
     * @param string|null $type
     * @see film::validContentType()
     * @return array of \jinni\film
     */
    public function search($searchStr, $type = null) {
        $films = array();
        $results = $this->http->search($searchStr, $type);
        foreach ($results as $result) {
            $films[] = $film = new film($this->http);
            $film->setFilmId($result['id']);
            $film->setName($result['name']);
            $film->setYear($result['year']);
            $film->setContentType($result['contentType']);
        }
        return $films;
    }

    protected function getFilmsFromRatingsPage($page) {
        $films = array();
        $filmSections = explode('<div id="ratingStrip', $page);
        array_shift($films);
        foreach ($filmSections as $filmSection) {
            if (0 === preg_match('@<a class="" href="/movies/([^/]+)/" title="([^"]+)" onclick=""><span class="title">[^<]+</span>@', $filmSection, $matches)) {
                continue;
            }
            
            if (0 === preg_match('@<span class="digitRate" id="digitalRate\[\d+\]">(\d+)/10@', $filmSection, $ratingMatches)) {
                continue;
            }

            if (0 === preg_match('@myRatingsFuncs.removeRatingStrip\(\'ratingStrip\[\d+\]\',(\d+)\);@', $filmSection, $filmIdMatches)) {
                continue;
            }

            $films[] = $film = new film($this->http);
            $film->setUrlName($matches[1]);
            $film->setName($matches[2]);
            $film->setRating($ratingMatches[1]);
            $film->setFilmId($filmIdMatches[1]);
        }

        return $films;
    }

    protected function getNextRatingPagePostData($page) {

        if (false === strpos($page, 'class="next_rev"')) {
            return false;
        }

        if (0 == preg_match('@<input type="hidden" name="javax.faces.ViewState" id="javax.faces.ViewState" value="(j_id\d+:j_id\d+)" />@', $page, $matches)) {
            return false;
        }

        $viewState = $matches[1];

        if (0 == preg_match('@<td class="activePage"><a href="#" onclick="if\(typeof jsfcljs == \'function\'\)\{jsfcljs\(document.forms\[\'userRatingForm\'\],\'userRatingForm:j_id(\d+)idx(\d+)@', $page, $matches)) {
            return false;
        }

        $id = $matches[1];
        $page = $matches[2] + 1;

        return array(
            'javax.faces.ViewState' => $viewState,
            'userRatingForm'        => 'userRatingForm',
            "userRatingForm:j_id$id" => "idx$page",
            "userRatingForm:j_id{$id}idx$page" =>	"userRatingForm:j_id{$id}idx$page"
        );
    }
}