<?php

    // SQL query to create table
    // CREATE TABLE `tbl_password` ( `password_id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(64) NULL DEFAULT NULL, `user_name` VARCHAR(64) NULL DEFAULT NULL, `pass_word` VARCHAR(64) NULL DEFAULT NULL, PRIMARY KEY (`password_id`) ) COLLATE='utf8mb4_general_ci';

    $server_name = "localhost";
    $username = "root";
    $password = "";
    $db_name = "password_manager";

    $link = mysqli_connect($server_name, $username, $password, $db_name);
    
    // Check connection
    if($link === false){
        die("ERROR: Could not connect. " . mysqli_connect_error());
    }
    
    // Escape user inputs for security
    $name = mysqli_real_escape_string($link, $_REQUEST['name']);
    $user_name = mysqli_real_escape_string($link, $_REQUEST['user_name']);
    $pass_word = mysqli_real_escape_string($link, $_REQUEST['pass_word']);
    
    // Attempt insert query execution
    $sql = "INSERT INTO tbl_password (name, user_name, pass_word) VALUES ('$name', '$user_name', '$pass_word')";
    if(mysqli_query($link, $sql)){
        header('Location: index.php');
    } else{
        echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
    }
    
    // Close connection
    mysqli_close($link);
?>