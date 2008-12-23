<?php
require('lib/evergreen.class.php');

session_start();

echo "Post: ";
var_dump($_POST);
echo "<br />Get: ";
var_dump($_GET);

new Evergreen();
?>