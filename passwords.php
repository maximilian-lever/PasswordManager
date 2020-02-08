<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "password_manager";
    
    $conn = new mysqli($servername, $username, $password, $dbname);// Create connection
    if ($conn->connect_error) {// Check connection
        die("Connection failed: " . $conn->connect_error);
    }
    
    $SQLQuery = "SELECT password_id, name, user_name, pass_word FROM tbl_password";
    $result = $conn->query($SQLQuery);// Runs query

    echo '<head>';
    echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">';//Bootstrap
    echo '<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>';//Bootstrap
    echo '</head>';

    echo '<div style="width: 50%;">';
    echo '<table class="table table-bordered">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Name</th>';
    echo '<th>Username</th>';
    echo '<th>Password</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {// Outputs each row from table        
            echo '<tr>';
            echo '<td>' . $row["password_id"] . '</td>';
            echo '<td>' . $row["name"] . '</td>';
            echo '<td>' . $row["user_name"] . '</td>';
            echo '<td>' . $row["pass_word"] . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    $conn->close();// Closes DB connection
?>