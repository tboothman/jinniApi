Jinni chose not to have a public API, so I created this to interface with Jinni using PHP.

Fortunately most of this can be done without scraping the site as there is a DWR interface (a java RPC for ajax) that supports searching and rating.

Features
--------
* Retrieve all films you have rated, and the rating you gave it.
* Search Jinni for a film, retrieve some information about the film and rate it.

Usage - Finding a film and rating it
------------------------------------

    // The $cachePath is used to store copies of the hmtl for the film pages so they are never retrieved twice
    $jinni = new \jinni\jinni($username, $cachePath);

    // Search for the matrix. Specify that it's a film, which will filter the search results
    $searchResults = $jinni->search('The Matrix 1999', \jinni\film::CONTENT_FILM);

    // $searchResults is an array of \jinni\film
    // Try to find our film in the results. Use whatever mechanism you like, but film year should be fairly accurate.
    // The film object offers other variables like imdb number, but this requires another request
    foreach ($searchResults as $film) {
        if (1999 == $film->getYear()) {
            $theMatrix = $film;
            break;
        }
    }

    // The \jinni\film object lets you rate the film
    $theMatrix->rate(10);
