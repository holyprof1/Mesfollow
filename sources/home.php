<?php
// Redirect to login if not logged in
if($logedIn == '0'){
    header('Location: ' . $base_url . 'login');
    exit();
}

// Show main page for logged-in users
include("themes/$currentTheme/layouts/main.php");
?>