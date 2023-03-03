<!DOCTYPE html>
<html>
<head>
    <title>Steam Link Generator - ALPHA v0.1</title>
    <style>
        table {
            border-collapse: collapse;
            margin: auto;
        }
        td, th {
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #eee;
        }
        img {
            height: 69px;
            width: 184px;
        }
        .game {
            font-size: 16px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Steam Link Generator - ALPHA v0.1</h1>
    <form method="post">
        <label for="app_names">Enter app names (separated by new row):</label>
        <br>
        <textarea id="app_names" name="app_names" rows="4" cols="50"></textarea>
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

    $table = "<table border='1'><tr><th>#</th><th>Game</th><th>Thumbnail</th></tr>";
    $num = 1;
    foreach ($app_names_array as $app_name) {
        $app_name = trim($app_name);
        $app_id = '';
        foreach ($app_list as $app) {
            if (strtolower($app_name) == strtolower($app['name'])) {
                $app_id = $app['appid'];
                break;
            }
        }
        if (empty($app_id)) {
            $game_name = ucwords($app_name) . " (Not found on Steam)";
            $thumbnail_url = "https://via.placeholder.com/184x69?text=No+Thumbnail";
        } else {
            $game_name = "<a href='https://store.steampowered.com/app/{$app_id}/{$app_name}/'>" . ucwords($app_name) . "</a><br>";
            $thumbnail_url = "https://steamcdn-a.akamaihd.net/steam/apps/{$app_id}/capsule_184x69.jpg";
        }
        $table .= "<tr><td>$num</td><td class='game'>$game_name</td><td><a href='https://store.steampowered.com/app/{$app_id}/{$app_name}/'><img src='$thumbnail_url' alt='Thumbnail for $app_name'></a></td></tr>";
        $num++;
    }        
    $table .= "</table>";
    echo $table;
}
?>
</body>
