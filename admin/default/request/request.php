<?php
include_once "../../../includes/inc.php";
if ($s3Status == '1') {
	include "../../../includes/s3.php";
}else if($digitalOceanStatus == '1'){
    include "../../../includes/spaces/spaces.php";
}
/*PhpMailer*/
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
//Load Composer's autoloader
require '../../../includes/phpmailer/vendor/autoload.php';
//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);
$statusValue = array('0', '1');
$yesNo = array('no', 'yes');
$beACreatorArray = array('request', 'admin_accept','auto_approve');
$statusTrueFalse = array('false', 'true');
$announcementTypes = array('creators', 'everyone');
$statusSubOneTwo = array('1', '2');
if (isset($_POST['f']) && $logedIn == '1' && $userType == '2') {
	$type = mysqli_real_escape_string($db, $_POST['f']);
	if ($type == 'logoFile' || $type == 'faviconFile') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = mysqli_real_escape_string($db, $_POST['c']);
			$fileReq = isset($_FILES['uploading']['name']) ? $_FILES['uploading']['name'] : NULL;
			if (is_array($fileReq) && !empty($fileReq)) {
				foreach ($fileReq as $iname => $value) {
					$name = stripslashes($_FILES['uploading']['name'][$iname]);
					$size = $_FILES['uploading']['size'][$iname];
					$ext = getExtension($name);
					$ext = strtolower($ext);
					$valid_formats = explode(',', $availableVerificationFileExtensions);
					if (in_array($ext, $valid_formats)) {
						if (convert_to_mb($size) < $availableUploadFileSize) {
							$microtime = microtime();
							$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
							$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
							$getFilename = $UploadedFileName . "." . $ext;
							// Change the image ame
							$tmp = $_FILES['uploading']['tmp_name'][$iname];
							$mimeType = $_FILES['uploading']['type'][$iname];
							$d = date('Y-m-d');
							if (preg_match('/video\/*/', $mimeType)) {
								$fileTypeIs = 'video';
							} else if (preg_match('/image\/*/', $mimeType)) {
								$fileTypeIs = 'Image';
							}
							if (!file_exists($uploadIconLogo . $d)) {
								$newFile = mkdir($uploadIconLogo . $d, 0755);
							}
							if (!file_exists($xImages . $d)) {
								$newFile = mkdir($xImages . $d, 0755);
							}
							if (move_uploaded_file($tmp, $uploadIconLogo . $d . '/' . $getFilename)) {
								/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
								if ($fileTypeIs == 'Image') {
									$pathFile = 'img/' . $d . '/' . $getFilename;
									$UploadSourceUrl = $base_url . 'img/' . $d . '/' . $getFilename;
								}
								echo 'img/' . $d . '/' . $getFilename;
							}
						} else {
							echo iN_HelpSecure($size);
						}
					}
				}
			}
		}
	}
	/*Update Site General Settings*/
	if ($type == 'updateGeneral') {
		$updateSiteLogo = mysqli_real_escape_string($db, $_POST['logo']);
		$updateSiteFavicon = mysqli_real_escape_string($db, $_POST['favicon']);
		$updateWAtermark = mysqli_real_escape_string($db, $_POST['walogo']);
		$updateSiteKeywords = mysqli_real_escape_string($db, $_POST['site_keywords']);
		$updateSiteDescription = mysqli_real_escape_string($db, $_POST['site_description']);
		$updateSiteTitle = mysqli_real_escape_string($db, $_POST['site_title']);
		$updateSiteName = mysqli_real_escape_string($db, $_POST['site_name']);
		$updateSiteConfirugarion = $iN->iN_UpdateSiteConfiguration($userID, $iN->iN_Secure($updateWAtermark),$iN->iN_Secure($updateSiteLogo), $iN->iN_Secure($updateSiteFavicon), $iN->iN_Secure($updateSiteKeywords), $iN->iN_Secure($updateSiteDescription), $iN->iN_Secure($updateSiteTitle), $iN->iN_Secure($updateSiteName));
		if ($updateSiteConfirugarion) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Update Site Business Informations*/
	if ($type == 'updateBusiness') {
		$updateSiteCampanyName = mysqli_real_escape_string($db, $_POST['site_campany']);
		$updateSiteCountry = mysqli_real_escape_string($db, $_POST['country_code']);
		$updateSiteCity = mysqli_real_escape_string($db, $_POST['site_city']);
		$updateSiteBusinessAddress = mysqli_real_escape_string($db, $_POST['site_business_address']);
		$updateSitePostCode = mysqli_real_escape_string($db, $_POST['site_post_code']);
		$updateSiteVAT = mysqli_real_escape_string($db, $_POST['site_vat']);
		if (empty($updateSiteCampanyName) || empty($updateSiteCountry) || empty($updateSiteCity) || empty($updateSiteBusinessAddress) || empty($updateSitePostCode) || empty($updateSiteVAT)) {
			exit('1');
		}
		$updateSiteBusinessInformations = $iN->iN_UpdateSiteBusinessInformations($userID, $iN->iN_Secure($updateSiteCampanyName), $iN->iN_Secure($updateSiteCountry), $iN->iN_Secure($updateSiteCity), $iN->iN_Secure($updateSiteBusinessAddress), $iN->iN_Secure($updateSitePostCode), $iN->iN_Secure($updateSiteVAT));
		if ($updateSiteBusinessInformations) {
			exit('200');
		} else {
			echo '404';
		}
	}
	if ($type == 'updateLimits') {
		$fileLimit = mysqli_real_escape_string($db, $_POST['file_limit']);
		$lengthLimit = mysqli_real_escape_string($db, $_POST['length_limit']);
		$postShowLimit = mysqli_real_escape_string($db, $_POST['post_show_limit']);
		$paginatonLimit = mysqli_real_escape_string($db, $_POST['pagination_limit']);
		$approvalFileExtension = mysqli_real_escape_string($db, $_POST['available_verification_file_extensions']);
		$availableUploadFileExtensions = mysqli_real_escape_string($db, $_POST['available_file_extensions']);
		$unavailableUsernames = mysqli_real_escape_string($db, $_POST['unavailable_usernames']);
		$ffmpeg_path = mysqli_real_escape_string($db, $_POST['ffmpeg_path']);
		$postCreateStatus = mysqli_real_escape_string($db, $_POST['postCreateStatus']);
		$reCaptchaStatus = isset($_POST['reCreateStatus']) ? $_POST['reCreateStatus'] : 'no';
		$blockCountryStatus = mysqli_real_escape_string($db, $_POST['blockCountriesStatus']);
		$reCaptchaSiteKey = mysqli_real_escape_string($db, $_POST['rsitekey']);
		$reCaptchaSecretKey = mysqli_real_escape_string($db, $_POST['rseckey']);
		$oneSignalApiKey = mysqli_real_escape_string($db, $_POST['onesignalapikey']);
		$oneSignalRestApiKey = mysqli_real_escape_string($db, $_POST['onesignalrestapikey']);
		$oneSignalStatus = isset($_POST['oneSignalStatus']) ? $_POST['oneSignalStatus'] : 'close';
		$messageLimit = mysqli_real_escape_string($db, $_POST['message_show_limit']);
		$adsShowLimit = mysqli_real_escape_string($db, $_POST['ads_show_limit']);
		$sugUserShowLimit = mysqli_real_escape_string($db, $_POST['suggu_show_limit']);
		$sugProductShowLimit = mysqli_real_escape_string($db, $_POST['prod_show_limit']);
		$TrendPostShowLimit = mysqli_real_escape_string($db, $_POST['trend_show_limit']);
		$friendActivityShowLimit = mysqli_real_escape_string($db, $_POST['activity_show_limit']);
		$friendActivityShowTimeLimit = mysqli_real_escape_string($db, $_POST['activity_show_time_limit']);


		if (empty($availableUploadFileExtensions) || $availableUploadFileExtensions == '') {
			exit('1');
		}
		if (empty($approvalFileExtension) || $approvalFileExtension == '') {
			exit('2');
		}
		$unavailableUsernames = strtolower($unavailableUsernames);
		$updateLimitValues = $iN->iN_UpdateLimitValues($userID,$iN->iN_Secure($friendActivityShowTimeLimit),$iN->iN_Secure($friendActivityShowLimit),$iN->iN_Secure($TrendPostShowLimit),$iN->iN_Secure($sugProductShowLimit),$iN->iN_Secure($sugUserShowLimit),$iN->iN_Secure($oneSignalStatus),$iN->iN_Secure($oneSignalApiKey),$iN->iN_Secure($oneSignalRestApiKey), $iN->iN_Secure($reCaptchaStatus),$iN->iN_Secure($reCaptchaSiteKey),$iN->iN_Secure($reCaptchaSecretKey),$iN->iN_Secure($postCreateStatus),$iN->iN_Secure($blockCountryStatus), $iN->iN_Secure($fileLimit), $iN->iN_Secure($lengthLimit), $iN->iN_Secure($postShowLimit), $iN->iN_Secure($paginatonLimit), $iN->iN_Secure($approvalFileExtension), $iN->iN_Secure($availableUploadFileExtensions), $iN->iN_Secure($ffmpeg_path), $iN->iN_Secure($unavailableUsernames), $iN->iN_Secure($messageLimit) , $iN->iN_Secure($adsShowLimit));
		if ($updateLimitValues) {
			exit('200');
		} else {
			echo '404';
		}
	}

	if ($type == 'updateDefaultLang') {
		if (isset($_POST['lang'])) {
			$lang = mysqli_real_escape_string($db, $_POST['lang']);
			$updateDefaultLang = $iN->iN_UpdateDefaultLanguage($userID, $iN->iN_Secure($lang));
			if ($updateDefaultLang) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Maintenance Mode Status*/
	if ($type == 'maintenance_status') {
		if (in_array($_POST['mod'], $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateMaintenanceStatus = $iN->iN_UpdateMaintenanceStatus($userID, $iN->iN_Secure($mod));
			if ($updateMaintenanceStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Email Send Mode Status*/
	if ($type == 'email_verification_status') {
		if (in_array($_POST['mod'], $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateEmailSendStatus = $iN->iN_UpdateEmailSendStatus($userID, $iN->iN_Secure($mod));
			if ($updateEmailSendStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Register Status*/
	if ($type == 'register_new') {
		if (in_array($_POST['mod'], $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateRegisterStatus = $iN->iN_UpdateRegisterStatus($userID, $iN->iN_Secure($mod));
			if ($updateRegisterStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update ip Limit Status*/
	if ($type == 'ipLimit') {
		if (in_array($_POST['mod'], $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateipLimitStatus = $iN->iN_UpdateIpLimitStatus($userID, $iN->iN_Secure($mod));
			if ($updateipLimitStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Email Settings*/
	if ($type == 'emailSettings') {
		$updateSmtpMail = mysqli_real_escape_string($db, $_POST['smtpmail']);
		$updateSmtpEncription = mysqli_real_escape_string($db, $_POST['smtpecript']);
		$updateSmtpHost = mysqli_real_escape_string($db, $_POST['smtp_host']);
		$updateSmtpUsername = mysqli_real_escape_string($db, $_POST['smtp_username']);
		$updateSmtpPassword = mysqli_real_escape_string($db, $_POST['smtp_password']);
		$updateSmtpPort = mysqli_real_escape_string($db, $_POST['smtp_port']);
		$updateSmtpEmail = mysqli_real_escape_string($db, $_POST['smtp_host_email']);
		if (empty($updateSmtpHost) || empty($updateSmtpUsername) || empty($updateSmtpPassword) || empty($updateSmtpPort)) {
			exit('1');
		}
		$updateEmailSettings = $iN->iN_UpdateEmailSettings($userID, $iN->iN_Secure($updateSmtpEmail), $iN->iN_Secure($updateSmtpMail), $iN->iN_Secure($updateSmtpEncription), $iN->iN_Secure($updateSmtpHost), $iN->iN_Secure($updateSmtpUsername), $iN->iN_Secure($updateSmtpPassword), $iN->iN_Secure($updateSmtpPort));
		if ($updateEmailSettings) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Update Amazon S3 Storage Details*/
	if ($type == 's3Settings') {
		$updateS3Region = mysqli_real_escape_string($db, $_POST['s3region']);
		$updateS3Bucket = mysqli_real_escape_string($db, $_POST['s3Bucket']);
		$updateS3Key = mysqli_real_escape_string($db, $_POST['s3Key']);
		$updateS3SecretKey = mysqli_real_escape_string($db, $_POST['s3sKey']);
		$updateS3Status = mysqli_real_escape_string($db, $_POST['s3Status']);
		$updateS3Settings = $iN->iN_UpdateAmazonS3Details($userID, $iN->iN_Secure($updateS3Region), $iN->iN_Secure($updateS3Bucket), $iN->iN_Secure($updateS3Key), $iN->iN_Secure($updateS3SecretKey), $iN->iN_Secure($updateS3Status));
		if ($updateS3Settings) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Update Amazon S3 Storage Details*/
	if ($type == 'WasSettings') {
		$updateWasRegion = mysqli_real_escape_string($db, $_POST['wasregion']);
		$updateWasBucket = mysqli_real_escape_string($db, $_POST['wasBucket']);
		$updateWasKey = mysqli_real_escape_string($db, $_POST['wasKey']);
		$updateWasSecretKey = mysqli_real_escape_string($db, $_POST['wassKey']);
		$updateWasStatus = mysqli_real_escape_string($db, $_POST['wasStatus']);
		$updateWasSettings = $iN->iN_UpdateWasabiDetails($userID, $iN->iN_Secure($updateWasRegion), $iN->iN_Secure($updateWasBucket), $iN->iN_Secure($updateWasKey), $iN->iN_Secure($updateWasSecretKey), $iN->iN_Secure($updateWasStatus));
		if ($updateWasSettings) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Approve / Decline / Reject Pot*/
	if ($type == "postApprove") {
		$postDescription = mysqli_real_escape_string($db, $_POST['newpostDesc']);
		$postNewPoint = mysqli_real_escape_string($db, $_POST['newPostPoint']);
		$postApproveStat = mysqli_real_escape_string($db, $_POST['postApproveStatus']);
		$approvePostOwnerID = mysqli_real_escape_string($db, $_POST['postOwnerID']);
		$approvePostID = mysqli_real_escape_string($db, $_POST['postID']);
		$postApproveNot = mysqli_real_escape_string($db, $_POST['approve_not']);
		if (!isset($postApproveStat) || empty($postApproveStat) || $postApproveStat == '') {
			exit('You should Select the Post Status Approve, Decline or Reject');
		}
		if ($postNewPoint < $minimumPointLimit) {
			exit(preg_replace('/{.*?}/', $minimumPointLimit, $LANG['plan_point_warning']));
		}
		$approveUpdate = $iN->iN_UpdateApprovePostStatus($userID, $iN->iN_Secure($postDescription), $iN->iN_Secure($postNewPoint), $iN->iN_Secure($postApproveStat), $iN->iN_Secure($approvePostID), $iN->iN_Secure($approvePostOwnerID), $iN->iN_Secure($postApproveNot));
		if ($approveUpdate) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
	/*Delete Post*/
	if ($type == 'deletePost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			if(!empty($postID) && $digitalOceanStatus == '1'){
				$getPostFileIDs = $iN->iN_GetAllPostDetails($postID);
				$postFileIDs = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : NULL;
				$trimValue = rtrim($postFileIDs, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach ($explodeFiles as $explodeFile) {
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					if($theFileID){
						$uploadedFileID = $theFileID['upload_id'];
						$uploadedFilePath = $theFileID['uploaded_file_path'];
						$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
						$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
						$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
						$my_space->DeleteObject($uploadedFilePath);

						$space_two = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
						$space_two->DeleteObject($uploadedFilePathX);

						$space_tree = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
						$space_tree->DeleteObject($uploadedTumbnailFilePath);
						mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
					}
				}
				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorageAdmin($userID, $iN->iN_Secure($postID));
				if($deleteStoragePost){
				    echo '200';
				}else{
					echo '404';
				}
			}else if(!empty($postID) && $s3Status == '1'){
				$getPostFileIDs = $iN->iN_GetAllPostDetails($postID);
				$postFileIDs = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : NULL;
				$trimValue = rtrim($postFileIDs, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach ($explodeFiles as $explodeFile) {
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					if($theFileID){
						$uploadedFileID = $theFileID['upload_id'];
						$uploadedFilePath = $theFileID['uploaded_file_path'];
						$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
						$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
						$s3->deleteObject([
							'Bucket' => $s3Bucket,
							'Key'    => $uploadedFilePath,
						]);
						$s3->deleteObject([
							'Bucket' => $s3Bucket,
							'Key'    => $uploadedFilePathX,
						]);
						$s3->deleteObject([
							'Bucket' => $s3Bucket,
							'Key'    => $uploadedTumbnailFilePath,
						]);
						mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID'");
					}
				}
				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorageAdmin($userID, $iN->iN_Secure($postID));
				if($deleteStoragePost){
				    echo '200';
				}else{
					echo '404';
				}
			}else if(!empty($postID) && $WasStatus == '1'){
				$getPostFileIDs = $iN->iN_GetAllPostDetails($postID);
				$postFileIDs = isset($getPostFileIDs['post_file']) ? $getPostFileIDs['post_file'] : NULL;
				$trimValue = rtrim($postFileIDs, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach ($explodeFiles as $explodeFile) {
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					if($theFileID){
						$uploadedFileID = $theFileID['upload_id'];
						$uploadedFilePath = $theFileID['uploaded_file_path'];
						$uploadedTumbnailFilePath = $theFileID['upload_tumbnail_file_path'];
						$uploadedFilePathX = $theFileID['uploaded_x_file_path'];
						$s3->deleteObject([
							'Bucket' => $WasBucket,
							'Key'    => $uploadedFilePath,
						]);
						$s3->deleteObject([
							'Bucket' => $WasBucket,
							'Key'    => $uploadedFilePathX,
						]);
						$s3->deleteObject([
							'Bucket' => $WasBucket,
							'Key'    => $uploadedTumbnailFilePath,
						]);
						mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID'");
					}
				}
				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorageAdmin($userID, $postID);
				if($deleteStoragePost){
				    if($ataNewPostPointSatus == 'yes'){$iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);}
				    echo '200';
				}else{
					echo '404';
				}
			}else if(!empty($postID)){
				$deletePostFromData = $iN->iN_DeletePostAdmin($userID, $postID);
				if ($deletePostFromData) {
					echo '200';
				} else {
					echo '404';
				}
			}
		}
	}
	/*Delete Question*/
	if ($type == 'deleteQuest') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$deletePost = $iN->iN_DeleteQuestion($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Report*/
	if ($type == 'deleteReport') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$deletePost = $iN->iN_DeleteReport($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Edit Post*/
	if ($type == "editPostDetails") {
		$postDescription = mysqli_real_escape_string($db, $_POST['newpostDesc']);
		$editedPostOwnerID = mysqli_real_escape_string($db, $_POST['postOwnerID']);
		$editedPostID = mysqli_real_escape_string($db, $_POST['postID']);
		$postUpdate = $iN->iN_UpdatePostDetailsAdmin($userID, $iN->iN_Secure($postDescription), $iN->iN_Secure($editedPostID), $iN->iN_Secure($editedPostOwnerID));
		if ($postUpdate) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Edit Post*/
	if ($type == "customCodes") {
		$customCssCode = mysqli_real_escape_string($db, $_POST['customCss']);
		$customHeaderJsCode = mysqli_real_escape_string($db, $_POST['customHeaderJs']);
		$customFooterJsCode = mysqli_real_escape_string($db, $_POST['customFooterJs']);
		$updateCustomCssCode = $iN->iN_UpdateCustomCodes($userID, $customCssCode, '1');
		$updateCustomHeaderJSCode = $iN->iN_UpdateCustomCodes($userID, $customHeaderJsCode, '2');
		$updateCustomFooterJsCode = $iN->iN_UpdateCustomCodes($userID, $customFooterJsCode, '3');
		exit('200');
	}
	/*Edited SVG*/
	if ($type == 'editedSVG') {
		$svgCode = mysqli_real_escape_string($db, $_POST['svgcode']);
		$iconID = mysqli_real_escape_string($db, $_POST['iconid']);
		if (!substr_count($svgCode, '<svg')) {
			exit('2');
		}
		if (empty($svgCode) || $svgCode == '') {
			exit('1');
		}
		$updateSvgCode = $iN->iN_UpdateSVGCode($userID, $iN->iN_Secure($iconID), $svgCode);
		if ($updateSvgCode) {
			exit('200');
		} else {
			exit($LANG['save_failed']);
		}
	}
	/*Update Icon SVG Status*/
	if ($type == 'iconSVGStatus') {
		if (in_array($_POST['mod'], $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$iconID = mysqli_real_escape_string($db, $_POST['svg']);
			$updateIconSVGStatus = $iN->iN_UpdateSVGIconStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($iconID));
			if ($updateIconSVGStatus) {
				exit('200');
			} else {
				exit($LANG['noway_desc']);
			}
		}
	}
	/*Save New Svg Code*/
	if ($type == 'newSVG') {
		if (isset($_POST['newsvgcode']) && !empty($_POST['newsvgcode']) && $_POST['newsvgcode'] != '') {
			$newSVGCode = mysqli_real_escape_string($db, $_POST['newsvgcode']);
			if (!substr_count($newSVGCode, '<svg')) {
				exit('2');
			}
			$insertNewSVGCode = $iN->iN_InsertNewSVGCode($userID, $newSVGCode);
			if ($insertNewSVGCode) {
				exit('200');
			} else {
				exit($LANG['save_failed']);
			}
		} else {
			exit('1');
		}
	}
	/*Edit Plan*/
	if ($type == 'editPlan') {
		if (isset($_POST['planKey']) && isset($_POST['planPoint']) && isset($_POST['pointAmount']) && isset($_POST['planid'])) {
			$planKey = mysqli_real_escape_string($db, $_POST['planKey']);
			$planPoint = mysqli_real_escape_string($db, $_POST['planPoint']);
			$planAmount = mysqli_real_escape_string($db, $_POST['pointAmount']);
			$planID = mysqli_real_escape_string($db, $_POST['planid']);
			$removeAllSpaceFromKey = preg_replace('/\s+/', '', $planKey);
			if (ctype_space($planPoint) || empty($planPoint)) {
				exit(preg_replace('/{.*?}/', $minimumPointLimit, $LANG['plan_point_warning']));
			}
			if (ctype_space($planAmount) || empty($planAmount)) {
				exit(preg_replace('/{.*?}/', $maximumPointAmountLimit, $LANG['plan_point_amount_warning']));
			}
			if (ctype_space($planKey) || !isset($planKey) || empty($planKey)) {
				exit($LANG['plan_key_warning']);
			}
			if (empty($removeAllSpaceFromKey) || $removeAllSpaceFromKey == '' || empty($removeAllSpaceFromKey) || strlen($removeAllSpaceFromKey) == '0' || ctype_space($removeAllSpaceFromKey)) {
				exit('404');
			} else {
				$updatePlan = $iN->iN_UpdatePlanFromID($userID, $iN->iN_Secure($planKey), $iN->iN_Secure($planPoint), $iN->iN_Secure($planAmount), $iN->iN_Secure($planID));
				if ($updatePlan) {
					exit('200');
				} else {
					exit($LANG['noway_desc']);
				}
			}
		}
	}

	/*Add New Point Plan*/
	if ($type == 'newPackageForm') {
		if (isset($_POST['planKey']) && isset($_POST['planPoint']) && isset($_POST['pointAmount'])) {
			$planKey = mysqli_real_escape_string($db, $_POST['planKey']);
			$planPoint = mysqli_real_escape_string($db, $_POST['planPoint']);
			$planAmount = mysqli_real_escape_string($db, $_POST['pointAmount']);
			$removeAllSpaceFromKey = preg_replace('/\s+/', '', $planKey);
			if (ctype_space($planKey) || !isset($planKey) || empty($planKey)) {
				exit('4');
			}
			if ($planPoint < $minimumPointLimit || ctype_space($planPoint)) {
				exit('1');
			}
			if ($planAmount > $maximumPointAmountLimit || ctype_space($planAmount) || empty($planAmount)) {
				exit('3');
			}
			$updatePlan = $iN->iN_InsertNewPointPlan($userID, $iN->iN_Secure($planKey), $iN->iN_Secure($planPoint), $iN->iN_Secure($planAmount));
			if ($updatePlan) {
				exit('200');
			} else {
				exit($LANG['noway_desc']);
			}
		} else {
			echo '5';
		}
	}
	/*Change Plan Status*/
	if ($type == 'planStatus') {
		if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$planID = mysqli_real_escape_string($db, $_POST['id']);
			$updatePlanStatus = $iN->iN_UpdatePlanStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($planID));
			if ($updatePlanStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Post*/
	if ($type == 'deleteThisPlan') {
		if (isset($_POST['id'])) {
			$planID = mysqli_real_escape_string($db, $_POST['id']);
			$deletePlan = $iN->iN_DeletePlanFromData($userID, $iN->iN_Secure($planID));
			if ($deletePlan) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Language Status*/
	if ($type == 'upLang') {
		if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$langID = mysqli_real_escape_string($db, $_POST['id']);
			$updateLanguageStatus = $iN->iN_UpdateLanguageStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($langID));
			if ($updateLanguageStatus) {
				exit('200');
			} else {
				exit($LANG['noway_desc']);
			}
		}
	}
	/*Add New Point Plan*/
	if ($type == 'editLanguage') {
		if (isset($_POST['langabbreviationName']) && isset($_POST['id'])) {
			$langKey = mysqli_real_escape_string($db, $_POST['langabbreviationName']);
			$langID = mysqli_real_escape_string($db, $_POST['id']);
			$removeSpaceFromLangKEY = preg_replace('/\s+/', '', $langKey);
			if (ctype_space($langKey) || !isset($langKey)) {
				exit('1');
			}
			if (!array_key_exists($langKey, $LANGNAME)) {
				exit('3');
			}
			$updateLanguage = $iN->iN_UpdateLanguageByID($userID, $iN->iN_Secure($langKey), $iN->iN_Secure($langID));
			if ($updateLanguage) {
				exit('200');
			} else {
				echo '404';
			}
		} else {
			echo '2';
		}
	}
	/*Add New Language*/
	if ($type == 'addNewLanguage') {
		if (isset($_POST['newLangAbbreviation'])) {
			$langKey = mysqli_real_escape_string($db, $_POST['newLangAbbreviation']);
			if (ctype_space($langKey) || !isset($langKey) || empty($langKey)) {
				exit('1');
			}
			if (!array_key_exists($langKey, $LANGNAME)) {
				exit('2');
			}
			$addNewLanguage = $iN->iN_AddNewLanguageFromData($userID, $iN->iN_Secure($langKey));
			if ($addNewLanguage) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Language*/
	if ($type == 'deleteThisLanguage') {
		if (isset($_POST['id'])) {
			$langID = mysqli_real_escape_string($db, $_POST['id']);
			if (ctype_space($langID) || !isset($langID) || empty($langID)) {
				exit('1');
			}
			$deleteLanguage = $iN->iN_DeleteLanguage($userID, $iN->iN_Secure($langID));
			if ($deleteLanguage) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Edit User Details*/
	if ($type == 'editUserDetails') {
		if (isset($_POST['verification']) && isset($_POST['usertype']) && isset($_POST['uwallet']) && isset($_POST['u'])) {
			$updateVerification = mysqli_real_escape_string($db, $_POST['verification']);
			$updateUserType = mysqli_real_escape_string($db, $_POST['usertype']);
			$updateUserWallet = mysqli_real_escape_string($db, $_POST['uwallet']);
			$updatedUser = mysqli_real_escape_string($db, $_POST['u']);

			if (empty($updateUserWallet)) {
				$updateUserWallet = '0';
			}
			$update = $iN->iN_UpdateUserProfile($userID, $iN->iN_Secure($updatedUser), $iN->iN_Secure($updateVerification), $iN->iN_Secure($updateUserType), $iN->iN_Secure($updateUserWallet));
			if ($update) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete User*/
	if ($type == 'deleteUser') {
		if (isset($_POST['id'])) {
			$deleteUserID = mysqli_real_escape_string($db, $_POST['id']);
			$deleteUser = $iN->iN_DeleteUser($userID, $iN->iN_Secure($deleteUserID));
			if ($deleteUser) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete User Verification Request*/
	if ($type == 'deleteUserVerification') {
		if (isset($_POST['id'])) {
			$verificationRequestID = mysqli_real_escape_string($db, $_POST['id']);
			$deleteVRequest = $iN->iN_DeleteVerificationRequest($userID, $iN->iN_Secure($verificationRequestID));
			if ($deleteVRequest) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Approve or Reject Verification Request*/
	if ($type == 'updateVerificationStatus') {
		if (isset($_POST['vID']) && isset($_POST['vApproveStatus'])) {
			$answerType = mysqli_real_escape_string($db, $_POST['vApproveStatus']);
			$answerValue = mysqli_real_escape_string($db, $_POST['approve_not']);
			$answeringVerificationID = mysqli_real_escape_string($db, $_POST['vID']);
			if (empty($answerType)) {
				exit('1');
			}
			if($answerType == '1'){
               $emailBody = $iN->iN_Secure($LANG['verification_accepted_email_not']);
			   $emailTitle = $iN->iN_Secure($LANG['your_confirmation_accepted_email_title']);
			   $finishButton = $iN->iN_Secure($LANG['finish_your_confirmation']);
			}else{
               $emailBody = $iN->iN_Secure($LANG['verification_declined_email_not']);
			   $emailTitle = $iN->iN_Secure($LANG['your_confirmation_declined_email_title']);
			   $finishButton = $iN->iN_Secure($LANG['re_send_your_verification_request']);
			}
			$InsertAnswer = $iN->iN_UpdateVerificationProfileStatus($userID, $iN->iN_Secure($answerType), $iN->iN_Secure($answerValue), $iN->iN_Secure($answeringVerificationID));
			if ($InsertAnswer) {
				$dataV = $iN->iN_GetVerificationRequestFromID($answeringVerificationID);
				$iuIDfk = $dataV['iuid_fk'];
				$dataEmail = $iN->iN_GetUserDetails($iuIDfk);
				$sendEmail = $dataEmail['i_user_email'];
				$wrapperStyle = "width:100%; border-radius:3px; background-color:#fafafa; text-align:center; padding:50px 0; overflow:hidden; display:flex; display:-webkit-flex;";
                $containerStyle = "width:100%; max-width:600px; border:1px solid #e6e6e6; margin:0 auto; background-color:#ffffff; padding:15px; border-radius:3px;";
                $logoBoxStyle = "width:100%; max-width:100px; margin:0 auto 30px auto; overflow:hidden;";
                $imgStyle = "width:100%; overflow:hidden;";
                $contentStyle = "width:100%; position:relative; display:inline-block; padding-bottom:10px;";
                $buttonBoxStyle = "width:100%; position:relative; padding:10px; background-color:#20B91A; max-width:350px; margin:0 auto; color:#ffffff !important;";
                $linkStyle = "text-decoration:none; color:#ffffff !important; font-weight:500; font-size:14px; position:relative;";

				if ($emailSendStatus == '1') {
					if ($smtpOrMail == 'mail') {
						$mail->IsMail();
					} else if ($smtpOrMail == 'smtp') {
						$mail->isSMTP();
						$mail->Host = $smtpHost; // Specify main and backup SMTP servers
						$mail->SMTPAuth = true;
						$mail->SMTPKeepAlive = true;
						$mail->Username = $smtpUserName; // SMTP username
						$mail->Password = $smtpPassword; // SMTP password
						$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
						$mail->Port = $smtpPort;
						$mail->SMTPOptions = array(
							'ssl' => array(
								'verify_peer' => false,
								'verify_peer_name' => false,
								'allow_self_signed' => true,
							),
						);
					} else {
						return false;
					}
					$body = '
                    <div style="' . $wrapperStyle . '">
                      <div style="' . $containerStyle . '">

                        <div style="' . $logoBoxStyle . '">
                          <img src="' . $siteLogoUrl . '" style="' . $imgStyle . '" />
                        </div>

                        <div style="' . $contentStyle . '">
                          ' . $emailBody . '
                        </div>

                        <div style="' . $buttonBoxStyle . '">
                          <a href="' . $base_url . '" style="' . $linkStyle . '">' . $finishButton . '</a>
                        </div>

                      </div>
                    </div>';
					$mail->setFrom($smtpEmail, $siteName);
					$send = false;
					$mail->IsHTML(true);
					$mail->addAddress($sendEmail, ''); // Add a recipient
					$mail->Subject = $emailTitle;
					$mail->CharSet = 'utf-8';
					$mail->MsgHTML($body);
					if ($mail->send()) {
						$mail->ClearAddresses();
						echo '200';
						return true;
					}
					/***********************************/
				}
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update Page Details*/
	if ($type == 'editPage') {
		if (isset($_POST['page_title']) && isset($_POST['page_seo_url']) && isset($_POST['editor']) && isset($_POST['pageID'])) {
			$pageTitle = mysqli_real_escape_string($db, $_POST['page_title']);
			$pageSeoUrl = mysqli_real_escape_string($db, $_POST['page_seo_url']);
			$pageEditor = mysqli_real_escape_string($db, $_POST['editor']);
			$pageID = mysqli_real_escape_string($db, $_POST['pageID']);
			$pageEditor = $iN->xss_clean($pageEditor);
			if (empty($pageTitle)) {
				exit('1');
			}
			if (empty($pageSeoUrl)) {
				exit('2');
			}
			$savePageEdit = $iN->iN_SavePageEdit($userID, $iN->iN_Secure($pageTitle), $iN->iN_Secure($iN->url_slugies($pageSeoUrl)), $iN->iN_strip_unsafe($pageEditor), $iN->iN_Secure($pageID));
			if ($savePageEdit) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Create a New Page*/
	if ($type == 'createNewPage') {
		if (isset($_POST['page_title']) && isset($_POST['page_seo_url']) && isset($_POST['editor'])) {
			$pageTitle = mysqli_real_escape_string($db, $_POST['page_title']);
			$pageSeoUrl = mysqli_real_escape_string($db, $_POST['page_seo_url']);
			$pageEditor = mysqli_real_escape_string($db, $_POST['editor']);
			$pageEditor = $iN->xss_clean($pageEditor);
			if (empty($pageTitle)) {
				exit('1');
			}
			if (empty($pageSeoUrl)) {
				exit('2');
			}
			$createANewPage = $iN->iN_CreateANewPage($userID, $iN->iN_Secure($pageTitle), $iN->iN_Secure($iN->url_slugies($pageSeoUrl)), $iN->iN_strip_unsafe($pageEditor));
			if ($createANewPage) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete Post*/
	if ($type == 'deletePage') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$deletePost = $iN->iN_DeletePage($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete QA*/
	if ($type == 'deleteQA') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$deletePost = $iN->iN_DeleteQA($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}

	/*Edited Sticker URL*/
	if ($type == 'stickerEdit') {
		if (isset($_POST['stickerURL']) && isset($_POST['sid'])) {
			$stickerUrl = mysqli_real_escape_string($db, $_POST['stickerURL']);
			$sID = mysqli_real_escape_string($db, $_POST['sid']);
			if (ctype_space($stickerUrl) || !isset($stickerUrl) || empty($stickerUrl)) {
				exit('1');
			}
			if (filter_var($stickerUrl, FILTER_VALIDATE_URL) === FALSE) {
				exit('2');
			}
			if (!preg_match('/\.(jpeg|jpg|png|gif)$/i', $stickerUrl)) {
				exit('3');
			}
			$updateStickerURL = $iN->iN_UpdateStickerURL($userID, $iN->iN_Secure($stickerUrl), $iN->iN_Secure($sID));
			if ($updateStickerURL) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete User*/
	if ($type == 'deleteSticker') {
		if (isset($_POST['id'])) {
			$deleteStickerID = mysqli_real_escape_string($db, $_POST['id']);
			$deleteSTicker = $iN->iN_DeleteSticker($userID, $iN->iN_Secure($deleteStickerID));
			if ($deleteSTicker) {
				exit('200');
			} else {
				exit($LANG['sticker_id_not_available']);
			}
		}
	}
/*Add New Sticker Url*/
	if ($type == 'stickerNew') {
		if (isset($_POST['stickerURL'])) {
			$newStickerUrl = mysqli_real_escape_string($db, $_POST['stickerURL']);
			if (ctype_space($newStickerUrl) || !isset($newStickerUrl) || empty($newStickerUrl)) {
				exit('1');
			}
			if (filter_var($newStickerUrl, FILTER_VALIDATE_URL) === FALSE) {
				exit('2');
			}
			if (!preg_match('/\.(jpeg|jpg|png|gif)$/i', $newStickerUrl)) {
				exit('3');
			}
			$insertNewSticker = $iN->iN_InsertNewStickerURL($userID, $iN->iN_Secure($newStickerUrl));
			if ($insertNewSticker) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		} else {
			exit('1');
		}
	}
/*Update Sticker Status*/
	if ($type == 'upStick') {
		if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$langID = mysqli_real_escape_string($db, $_POST['id']);
			$updateStickerStatus = $iN->iN_UpdateStickerStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($langID));
			if ($updateStickerStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update Payment Settings*/
	if ($type == 'paymentSettings') {
		if (isset($_POST['default_currency']) && isset($_POST['fee_comission']) && isset($_POST['min_point_amount']) && isset($_POST['min_sub_weekly']) && isset($_POST['min_sub_monthly']) && isset($_POST['min_sub_yearly']) && isset($_POST['min_point_amount']) && isset($_POST['max_point_amount']) && isset($_POST['point_to_dolar']) && isset($_POST['min_withdrawl_amount'])) {
			$defaultCurrency = mysqli_real_escape_string($db, $_POST['default_currency']);
			$defaultSubType = mysqli_real_escape_string($db, $_POST['choose_sub_type']);
			$comissionFee = mysqli_real_escape_string($db, $_POST['fee_comission']);
			$minimumSubscriptionAmountWeekly = mysqli_real_escape_string($db, $_POST['min_sub_weekly']);
			$minimumSubscriptionAmountMonthly = mysqli_real_escape_string($db, $_POST['min_sub_monthly']);
			$minimumSubscriptionAmountYearly = mysqli_real_escape_string($db, $_POST['min_sub_yearly']);
			$minimumPointAmount = mysqli_real_escape_string($db, $_POST['min_point_amount']);
			$maximumPointAmount = mysqli_real_escape_string($db, $_POST['max_point_amount']);
			$pointToMoney = mysqli_real_escape_string($db, $_POST['point_to_dolar']);
			$minWihDrawlAmount = mysqli_real_escape_string($db, $_POST['min_withdrawl_amount']);
			$minFeePointWeekly = mysqli_real_escape_string($db, $_POST['min_point_fee_weekly']);
			$minFeePointMonthly = mysqli_real_escape_string($db, $_POST['min_point_fee_monthly']);
			$minFeePointYearly = mysqli_real_escape_string($db, $_POST['min_point_fee_yearly']);
			$minTipAmount = mysqli_real_escape_string($db, $_POST['min_tip_amount']);
			if (empty($minFeePointWeekly) || empty($minTipAmount) || empty($minFeePointMonthly) || empty($minFeePointYearly) ||empty($minimumSubscriptionAmountMonthly) || empty($minimumSubscriptionAmountWeekly) || empty($minimumSubscriptionAmountYearly) || empty($minimumPointAmount) || empty($maximumPointAmount) || empty($pointToMoney) || empty($minWihDrawlAmount)) {
				exit('1');
			}
			$updatePaymentSettings = $iN->iN_UpdatePaymentSettings($userID, $iN->iN_Secure($minTipAmount), $iN->iN_Secure($defaultSubType), $iN->iN_Secure($defaultCurrency), $iN->iN_Secure($comissionFee), $iN->iN_Secure($minimumSubscriptionAmountWeekly), $iN->iN_Secure($minimumSubscriptionAmountMonthly), $iN->iN_Secure($minimumSubscriptionAmountYearly), $iN->iN_Secure($minimumPointAmount), $iN->iN_Secure($maximumPointAmount), $iN->iN_Secure($pointToMoney), $iN->iN_Secure($minWihDrawlAmount), $iN->iN_Secure($minFeePointWeekly), $iN->iN_Secure($minFeePointMonthly),$iN->iN_Secure($minFeePointYearly));
			if ($updatePaymentSettings) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayPal Mode Status*/
	if ($type == 'sendboxmode') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePayPalSendBoxMode = $iN->iN_UpdatePayPalSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updatePayPalSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayPal Status*/
	if ($type == 'paypal_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePayPalStatus = $iN->iN_UpdatePayPalStatus($userID, $iN->iN_Secure($mod));
			if ($updatePayPalStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayPal Business And Sandbox Email Address*/
	if ($type == 'updatePaypal') {
		if (isset($_POST['sndbox_email']) && isset($_POST['product_email']) && isset($_POST['paypal_currency'])) {
			$sandBoxEmail = mysqli_real_escape_string($db, $_POST['sndbox_email']);
			$paypalProductEmail = mysqli_real_escape_string($db, $_POST['product_email']);
			$paypalCurrency = mysqli_real_escape_string($db, $_POST['paypal_currency']);
			$updatePayPalDetails = $iN->iN_UpdatePayPalDetails($userID, $iN->iN_Secure($sandBoxEmail), $iN->iN_Secure($paypalProductEmail), $iN->iN_Secure($paypalCurrency));
			if ($updatePayPalDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update BitPay Mode Status*/
	if ($type == 'bitpay_mode') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateBitPaySendBoxMode = $iN->iN_UpdateBitPaySendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateBitPaySendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update BitPay Status*/
	if ($type == 'bitpay_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateBitPayStatus = $iN->iN_UpdateBitPayStatus($userID, $iN->iN_Secure($mod));
			if ($updateBitPayStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update BitPay Business And Sandbox Email Address*/
	if ($type == 'updateBitPay') {
		if (isset($_POST['notification_email']) && isset($_POST['bit_password']) && isset($_POST['pairinccode']) && isset($_POST['bitLabel']) && isset($_POST['bitpay_currency'])) {
			$bitNotificationEmail = mysqli_real_escape_string($db, $_POST['notification_email']);
			$bitPassword = mysqli_real_escape_string($db, $_POST['bit_password']);
			$bitPairingCode = mysqli_real_escape_string($db, $_POST['pairinccode']);
			$bitLabel = mysqli_real_escape_string($db, $_POST['bitLabel']);
			$bitCurrency = mysqli_real_escape_string($db, $_POST['bitpay_currency']);
			$updateBitPayDetails = $iN->iN_UpdateBitPayDetails($userID, $iN->iN_Secure($bitNotificationEmail), $iN->iN_Secure($bitPassword), $iN->iN_Secure($bitPairingCode), $iN->iN_Secure($bitLabel), $iN->iN_Secure($bitCurrency));
			if ($updateBitPayDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update Stripe Mode Status*/
	if ($type == 'stripe_mode') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateStripeSendBoxMode = $iN->iN_UpdateStripeSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateStripeSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update Stripe Status*/
	if ($type == 'stripe_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateStripeStatus = $iN->iN_UpdateStripeStatus($userID, $iN->iN_Secure($mod));
			if ($updateStripeStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update StripeDetails */
	if ($type == 'updateStripe') {
		if (isset($_POST['testSecretKey']) && isset($_POST['testPublicKey']) && isset($_POST['liveSecretKey']) && isset($_POST['livePublicKey']) && isset($_POST['stripe_currency'])) {
			$stTestSecretKey = mysqli_real_escape_string($db, $_POST['testSecretKey']);
			$stTestPublicKey = mysqli_real_escape_string($db, $_POST['testPublicKey']);
			$stLiveSecretKey = mysqli_real_escape_string($db, $_POST['liveSecretKey']);
			$stLivePublicKey = mysqli_real_escape_string($db, $_POST['livePublicKey']);
			$stCurrency = mysqli_real_escape_string($db, $_POST['stripe_currency']);
			$updateStripeDetails = $iN->iN_UpdateStripeDetails($userID, $iN->iN_Secure($stTestSecretKey), $iN->iN_Secure($stTestPublicKey), $iN->iN_Secure($stLiveSecretKey), $iN->iN_Secure($stLivePublicKey), $iN->iN_Secure($stCurrency));
			if ($updateStripeDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update authorizenet Mode Status*/
	if ($type == 'authorize_mode') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateAuthorizeNetSendBoxMode = $iN->iN_UpdateAuthorizeNetSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateAuthorizeNetSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update authorizenet Status*/
	if ($type == 'authorizenet_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateAuthorizeNetStatus = $iN->iN_UpdateAuthorizeNetStatus($userID, $iN->iN_Secure($mod));
			if ($updateAuthorizeNetStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update AuthorizeNet*/
	if ($type == 'updateAuthorizeNet') {
		if (isset($_POST['testAppID']) && isset($_POST['testTransactionKEY']) && isset($_POST['liveAppID']) && isset($_POST['liveTransactionKEY']) && isset($_POST['authorizenet_currency'])) {
			$autTestAppID = mysqli_real_escape_string($db, $_POST['testAppID']);
			$autTestTransactionKey = mysqli_real_escape_string($db, $_POST['testTransactionKEY']);
			$autLiveAppID = mysqli_real_escape_string($db, $_POST['liveAppID']);
			$autLiveTransactionKey = mysqli_real_escape_string($db, $_POST['liveTransactionKEY']);
			$autCurrency = mysqli_real_escape_string($db, $_POST['authorizenet_currency']);

			$updateAuthorizeNetDetails = $iN->iN_UpdateAuthorizeNetDetails($userID, $iN->iN_Secure($autTestAppID), $iN->iN_Secure($autTestTransactionKey), $iN->iN_Secure($autLiveAppID), $iN->iN_Secure($autLiveTransactionKey), $iN->iN_Secure($autCurrency));
			if ($updateAuthorizeNetDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update IyziCo Mode Status*/
	if ($type == 'iyzico_mode') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateIyziCoSendBoxMode = $iN->iN_UpdateIyziCoSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateIyziCoSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update IyziCo Status*/
	if ($type == 'iyzico_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateIyziCoStatus = $iN->iN_UpdateIyziCoStatus($userID, $iN->iN_Secure($mod));
			if ($updateIyziCoStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update IyziCo*/
	if ($type == 'updateIyziCo') {
		if (isset($_POST['iyziTestSecretKey']) && isset($_POST['iyziTestApiKey']) && isset($_POST['iyziLiveApiKey']) && isset($_POST['iyziLiveApiSeckretKey']) && isset($_POST['iyzico_crncy'])) {
			$iyziTestSecretKey = mysqli_real_escape_string($db, $_POST['iyziTestSecretKey']);
			$iyziTestApiKey = mysqli_real_escape_string($db, $_POST['iyziTestApiKey']);
			$iyziLiveApiKey = mysqli_real_escape_string($db, $_POST['iyziLiveApiKey']);
			$iyziLiveApiSeckretKey = mysqli_real_escape_string($db, $_POST['iyziLiveApiSeckretKey']);
			$iyziCurrency = mysqli_real_escape_string($db, $_POST['iyzico_crncy']);
			$updateIyziCoDetails = $iN->iN_UpdateIyziCoDetails($userID, $iN->iN_Secure($iyziTestSecretKey), $iN->iN_Secure($iyziTestApiKey), $iN->iN_Secure($iyziLiveApiKey), $iN->iN_Secure($iyziLiveApiSeckretKey), $iN->iN_Secure($iyziCurrency));
			if ($updateIyziCoDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update RazorPay Mode Status*/
	if ($type == 'razorpay_mode') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateRazorPaySendBoxMode = $iN->iN_UpdateRazorPaySendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updateRazorPaySendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update RazorPay Status*/
	if ($type == 'razorpay_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateRazorPayStatus = $iN->iN_UpdateRazorPayStatus($userID, $iN->iN_Secure($mod));
			if ($updateRazorPayStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update RazorPay*/
	if ($type == 'updateRazorPay') {
		if (isset($_POST['razorTestKey']) && isset($_POST['razorTestSecret']) && isset($_POST['razorLiveKey']) && isset($_POST['razorLiveSecret']) && isset($_POST['razorpay_crncy'])) {
			$razorTestKey = mysqli_real_escape_string($db, $_POST['razorTestKey']);
			$razorTestSecret = mysqli_real_escape_string($db, $_POST['razorTestSecret']);
			$razorLiveKey = mysqli_real_escape_string($db, $_POST['razorLiveKey']);
			$razorLiveSecret = mysqli_real_escape_string($db, $_POST['razorLiveSecret']);
			$razorCurrency = mysqli_real_escape_string($db, $_POST['razorpay_crncy']);
			$updateRazorPayDetails = $iN->iN_UpdateRazorPayDetails($userID, $iN->iN_Secure($razorTestKey), $iN->iN_Secure($razorTestSecret), $iN->iN_Secure($razorLiveKey), $iN->iN_Secure($razorLiveSecret), $iN->iN_Secure($razorCurrency));
			if ($updateRazorPayDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayStack Mode Status*/
	if ($type == 'paystack_mode') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePayStackSendBoxMode = $iN->iN_UpdatePayStackSendBoxMode($userID, $iN->iN_Secure($mod));
			if ($updatePayStackSendBoxMode) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayStack Status*/
	if ($type == 'paystack_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePayStackStatus = $iN->iN_UpdatePayStackStatus($userID, $iN->iN_Secure($mod));
			if ($updatePayStackStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update PayStack*/
	if ($type == 'updatePayStack') {
		if (isset($_POST['paystackTestSecret']) && isset($_POST['paystackTestPublic']) && isset($_POST['paystackLiveSecretKey']) && isset($_POST['paystackLivePublicKey']) && isset($_POST['paystack_crncy'])) {
			$payStackTestSecret = mysqli_real_escape_string($db, $_POST['paystackTestSecret']);
			$payStackTestPublic = mysqli_real_escape_string($db, $_POST['paystackTestPublic']);
			$payStackLiveSecret = mysqli_real_escape_string($db, $_POST['paystackLiveSecretKey']);
			$payStackLivePublic = mysqli_real_escape_string($db, $_POST['paystackLivePublicKey']);
			$payStackCurrency = mysqli_real_escape_string($db, $_POST['paystack_crncy']);
			$updatePayStackDetails = $iN->iN_UpdatePayStackDetails($userID, $iN->iN_Secure($payStackTestSecret), $iN->iN_Secure($payStackTestPublic), $iN->iN_Secure($payStackLiveSecret), $iN->iN_Secure($payStackLivePublic), $iN->iN_Secure($payStackCurrency));
			if ($updatePayStackDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Setting social Login Status*/
	if ($type == 'sLoginSet') {
		$GoogleCliendID = mysqli_real_escape_string($db, $_POST['google_cliend_id']);
		$TwitterCliendID = mysqli_real_escape_string($db, $_POST['twitter_cliend_id']);
		$GoogleIcon = mysqli_real_escape_string($db, $_POST['google_icon']);
		$TwitterIcon = mysqli_real_escape_string($db, $_POST['twitter_icon']);
		$GoogleCliendSecret = mysqli_real_escape_string($db, $_POST['google_cliend_secret']);
		$TwitterCliendSecret = mysqli_real_escape_string($db, $_POST['twitter_cliend_secret']);
		$GoogleSocialLoginStatus = mysqli_real_escape_string($db, $_POST['google_status']);
		$TwitterSocialLoginStatus = mysqli_real_escape_string($db, $_POST['twitter_status']);

		if ($GoogleSocialLoginStatus == '1') {
			if (empty($GoogleCliendID) || empty($GoogleCliendSecret)) {
				exit($LANG['fill_all_google_requirements']);
			}
		}
		if ($TwitterSocialLoginStatus == '1') {
			if (empty($TwitterCliendID) || empty($TwitterCliendSecret)) {
				exit($LANG['fill_all_twitter_requirements']);
			}
		}
		$UpdateSocialLoginDetails = $iN->iN_UpdateSocialLoginDetails($userID, $iN->iN_Secure($GoogleCliendID), $iN->iN_Secure($TwitterCliendID), $iN->iN_Secure($GoogleIcon), $iN->iN_Secure($TwitterIcon), $iN->iN_Secure($GoogleCliendSecret), $iN->iN_Secure($TwitterCliendSecret), $iN->iN_Secure($GoogleSocialLoginStatus), $iN->iN_Secure($TwitterSocialLoginStatus));
		if ($UpdateSocialLoginDetails) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
/*Mark As Paid*/
	if ($type == 'paid') {
		if (isset($_POST['id']) && !empty($_POST['id'])) {
			$paymentID = mysqli_real_escape_string($db, $_POST['id']);
			$updatePayoutStatus = $iN->iN_UpdatePayoutStatus($userID, $paymentID);
			if ($updatePayoutStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
/*Yes Decline Payment Request*/
	if ($type == 'yesDecline') {
		if (isset($_POST['id']) && !empty($_POST['id'])) {
			$declinedID = mysqli_real_escape_string($db, $_POST['id']);
			$checkPaymentRequestID = $iN->iN_CheckPaymentRequestIDExist($userID, $declinedID);
			if ($checkPaymentRequestID) {
				$okDecline = $iN->iN_DeclineRequest($userID, $iN->iN_Secure($declinedID));
				if ($okDecline) {
					exit('200');
				} else {
					echo '404';
				}
			} else {
				exit($LANG['payment_request_no_longer_available']);
			}
		}
	}
/*Yes Delete Payout From Data*/
	if ($type == 'deletePayoutt') {
		if (isset($_POST['id']) && !empty($_POST['id'])) {
			$deleteID = mysqli_real_escape_string($db, $_POST['id']);
			$checkPaymentRequestID = $iN->iN_CheckPaymentRequestIDExist($userID, $deleteID);
			if ($checkPaymentRequestID) {
				$okDelete = $iN->iN_DeletePayoutRequest($userID, $iN->iN_Secure($deleteID));
				if ($okDelete) {
					exit('200');
				} else {
					echo '404';
				}
			} else {
				exit($LANG['payment_request_no_longer_available']);
			}
		}
	}
	if ($type == 'adsFile') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = mysqli_real_escape_string($db, $_POST['c']);
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableVerificationFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadAdsImage . $d)) {
							$newFile = mkdir($uploadAdsImage . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadAdsImage . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/spImages/' . $d . '/' . $getFilename;
								$UploadSourceUrl = $base_url . 'uploads/spImages/' . $d . '/' . $getFilename;
							}
							echo $UploadSourceUrl;
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
/*Insert New Ads*/
	if ($type == 'adsDForm') {
		if (isset($_POST['adsFile']) && isset($_POST['ads_title']) && isset($_POST['ads_description']) && isset($_POST['ads_url'])) {
			$adsImage = mysqli_real_escape_string($db, $_POST['adsFile']);
			$adsTitle = mysqli_real_escape_string($db, $_POST['ads_title']);
			$adsDescription = mysqli_real_escape_string($db, $_POST['ads_description']);
			$adsRedirectUrl = mysqli_real_escape_string($db, $_POST['ads_url']);
			if (empty($adsImage)) {
				exit('3');
			}
			if (filter_var($adsRedirectUrl, FILTER_VALIDATE_URL) === FALSE) {
				exit('2');
			}
			if (empty($adsTitle)) {
				exit('4');
			}
			if (!empty($adsImage) && !empty($adsTitle) && !empty($adsRedirectUrl)) {
				$insertNewAds = $iN->iN_InsertNewAdvertisement($userID, $iN->iN_Secure($adsImage), $iN->iN_Secure($adsTitle), $iN->iN_Secure($adsDescription), $iN->iN_Secure($adsRedirectUrl));
				if ($insertNewAds) {
					exit('200');
				} else {
					echo iN_HelpSecure($LANG['noway_desc']);
				}
			} else {
				exit('1');
			}
		}
	}
/*Change Ads Status*/
	if ($type == 'adsStatus') {
		if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$adsID = mysqli_real_escape_string($db, $_POST['id']);
			$updateAdsStatus = $iN->iN_UpdateAdsStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($adsID));
			if ($updateAdsStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
/*Insert New Ads*/
	if ($type == 'adsUForm') {
		if (isset($_POST['adsFile']) && isset($_POST['ads_title']) && isset($_POST['ads_description']) && isset($_POST['ads_url']) && isset($_POST['adsi'])) {
			$adsImage = mysqli_real_escape_string($db, $_POST['adsFile']);
			$adsTitle = mysqli_real_escape_string($db, $_POST['ads_title']);
			$adsDescription = mysqli_real_escape_string($db, $_POST['ads_description']);
			$adsRedirectUrl = mysqli_real_escape_string($db, $_POST['ads_url']);
			$editingAdsID = mysqli_real_escape_string($db, $_POST['adsi']);
			if (empty($adsImage)) {
				exit('3');
			}
			if (filter_var($adsRedirectUrl, FILTER_VALIDATE_URL) === FALSE) {
				exit('2');
			}
			if (empty($adsTitle)) {
				exit('4');
			}
			if (!empty($adsImage) && !empty($adsTitle) && !empty($adsDescription) && !empty($adsRedirectUrl) && trim($adsTitle) != '' && trim($adsDescription) != '') {
				$insertNewAds = $iN->iN_UpdateAdvertisement($userID, $iN->iN_Secure($editingAdsID), $iN->iN_Secure($adsImage), $iN->iN_Secure($adsTitle), $iN->iN_Secure($adsDescription), $iN->iN_Secure($adsRedirectUrl));
				if ($insertNewAds) {
					exit('200');
				} else {
					echo iN_HelpSecure($LANG['noway_desc']);
				}
			} else {
				exit('1');
			}
		}
	}
/*Delete Ads*/
	if ($type == 'deleteThisAds') {
		if (isset($_POST['id'])) {
			$adID = mysqli_real_escape_string($db, $_POST['id']);
			$deleteAds = $iN->iN_DeleteAdsFromData($userID, $iN->iN_Secure($adID));
			if ($deleteAds) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
/*Update Stripe Subscriptoion Status*/
	if ($type == 'stripe_sub_status') {
		if (in_array(isset($_POST['mod']), $statusSubOneTwo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateStripeStatus = $iN->iN_UpdateStripeSubStatus($userID, $iN->iN_Secure($mod));
			if ($updateStripeStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update Subscription StripeDetails */
	if ($type == 'updateSubStripe') {
		if (isset($_POST['subSecretKey']) && isset($_POST['subPublicKey']) && isset($_POST['stripe_currency'])) {
			$stSubSecretKey = mysqli_real_escape_string($db, $_POST['subSecretKey']);
			$stSubPublicKey = mysqli_real_escape_string($db, $_POST['subPublicKey']);
			$stSubCurrency = mysqli_real_escape_string($db, $_POST['stripe_currency']);
			$updateStripeDetails = $iN->iN_UpdateSubStripeDetails($userID, $iN->iN_Secure($stSubSecretKey), $iN->iN_Secure($stSubPublicKey), $iN->iN_Secure($stSubCurrency));
			if ($updateStripeDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Update Giphy Api Key*/
	if ($type == 'updateGiphy') {
		if (isset($_POST['giphyKey']) && !empty($_POST['giphyKey'])) {
			$giphyKey = mysqli_real_escape_string($db, $_POST['giphyKey']);
			$updateGiphyKey = $iN->iN_UpdateGiphyAPIKey($userID, $iN->iN_Secure($giphyKey));
			if ($updateGiphyKey) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		} else {
			exit($LANG['enter_valid_giphy_key']);
		}
	}
	/*Update Ai Generator Api Data*/
	if ($type == 'updateAiCredit') {
		if (isset($_POST['apiKey']) && !empty($_POST['apiKey']) && isset($_POST['perAmount']) && !empty($_POST['perAmount'])) {
			$aiApiKey = mysqli_real_escape_string($db, $_POST['apiKey']);
			$aiPerAmount = mysqli_real_escape_string($db, $_POST['perAmount']);
			$updatedApiInfo = $iN->iN_UpdateAiAPIData($userID, $iN->iN_Secure($aiApiKey), $iN->iN_Secure($aiPerAmount));
			if ($updatedApiInfo) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		} else {
			exit($LANG['all_fields_must_be_filled']);
		}
	}
	/*Email Settings*/
	if ($type == 'updateLiveSettings') {
		$liveStatus = mysqli_real_escape_string($db, $_POST['s3Status']);
		$freeLiveLimit = mysqli_real_escape_string($db, $_POST['post_show_limit']);
		$agora_AppID = mysqli_real_escape_string($db, $_POST['appID']);
		$agora_Certificate = mysqli_real_escape_string($db, $_POST['appCertificate']);
		$agora_CustomerID = mysqli_real_escape_string($db, $_POST['appCustomerID']);
		$liveMinimumFee = mysqli_real_escape_string($db, $_POST['liveMinPrice']);
		$freeLiveStreamingStatus = mysqli_real_escape_string($db, $_POST['sPlStatus']);
		$paidLiveStreamingStatus = mysqli_real_escape_string($db, $_POST['sflStatus']);
		if ($liveStatus == '1') {
			if (empty($freeLiveLimit) || empty($agora_AppID) || empty($agora_Certificate) || empty($agora_CustomerID)) {
				exit($LANG['all_information_need_filled']);
			}
		}
		$updateLiveSettings = $iN->iN_UpdateAgoraLiveStreamingSettings($userID,$iN->iN_Secure($freeLiveStreamingStatus), $iN->iN_Secure($paidLiveStreamingStatus), $iN->iN_Secure($liveStatus), $iN->iN_Secure($freeLiveLimit), $iN->iN_Secure($agora_AppID), $iN->iN_Secure($agora_Certificate), $iN->iN_Secure($agora_CustomerID), $iN->iN_Secure($liveMinimumFee));
		if ($updateLiveSettings) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
	/*Update Page*/
	if ($type == 'updateMainPage') {
		if (isset($_POST['tm']) && !empty($_POST['tm'])) {
			$theme = mysqli_real_escape_string($db, $_POST['tm']);
			$updateTheme = $iN->iN_UpdateTheme($userID, $iN->iN_Secure($theme));
			if ($updateTheme) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	if($type == 'wall'){
		//$iN->iN_Sen($mycd, $mycdStatus,$base_url);
		echo $iN->iN_Sen($mycd, $mycdStatus,$base_url);
	}

	/*Update Landing Page Images*/
	if ($type == 'imageOne' || $type == 'imageTwo' || $type == 'imageThree' || $type == 'imageFour' || $type == 'imageFive' || $type == 'imageSix' || $type == 'imageSeventh' || $type == 'imageBg' || $type == 'imageFrnt') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = mysqli_real_escape_string($db, $_POST['c']);
			$fileReq = isset($_FILES['uploading']['name']) ? $_FILES['uploading']['name'] : NULL;
			foreach ($fileReq as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableVerificationFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadIconLogo . 'landingImages/' . $d)) {
							$newFile = mkdir($uploadIconLogo . 'landingImages/' . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadIconLogo . 'landingImages/' . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'img/landingImages/' . $d . '/' . $getFilename;
								$UploadSourceUrl = $base_url . 'img/landingImages/' . $d . '/' . $getFilename;
								if ($type == 'imageOne') {
									$iN->iN_UpdateFirstLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageTwo') {
									$iN->iN_UpdateSecondLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageThree') {
									$iN->iN_UpdateThirdLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageFour') {
									$iN->iN_UpdateFourthLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageFive') {
									$iN->iN_UpdateFifthLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageSix') {
									$iN->iN_UpdateSixthLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageSeventh') {
									$iN->iN_UpdateSeventhLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageBg') {
									$iN->iN_UpdateBgLandingPageImage($userID, $pathFile);
								} else if ($type == 'imageFrnt') {
									$iN->iN_UpdateFrntLandingPageImage($userID, $pathFile);
								}
							}
							echo 'img/landingImages/' . $d . '/' . $getFilename;
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	/*Save New Question Answer*/
	if ($type == 'newQA') {
		if (isset($_POST['newq']) && isset($_POST['newqa'])) {
			$newQusetion = mysqli_real_escape_string($db, $_POST['newq']);
			$newQusetionAnswer = mysqli_real_escape_string($db, $_POST['newqa']);
			if (empty($newQusetion) || empty($newQusetionAnswer)) {
				exit('2');
			}
			$insertNewQuestionAnsser = $iN->iN_InsertNewQuestionAnswer($userID, $iN->iN_Secure($newQusetionAnswer), $iN->iN_Secure($newQusetion));
			if ($insertNewQuestionAnsser) {
				exit('200');
			} else {
				exit($LANG['save_failed']);
			}
		} else {
			exit('2');
		}
	}
	/*Save New Question Answer*/
	if ($type == 'edQA') {
		if (isset($_POST['newq']) && isset($_POST['newqa']) && isset($_POST['qid'])) {
			$newQusetion = mysqli_real_escape_string($db, $_POST['newq']);
			$newQusetionAnswer = mysqli_real_escape_string($db, $_POST['newqa']);
			$QAID = mysqli_real_escape_string($db, $_POST['qid']);
			if (empty($newQusetion) || empty($newQusetionAnswer) || empty($QAID)) {
				exit('2');
			}
			$updateQuestionAnswer = $iN->iN_UpdateLandingQA($userID, $iN->iN_Secure($newQusetionAnswer), $iN->iN_Secure($newQusetion), $iN->iN_Secure($QAID));
			if ($updateQuestionAnswer) {
				exit('200');
			} else {
				exit($LANG['save_failed']);
			}
		} else {
			exit('2');
		}
	}
	/*Update CCBILL Details */
	if ($type == 'updateSubStripeCCBILL') {
		if (isset($_POST['accountNumber']) && isset($_POST['subAccountNumber']) && isset($_POST['flexFormID']) && isset($_POST['saltKey']) && isset($_POST['ccbill_currency'])) {
			$accountNumber = mysqli_real_escape_string($db, $_POST['accountNumber']);
			$subAccountNumber = mysqli_real_escape_string($db, $_POST['subAccountNumber']);
			$flexFormID = mysqli_real_escape_string($db, $_POST['flexFormID']);
			$saltKey = mysqli_real_escape_string($db, $_POST['saltKey']);
			$ccbillCurrency = mysqli_real_escape_string($db, $_POST['ccbill_currency']);
			$updateCCBILLDetails = $iN->iN_UpdateSubCCBILLDetails($userID, $iN->iN_Secure($accountNumber), $iN->iN_Secure($subAccountNumber), $iN->iN_Secure($flexFormID), $iN->iN_Secure($saltKey), $iN->iN_Secure($ccbillCurrency));
			if ($updateCCBILLDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update DigitalOceal Storage Details*/
	if ($type == 'DigitalOceanSettings') {
		$dOceanRegion = mysqli_real_escape_string($db, $_POST['oceanregion']);
		$dOgeanBucket = mysqli_real_escape_string($db, $_POST['docean_ducket']);
		$dOceanKey = mysqli_real_escape_string($db, $_POST['docean_key']);
		$dOceanSecretKey = mysqli_real_escape_string($db, $_POST['oceansecret_key']);
		$dOceanStatus = mysqli_real_escape_string($db, $_POST['s3Status']);
		$updateDigitalOceanSettings = $iN->iN_UpdateDigitalOceanDetails($userID, $iN->iN_Secure($dOceanRegion), $iN->iN_Secure($dOgeanBucket), $iN->iN_Secure($dOceanKey), $iN->iN_Secure($dOceanSecretKey), $iN->iN_Secure($dOceanStatus));
		if ($updateDigitalOceanSettings) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*ffmpeg status*/
	if ($type == 'ffmpegMode') {
		if (in_array($_POST['mod'], $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateffmpegSendStatus = $iN->iN_UpdateFFMPEGSendStatus($userID, $iN->iN_Secure($mod));
			if ($updateffmpegSendStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Post Creator status*/
	if ($type == 'postCreateStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdatePostCretaeStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Block Countries status*/
	if ($type == 'blockCountriesStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateBlockCountriesStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Auto Approve Post status*/
	if ($type == 'autoApprovePost') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateAutoApprovePostStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Affilate System status*/
	if ($type == 'affilateSystemStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateAffilateSystemStatus = $iN->iN_UpdateAffilateSystemStatus($userID, $iN->iN_Secure($mod));
			if ($updateAffilateSystemStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Question Answer status*/
	if ($type == 'questionAnswerStatus') {
		if (in_array($_POST['mod'], $statusValue) && isset($_POST['qid'])) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$qid = mysqli_real_escape_string($db, $_POST['qid']);
			$updatePostCreateStatus = $iN->iN_UpdateQuestionAnswerStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($qid));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Post Report status*/
	if ($type == 'rCheckStatus') {
		if (in_array($_POST['mod'], $statusValue) && isset($_POST['rid'])) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$rid = mysqli_real_escape_string($db, $_POST['rid']);
			$updatePostCheckedStatus = $iN->iN_UpdateReportedPostCheckedStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($rid));
			if ($updatePostCheckedStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Comment Report status*/
	if ($type == 'rcCheckStatus') {
		if (in_array($_POST['mod'], $statusValue) && isset($_POST['rid'])) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$rid = mysqli_real_escape_string($db, $_POST['rid']);
			$updatePostCheckedStatus = $iN->iN_UpdateReportedCommentCheckedStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($rid));
			if ($updatePostCheckedStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Comment Report*/
	if ($type == 'deleteCReport') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$deletePost = $iN->iN_DeleteCommentReport($userID, $iN->iN_Secure($postID));
			if ($deletePost) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Stripe Status*/
	if ($type == 'coinpayment_status') {
		if (in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateStripeStatus = $iN->iN_UpdateCoinPaymentStatus($userID, $iN->iN_Secure($mod));
			if ($updateStripeStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Update StripeDetails */
	if ($type == 'updateCoinPayment') {
		if (isset($_POST['cprivatekey']) && isset($_POST['cpublickey']) && isset($_POST['cmerchandid']) && isset($_POST['cipnsecret']) && isset($_POST['cdebugemail']) && isset($_POST['crpCurrency'])) {
			$cpPrivateKey = mysqli_real_escape_string($db, $_POST['cprivatekey']);
			$cpPublicKey = mysqli_real_escape_string($db, $_POST['cpublickey']);
			$cpMerchandID = mysqli_real_escape_string($db, $_POST['cmerchandid']);
			$cpIPNSecret = mysqli_real_escape_string($db, $_POST['cipnsecret']);
			$cpDebugEmail = mysqli_real_escape_string($db, $_POST['cdebugemail']);
			$cpCurrency = mysqli_real_escape_string($db, $_POST['crpCurrency']);
			$updateStripeDetails = $iN->iN_UpdateCoinPaymentDetails($userID, $iN->iN_Secure($cpPrivateKey), $iN->iN_Secure($cpPublicKey), $iN->iN_Secure($cpMerchandID), $iN->iN_Secure($cpIPNSecret), $iN->iN_Secure($cpDebugEmail), $iN->iN_Secure($cpCurrency));
			if ($updateStripeDetails) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	if ($type == 'WatlogoFile') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = mysqli_real_escape_string($db, $_POST['c']);
			$fileReq = isset($_FILES['uploading']['name']) ? $_FILES['uploading']['name'] : NULL;
			if (is_array($fileReq) && !empty($fileReq)) {
				foreach ($fileReq as $iname => $value) {
					$name = stripslashes($_FILES['uploading']['name'][$iname]);
					$size = $_FILES['uploading']['size'][$iname];
					$ext = getExtension($name);
					$ext = strtolower($ext);
					$valid_formats = explode(',', $availableVerificationFileExtensions);
					if (in_array($ext, $valid_formats)) {
						if (convert_to_mb($size) < $availableUploadFileSize) {
							$microtime = microtime();
							$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
							$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
							$getFilename = $UploadedFileName . "." . $ext;
							// Change the image ame
							$tmp = $_FILES['uploading']['tmp_name'][$iname];
							$mimeType = $_FILES['uploading']['type'][$iname];
							$d = date('Y-m-d');
							if (preg_match('/video\/*/', $mimeType)) {
								$fileTypeIs = 'video';
							} else if (preg_match('/image\/*/', $mimeType)) {
								$fileTypeIs = 'Image';
							}
							if (!file_exists($uploadIconLogo . $d)) {
								$newFile = mkdir($uploadIconLogo . $d, 0755);
							}
							if (!file_exists($xImages . $d)) {
								$newFile = mkdir($xImages . $d, 0755);
							}
							if (move_uploaded_file($tmp, $uploadIconLogo . $d . '/' . $getFilename)) {
								/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
								if ($fileTypeIs == 'Image') {
									$pathFile = 'img/' . $d . '/' . $getFilename;
									$UploadSourceUrl = $base_url . 'img/' . $d . '/' . $getFilename;
								}
								echo 'img/' . $d . '/' . $getFilename;
							}
						} else {
							echo iN_HelpSecure($size);
						}
					}
				}
			}
		}
	}
	if ($type == 'GiftFile' || $type == 'GiftAnimationFile') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = mysqli_real_escape_string($db, $_POST['c']);
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableVerificationFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
                        $uploadGiftImage = $serverDocumentRoot . '/img/gifts/';
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadGiftImage . $d)) {
							$newFile = mkdir($uploadGiftImage . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadGiftImage . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'img/gifts/' . $d . '/' . $getFilename;
								$UploadSourceUrl = $base_url . 'img/gifts/' . $d . '/' . $getFilename;
							}
							echo $pathFile;
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	if ($type == 'newGiftCardForm') {
		if (isset($_POST['giftFile']) && isset($_POST['GiftAnimationFile']) && isset($_POST['gift_name']) && isset($_POST['giftPoint'])) {
			$giftImage = mysqli_real_escape_string($db, $_POST['giftFile']);
			$GiftAnimationFile = mysqli_real_escape_string($db, $_POST['GiftAnimationFile']);
			$giftName = mysqli_real_escape_string($db, $_POST['gift_name']);
			$giftPoint = mysqli_real_escape_string($db, $_POST['giftPoint']);
			$giftAmount = $giftPoint * $onePointEqual;
			if (empty($giftImage) || empty($GiftAnimationFile)) {
				exit('3');
			}
			if (empty($giftPoint)) {
				exit('3');
			}
			if (empty($giftName)) {
				exit('4');
			}
			if (!empty($giftImage) && !empty($giftName) && !empty($giftAmount) && !empty($giftPoint) && !empty($GiftAnimationFile)) {
				$insertNewAds = $iN->iN_InsertNewGiftCard($userID, $iN->iN_Secure($giftImage), $iN->iN_Secure($giftName), $iN->iN_Secure($giftPoint), $iN->iN_Secure($giftAmount), $iN->iN_Secure($GiftAnimationFile));
				if ($insertNewAds) {
					exit('200');
				} else {
					echo iN_HelpSecure($LANG['noway_desc']);
				}
			} else {
				exit('1');
			}
		}
	}
	/*Edit Plan*/
	if ($type == 'editLivePlan') {
		if (isset($_POST['planKey']) && isset($_POST['planPoint']) && isset($_POST['pointAmount']) && isset($_POST['planid']) && isset($_POST['giftFile']) && isset($_POST['GiftAnimationFile'])) {
			$giftName = mysqli_real_escape_string($db, $_POST['planKey']);
			$giftPoint = mysqli_real_escape_string($db, $_POST['planPoint']);
			$giftAmount = mysqli_real_escape_string($db, $_POST['pointAmount']);
			$giftID = mysqli_real_escape_string($db, $_POST['planid']);
			$giftAvatar = mysqli_real_escape_string($db, $_POST['giftFile']);
			$giftAnimationFile = mysqli_real_escape_string($db, $_POST['GiftAnimationFile']);
			$removeAllSpaceFromKey = preg_replace('/\s+/', '', $giftName);
			if ($giftPoint < $minimumPointLimit || ctype_space($giftPoint) || empty($giftPoint)) {
				exit(preg_replace('/{.*?}/', $minimumPointLimit, $LANG['plan_point_warning']));
			}
			if ($giftAmount > $maximumPointAmountLimit || ctype_space($giftAmount) || empty($giftAmount)) {
				exit(preg_replace('/{.*?}/', $maximumPointAmountLimit, $LANG['plan_point_amount_warning']));
			}

				$updateLivePlan = $iN->iN_UpdateLivePlanFromID($userID, $iN->iN_Secure($giftName),$giftAvatar,$giftAnimationFile, $iN->iN_Secure($giftPoint), $iN->iN_Secure($giftAmount), $iN->iN_Secure($giftID));
				if ($updateLivePlan) {
					exit('200');
				} else {
					exit($LANG['noway_desc']);
				}
		}
	}
	/*Change Plan Status*/
	if ($type == 'liveplanStatus') {
		if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$planID = mysqli_real_escape_string($db, $_POST['id']);
			$updatePlanStatus = $iN->iN_UpdateLivePlanStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($planID));
			if ($updatePlanStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Plan Status*/
	if ($type == 'frameplanStatus') {
		if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$planID = mysqli_real_escape_string($db, $_POST['id']);
			$updatePlanStatus = $iN->iN_UpdateFramePlanStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($planID));
			if ($updatePlanStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Post*/
	if ($type == 'deleteThisLivePlan') {
		if (isset($_POST['id'])) {
			$planID = mysqli_real_escape_string($db, $_POST['id']);
			$deletePlan = $iN->iN_DeleteLivePlanFromData($userID, $iN->iN_Secure($planID));
			if ($deletePlan) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Frame Plan*/
	if ($type == 'deleteThisFramePlan') {
		if (isset($_POST['id'])) {
			$planID = mysqli_real_escape_string($db, $_POST['id']);
			$deletePlan = $iN->iN_DeleteFramePlanFromData($userID, $iN->iN_Secure($planID));
			if ($deletePlan) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Weekly Subscription Status*/
	if ($type == 'weeklySubStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateWeeklySubStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Weekly Subscription Status*/
	if ($type == 'monthlySubStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateMonthlySubStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Weekly Subscription Status*/
	if ($type == 'yearlySubStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateYearlySubStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change WaterMark Image Status*/
	if ($type == 'watermarkStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateWatermarkStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Watermark Text Status*/
	if ($type == 'lwatermarkStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateLinkWatermarkStatus = $iN->iN_UpdateLinkWatermarkStatus($userID, $iN->iN_Secure($mod));
			if ($updateLinkWatermarkStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Watermark Text Status*/
	if ($type == 'fullnamestatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updateShowFullNameStatus = $iN->iN_UpdateShowFullNameStatus($userID, $iN->iN_Secure($mod));
			if ($updateShowFullNameStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Affilate Status*/
	if ($type == 'affilateStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateAffiliateStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Update Site Business Informations*/
	if ($type == 'updateAffilate') {
		$minimumPointTransferAmount = mysqli_real_escape_string($db, $_POST['minpointtransfer']);
		$affilateEarnAmount = mysqli_real_escape_string($db, $_POST['affilateamount']);

		if (empty($minimumPointTransferAmount) || empty($affilateEarnAmount)) {
			exit('1');
		}
		$updateAffilateInfos = $iN->iN_UpdateAffilateInfos($userID, $iN->iN_Secure($minimumPointTransferAmount), $iN->iN_Secure($affilateEarnAmount));
		if ($updateAffilateInfos) {
			exit('200');
		} else {
			echo '404';
		}
	}
	/*Update Point Earning Informations*/
	if($type == 'epdSettings'){
	   $maxPointinaDay = isset($_POST['max_point_amount']) ? $_POST['max_point_amount'] : NULL ;
       $epdRegisterStatus = isset($_POST['registerSystemStatus']) ? $_POST['registerSystemStatus'] : NULL ;
	   $epdCommentStatus = isset($_POST['commentSystemStatus']) ? $_POST['commentSystemStatus'] : NULL ;
	   $epdNewPostStatus = isset($_POST['new_postSystemStatus']) ? $_POST['new_postSystemStatus'] : NULL ;
	   $epdCommetLikeStatus = isset($_POST['comment_likeSystemStatus']) ? $_POST['comment_likeSystemStatus'] : NULL ;
	   $epdPostLikeStatus = isset($_POST['post_likeSystemStatus']) ? $_POST['post_likeSystemStatus'] : NULL ;
	   $epdRegisterAmount = isset($_POST['register_amount']) ? $_POST['register_amount'] : NULL ;
	   $epdCommendAmount = isset($_POST['comment_amount']) ? $_POST['comment_amount'] : NULL ;
	   $epdCommentLikeAmount = isset($_POST['comment_like_amount']) ? $_POST['comment_like_amount'] : NULL ;
	   $epdNewPostAmount = isset($_POST['new_post_amount']) ? $_POST['new_post_amount'] : NULL ;
	   $epdPostLikeAmount = isset($_POST['post_like_amount']) ? $_POST['post_like_amount'] : NULL ;
	    if(!$epdRegisterStatus){
		    $epdRegisterStatus = 'no';
		}
		if(!$epdCommentStatus){
			$epdCommentStatus = 'no';
		}
		if(!$epdNewPostStatus){
			$epdNewPostStatus = 'no';
		}
		if(!$epdCommetLikeStatus){
			$epdCommetLikeStatus = 'no';
		}
		if(!$epdPostLikeStatus){
			$epdPostLikeStatus = 'no';
		}
	   $epdSave = $iN->iN_EPDUpdate($userID, $iN->iN_Secure($maximumPointInADay), $iN->iN_Secure($epdRegisterStatus),$iN->iN_Secure($epdCommentStatus),$iN->iN_Secure($epdNewPostStatus),$iN->iN_Secure($epdCommetLikeStatus),$iN->iN_Secure($epdPostLikeStatus),$iN->iN_Secure($epdRegisterAmount),$iN->iN_Secure($epdCommendAmount),$iN->iN_Secure($epdCommentLikeAmount),$iN->iN_Secure($epdNewPostAmount),$iN->iN_Secure($epdPostLikeAmount));
	   if($epdSave === true){
          exit('200');
	   }
	}
	/*Fake User Generator*/
	if ($type == 'fake_generaator') {
		if (isset($_POST['n']) && isset($_POST['p'])) {
			$fakeUserNumber = mysqli_real_escape_string($db, $_POST['n']);
			$fakeUserPasswords = mysqli_real_escape_string($db, $_POST['p']);
			require "../../../includes/fake-users/vendor/autoload.php";
			$faker = Faker\Factory::create();
			$count_users = $fakeUserNumber;
			$password = $fakeUserPasswords;

			for ($i = 0; $i < $count_users; $i++) {
				$genders = array("male", "female");
				$random_keys = array_rand($genders, 1);
				$gender = array_rand(array("male", "female"), 1);
				$gender = $genders[$random_keys];
				$random_countries = array_rand($COUNTRIES);
				$randomProfileCategories = array_rand($PROFILE_CATEGORIES);
				$fakeUserEmail = $faker->userName . '_' . rand(111, 999) . "@yahoo.com";
				$fakeUserUsername = $faker->userName . '_' . rand(111, 999);
				$fakeUserPassword = sha1(md5(trim($password)));
				$fakeUserGender = $gender;
				$fakeUserRegisterTime = time();
				$fakeUserLastSeen = time();
				$fakeUserFullName = $faker->firstName($gender) . ' ' . $faker->lastName;
				$fakeuserBithYear = $faker->year($max = 'now');
				$fakeUserBirthMonth = $faker->month($max = 'now');
				$fakeUserBirthDay = ltrim($faker->dayOfMonth($max = 'now'), '0');
				$fakeUserCountry = $faker->countryCode;
				$fakeUserLatitude = $faker->latitude($min = -90, $max = 90);
				$fakeUserLongitude = $faker->longitude($min = -180, $max = 180);
				$fakerBithYearMonthDay = $fakeuserBithYear.'-'.$fakeUserBirthMonth.'-'.$fakeUserBirthDay;
				$GenerateFakeUser = $iN->iN_GenerateFakeUsers($userID, $fakeUserEmail, $fakeUserUsername, $fakeUserFullName, $fakeUserGender, $fakeUserPassword, $fakerBithYearMonthDay, $fakeUserRegisterTime, $fakeUserLatitude, $fakeUserLongitude,$random_countries, $randomProfileCategories);
			}
			if ($GenerateFakeUser) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}else{
			echo iN_HelpSecure($LANG['please_fill_all_requirements']);
		}
	}
	/*Change Affilate Status*/
	if ($type == 'pointSystemStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$UserCanEarnPointStatus = $iN->iN_UpdateUserCanEarnPointStatus($userID, $iN->iN_Secure($mod));
			if ($UserCanEarnPointStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Change Affilate Status*/
	if ($type == 'becomecreatortypestatus') {
		if (in_array($_POST['mod'], $beACreatorArray)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$UserCanEarnPointStatus = $iN->iN_UpdateBecomeACreatorTypeStatus($userID, $iN->iN_Secure($mod));
			if ($UserCanEarnPointStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Delete Story From Database*/
	if($type == 'deleteStorie'){
		if(isset($_POST['id'])){
		   $storieID = mysqli_real_escape_string($db, $_POST['id']);
		   $checkStorieIDExist = $iN->iN_CheckStorieIDExistForAdmin($userID, $storieID);
		   if($checkStorieIDExist){
			   $sData = $iN->iN_GetUploadedStoriesDataForAdmin($storieID);
			   $uploadedFileID = $sData['s_id'];
			   $uploadedFilePath = $sData['uploaded_file_path'];
			   $uploadedTumbnailFilePath = $sData['upload_tumbnail_file_path'];
			   $uploadedFilePathX = $sData['uploaded_x_file_path'];
			   if($uploadedFileID && $digitalOceanStatus == '1'){
				 $my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
				 $my_space->DeleteObject($uploadedFilePath);

				 $space_two = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
				 $space_two->DeleteObject($uploadedFilePathX);

				 $space_tree = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
				 $space_tree->DeleteObject($uploadedTumbnailFilePath);
				 $query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID'");
				 if($query){
					 exit('200');
				 }else{
					 exit('404');
				 }
			   } else if($uploadedFileID && $s3Status == '1'){
				 $s3->deleteObject([
					 'Bucket' => $s3Bucket,
					 'Key'    => $uploadedFilePath,
				 ]);
				 $s3->deleteObject([
					 'Bucket' => $s3Bucket,
					 'Key'    => $uploadedFilePathX,
				 ]);
				 $s3->deleteObject([
					 'Bucket' => $s3Bucket,
					 'Key'    => $uploadedTumbnailFilePath,
				 ]);
				 $query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID'");
				 if($query){
					exit('200');
				 }else{
					exit('404');
				 }
			   }else if($uploadedFileID && $WasStatus == '1'){
					$s3->deleteObject([
						'Bucket' => $WasBucket,
						'Key'    => $uploadedFilePath,
					]);
					$s3->deleteObject([
						'Bucket' => $WasBucket,
						'Key'    => $uploadedFilePathX,
					]);
					$s3->deleteObject([
						'Bucket' => $WasBucket,
						'Key'    => $uploadedTumbnailFilePath,
					]);
					$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID'");
					if($query){
						echo '200';
					}else{
						echo '404';
					}
			    }else{
				 @unlink('../../../' . $uploadedFilePath);
				 @unlink('../../../' . $uploadedFilePathX);
				 @unlink('../../../' . $uploadedTumbnailFilePath);
				 $query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID'");
				 if($query){
					exit('200');
				 }else{
					exit('404');
				 }
			   }
		   }
		}
	 }
	 if ($type == 'stBgImage') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = mysqli_real_escape_string($db, $_POST['c']);
			$fileReq = isset($_FILES['uploading']['name']) ? $_FILES['uploading']['name'] : NULL;
			if (is_array($fileReq) && !empty($fileReq)) {
				foreach ($fileReq as $iname => $value) {
					$name = stripslashes($_FILES['uploading']['name'][$iname]);
					$size = $_FILES['uploading']['size'][$iname];
					$ext = getExtension($name);
					$ext = strtolower($ext);
					$valid_formats = explode(',', $availableVerificationFileExtensions);
					if (in_array($ext, $valid_formats)) {
						if (convert_to_mb($size) < $availableUploadFileSize) {
							$microtime = microtime();
							$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
							$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
							$getFilename = $UploadedFileName . "." . $ext;
							// Change the image ame
							$tmp = $_FILES['uploading']['tmp_name'][$iname];
							$mimeType = $_FILES['uploading']['type'][$iname];
							$d = date('Y-m-d');
							if (preg_match('/image\/*/', $mimeType)) {
								$fileTypeIs = 'Image';
							}
							if (!file_exists($uploadFile . $d)) {
								$newFile = mkdir($uploadFile . $d, 0755);
							}
							if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
								/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
								if ($fileTypeIs == 'Image') {
									$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
									$InsertNewBg = $iN->iN_InsertNewStoryBg($userID, $pathFile);
									if($InsertNewBg){
										exit('200');
									} else{
										exit('404');
									}
								}
							}else{
								exit('Check your file permission');
							}
						} else {
							echo iN_HelpSecure($size);
						}
					}
				}
			}
		}
	}
	/*Update Sticker Status*/
	if ($type == 'upStoryBg') {
		if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$bgID = mysqli_real_escape_string($db, $_POST['id']);
			$updateStoryBgStatus = $iN->iN_UpdateStoryBgStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($bgID));
			if ($updateStoryBgStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete User*/
	if ($type == 'deleteStoryBg') {
		if (isset($_POST['id'])) {
			$deleteStickerID = mysqli_real_escape_string($db, $_POST['id']);
			$deleteSTicker = $iN->iN_DeleteStoryBg($userID, $iN->iN_Secure($deleteStickerID));
			if ($deleteSTicker) {
				exit('200');
			} else {
				exit($LANG['storybg_id_not_available']);
			}
		}
	}
	/*Shop Feature status*/
	if ($type == 'shopFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '1';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Scratch Feature status*/
	if ($type == 'shopScratchStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '2';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Book a Zoom Feature status*/
	if ($type == 'shopBookaZoomStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '3';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Digital Download Feature status*/
	if ($type == 'shopDigitalDownloadStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '4';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Live Event Ticket Feature status*/
	if ($type == 'shopLiveEventTicketStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '5';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Art Commission Feature status*/
	if ($type == 'shopArtCommissionStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '6';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Shop Join Instagram Close Friends Feature status*/
	if ($type == 'shopInstagramGloseFriendsStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '7';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Who can create a product*/
	if ($type == 'whoCanCretaProduct') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '8';
			$updatePostCreateStatus = $iN->iN_UpdateShopFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Who can create a product*/
	if ($type == 'storyFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '1';
			$updatePostCreateStatus = $iN->iN_UpdateStoryFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Story Image Feature Status*/
	if ($type == 'storyImageFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '2';
			$updatePostCreateStatus = $iN->iN_UpdateStoryFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Video Call FEature Status*/
	if ($type == 'videoCallFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '2';
			$updatePostCreateStatus = $iN->iN_UpdateVideoCallFeatureStatus($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Story Text Feature Status*/
	if ($type == 'storyTextFeatureStatus') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '3';
			$updatePostCreateStatus = $iN->iN_UpdateStoryFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Who Can Create Status*/
	if ($type == 'whoCanCretaStory') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$ID = '4';
			$updatePostCreateStatus = $iN->iN_UpdateStoryFeatureStatus($userID, $iN->iN_Secure($mod), $ID);
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Add New Sticker Url*/
	if ($type == 'createNewAnnouncement') {
		if(isset($_POST['announcementText']) && isset($_POST['announcementStatus']) && isset($_POST['announcementType']) && in_array($_POST['announcementStatus'], $yesNo) && in_array($_POST['announcementType'], $announcementTypes)){

		    $announcementText = mysqli_real_escape_string($db, $_POST['announcementText']);
			$annoucementStatus = mysqli_real_escape_string($db, $_POST['announcementStatus']);
			$announcementType = mysqli_real_escape_string($db, $_POST['announcementType']);
			if(preg_replace('/\s+/', '',$announcementText) == ''){
                exit('2');
			}
			$insertAnnouncement = $iN->iN_InsertAnnouncement($userID, $iN->iN_Secure($announcementText), $iN->iN_Secure($annoucementStatus), $iN->iN_Secure($announcementType));
			if($insertAnnouncement){
                exit('200');
			}else{
				exit('404');
			}
		}
	}
	/*Update Sticker Status*/
	if ($type == 'upAnnon') {
		if (isset($_POST['id']) && in_array(isset($_POST['mod']), $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$anID = mysqli_real_escape_string($db, $_POST['id']);
			$updateAnnouncementStatus = $iN->iN_UpdateAnnouncementStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($anID));
			if ($updateAnnouncementStatus) {
				exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
	/*Delete Announcement*/
	if ($type == 'deleteAnnouncement') {
		if (isset($_POST['id'])) {
			$annunceID = mysqli_real_escape_string($db, $_POST['id']);
			$deleteAnnounce = $iN->iN_DeleteAnnouncement($userID, $iN->iN_Secure($annunceID));
			if ($deleteAnnounce) {
				exit('200');
			} else {
				exit($LANG['announcement_not_founded']);
			}
		}
	}
	/*Edited Sticker URL*/
	if ($type == 'announcementEdit') {
		if (isset($_POST['announcementText']) && isset($_POST['announcementStatus']) && isset($_POST['announcementType']) && in_array($_POST['announcementStatus'], $yesNo) && in_array($_POST['announcementType'], $announcementTypes) && isset($_POST['aid'])) {
			$announcementText = mysqli_real_escape_string($db, $_POST['announcementText']);
			$annoucementStatus = mysqli_real_escape_string($db, $_POST['announcementStatus']);
			$announcementType = mysqli_real_escape_string($db, $_POST['announcementType']);

			$aID = mysqli_real_escape_string($db, $_POST['aid']);

			if(preg_replace('/\s+/', '',$announcementText) == ''){
                exit('2');
			}
			$insertAnnouncement = $iN->iN_UpdateAnnouncement($userID, $iN->iN_Secure($aID),$iN->iN_Secure($announcementText), $iN->iN_Secure($annoucementStatus), $iN->iN_Secure($announcementType));
			if($insertAnnouncement){
                exit('200');
			} else {
				echo iN_HelpSecure($LANG['noway_desc']);
			}
		}
	}
/*Yes Delete Product From Data*/
if ($type == 'deleteProductt') {
	if (isset($_POST['id']) && !empty($_POST['id'])) {
		$productID = mysqli_real_escape_string($db, $_POST['id']);
		$checkProductIDExist = $iN->iN_CheckProductIDExistFromURL($productID);
		if ($checkProductIDExist) {
			$okDelete = $iN->iN_DeleteProductAdmin($userID, $productID);
			if ($okDelete) {
				exit('200');
			} else {
				echo '404';
			}
		} else {
			exit($LANG['payment_request_no_longer_available']);
		}
	}
}
/*Add New Sticker Url*/
if ($type == 'newSocialSite') {
	if(isset($_POST['social_site']) && isset($_POST['socail_key']) && isset($_POST['socialsvgcode']) && in_array($_POST['socialsitestatus'], $yesNo)){

		$newSocialSite = mysqli_real_escape_string($db, $_POST['social_site']);
		$newSocialSiteKey = mysqli_real_escape_string($db, $_POST['socail_key']);
		$newSocialSiteSVGCode = mysqli_real_escape_string($db, $_POST['socialsvgcode']);
		$newSocialSiteStatus = mysqli_real_escape_string($db, $_POST['socialsitestatus']);
		if (!substr_count($newSocialSiteSVGCode, '<svg')) {
			exit('1');
		}
		if(preg_replace('/\s+/', '',$newSocialSite) == '' || preg_replace('/\s+/', '',$newSocialSiteKey) == '' || preg_replace('/\s+/', '',$newSocialSiteSVGCode) == ''){
			exit('2');
		}
		$insertNewSocialSite = $iN->iN_InsertNewSocialSite($userID, $iN->iN_Secure($newSocialSite), $iN->iN_Secure($newSocialSiteKey), $iN->iN_Secure($newSocialSiteStatus), $newSocialSiteSVGCode);
		if($insertNewSocialSite){
			exit('200');
		}else{
			exit(iN_HelpSecure($LANG['noway_desc']));
		}
	}
}
/*Update Sticker Status*/
if ($type == 'upSocial') {
	if (isset($_POST['id']) && in_array(isset($_POST['mod']), $yesNo)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$sID = mysqli_real_escape_string($db, $_POST['id']);
		$updateSocialSiteStatus = $iN->iN_UpdateSocialSiteStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($sID));
		if ($updateSocialSiteStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
if ($type == 'upwSocial') {
	if (isset($_POST['id']) && in_array(isset($_POST['mod']), $yesNo)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$sID = mysqli_real_escape_string($db, $_POST['id']);
		$updateSocialSiteStatus = $iN->iN_UpdateWebsiteSocialSiteStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($sID));
		if ($updateSocialSiteStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Add New Sticker Url*/
if ($type == 'editnewSocialSite') {
	if(isset($_POST['ssid']) && isset($_POST['social_site']) && isset($_POST['socail_key']) && isset($_POST['socialsvgcode']) && in_array($_POST['socialsitestatus'], $yesNo)){
		$socialSiteID = mysqli_real_escape_string($db, $_POST['ssid']);
		$newSocialSite = mysqli_real_escape_string($db, $_POST['social_site']);
		$newSocialSiteKey = mysqli_real_escape_string($db, $_POST['socail_key']);
		$newSocialSiteSVGCode = mysqli_real_escape_string($db, $_POST['socialsvgcode']);
		$newSocialSiteStatus = mysqli_real_escape_string($db, $_POST['socialsitestatus']);
		if (!substr_count($newSocialSiteSVGCode, '<svg')) {
			exit('1');
		}
		if(preg_replace('/\s+/', '',$newSocialSite) == '' || preg_replace('/\s+/', '',$newSocialSiteKey) == '' || preg_replace('/\s+/', '',$newSocialSiteSVGCode) == ''){
			exit('2');
		}
		$insertNewSocialSite = $iN->iN_UpdateSocialSite($userID, $iN->iN_Secure($socialSiteID), $iN->iN_Secure($newSocialSite), $iN->iN_Secure($newSocialSiteKey), $iN->iN_Secure($newSocialSiteStatus), $newSocialSiteSVGCode);
		if($insertNewSocialSite){
			exit('200');
		}else{
			exit(iN_HelpSecure($LANG['noway_desc']));
		}
	}
}
/*Delete Question*/
if ($type == 'deleteSocialSit') {
	if (isset($_POST['id'])) {
		$sSite = mysqli_real_escape_string($db, $_POST['id']);
		$deletesSite = $iN->iN_DeleteSocialSite($userID, $iN->iN_Secure($sSite));
		if ($deletesSite) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Who Can Create Status*/
if ($type == 'whoCanCreateVideoCall') {
	if (in_array($_POST['mod'], $yesNo)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updatePostCreateStatus = $iN->iN_UpdateWhoCanCreateVideoCallFeatureStatus($userID, $iN->iN_Secure($mod));
		if ($updatePostCreateStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Who Can Create Status*/
if ($type == 'isVideoCallFree') {
	if (in_array($_POST['mod'], $yesNo)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updatePostCreateStatus = $iN->iN_UpdateIsVideoCallPaidStatus($userID, $iN->iN_Secure($mod));
		if ($updatePostCreateStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}

/*Search Result Aupdate*/
if ($type == 'searchResultUpdate') {
	if (in_array($_POST['mod'], $yesNo)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updatePostCreateStatus = $iN->iN_UpdateSarchResultStatus($userID, $iN->iN_Secure($mod));
		if ($updatePostCreateStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Add New Sticker Url*/
if ($type == 'editnewWebsiteSocialSite') {
	if(isset($_POST['ssid']) && isset($_POST['social_site']) && isset($_POST['socail_key']) && isset($_POST['socialsvgcode']) && in_array($_POST['socialsitestatus'], $yesNo)){
		$socialSiteID = mysqli_real_escape_string($db, $_POST['ssid']);
		$newSocialSite = mysqli_real_escape_string($db, $_POST['social_site']);
		$newSocialSiteKey = mysqli_real_escape_string($db, $_POST['socail_key']);
		$newSocialSiteSVGCode = mysqli_real_escape_string($db, $_POST['socialsvgcode']);
		$newSocialSiteStatus = mysqli_real_escape_string($db, $_POST['socialsitestatus']);
		if (!substr_count($newSocialSiteSVGCode, '<svg')) {
			exit('1');
		}
		if(preg_replace('/\s+/', '',$newSocialSite) == '' || preg_replace('/\s+/', '',$newSocialSiteKey) == '' || preg_replace('/\s+/', '',$newSocialSiteSVGCode) == ''){
			exit('2');
		}
		$insertNewSocialSite = $iN->iN_UpdateWebsiteSocialSite($userID, $iN->iN_Secure($socialSiteID), $iN->iN_Secure($newSocialSite), $iN->iN_Secure($newSocialSiteKey), $iN->iN_Secure($newSocialSiteStatus), $newSocialSiteSVGCode);
		if($insertNewSocialSite){
			exit('200');
		}else{
			exit(iN_HelpSecure($LANG['noway_desc']));
		}
	}
}
/*Delete Question*/
if ($type == 'deleteSocialSitW') {
	if (isset($_POST['id'])) {
		$sSite = mysqli_real_escape_string($db, $_POST['id']);
		$deletesSite = $iN->iN_DeleteWebsiteSocialSite($userID, $iN->iN_Secure($sSite));
		if ($deletesSite) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Search Result Aupdate*/
if ($type == 'autoAcceptPremiumPostStatus') {
	if (in_array($_POST['mod'], $yesNo)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updateAutoUpdatePremiumPostStatus = $iN->iN_UpdateAutoAcceptPremiumPostStatus($userID, $iN->iN_Secure($mod));
		if ($updateAutoUpdatePremiumPostStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Update Mercadopago Business And Sandbox Email Address*/
if ($type == 'updateMercadoPago') {
	if (isset($_POST['mercadopagotesttoken']) && isset($_POST['mercadopagolivetoken']) && isset($_POST['mercadopago_currency'])) {
		$testTokenID = mysqli_real_escape_string($db, $_POST['mercadopagotesttoken']);
		$liveTokenID = mysqli_real_escape_string($db, $_POST['mercadopagolivetoken']);
		$mercadoPago_Currency = mysqli_real_escape_string($db, $_POST['mercadopago_currency']);
		$updateMercadoPagoDetails = $iN->iN_UpdateMercadoPagoDetails($userID, $iN->iN_Secure($testTokenID), $iN->iN_Secure($liveTokenID), $iN->iN_Secure($mercadoPago_Currency));
		if ($updateMercadoPagoDetails) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update MercadoPago Mode Status*/
if ($type == 'mercadomode') {
	if (in_array(isset($_POST['mod']), $statusValue)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updateMercadoPagoMode = $iN->iN_UpdateMercadoPagoMode($userID, $iN->iN_Secure($mod));
		if ($updateMercadoPagoMode) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update MercadoPago Status*/
if ($type == 'mercadopago_status') {
	if (in_array(isset($_POST['mod']), $statusValue)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updateMercadopagoStatus = $iN->iN_UpdateMercadoPagoStatus($userID, $iN->iN_Secure($mod));
		if ($updateMercadopagoStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*drawTextMode status*/
if ($type == 'drawTextMode') {
	if (in_array($_POST['mod'], $statusValue)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updateffmpegSendStatus = $iN->iN_UpdateFFMPEGDrawTextStatus($userID, $iN->iN_Secure($mod));
		if ($updateffmpegSendStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Search Result Aupdate*/
if ($type == 'subCatMod') {
	if (in_array($_POST['mod'], $statusValue) && isset($_POST['sID']) && $_POST['sID'] !== '') {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$iD = mysqli_real_escape_string($db, $_POST['sID']);
		$updateSubCategoryStatus = $iN->iN_UpdateSubCategoryStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($iD));
		if ($updateSubCategoryStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}

/*Update Sub Kategory Key*/
if($type == 'upSubKey'){
   if(isset($_POST['skey']) && isset($_POST['sid']) && strlen(trim($_POST['skey'])) != 0){
		$newKey = mysqli_real_escape_string($db, $_POST['skey']);
		$id = mysqli_real_escape_string($db, $_POST['sid']);
		$updateSubCategoryKey = $iN->iN_UpdateSubCategoryKey($userID, $iN->iN_Secure($newKey), $iN->iN_Secure($id));
		if ($updateSubCategoryStatus) {
			exit('200');
		} else {
			echo '404';
		}
   }
}

/*CREATE NEW SUB CATEGORY*/
if($type == 'addNewSubCat'){
   if(isset($_POST['nkey']) && isset($_POST['addTo']) && strlen(trim($_POST['nkey'])) != 0){
      $newSubCatKey = mysqli_real_escape_string($db, $_POST['nkey']);
	  $addToThisCategory = mysqli_real_escape_string($db, $_POST['addTo']);
	  $addNewCategoryKey = $iN->iN_CreateNewSubCategory($userID, $newSubCatKey, $addToThisCategory);
	  if($addNewCategoryKey){
        $subCategoryKey = $addNewCategoryKey['sc_key'];
		$scID = $addNewCategoryKey['sc_id'];
		$scStatus = $addNewCategoryKey['sc_status'];
		include("../sources/contents/newSubCategory.php");
	  }
   }
}
if($type == 'delSubCat'){
   if(isset($_POST['id']) && strlen(trim($_POST['id'])) != 0){
	   $subCID = mysqli_real_escape_string($db, $_POST['id']);
	   $deleteSubCategory = $iN->iN_DeleteSubCat($userID,$subCID);
	   if($deleteSubCategory){
		   exit('200');
	   }else{
		   exit('404');
	   }
   }
}

/*Update Category Status*/
if ($type == 'catModStatus') {
	if (in_array($_POST['mod'], $statusValue) && isset($_POST['Cid']) && $_POST['Cid'] !== '') {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$iD = mysqli_real_escape_string($db, $_POST['Cid']);
		$updateSubCategoryStatus = $iN->iN_UpdateCategoryStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($iD));
		if ($updateSubCategoryStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}

/*Update Sub Kategory Key*/
if($type == 'upCatKey'){
	if(isset($_POST['ckey']) && isset($_POST['cid'])){
		 $newKey = mysqli_real_escape_string($db, $_POST['ckey']);
		 $id = mysqli_real_escape_string($db, $_POST['cid']);
		 $updateCategoryKey = $iN->iN_UpdateCategoryKey($userID, $iN->iN_Secure($newKey), $iN->iN_Secure($id));
		 if ($updateCategoryKey) {
			 exit('200');
		 } else {
			 echo '404';
		 }
	}
}
if($type == 'delCatt'){
	if(isset($_POST['id']) && strlen(trim($_POST['id'])) != 0){
		$subCID = mysqli_real_escape_string($db, $_POST['id']);
		$deleteSubCategory = $iN->iN_DeleteCat($userID,$subCID);
		if($deleteSubCategory){
			exit('200');
		}else{
			exit('404');
		}
	}
}
if($type == 'cNewCatP'){
	if(isset($_POST['ky']) && strlen(trim($_POST['ky'])) != 0){
		$newCategoryKey = mysqli_real_escape_string($db, $_POST['ky']);
		$insertNewProfileCategory = $iN->iN_InsertNewProfileCategory($userID, $newCategoryKey);
		if ($insertNewProfileCategory) {
			exit('200');
		} else {
			exit($LANG['save_failed']);
		}

	}
}
/*Add New Sticker Url*/
if ($type == 'newWebSocialSite') {
	if(isset($_POST['social_site']) && isset($_POST['socail_key']) && isset($_POST['socialsvgcode']) && in_array($_POST['socialsitestatus'], $yesNo)){

		$newSocialSite = mysqli_real_escape_string($db, $_POST['social_site']);
		$newSocialSiteKey = mysqli_real_escape_string($db, $_POST['socail_key']);
		$newSocialSiteSVGCode = mysqli_real_escape_string($db, $_POST['socialsvgcode']);
		$newSocialSiteStatus = mysqli_real_escape_string($db, $_POST['socialsitestatus']);
		if (!substr_count($newSocialSiteSVGCode, '<svg')) {
			exit('1');
		}
		if(preg_replace('/\s+/', '',$newSocialSite) == '' || preg_replace('/\s+/', '',$newSocialSiteKey) == '' || preg_replace('/\s+/', '',$newSocialSiteSVGCode) == ''){
			exit('2');
		}
		$insertNewSocialSite = $iN->iN_InsertNewWebSocialSite($userID, $iN->iN_Secure($newSocialSite), $iN->iN_Secure($newSocialSiteKey), $iN->iN_Secure($newSocialSiteStatus), $newSocialSiteSVGCode);
		if($insertNewSocialSite){
			exit('200');
		}else{
			exit(iN_HelpSecure($LANG['noway_desc']));
		}
	}
}
/*Update Boost Status*/
if ($type == 'uPBoost') {
	if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$bgID = mysqli_real_escape_string($db, $_POST['id']);
		$updateStoryBgStatus = $iN->iN_UpdateBoostPostStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($bgID));
		if ($updateStoryBgStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Yes Delete Product From Data*/
if ($type == 'deleteBoostedPost') {
	if (isset($_POST['id']) && !empty($_POST['id'])) {
		$productID = mysqli_real_escape_string($db, $_POST['id']);
		$checkProductIDExist = $iN->iN_CheckBoostExist($productID);
		if ($checkProductIDExist) {
			$okDelete = $iN->iN_DeleteBoostedPost($userID, $productID);
			if ($okDelete) {
				exit('200');
			} else {
				echo '404';
			}
		} else {
			exit($LANG['this_pos_no_longer_available']);
		}
	}
}
/*Change Plan Status*/
if ($type == 'planBoostStatus') {
	if (isset($_POST['id']) && in_array(isset($_POST['mod']), $statusValue)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$planID = mysqli_real_escape_string($db, $_POST['id']);
		$updatePlanStatus = $iN->iN_UpdateBoostPlanStatus($userID, $iN->iN_Secure($mod), $iN->iN_Secure($planID));
		if ($updatePlanStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Delete Post*/
if ($type == 'deleteThisBoostPlan') {
	if (isset($_POST['id'])) {
		$planID = mysqli_real_escape_string($db, $_POST['id']);
		$deletePlan = $iN->iN_DeleteBoostPlanFromData($userID, $iN->iN_Secure($planID));
		if ($deletePlan) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Edit Plan*/
if ($type == 'editBoostPlan') {
	if (isset($_POST['planKey']) && isset($_POST['planViewTime']) && isset($_POST['planAmount']) && isset($_POST['newsvgcode']) && isset($_POST['planid'])) {
		$planKey = mysqli_real_escape_string($db, $_POST['planKey']);
		$planViewTime = mysqli_real_escape_string($db, $_POST['planViewTime']);
		$planAmount = mysqli_real_escape_string($db, $_POST['planAmount']);
		$planID = mysqli_real_escape_string($db, $_POST['planid']);
		$planSVGIcon = mysqli_real_escape_string($db, $_POST['newsvgcode']);
		$removeAllSpaceFromKey = preg_replace('/\s+/', '', $planKey);
		if (ctype_space($planViewTime) || empty($planViewTime)) {
			exit(iN_HelpSecure($LANG['please_fill_in_all_fields']));
		}
		if (ctype_space($planAmount) || empty($planAmount)) {
			exit(iN_HelpSecure($LANG['please_fill_in_all_fields']));
		}
		if (ctype_space($planKey) || !isset($planKey) || empty($planKey)) {
			exit(iN_HelpSecure($LANG['plan_key_warning']));
		}
		if (ctype_space($planSVGIcon) || !isset($planSVGIcon) || empty($planSVGIcon)) {
			exit(iN_HelpSecure($LANG['mustwritesvgcode']));
		}
		if (empty($removeAllSpaceFromKey) || $removeAllSpaceFromKey == '' || empty($removeAllSpaceFromKey) || strlen($removeAllSpaceFromKey) == '0' || ctype_space($removeAllSpaceFromKey)) {
			exit('404');
		} else {
			$updatePlan = $iN->iN_UpdateBoostPlanFromID($userID, $iN->iN_Secure($planKey), $iN->iN_Secure($planViewTime), $iN->iN_Secure($planAmount), $planSVGIcon, $iN->iN_Secure($planID));
			if ($updatePlan) {
				exit('200');
			} else {
				exit($LANG['noway_desc']);
			}
		}
	}
}
/*Add New Point Plan*/
if ($type == 'newBoostPackageForm') {
	if (isset($_POST['planKey']) && isset($_POST['planViewTime']) && isset($_POST['planAmount']) && isset($_POST['newsvgcode'])) {
		$planKey = mysqli_real_escape_string($db, $_POST['planKey']);
		$planViewTime = mysqli_real_escape_string($db, $_POST['planViewTime']);
		$planAmount = mysqli_real_escape_string($db, $_POST['planAmount']);
		$planSVGIcon = mysqli_real_escape_string($db, $_POST['newsvgcode']);
		$removeAllSpaceFromKey = preg_replace('/\s+/', '', $planKey);
		if (ctype_space($planKey) || !isset($planKey) || empty($planKey)) {
			exit('4');
		}
		if (ctype_space($planViewTime)) {
			exit('1');
		}
		if (ctype_space($planAmount) || empty($planAmount)) {
			exit('3');
		}
		$updatePlan = $iN->iN_InsertNewBOOSTPlan($userID, $iN->iN_Secure($planKey), $iN->iN_Secure($planViewTime), $iN->iN_Secure($planAmount), $planSVGIcon);
		if ($updatePlan) {
			exit('200');
		} else {
			exit($LANG['noway_desc']);
		}
	} else {
		echo '5';
	}
}
/*Approve Bank Payment*/
if($type == 'approveBankPayment'){
    if(isset($_POST['payerid']) && isset($_POST['planID']) && isset($_POST['imID']) && isset($_POST['paymentID'])){
        $payerID = mysqli_real_escape_string($db, $_POST['payerid']);
        $imageID = mysqli_real_escape_string($db, $_POST['imID']);
        $planID = mysqli_real_escape_string($db, $_POST['planID']);
        $paymentIDD = mysqli_real_escape_string($db, $_POST['paymentID']);
		$insertApprove = $iN->iN_InsertApprove($userID, $payerID, $planID, $imageID,$paymentIDD);
		/****/
		if($insertApprove){
			$dataEmail = $iN->iN_GetUserDetails($payerID);
			$emailBody = iN_HelpSecure($LANG['bank_payment_accepted']);
			$sendEmail = isset($dataEmail['i_user_email']) ? $dataEmail['i_user_email'] : NULL;
			$wrapperStyle = "width:100%; border-radius:3px; background-color:#fafafa; text-align:center; padding:50px 0; overflow:hidden; display:flex; display:-webkit-flex;";
            $containerStyle = "width:100%; max-width:600px; border:1px solid #e6e6e6; margin:0 auto; background-color:#ffffff; padding:15px; border-radius:3px;";
            $logoBoxStyle = "width:100%; max-width:100px; margin:0 auto 30px auto; overflow:hidden;";
            $imgStyle = "width:100%; overflow:hidden;";
            $contentStyle = "width:100%; position:relative; display:inline-block; padding-bottom:10px;";
            $buttonBoxStyle = "width:100%; position:relative; padding:10px; background-color:#20B91A; max-width:350px; margin:0 auto; color:#ffffff !important;";
            $linkStyle = "text-decoration:none; color:#ffffff !important; font-weight:500; font-size:14px; position:relative;";
			if ($emailSendStatus == '1') {
				if ($smtpOrMail == 'mail') {
					$mail->IsMail();
				} else if ($smtpOrMail == 'smtp') {
					$mail->isSMTP();
					$mail->Host = $smtpHost; // Specify main and backup SMTP servers
					$mail->SMTPAuth = true;
					$mail->SMTPKeepAlive = true;
					$mail->Username = $smtpUserName; // SMTP username
					$mail->Password = $smtpPassword; // SMTP password
					$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
					$mail->Port = $smtpPort;
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true,
						),
					);
				} else {
					return false;
				}
				$body = '
                <div style="' . $wrapperStyle . '">
                  <div style="' . $containerStyle . '">

                    <div style="' . $logoBoxStyle . '">
                      <img src="' . $siteLogoUrl . '" style="' . $imgStyle . '" />
                    </div>

                    <div style="' . $contentStyle . '">
                      ' . $emailBody . '
                    </div>

                    <div style="' . $buttonBoxStyle . '">
                      <a href="' . $base_url . '" style="' . $linkStyle . '">' . iN_HelpSecure($LANG['gotowebsite']) . '</a>
                    </div>

                  </div>
                </div>';
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $emailTitle;
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if ($mail->send()) {
					$mail->ClearAddresses();
					echo '200';
					return true;
				}
			}
			exit('200');
		}
		/****/
	}
}
/*Decline Bank Payment*/
if($type == 'declineBankPayment'){
	if(isset($_POST['payerid']) && isset($_POST['planID']) && isset($_POST['imID']) && isset($_POST['paymentID'])){
        $payerID = mysqli_real_escape_string($db, $_POST['payerid']);
        $imageID = mysqli_real_escape_string($db, $_POST['imID']);
        $planID = mysqli_real_escape_string($db, $_POST['planID']);
        $paymentIDD = mysqli_real_escape_string($db, $_POST['paymentID']);
		$declineBankPayment = $iN->iN_DeclineBankPaymentRequest($userID, $payerID, $planID, $imageID,$paymentIDD);
	    /****/
		if($declineBankPayment){
			$dataEmail = $iN->iN_GetUserDetails($payerID);
			$emailBody = iN_HelpSecure($LANG['bank_payment_declined']);
			$sendEmail = isset($dataEmail['i_user_email']) ? $dataEmail['i_user_email'] : NULL;
			$wrapperStyle = "width:100%; border-radius:3px; background-color:#fafafa; text-align:center; padding:50px 0; overflow:hidden; display:flex; display:-webkit-flex;";
            $containerStyle = "width:100%; max-width:600px; border:1px solid #e6e6e6; margin:0 auto; background-color:#ffffff; padding:15px; border-radius:3px;";
            $logoBoxStyle = "width:100%; max-width:100px; margin:0 auto 30px auto; overflow:hidden;";
            $imgStyle = "width:100%; overflow:hidden;";
            $contentStyle = "width:100%; position:relative; display:inline-block; padding-bottom:10px;";
            $buttonBoxStyle = "width:100%; position:relative; padding:10px; background-color:#20B91A; max-width:350px; margin:0 auto; color:#ffffff !important;";
            $linkStyle = "text-decoration:none; color:#ffffff !important; font-weight:500; font-size:14px; position:relative;";

			if ($emailSendStatus == '1') {
				if ($smtpOrMail == 'mail') {
					$mail->IsMail();
				} else if ($smtpOrMail == 'smtp') {
					$mail->isSMTP();
					$mail->Host = $smtpHost; // Specify main and backup SMTP servers
					$mail->SMTPAuth = true;
					$mail->SMTPKeepAlive = true;
					$mail->Username = $smtpUserName; // SMTP username
					$mail->Password = $smtpPassword; // SMTP password
					$mail->SMTPSecure = $smtpEncryption; // Enable TLS encryption, `ssl` also accepted
					$mail->Port = $smtpPort;
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true,
						),
					);
				} else {
					return false;
				}
				$body = '
                <div style="' . $wrapperStyle . '">
                  <div style="' . $containerStyle . '">

                    <div style="' . $logoBoxStyle . '">
                      <img src="' . $siteLogoUrl . '" style="' . $imgStyle . '" />
                    </div>

                    <div style="' . $contentStyle . '">
                      ' . $emailBody . '
                    </div>

                    <div style="' . $buttonBoxStyle . '">
                      <a href="' . $base_url . '" style="' . $linkStyle . '">' . iN_HelpSecure($LANG['gotowebsite']) . '</a>
                    </div>

                  </div>
                </div>';
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $emailTitle;
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if ($mail->send()) {
					$mail->ClearAddresses();
					echo '200';
					return true;
				}
			}
			exit('200');
		}
	}
}
/*Update Auto Detect Language Status*/
if ($type == 'detect_lang_status') {
	if (in_array($_POST['mod'], $statusValue)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updateDetectLanguageStatus = $iN->iN_UpdateDetectLanguageStatus($userID, $iN->iN_Secure($mod));
		if ($updateDetectLanguageStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Update Ai Generator Status*/
if ($type == 'ai_generator_status') {
	if (in_array($_POST['mod'], $statusValue)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updateAiGeneratorStatus = $iN->iN_UpdateAiGeneratorStatus($userID, $iN->iN_Secure($mod));
		if ($updateAiGeneratorStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Update Email Send Mode Status*/
if ($type == 'send__email') {
	if (in_array($_POST['mod'], $statusValue)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updateEmailSendStatus = $iN->iN_UpdateEmailSendStatusForCPP($userID, $iN->iN_Secure($mod));
		if ($updateEmailSendStatus) {
			exit('200');
		} else {
			echo '404';
		}
	}
}
/*Update MercadoPago Status*/
if ($type == 'bankPaymentStatus') {
	if (in_array(isset($_POST['mod']), $statusValue)) {
		$mod = mysqli_real_escape_string($db, $_POST['mod']);
		$updateBankPaymentStatus = $iN->iN_UpdateBankPaymentPagoStatus($userID, $iN->iN_Secure($mod));
		if ($updateBankPaymentStatus) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
/*Update Bankpayment*/
if ($type == 'bankPaymentStatusa') {
	if (isset($_POST['bankpaymentpercentagefee']) && isset($_POST['bankpaymentfixedcharge']) && isset($_POST['bank_description'])) {
		$percentageFee = mysqli_real_escape_string($db, $_POST['bankpaymentpercentagefee']);
		$fixedCharge = mysqli_real_escape_string($db, $_POST['bankpaymentfixedcharge']);
		$bankDescription = mysqli_real_escape_string($db, $_POST['bank_description']);
		$updateBankPaymentDetails = $iN->iN_UpdateBankPaymentDetails($userID, $iN->iN_Secure($percentageFee), $iN->iN_Secure($fixedCharge), $bankDescription);
		if ($updateBankPaymentDetails) {
			exit('200');
		} else {
			echo iN_HelpSecure($LANG['noway_desc']);
		}
	}
}
if ($type == 'frameFile') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			$theValidateType = mysqli_real_escape_string($db, $_POST['c']);
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableVerificationFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
                        $uploadGiftImage = $serverDocumentRoot . '/img/frames/';
						// Change the image ame
						$tmp = $_FILES['uploading']['tmp_name'][$iname];
						$mimeType = $_FILES['uploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadGiftImage . $d)) {
							$newFile = mkdir($uploadGiftImage . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadGiftImage . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'img/frames/' . $d . '/' . $getFilename;
								$UploadSourceUrl = $base_url . 'img/frames/' . $d . '/' . $getFilename;
							}
							echo $pathFile;
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
    /*Edit Frame Plan*/
	if ($type == 'editFramePlan') {
		if (isset($_POST['planPoint']) && isset($_POST['planid']) && isset($_POST['frameFile'])) {
			$framePrice = mysqli_real_escape_string($db, $_POST['planPoint']);
			$frameID = mysqli_real_escape_string($db, $_POST['planid']);
			$frameFile = mysqli_real_escape_string($db, $_POST['frameFile']);

			if ($framePrice < $minimumPointLimit || ctype_space($framePrice) || empty($framePrice)) {
				exit(preg_replace('/{.*?}/', $minimumPointLimit, $LANG['plan_point_warning']));
			}
			if ($framePrice > $maximumPointAmountLimit || ctype_space($framePrice) || empty($framePrice)) {
				exit(preg_replace('/{.*?}/', $maximumPointAmountLimit, $LANG['plan_point_amount_warning']));
			}

		    $updateLivePlan = $iN->iN_UpdateFramePlanFromID($userID, $framePrice, $frameFile,$frameID);
				if ($updateLivePlan) {
					exit('200');
				} else {
					exit($LANG['noway_desc']);
				}
		}
	}
	/*Add new Frame Card*/
	if ($type == 'newFrameCardForm') {
		if (isset($_POST['frameFile']) && isset($_POST['framePoint'])) {
			$giftImage = mysqli_real_escape_string($db, $_POST['frameFile']);
			$giftPoint = mysqli_real_escape_string($db, $_POST['framePoint']);
			$giftAmount = $giftPoint * $onePointEqual;
			if (empty($giftImage)) {
				exit('3');
			}
			if (empty($giftPoint)) {
				exit('3');
			}

			if (!empty($giftImage) && !empty($giftPoint)) {
				$insertNewFrame = $iN->iN_InsertNewFrameCard($userID, $iN->iN_Secure($giftImage), $iN->iN_Secure($giftPoint));
				if ($insertNewFrame) {
					exit('200');
				} else {
					echo iN_HelpSecure($LANG['noway_desc']);
				}
			} else {
				exit('1');
			}
		}
	}
	/*Save Color Change*/
	if($type == 'changeColor'){
	    if(isset($_POST['data']) && isset($_POST['clr'])){
	        $dataRow = mysqli_real_escape_string($db, $_POST['data']);
	        $dataColor = mysqli_real_escape_string($db, $_POST['clr']);
	        $dataColor = str_replace('#', '', $dataColor);
	        $checkDataRowExist = $iN->iN_CheckRowExist($dataRow);
	        if($checkDataRowExist){
	             $updateColor = $iN->iN_ChangeColor($userID, $dataRow, $dataColor);
	             if($updateColor){
	                 exit('200');
	             }else{
	                 exit('404');
	             }
	        }else{
	            exit('4042');
	        }
	    }
	}
	/*Set Default Color*/
	if($type == 'setDefaultColor'){
	    if(isset($_POST['data'])){
	        $dataRow = mysqli_real_escape_string($db, $_POST['data']);
	        $checkDataRowExist = $iN->iN_CheckRowExist($dataRow);
	        if($checkDataRowExist){
	             $updateColor = $iN->iN_UpdateDefaultColor($userID, $dataRow);
	             if($updateColor){
	                 exit('200');
	             }else{
	                 exit('404');
	             }
	        }else{
	            exit('404');
	        }
	    }
	}
	/*Change Subscription Mode*/
	if ($type == 'renewalsubs') {
		if (in_array($_POST['mod'], $yesNo)) {
			$mod = mysqli_real_escape_string($db, $_POST['mod']);
			$updatePostCreateStatus = $iN->iN_UpdateSubscriptionType($userID, $iN->iN_Secure($mod));
			if ($updatePostCreateStatus) {
				exit('200');
			} else {
				echo '404';
			}
		}
	}
	/*Search Result Aupdate*/
    if ($type == 'autoFollowAdmin') {
    	if (in_array($_POST['mod'], $yesNo)) {
    		$mod = mysqli_real_escape_string($db, $_POST['mod']);
    		$updatePostCreateStatus = $iN->iN_UpdateAutoFollowAdminStatus($userID, $iN->iN_Secure($mod));
    		if ($updatePostCreateStatus) {
    			exit('200');
    		} else {
    			echo '404';
    		}
    	}
    }
} else {
	echo 'This is test account you can not change anything because admin features disabled for test admin user.';
}
?>