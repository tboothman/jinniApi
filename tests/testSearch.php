<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Search Jinni Test</title>
    </head>
<body>

<h1>Search for 'Wolf Wall Street'</h1>

<?php
require_once "../jinni.php";

// The $cachePath is used to store copies of the hmtl for the film pages so they are never retrieved twice
$jinni = new \jinni\jinni("change_to_a_real_username", $cachePath);

// Search for the matrix. Specify that it's a film, which will filter the search results
$searchResults = $jinni->search('Wolf Wall Street', \jinni\film::CONTENT_FILM);

foreach ($searchResults as $film) {
    $name = $film->getName();
    echo "Name: $name<br>";
}
?>

</body>
</html>

