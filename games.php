<html>
    <head>
        <title>Game List</title>
    </head>
    <body>
        <pre>
        <?php
            require('connect.php');
            set_time_limit(0);
            
            $sql = "SELECT Id, Name FROM console";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                echo '<form method="POST" action="game_load.php">';
                echo "<select name='consoles' size='10'>";
                while($row = mysqli_fetch_assoc($result)) {
                    echo "<option value='" . $row["Id"] . "'>" . $row["Name"] . "</option>";
                }
                echo "</select>";
                echo "<br><br>";
                echo '<input type="submit">';
                echo "</form>";
            } else {
                echo "0 results";
            }
            mysqli_close($conn);
        ?>
        </pre>
    </body>
</html>