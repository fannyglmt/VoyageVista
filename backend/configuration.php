<?php
 $servername = "localhost";
 $username = "root";
 $password = "";
 $dbname = "voyagevista";
$conn = mysqli_connect($servername,$username,$password,$dbname);
if(!$conn){
    die("Echec de la connexion: " . mysqli_connect_error());
}
?>