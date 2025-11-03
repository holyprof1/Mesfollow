<?php
if ($logedIn == 1) {
    if (isset($_GET['v']) && !empty($_GET['v'])) {
        $activationCode = mysqli_real_escape_string($db, $_GET['v']);
        $checkCodeExist = $iN->iN_CheckVerCodeExist($activationCode);

        if ($checkCodeExist) {
            $updateQuery = mysqli_query($db, "UPDATE i_users SET verify_key = '', email_verify_status = 'yes' WHERE verify_key = '$activationCode'");

            if (!$updateQuery) { 
                include("themes/$currentTheme/404.php");
                exit;
            }

            header("Location:$base_url");
            exit;
        } else {
            include("themes/$currentTheme/404.php");
            exit;
        }

    } else {
        include("themes/$currentTheme/404.php");
        exit;
    }
} else {
    header("Location:$base_url");
    exit;
}
?>