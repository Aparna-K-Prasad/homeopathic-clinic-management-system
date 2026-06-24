<?php
session_start();

session_unset(); 
session_destroy(); 
echo "<script>sessionStorage.clear();</script>";
header("Location: /minipro/home/home.html"); 
exit();
?>