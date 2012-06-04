<?php
namespace jinni;

require_once "film.php";
require_once "http.php";
require_once "jsParser.php";

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
     * This searches using search suggestions. It is quick but does not get imdb numbers from the initial request
     * @param str $searchStr
     * @param string|null $type
     * @see film::validContentType()
     * @return array of \jinni\film
     */
    public function search($searchStr, $type = null) {
        $films = array();
        $results = $this->http->searchSuggestions($searchStr, $type);
        foreach ($results as $result) {
            $films[] = $film = new film($this->http);
            $film->setFilmId($result['id']);
            $film->setName($result['name']);
            $film->setYear($result['year']);
            $film->setContentType($result['contentType']);
            }
        return $films;
    }

    /**
     * Search for a string on jinni optionally fitered by $type
     * This searches using the main site search.
     * It gets imdb numbers in search results but is slower than jinni::search.
     * Use if IMDB numbers are wanted but no other info from he main film page
     * @param str $searchStr
     * @param string|null $type
     * @see film::validContentType()
     * @return array of \jinni\film
     */
    public function search2($searchStr, $type = null) {
        $films = array();
        switch ($type) {
            case \jinni\film::CONTENT_FILM:
                $type = 'Movies';
                break;
            case \jinni\film::CONTENT_TV:
                $type = 'TV';
                break;
            default:
                $type = null;
        }
        $resultPage = $this->http->getPage("/discovery.html?query=".urlencode($searchStr).($type?"&content=$type":''), null, true);
        $results = $this->getFilmsFromSearchResults($resultPage);
        foreach ($results as $result) {
            $films[] = $film = new film($this->http);

            $film->setName($result['extendedTitle']);
            $film->setFilmId($result['DBID']);
            $film->setUrlName(rtrim(preg_replace("@(movies|tv)/@i", "", $result['contentPath']),'/'));
            $film->setContentType($result['contentType']);
            if (isset($result['affiliates']['IMDB']['affiliateContentIds'][0]['key'])) {
                $film->setImdb(ltrim($result['affiliates']['IMDB']['affiliateContentIds'][0]['key'],'t'));
            }
            $film->setYear($result['year']);
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

    /*
     * Returns an array of the films in the search results. Films are as below:
     * {
            entryId: 'collageEntry0',
            extendedTitle: "The Matrix",
            DBID: 191,
            contentPath: "movies/the-matrix/",
            contentType: "FeatureFilm",
            affiliates: {"iTunes":{"affiliate":{"id":53,"name":"iTunes"},"affiliateContentIds":[{"key":"271469518"}],"deliveryMethods":[]},"AMG":{"affiliate":{"id":9,"name":"AMG"},"affiliateContentIds":[{"key":"177524"}],"deliveryMethods":[]},"IMDB":{"affiliate":{"id":10,"name":"IMDB"},"affiliateContentIds":[{"key":"tt0133093"}],"deliveryMethods":[]},"Blockbuster":{"affiliate":{"id":19,"name":"Blockbuster"},"affiliateContentIds":[{"key":"47952","value":"Jinni internal ID"}],"deliveryMethods":["Download","Ship"]},"Netflix":{"affiliate":{"id":104,"name":"Netflix"},"affiliateContentIds":[{"key":"20557937"}],"deliveryMethods":["Ship"]},"Amazon":{"affiliate":{"id":51,"name":"Amazon"},"affiliateContentIds":[{"key":"B001DJLD1M"},{"key":"B001NXBRJG"},{"key":"B001XVD2Z0"},{"key":"B00319ECGK","value":"[Blu-ray]"}],"deliveryMethods":["Ship"]},"Lovefilm":{"affiliate":{"id":1,"name":"Lovefilm"},"affiliateContentIds":[{"key":"1759"}],"deliveryMethods":["Ship"]},"Tribune":{"affiliate":{"id":102,"name":"Tribune"},"affiliateContentIds":[{"key":"22804"}],"deliveryMethods":[]}},
            year: "1999",
            shortSynopsis: "With THE MATRIX, the Wachowskis have established themselves as innovative filmmakers who push the boundaries...",
            rollOverImage: "movie/the-matrix/the-matrix-1.jpeg",
            availableCinema: false,
            availableDvd: true,
            availableTv: false,
            availableOnline: false,
            availableMobile: false,
            availableDownload: true,
            isSimilar: false,
            similarTo: {id: "", title: ""},
            peopleOrigins: null,
            titlesOrignis: [{"entityId":191,"type":"Title","name":"The Matrix","aka":false,"freeTextOrigin":true,"categoryPerfectMatch":false}],
            categoriesOrigins: {},
            numOfCategories: 0
        };
     */
    protected function getFilmsFromSearchResults($page) {
        $results = array();
        if (false == strpos($page, 'var obj_collageEntry0 =')) {
            return $results;
        }

        $films = preg_split("@var obj_collageEntry\d+ = @", $page);

        array_shift($films);
        $lastParts = explode('</script>', array_pop($films), 1);
        $films[] = $lastParts[0];

        foreach ($films as $film) {
            $film = trim(trim($film),';');

            $filmObj = \jsParser::doParse($film);

            if ($filmObj) {
                $results[] = $filmObj;
            }
        }
        return $results;
    }
}