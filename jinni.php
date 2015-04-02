<?php
namespace jinni;

require_once __DIR__."/film.php";
require_once __DIR__."/http.php";
require_once __DIR__."/jsParser.php";

class jinni {
    /**
     * @var http
     */
    public $http;
    protected $username;

    public function __construct($username, $cacheFolder) {
        $this->http = new http($username, $cacheFolder);
        $this->username = $username;
    }

    /**
     * Get every rating on $this->username's account
     * @return array of \jinni\film
     */
    public function getRatings($limitPages = NULL, $beginPage = 1) {
        $films = array();
        //get ratings pages
        $page = $this->http->getPage('/user/'.urlencode($this->username).'/ratings?pagingSlider_index='.$beginPage);
        //create film classes for each one, give it its rating
        $films = $this->getFilmsFromRatingsPage($page);
        
        $pageCount = 1;
        while (($limitPages == NULL || $limitPages > $pageCount) && (false !== ($nextPageNumber = $this->getNextRatingPageNumber($page)))) {
            $page = $this->http->getPage('/user/'.urlencode($this->username).'/ratings?pagingSlider_index='.$nextPageNumber);
            $films = array_merge($films, $this->getFilmsFromRatingsPage($page));
            $pageCount++;
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
     * Use if IMDB numbers are wanted but no other info from the main film page
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
        $filmSections = explode('<div class="ratings_cell5">', $page);
        array_shift($films);
        foreach ($filmSections as $filmSection) {
            // URL name and film name
            if (0 === preg_match('@<a href="http://www.jinni.com/movies/([^/]+)/" class="ratings_link" onclick="">([^"]+)</a>@', $filmSection, $matches)) {
                continue;
            }
            
            // Rating
            if (0 === preg_match('@RatedORSuggestedValue: (\d+)@', $filmSection, $ratingMatches)) {
                continue;
            }
            
            // Rating Date
            if (0 === preg_match('@<div class="ratings_cell4"><span[^>]+>(\d+/\d+/\d+)<@', $filmSection, $ratingDateMatches)) {
                continue;
            }

            // Film ID
            if (0 === preg_match('@contentId: "(\d+)@', $filmSection, $filmIdMatches)) {
                continue;
            }

            // Content type (Movie/TV)
            if (0 === preg_match('@<img src="(http://media1.jinni.com/(tv|movie|no-image)/[^/]+/[^"]+)"@', $filmSection, $contentTypeMatches)) {
                continue;
            }

            $films[] = $film = new film($this->http);
            $film->setUrlName($matches[1]);
            $film->setName(htmlspecialchars_decode($matches[2]));
            $film->setRating($ratingMatches[1]);
            $film->setFilmId($filmIdMatches[1]);
            if ($contentTypeMatches[2] == 'movie') {
                $film->setContentType(\jinni\film::CONTENT_FILM);
            } elseif ($contentTypeMatches[2] == 'tv') {
                $film->setContentType(\jinni\film::CONTENT_TV);
            }
        }

        return $films;
    }

    protected function getNextRatingPageNumber($page) {

        if (0 == preg_match('@pagingSlider\.addPage\(\d+,false\);[\n|\t]+\$\(document\)@', $page, $matches)) {
            return false;
        }

        if (0 == preg_match('@<input type="hidden" name="pagingSlider_index" id="pagingSlider_index" value="(\d+)" />@', $page, $matches)) {
            return false;
        }

        return $matches[1] + 1;
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