<?php
session_start();
include "../includes/inc.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
//Load Composer's autoloader
require '../includes/phpmailer/vendor/autoload.php';
$mail = new PHPMailer(true);

$genders = array('male', 'couple', 'female');
if ($logedIn == '0') {
	if (empty($_POST['y_email']) || empty($_POST['flname']) || empty($_POST['uusername']) || empty($_POST['y_password']) || empty($_POST['gender'])) {
		echo '1';
	} else {
		$code = md5(rand(1111, 9999) . time());
		$checkUserNameExist = $iN->iN_CheckUsernameExistForRegister($_POST['uusername']);
		$checkEmailExist = $iN->iN_CheckEmailExistForRegister($_POST['y_email']);
		/*Username Exist*/
		if ($checkUserNameExist) {
			exit('2');
		}
		/*User Email Exist*/
		if ($checkEmailExist === true) {
			exit('3');
		}
		if(isset($_POST['refuser'])){
            if($_POST['refuser'] != '' && !empty($_POST['refuser'])){
               if($_POST['uusername'] != $_POST['refuser']){
				   $refUserName = mysqli_real_escape_string($db, $_POST['refuser']);
                   $checkRefUserNameExist = $iN->iN_CheckUsernameExistForRegister($iN->iN_Secure($refUserName));
					if($checkRefUserNameExist && $affilateSystemStatus == 'yes'){
						$refAvailable = 'ok';
					}else{
                        $refAvailable = 'not';
					}
			   }
			}
		} else{
			$refAvailable = '';
		}
		/*Username Character Lenght*/
		if (strlen($_POST['uusername']) < 5 OR strlen($_POST['uusername']) > 32) {
			exit('4');
		}
		/*Invalid Character*/
		if (!preg_match('/^[\w]+$/', $_POST['uusername'])) {
			exit('5');
		}
		$validUserNames = explode(',', $disallowedUserNames);
		if(in_array(strtolower($_POST['uusername']), $validUserNames)){
		  exit('2');
		}
		/*Invalid Email*/
		if (!filter_var($_POST['y_email'], FILTER_VALIDATE_EMAIL)) {
			exit('6');
		}
		/*Password Short*/
		if (strlen($_POST['y_password']) < 6) {
			exit('7');
		}
		$gender = 'male';
		if (isset($_POST['gender']) && in_array($_POST['gender'], $genders)) {
			$gender = $_POST['gender'];
		}
		$get_ip = $iN->iN_GetIPAddress();
		$getIpInfo = $iN->iN_fetchDataFromURL("http://ip-api.com/json/$get_ip");
		$rCountryCode = $rUTimeZone = $rUserCity = $rUserLat = $rUserLon = '';
		$getIpInfo = json_decode($getIpInfo, true);
		if ($getIpInfo['status'] == 'success' && !empty($getIpInfo['regionName']) && !empty($getIpInfo['countryCode']) && !empty($getIpInfo['timezone']) && !empty($getIpInfo['city'])) {
			$registerCountryCode = $getIpInfo['countryCode'];
			$reigsetrUserTimeZone = $getIpInfo['timezone'];
			$registerUserCity = $getIpInfo['city'];
			$registerUserLatitude = $getIpInfo['lat'];
			$registerUserLongitude = $getIpInfo['lon'];
		}
		$registerUserName = $iN->iN_Secure($_POST['uusername']);
		$registerUserFullName = $iN->iN_Secure($_POST['flname']);
		$registerEmail = $iN->iN_Secure($_POST['y_email']);
		$registerGender = $iN->iN_Secure($gender);
		$registerUserName = $iN->iN_Secure($_POST['uusername']);
		$registerUserPassword = $iN->iN_Secure(sha1(md5($_POST['y_password'])));
		$time = time();
		$rCountryCode = !empty($registerCountryCode) ? "'$registerCountryCode'" : "NULL";
		$rUTimeZone = !empty($reigsetrUserTimeZone) ? "'$reigsetrUserTimeZone'" : "NULL";
		$rUserCity = !empty($registerUserCity) ? "'$registerUserCity'" : "NULL";
		$rUserLat = !empty($registerUserLatitude) ? "'$registerUserLatitude'" : "NULL";
		$rUserLon = !empty($registerUserLongitude) ? "'$registerUserLongitude'" : "NULL";
		mysqli_query($db, "SET character_set_client=utf8") or die(mysqli_error($db));
		mysqli_query($db, "SET character_set_connection=utf8") or die(mysqli_error($db));
		$defaultLanguage = strtolower($defaultLanguage);
		$registerUser = mysqli_query($db, "INSERT INTO i_users(i_username,i_user_fullname,i_user_email,i_password,user_gender,registered,profile_category,last_login_time,online_offline_status,countryCode,u_timezone,lat,lon,lang)VALUES('$registerUserName','$registerUserFullName','$registerEmail','$registerUserPassword','$registerGender','$time','normal_user','$time','1',$rCountryCode,$rUTimeZone,$rUserLat,$rUserLon,'$defaultLanguage')") or die(mysqli_error($db));
		if ($registerUser) {
			$query = mysqli_query($db, "SELECT * FROM `i_users` WHERE i_username = '$registerUserName'") or die(mysqli_error($db));
			$uData = mysqli_fetch_array($query, MYSQLI_ASSOC);
			$userID = $uData['iuid'];
			$time = time();
			if($beaCreatorStatus == 'auto_approve'){
				mysqli_query($db, "UPDATE i_users SET certification_status = '2', validation_status = '2', condition_status = '2', fees_status = '2' , payout_status = '1' WHERE iuid = '$userID'") or die(mysqli_error($db));
			}
			mysqli_query($db, "UPDATE i_users SET last_login_time = '$time' WHERE iuid = '$userID'") or die(mysqli_error($db));
			mysqli_query($db, "INSERT INTO `i_friends` (fr_one,fr_two,fr_time,fr_status)VALUES('$userID','$userID','$time', 'me')");
			if($refAvailable == 'ok' && $affilateSystemStatus == 'yes'){
			   $refOwnerData = $iN->iN_GetUserDetailsFromUsername($refUserName);
			   $refOwnerUserID = $iN->iN_Secure($refOwnerData['iuid']);
			   $checkAffiliatedBefore = $iN->iN_CheckUserAffilatedBefore($get_ip,$refOwnerUserID);
			   if($ataAffilateAmount && $checkAffiliatedBefore === true){
				 $registerRefUser = mysqli_query($db,"INSERT INTO `i_refUsers`(ref_owner_user_id, ref_user_id,ref_type, time, ip)VALUES('$refOwnerUserID','$userID','reg', '$time','$get_ip')") or die(mysqli_error($db));
				 mysqli_query($db,"UPDATE i_users SET affilate_earnings = affilate_earnings + '$ataAffilateAmount' WHERE iuid = '$refOwnerUserID'") or die(mysqli_error($db));
			   }
			}
			$hash = sha1($registerUserName) . $time;
			setcookie($cookieName, $hash, time() + 31556926, '/');
			$saveLogin = mysqli_query($db, "INSERT INTO `i_sessions`(session_uid, session_key, session_time) VALUES ('$userID','$hash', '$time')") or die(mysqli_error($db));
			if($autoFollowAdmin == 'yes'){
			    $getAdmins = mysqli_query($db,"SELECT iuid FROM i_users WHERE userType = '2'") or die(mysqli_error($db));
			    if($getAdmins){
			        while($ad = mysqli_fetch_assoc($getAdmins)){
			            $AdminID = isset($ad['iuid']) ? $ad['iuid'] : NULL;
			            if($AdminID){
			                mysqli_query($db, "INSERT INTO `i_friends` (fr_one,fr_two,fr_time,fr_status)VALUES('$userID','$AdminID','$time', 'flwr')") or die(mysqli_error($db));   
			            }
			        }
			    }
			}
			$_SESSION['iuid'] = $userID;
			if ($sendEmailForAll == '1' && !empty($smtpEmail)) {
                mysqli_query($db, "UPDATE i_users SET verify_key = '$code' WHERE iuid = '$userID'") or die(mysqli_error($db));
                $sendEmail = $registerEmail;
                
                try {
                    $mail->isSMTP();
                    $mail->Host = $smtpHost;
                    $mail->SMTPAuth = true;
                    $mail->SMTPKeepAlive = true;
                    $mail->Username = $smtpUserName;
                    $mail->Password = $smtpPassword;
                    $mail->SMTPSecure = $smtpEncryption;
                    $mail->Port = $smtpPort;
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ),
                    );
             
            
                    $instagramIcon = $iN->iN_SelectedMenuIcon('88');
                    $facebookIcon = $iN->iN_SelectedMenuIcon('90');
                    $twitterIcon = $iN->iN_SelectedMenuIcon('34');
                    $linkedinIcon = $iN->iN_SelectedMenuIcon('89');
                    $startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
                    $theCode = $base_url.'verify?v='.$code;
                    include_once '../includes/mailTemplates/verificationTemplate.php';
                    $body = $bodyVerifyEmail;
                    $mail->setFrom($smtpEmail, $siteName);
                    $send = false;
                    $mail->IsHTML(true);
                    $mail->addAddress($sendEmail, '');
                    $mail->Subject = $iN->iN_Secure($LANG['verify_your_email']);
                    $mail->CharSet = 'utf-8';
                    $mail->MsgHTML($body);
            
                    if ($mail->send()) {
                        $mail->ClearAddresses();
                        echo '8';
                        return true;
                    } else {
                        echo '9';
                    }
                } catch (Exception $e) { 
                    $mail->IsMail(); 
            
                    if ($mail->send()) {
                        echo '8';
                        return true;
                    } else {
                        echo '9';
                    }
                }
            }

			if ($saveLogin) {
				exit('8');
			}
		}
	}
}
?>