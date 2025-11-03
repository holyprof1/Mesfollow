<?php 
if($agoraStatus == '1'){
include("themes/$currentTheme/layouts/friends_stories.php");
}else{
  header('Location:'.$base_url.'404');
} 
?>