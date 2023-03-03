<!DOCTYPE html>
<html>
<head>
    <title>Steam Link Generator - ALPHA v0.2</title>
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-top: 50px;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 50px;
        }
        label {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        textarea {
            height: 150px;
            font-size: 16px;
            padding: 10px;
            border: none;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            resize: none;
        }
        input[type="submit"] {
            background-color: #0072c6;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        input[type="submit"]:hover {
            background-color: #005ea3;
        }
        table {
            border-collapse: collapse;
            margin: 50px auto;
        }
        td, th {
            padding: 10px;
            text-align: center;
            vertical-align: middle;
            font-size: 16px;
        }
        th {
            background-color: #0072c6;
            color: #fff;
        }
        img {
            height: 69px;
            width: 184px;
        }
        .game {
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Steam Link Generator - ALPHA v0.2</h1>
    <form method="post">
        <label for="app_names">Enter app names (separated by new row):</label>
        <br>
        <textarea id="app_names" name="app_names" rows="6" cols="60" placeholder="Example game 1&#10;Example game 2&#10;Example game 3" required></textarea>
        <br><br>
        <input type="submit" name="submit" value="Generate Links">
    </form>

    <br><br>

    <?php
if(isset($_POST['submit'])){
    $app_names = $_POST['app_names'];
    $app_names_array = preg_split('/\r\n|\r|\n/', $app_names);

    $app_list_url = 'https://api.steampowered.com/ISteamApps/GetAppList/v0002/?key=CB704872ED5681101D7726162E8D1461?format=json';
    $cache_file = 'app_list.json';
    if (file_exists($cache_file) && filemtime($cache_file) > time() - 86400) {
        // Cached data is available and up to date
        $app_list_json = file_get_contents($cache_file);
    } else {
        // Cached data is not available or out of date, fetch from API and save to cache
        $app_list_json = file_get_contents($app_list_url);
        file_put_contents($cache_file, $app_list_json);
    }
    $app_list_array = json_decode($app_list_json, true);
    $app_list = $app_list_array['applist']['apps'];

    $app_names_array = preg_split('/\r\n|\r|\n/', $app_names);
    $app_list = array_combine(array_column($app_list, 'name'), $app_list);
    $app_list_lower = array_change_key_case($app_list, CASE_LOWER);
    
    $table = "<table border='1'><tr><th>#</th><th>Game</th><th>Thumbnail</th></tr>";
    $num = 1;
    foreach ($app_names_array as $app_name) {
        $app_name = trim($app_name);
        $app_id = '';
        $best_match = null;
        $exact_match = null;
    
        // Convert the app name to lowercase to perform case-insensitive matching
        $app_name_lower = strtolower($app_name);
    
        //Explicit exceptions
        if ($app_name_lower == ">observer_") {
            $app_name_lower = "observer";
        }

        // Look for an exact match in the pre-processed app list
        if (isset($app_list_lower[$app_name_lower])) {
            $exact_match = $app_list_lower[$app_name_lower];
        } else {
            // Look for a fuzzy match using a combination of different similarity metrics and thresholds
            foreach ($app_list_lower as $app_lower) {
                $name_similarity = similar_text($app_name_lower, strtolower($app_lower['name']), $name_similarity_percentage);
                $name_distance = levenshtein($app_name_lower, strtolower($app_lower['name']));
    
                if ($name_similarity_percentage >= 90 || $name_distance <= 3) {
                    $best_match = $app_lower;
                    break;
                } elseif ($name_similarity_percentage >= 75 || $name_distance <= 5) {
                    if (!$best_match || ($name_similarity_percentage > similar_text($app_name_lower, strtolower($best_match['name']), $best_match_similarity_percentage) && $name_distance < levenshtein($app_name_lower, strtolower($best_match['name'])))) {
                        $best_match = $app_lower;
                    }
                }
            }
        }
    
        if ($exact_match) {
            $app_id = $exact_match['appid'];
            $game_name = "<a href='https://store.steampowered.com/app/{$app_id}/{$app_name}/'>" . ucwords($exact_match['name']) . "</a><br>";
            $thumbnail_url = "https://steamcdn-a.akamaihd.net/steam/apps/{$app_id}/capsule_184x69.jpg";
        } elseif ($best_match) {
            $app_id = $best_match['appid'];
            $game_name = "<a href='https://store.steampowered.com/app/{$app_id}/{$app_name}/'>" . ucwords($best_match['name']) . "</a><br>";
            $thumbnail_url = "https://steamcdn-a.akamaihd.net/steam/apps/{$app_id}/capsule_184x69.jpg";
        } else {
            $game_name = ucwords($app_name) . " (Not found on Steam)";
            $thumbnail_url = "https://via.placeholder.com/184x69?text=No+Thumbnail";
        }
    
        $table .= "<tr><td>$num</td><td class='game'>$game_name</td><td><a href='https://store.steampowered.com/app/{$app_id}/{$app_name}/'><img src='$thumbnail_url' alt='Thumbnail for $app_name'></a></td></tr>";
        $num++;
    }
    $table .= "</table>";
    echo $table;
}
?>
</body>
