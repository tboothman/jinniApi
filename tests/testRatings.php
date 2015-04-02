<?php
$username = "change_to_a_real_username";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Ratings Jinni Test</title>
    </head>
    <body>
        <h1>Jinni Ratings (<?php echo $username; ?>)</h1>
<?php
require_once "../jinni.php";

// The $cachePath is used to store copies of the hmtl for the film pages so they are never retrieved twice
$jinni = new \jinni\jinni($username, $cachePath);

$films = $jinni->getRatings();
echo "<h2>Count: " . count($films) . "</h2>";

echo "<table>";
echo "  <tr><td>Name</td><td>Rated</td><td>Unique Name</td><td>Unique ID</td><td>Content</td><td>IMDB</td></tr>";
foreach($films as $film) {
    echo "<tr>";
    echo "<td>" . $film->getName() . " (" . $film->getYear() . ")</td>";
    echo "<td>" . $film->getRating() . "</td>";
    echo "<td>" . $film->getUrlName() . "</td>";
    echo "<td>" . $film->getFilmId() . "</td>";
    echo "<td>" . $film->getContentType() . "</td>";
    echo "<td>" . $film->getImdb() . "</td>";
    echo "</tr>";
}
echo "</table>";

?>
    </body>
</html>
