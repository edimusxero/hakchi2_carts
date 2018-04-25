<?php
    
    require('connect.php');
       
    $sql = "SELECT Id FROM games WHERE Players IS NULL";
    
    $result = mysqli_query($conn, $sql);
    set_time_limit(0);

    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $game_id = $row["Id"];
            $metadata   = file_get_contents('http://thegamesdb.net/api/GetGame.php?id=' . $game_id);
            $xml        = new SimpleXMLElement($metadata);
            
            $publisher = $xml->Game->Publisher;
            $players = $xml->Game->Players;
            $co_op = $xml->Game->{'Co-op'};
                       
            if(!$players){
                $players = 1;
            }
            else {
                $players = preg_replace("/[^0-9,.]/", "", $players);
            }
            
            if($co_op == 'Yes'){
                $co_op = 1;
            }
            else{
                $co_op = 0;
            }
            
            if(!$publisher){
                $publisher = 'N/A';
            }
            
            $escaped_title = mysqli_real_escape_string($conn, $publisher);
            
            $select_publisher = "SELECT Id FROM assc_pub WHERE Publisher = '$escaped_title'";

            $pub_result = mysqli_query($conn, $select_publisher);
            if ($pub_result !== false) {
                $row = mysqli_fetch_assoc($pub_result);
                $pub_id = $row['Id'];
            }
            if(!$pub_id){
                $insert_publisher = "INSERT IGNORE INTO assc_pub (Publisher) VALUES ('$escaped_title')";
                
                if ($conn->query($insert_publisher) === TRUE) {
                    $pub_id = $conn->insert_id;
                    echo "Publisher Added<br/>";
                } 
                else {
                    echo "Error: " . $insert_publisher . "<br>" . $conn->error;
                }
            }
            
            $update = "UPDATE games SET Publisher = '$pub_id', Players = $players, `Co-op` = $co_op WHERE Id = $game_id";
            
            if ($conn->query($update) === TRUE) {
                echo "Updated Records<br/>";
            } else {
                echo "Error: " . $update . "<br>" . $conn->error;
            }
        }
    } else {
        echo "<h1>No Results!</h1>";
    }
    
    mysqli_close($conn);
?>
    