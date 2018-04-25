<?php
    
    require('connect.php');
    
    $id = $_POST["consoles"];
    
    $sql = "SELECT Name FROM console WHERE Id = $id";
    
    $result = mysqli_query($conn, $sql);
    if ($result !== false) {
        $row = mysqli_fetch_assoc($result);
        $console_name = $row['Name'];
    } else {
        $console_name = 'Null';
    }
    
    echo "<h1>" . urlencode($console_name) . "</h1>";
    
    $url = file_get_contents('http://thegamesdb.net/api/PlatformGames.php?platform=' . urlencode($console_name));
    $xml = new SimpleXMLElement($url);
    
    $num = count($xml->Game);
        
    $base_url = 'http://thegamesdb.net/banners/';
    
    $counter = 0;
    
    while ($counter < $num){
        $game_id        = $xml->Game[$counter]->id;
        $game_title     = $xml->Game[$counter]->GameTitle;
        $release_date   = $xml->Game[$counter]->ReleaseDate;
        $thumb          = $xml->Game[$counter]->thumb;
        
        if(!$release_date){
            $newDateString = '';
        } elseif(strlen($release_date) === 4){
            $newDateString = $release_date . '-01-01';
        } else {
            $myDateTime = DateTime::createFromFormat('m/d/Y', $release_date);
            $newDateString = $myDateTime->format('Y-m-d');
        }
    
        if(!$thumb){
            $art_path = '';
        } else {
            $art_path = $base_url . $thumb;
        }
        
        $escaped_title = mysqli_real_escape_string($conn, $game_title);

        echo "<br>Id - $game_id | Title - $escaped_title | ReleaseDate - $newDateString | Art - $thumb    -----    ";

        $sql = "INSERT IGNORE INTO `gamedb`.`games` (Id, GameTitle, ReleaseDate, Publisher, Art, Console) VALUES ($game_id, '$escaped_title', '$newDateString',1, '$art_path', $id)";

        print $sql;
        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        
        $counter++;
    }
        mysqli_close($conn);
?>
    