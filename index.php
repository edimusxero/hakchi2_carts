<?php
    // This loads all consoles from the gamesdb, creates a dropdown and allows you to load the data to a database
    // It's ugly, but again was only meant as a quick solution
?>
<html>
    <head>
        <title>Game Database</title>
        <script src="//code.jquery.com/jquery-1.12.4.js"></script>
        <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>
        
        <link rel="stylesheet" type="text/css" href="style.css">

        <script type="text/javascript">

            $(document).ready(function(){
                $('#games').DataTable({
                    "order": [[ 0, "asc" ]],
                    "iDisplayLength": 10
                });
            });

        </script>

    </head>
    <body>
        <?php
                $url = file_get_contents('http://thegamesdb.net/api/GetPlatformsList.php');
                $xml = new SimpleXMLElement($url);
                                
                $counter = 0;
                
                $num = count($xml->Platforms->Platform);
                
                echo '<form method="POST" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">';
                echo "<select name='consoles' size='10'>";
                
                while ($counter < $num){
                    $name   = $xml->Platforms->Platform[$counter]->name;
                    $id     = $xml->Platforms->Platform[$counter]->id;
                
                    echo "<option value='" . $id . "'>" . $name . "</option>";
                    $counter++;
                }
                echo "</select>";
                echo "<br><br>";
                echo '<input type="submit">';
                echo "</form>";

                if(isset($_POST['consoles'])){
                    $console = $_POST['consoles'];
                    
                    $game_url = file_get_contents('http://thegamesdb.net/api/GetPlatformGames.php?platform=' . $console);
                    $game_xml = new SimpleXMLElement($game_url);
                    
                    ?>
                    <table id="games" class="display cell-border compact order-column">
                        <thead>
                            <tr>
                                <th>GameTitle</th>
                                <th>ReleaseDate</th>
                                <th>Publisher</th>
                                <th>Players</th>
                            </tr>
                        </thead>
                    <?php
                    
                    $game_counter = 0;
                    $game_count = count($game_xml->Game);
                    
                    set_time_limit(0);
                    
                    while ($game_counter < 5){
                        
                        $game_id = $game_xml->Game[$game_counter]->id;
                        echo '<tr>';
                        echo '<td align="left">' . $game_xml->Game[$game_counter]->GameTitle . '</td>';
                        echo '<td align="left">' . $game_xml->Game[$game_counter]->ReleaseDate . '</td>';
                        
                        $game_info = file_get_contents('http://thegamesdb.net/api/GetGame.php?id=' . $game_id);
                        $info_xml = new SimpleXMLElement($game_info);
                        
                        echo '<td align="left">' . $info_xml->Game[0]->Publisher . '</td>';
                        echo '<td align="left">' . $info_xml->Game[0]->Players . '</td>';
                        
                        echo '</tr>';
                        
                        
                        echo '<pre>';
                        print_r($info_xml);
                        echo '</pre>';
                        $game_counter++;
                    }
                    echo '</table>';
                }
        ?>
    </body>
</html>