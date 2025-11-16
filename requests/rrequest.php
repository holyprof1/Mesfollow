<?php
// Load core application configuration and required constants
include "../includes/inc.php";

// Include thumbnail cropping helper (used for image resizing/cropping logic)
include "../includes/thumbncrop.inc.php";

// Load appropriate cloud storage handler based on active configuration
if ($s3Status == '1') {
    // AWS S3 file upload library
    include "../includes/s3.php";
} else if ($digitalOceanStatus == '1') {
    // DigitalOcean Spaces support
    include "../includes/spaces/spaces.php";
} else if ($WasStatus == '1') {
    // Wasabi storage integration
    include "../includes/ws3.php";
}

// Include image filtering class (used for contrast, brightness, grayscale etc.)
include "../includes/imageFilter.php";

// Import the ImageFilter class from its namespace
use imageFilter\ImageFilter;

/* -------------------------------------------
 | Email Delivery: PHPMailer Integration
 --------------------------------------------*/

// Import PHPMailer classes into global scope
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer via Composer (make sure it's installed)
require '../includes/phpmailer/vendor/autoload.php';

// Instantiate PHPMailer (true enables exception handling)
$mail = new PHPMailer(true);

/* -------------------------------------------
 | Define Application-Wide Constants
 --------------------------------------------*/

// Who can view (e.g., followers, subscribers, public etc.)
$whoCanSeeArrays = array('1', '2', '3', '4');

// Block types (user blocks, post blocks etc.)
$blockType = array('1', '2');

// Possible status flags
$statusValue = array('0', '1');

// Available UI themes
$themes = array('light', 'dark');

// Video formats supported if FFMPEG is not available
$nonFfmpegAvailableVideoFormat = array('mp4','MP4','mov','MOV');

// Supported payout methods for the platform
$defaultPayoutMethods = array('paypal', 'bank');

// Available gender options for users
$genders = array('male', 'couple', 'female');

// Common yes/no values
$yesOrNo = array('yes', 'no');

/* -------------------------------------------
 | OpenAI Integration (Chat Completion API)
 --------------------------------------------*/

if ($openAiStatus == '1') {
    /**
     * Call OpenAI Chat Completion API to generate creative responses
     *
     * @param string $userPrompt        User's input message
     * @param string $opanAiKey         API key for authorization
     * @return string                   AI-generated short response (max ~150 tokens)
     */
    function callOpenAI($userPrompt, $opanAiKey) {
        $apiKey = $opanAiKey;
        $url = 'https://api.openai.com/v1/chat/completions';

        $data = [
            "model" => "gpt-4-turbo",
            "messages" => [
                [
                    "role" => "system",
                    "content" => "You are a content creator assistant. Always respond with a creative text, maximum 250 characters."
                ],
                [
                    "role" => "user",
                    "content" => $userPrompt
                ]
            ],
            "temperature" => 0.8,
            "max_tokens" => 150
        ];

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['choices'][0]['message']['content'] ?? "no";
    }
}

/* -------------------------------------------
 | Watermark System (Image Branding Layer)
 --------------------------------------------*/

if ($watermarkStatus == 'yes') {
    /**
     * Apply watermark logo and optional link text to image
     */
    /**
     * Fallback watermark logic (only link text, no logo)
     */
    function watermark_image($target, $siteWatermarkLogo, $LinkWatermarkStatus, $ourl) {
        include_once "../includes/SimpleImage-master/src/claviska/SimpleImage.php";

        try {
            $image = new \claviska\SimpleImage();
            $image
                ->fromFile($target)
                ->autoOrient()
                ->overlay('../img/transparent.png', 'top left', 1, 30, 30)
                ->text($ourl, array(
                    'fontFile' => '../src/droidsanschinese.ttf',
                    'size' => 15,
                    'color' => '00897b',
                    'anchor' => 'bottom right',
                    'xOffset' => -10,
                    'yOffset' => -10
                ))
                ->toFile($target, 'image/jpeg');

            return true;
        } catch (Exception $err) {
            return $err->getMessage();
        }
    }
}

/**
 * Helper function to remove http:// or https:// from a given URL
 *
 * @param string $url
 * @return string cleaned URL without protocol
 */
function remove_http($url) {
    $disallowed = array('http://', 'https://');
    foreach ($disallowed as $d) {
        if (strpos($url, $d) === 0) {
            return str_replace($d, '', $url);
        }
    }
    return $url;
}
if (isset($_POST['f']) && $logedIn == '1') {
	$loginFormClass = '';
	$type = mysqli_real_escape_string($db, $_POST['f']);
	if ($type == 'topMenu') {
		include "../themes/$currentTheme/layouts/header/header_menu.php";
	}
	if ($type == 'topMessages') {
		$iN->iN_UpdateMessageNotificationStatus($userID);
		include "../themes/$currentTheme/layouts/header/messageNotifications.php";
	}
	if ($type == 'topNotifications') {
		$iN->iN_UpdateNotificationStatus($userID);
		include "../themes/$currentTheme/layouts/header/notifications.php";
	}
	if ($type == 'chooseLanguage') {
		include "../themes/$currentTheme/layouts/popup_alerts/chooseLanguage.php";
	}
	if ($type == "changeMyLang") {
		if (isset($_POST['id'])) {
			$langID = mysqli_real_escape_string($db, $_POST['id']);
			$updateUserLanguage = $iN->iN_UpdateLanguage($userID, $langID);
			if ($updateUserLanguage) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	if ($type == 'topPoints') {
		$iN->iN_UpdateMessageNotificationStatus($userID);
		include "../themes/$currentTheme/layouts/header/points_box.php";
	}

	if ($type == 'notifications') {
		if (isset($_POST['last'])) {
			$lastID = mysqli_real_escape_string($db, $_POST['last']);
			$moreNotifications = $iN->iN_GetMoreNotificationList($userID, $scrollLimit, $lastID);
			if ($moreNotifications) {
				include "../themes/$currentTheme/layouts/loadmore/morenotifications.php";
			} else {
				echo '<div class="nomore"><div class="no_more_in">' . $LANG['no_more_notifications'] . '</div></div>';
			}
		}
	}
	if ($type == 'whoSee') {
		if (isset($_POST['who']) && in_array($_POST['who'], $whoCanSeeArrays)) {
			$whoID = mysqli_real_escape_string($db, $_POST['who']);
			$updateWhoCanSee = $iN->iN_UpdateWhoCanSeePost($userID, $whoID);
			if ($updateWhoCanSee) {
				if ($whoID == 1) {
					$UpdatedWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('50') . '</div> ' . $LANG['weveryone'];
				} else if ($whoID == 2) {
					$UpdatedWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('15') . '</div> ' . $LANG['wfollowers'];
				} else if ($whoID == 3) {
					$UpdatedWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('51') . '</div> ' . $LANG['wsubscribers'];
				} else if ($whoID == 4) {
					$UpdatedWhoCanSee = '<div class="form_who_see_icon_set">' . $iN->iN_SelectedMenuIcon('9') . '</div> ' . $LANG['wpremium'];
				}
				echo html_entity_decode($UpdatedWhoCanSee);
			} else {
				echo '403';
			}
		}
	}
	if ($type == 'pw_premium') {
		echo '<div class="point_input_wrapper">
            <input type="text" name="point" class="pointIN" id="point" onkeypress="return event.charCode == 46 || (event.charCode >= 48 && event.charCode <= 57)" placeholder="' . $LANG['write_points'] . '">
            <div class="box_not box_not_padding_left">' . $LANG['point_wanted'] . '</div>
        </div>';
	}
	/*Video Custom Tumbnail*/
	if($type == 'vTumbnail'){
		if(isset($_POST['id']) && !empty($_POST['id'])){
			$dataID = mysqli_real_escape_string($db, $_POST['id']);
			$checkIDExist = $iN->iN_CheckImageIDExist($dataID, $userID);
			if($checkIDExist){
				if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
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
								if (!file_exists($uploadFile . $d)) {
									$newFile = mkdir($uploadFile . $d, 0755);
								}
								if (!file_exists($xImages . $d)) {
									$newFile = mkdir($xImages . $d, 0755);
								}
								if (!file_exists($xVideos . $d)) {
									$newFile = mkdir($xVideos . $d, 0755);
								}
								if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
                                  $tumbFilePath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
								  $thePath = '../uploads/files/'.$d.'/'.$UploadedFileName . '.' . $ext;
								  if (file_exists($thePath)) {
									try {
										$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.'.$ext;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
								}else{
									exit('Upload Failed');
								}
									if ($s3Status == '1') {
										/*Upload Full video*/
										$theName = '../uploads/files/' . $d . '/' . $getFilename;
										$key = basename($theName);
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/xvideos/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											//unlink($uploadFile . $d . '/' . $UploadedFileName . '.'.$ext);
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
									}else if ($WasStatus == '1') {
										/*Upload Full video*/
										$theName = '../uploads/files/' . $d . '/' . $getFilename;
										$key = basename($theName);
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $WasBucket,
												'Key' => 'uploads/xvideos/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											//unlink($uploadFile . $d . '/' . $UploadedFileName . '.'.$ext);
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $WasBucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
									} else if($digitalOceanStatus == '1'){
									   $theName = '../uploads/files/' . $d . '/' . $getFilename;
									   /*IF DIGITALOCEAN AVAILABLE THEN*/
									   $my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									   $upload = $my_space->UploadFile($theName, "public");
									   /**/
									   $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;;
									   $my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									   $upload = $my_space->UploadFile($thevTumbnail, "public");
									   $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
									   $my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									   $upload = $my_space->UploadFile($thevTumbnail, "public");
									   if($upload){
										   $UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;
										   @unlink($uploadFile . $d . '/' . $UploadedFileName . '.'.$ext);
										}
									   /*/IF DIGITAOCEAN AVAILABLE THEN*/
									} else {
										$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.'.$ext;;
									}
									//watermark_image($tumbFilePath);
									$updateTumbData = $iN->iN_UpdateUploadedFiles($userID, $tumbFilePath, $dataID);
									if($updateTumbData){
										echo $UploadSourceUrl;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	if ($type == 'upload') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableFileExtensions);
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
						}else if (preg_match('/audio\/*/', $mimeType)) {
							$fileTypeIs = 'audio';
						}
						if($mimeType == 'application/octet-stream'){
							$fileTypeIs = 'video';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						$wVideos = $serverDocumentRoot . '/uploads/videos/';
						if (!file_exists($wVideos . $d)) {
							$newFile = mkdir($wVideos . $d, 0755);
						}
						if ($fileTypeIs == 'video' && $ffmpegStatus == '0' && !in_array($ext, $nonFfmpegAvailableVideoFormat)) {
							exit('303');
						}
						$uploadTumbnail = '';
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'video') {
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
								$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								if ($ffmpegStatus == '1') {
									$convertUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$textVideoPath = '../uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4';

									$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									if ($ext == 'mpg') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mov') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec copy -acodec copy $convertUrl");
										$cmd = shell_exec("$ffmpegPath -ss 00:00:01.000 -i $convertUrl -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'wmv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'avi') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec h264 -acodec aac -strict -2 $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'webm') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -crf 1 -c:v libx264 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mpeg') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'flv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:a aac -strict -2 -b:a 128k -c:v libx264 -profile:v baseline $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'm4v') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mkv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -codec copy -strict -2 $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}else if($ext == '3gp'){
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}else{
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}

									$up_url = remove_http($base_url).$userName;
									$cmd = shell_exec("$ffmpegPath -ss 00:00:01 -i $convertUrl -c copy -t 00:00:04 $xVideoFirstPath 2>&1");
									if($drawTextStatus == '1'){
										$cmdText = shell_exec("$ffmpegPath -i $convertUrl -vf drawtext=fontfile=../src/droidsanschinese.ttf:text=$up_url:fontcolor=red:fontsize=18:x=10:y=H-th-10 $textVideoPath 2>&1");
									}else{
										$cmdText = shell_exec("$ffmpegPath -i $convertUrl -c:a copy -c:v libx264 -preset superfast -profile:v baseline $textVideoPath 2>&1");
									}
									if ($cmdText) {
									    //@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
										$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									}
									if (file_exists($videoTumbnailPath)) {
										try {
											$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.jpg';
											$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
											$image = new ImageFilter();
											$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");

										} catch (Exception $e) {
											echo '<span class="request_warning">' . $e->getMessage() . '</span>';
										}
									}else{
										exit('Please make sure that you have set the ffmpeg settings correct or that ffmpeg is installed on the server.');
									}
								} else {
									$cmd = '';
									$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
									$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
								}
								if ($ffmpegStatus == '1') {
    								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
    								$thePathM = '../' . $tumbnailPath;
									if($watermarkStatus == 'yes'){
    								  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}else if($LinkWatermarkStatus == 'yes'){
									  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}
								}
								/*CHECK AMAZON S3 AVAILABLE*/
								if ($s3Status == '1') {
									$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									/*Upload Full video*/
									$theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$key = basename($theName);
									if ($ffmpegStatus == '1') {
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($theName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$fullUploadedVideo = $result->get('ObjectURL');
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
								    }else{
										$theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									    $key = basename($theName);
									    try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($theName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$fullUploadedVideo = $result->get('ObjectURL');
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
									}
									if ($cmd) {
										/*Upload First x Second*/
										$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
										$key = basename($thexName);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/xvideos/' . $d . '/' . $key,
												'Body' => fopen($thexName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											/*rmdir($xVideos . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/xvideos/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										/*Upload Video tumbnail*/
										$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$key = basename($thevTumbnail);
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($thevTumbnail, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$UploadSourceUrl = $result->get('ObjectURL');
											/*rmdir($uploadFile . $d);*/
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
										@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
										@unlink($xVideos . $d . '/' . $UploadedFileName . '.jpg');
										@unlink($uploadFile . $d . '/' . $getFilename);
										@unlink($serverDocumentRoot . '/uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4');
									} else {
										$UploadSourceUrl = $base_url . 'uploads/web.png';
										/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
										$tumbnailPath = 'uploads/web.png';
										$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
									}

								}else if ($WasStatus == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false; // Show public access warning only once

                                    $theName = '../uploads/files/' . $d . '/' . $getFilename;
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/files/' . $d . '/' . $key,
                                                'Body'   => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/files/' . $d . '/' . $key,
                                                'Body'   => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        // Upload first X seconds clip
                                        $thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                        $key = basename($thexName);

                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body'   => fopen($thexName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to xvideos folder
                                        $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                        $key = basename($thevTumbnail);

                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body'   => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to original folder
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => 'uploads/files/' . $d . '/' . $key,
                                                'Body'   => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Remove temporary files
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($uploadFile . $d . '/' . $getFilename);
                                        @unlink($serverDocumentRoot . '/uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4');
                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }
                                } else if ($digitalOceanStatus == '1') {
                                	$localPath1 = '../uploads/files/' . $d . '/' . $getFilename;
                                	$remoteKey1 = 'uploads/files/' . $d . '/' . $getFilename;

                                	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
                                	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                                	if ($cmd) {
                                		$localPath2 = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$remoteKey2 = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                                		$localPath3 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                		$remoteKey3 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                		$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                                		$localPath4 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$remoteKey4 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$upload = $my_space->UploadFile($localPath4, "public", $remoteKey4);
                                	}

                                	if ($upload) {
                                		if ($cmd) {
                                			$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey3;
                                			$pathXFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                			$pathXImageFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                			$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';

                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
                                			@unlink($pathXImageFile);
                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
                                			@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                		} else {
                                			$UploadSourceUrl = $base_url . 'img/web.png';
                                			$tumbnailPath = 'img/web.png';
                                		}
                                	}
                                } else {
									if ($cmd) {
										$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
									} else {
										$UploadSourceUrl = $base_url . 'uploads/web.png';
										$tumbnailPath = 'uploads/web.png';
										$pathFile = $pathFile;
										$pathXFile = 'uploads/web.png';
									}
								}
								$ext = 'mp4';
								/**/
							} else if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$tumbnails = $serverDocumentRoot . '/uploads/files/' . $d . '/';
								$pathFilea = '../uploads/files/' . $d . '/' . $getFilename;
								$width = 500;
								$newHeight = 500;
								$file = $pathFilea;
								//indicate the path and name for the new resized file
								$resizedFile = $tumbnails . $UploadedFileName . '_' . $userID . '.' . $ext;
								$resizedFileTwo = '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
								$tb = new ThumbAndCrop();
								$tb->openImg($pathFilea);
								$newHeight = $tb->getRightHeight(500);
								$tb->creaThumb(500, $newHeight);
								$tb->setThumbAsOriginal();
								$tb->creaThumb(500, $newHeight);
								$tb->saveThumb($resizedFileTwo);

								$thePathM = '../' . $pathFile;
								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
								if($ext != 'gif'){
									if($watermarkStatus == 'yes'){
										watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									  }else if($LinkWatermarkStatus == 'yes'){
										watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									  }
								}
								if (file_exists($thePathM)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }
								if ($s3Status == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
										/*rmdir($uploadFile . $d);*/
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$UploadSourceUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $tumbnailPath;
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext);
								}else if ($WasStatus == '1') {

                                        // Define upload paths and their corresponding S3 keys
                                        $paths = [
                                            '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext,
                                            '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext,
                                            '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext,
                                        ];

                                        $keys = [
                                            'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext,
                                            'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext,
                                            'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext,
                                        ];

                                        // Flag to ensure public access error message is displayed only once
                                        $publicAccessErrorShown = false;

                                        foreach ($paths as $index => $filePath) {

                                            // Check if the file exists before trying to upload
                                            if (!file_exists($filePath)) {
                                                echo "File not found: $filePath<br>";
                                                continue;
                                            }

                                            $key = $keys[$index];

                                            try {
                                                // Upload the file to Wasabi S3 bucket
                                                $result = $s3->putObject([
                                                    'Bucket' => $WasBucket,
                                                    'Key'    => $key,
                                                    'Body'   => fopen($filePath, 'r'),
                                                    'CacheControl' => 'max-age=3153600',
                                                    // 'ACL' => 'public-read' is intentionally excluded for compatibility
                                                ]);

                                                $UploadSourceUrl = $result->get('ObjectURL');

                                                // Remove the local file after successful upload
                                                if (str_contains($key, 'uploads/files/')) {
                                                    @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                                } elseif (str_contains($key, 'uploads/pixel/')) {
                                                    @unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
                                                }

                                            } catch (Aws\S3\Exception\S3Exception $e) {
                                                $awsMsg = $e->getAwsErrorMessage();
                                                $fullMsg = $e->getMessage();
                                                // Only show this message once
                                                if (
                                                    !$publicAccessErrorShown &&
                                                    str_contains($awsMsg, 'Public use of objects is not allowed by this account')
                                                ) {
                                                    echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files.<br>
                                                    This typically occurs with trial accounts.<br>
                                                    Please contact Wasabi support or remove 'public-read' access settings from your upload configuration.</div>";

                                                    $publicAccessErrorShown = true;
                                                }
                                            }
                                        }

                                        // Optional: estimated public URL (may not work if object is private)
                                        $UploadSourceUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $tumbnailPath;
                                } else if ($digitalOceanStatus == '1') {

                            	$localPath1 = '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
                            	$remoteKey1 = 'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;

                            	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
                            	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                            	$localPath2 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
                            	$remoteKey2 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;

                            	$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                            	$localPath3 = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
                            	$remoteKey3 = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;

                            	$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                            	@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
                            	@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                            	@unlink($uploadFile . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext);

                            	if ($upload) {
                            		$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey1;
                            	}

                            } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}else if($fileTypeIs == 'audio'){
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$tumbnailPath = 'src/audio.png';
								$pathXFile = 'src/audio.png';
								$thePathM = '../' . $pathFile;
								if ($s3Status == '1') {
                                    $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$UploadSourceUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $tumbnailPath;
								}else if ($WasStatus == '1') {
									$key = basename($tumbnailPath);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($tumbnailPath, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink('uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$UploadSourceUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $tumbnailPath;
								} else if ($digitalOceanStatus == '1') {
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
									/*IF DIGITALOCEAN AVAILABLE THEN*/
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext);
									if ($upload) {
										$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $tumbnailPath;
									}
									/*/IF DIGITAOCEAN AVAILABLE THEN*/
								} else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							/**/
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							if ($fileTypeIs == 'video') {
								$uploadTumbnail = '
								<div class="v_custom_tumb">
									<label for="vTumb_' . $getUploadedFileID['upload_id'] . '">
										<div class="i_image_video_btn"><div class="pbtn pbtn_plus">' . $LANG['custom_tumbnail'] . '</div></div>
										<input type="file" id="vTumb_' . $getUploadedFileID['upload_id'] . '" class="imageorvideo cTumb editAds_file" data-id="' . $getUploadedFileID['upload_id'] . '" name="uploading[]" data-id="tupload">
									</label>
								</div>
								';
							}
							if ($fileTypeIs == 'video' || $fileTypeIs == 'Image') {
								/*AMAZON S3*/
								echo '
									<div class="i_uploaded_item iu_f_' . $getUploadedFileID['upload_id'] . ' ' . $fileTypeIs . '" id="' . $getUploadedFileID['upload_id'] . '">
									' . $postTypeIcon . '
									<div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
										' . $iN->iN_SelectedMenuIcon('5') . '
									</div>
									<div class="i_uploaded_file" id="viTumb' . $getUploadedFileID['upload_id'] . '" style="background-image:url(' . $UploadSourceUrl . ');">
											<img class="i_file" id="viTumbi' . $getUploadedFileID['upload_id'] . '" src="' . $UploadSourceUrl . '" alt="tumbnail">
									</div>
									' . $uploadTumbnail . '
									</div>
								';
							}else {
                                 echo '<div id="playing_' . $getUploadedFileID['upload_id'] . '" class="green-audio-player"><div class="i_uploaded_item nonePoint iu_f_' . $getUploadedFileID['upload_id'] . ' ' . $fileTypeIs . '"  id="' . $getUploadedFileID['upload_id'] . '"></div>
								  <audio  crossorigin="" preload="none">
								     <source src="' . $UploadSourceUrl . '" type="audio/mp3" />
								  </audio>
								  <script>
									$(function() {
									new GreenAudioPlayer("#playing_' . $getUploadedFileID['upload_id'] . '", { stopOthersOnPlay: true, showTooltips: true, showDownloadButton: false, enableKeystrokes: true });
									});
								  </script>
								 </div>';
							}
						}else{
							echo 'Something Wrong';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	/*DELETE UPLOADED FILE BEFORE PUBLISH*/
	if ($type == 'delete_file') {
		if (isset($_POST['file'])) {
			$fileID = mysqli_real_escape_string($db, $_POST['file']);
			$deleteFileFromData = $iN->iN_DeleteFile($userID, $fileID);
			if ($deleteFileFromData) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	/*INSERT NEW POST*/
	if ($type == 'newPost') {
		if (isset($_POST['txt']) && isset($_POST['file'])) {
			$text = mysqli_real_escape_string($db, $_POST['txt']);
			$file = mysqli_real_escape_string($db, $_POST['file']);
			if (empty($iN->iN_Secure($text)) && empty($file)) {
				echo '200';
				exit();
			}
			if($file != '' && !empty($file) && $file != 'undefined'){
				$trimValue = rtrim($file, ',');
				$explodeFiles = explode(',', $trimValue);
				$explodeFiles = array_unique($explodeFiles);
				foreach($explodeFiles as $explodeFile){
					$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
					$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
					if(isset($uploadedFileID)){
                       $updateUploadStatus = $iN->iN_UpdateUploadStatus($uploadedFileID);
					}
					if(empty($uploadedFileID)){
					   exit('204');
					}
				}
			}
			if (!empty($text)) {
				$slug = $iN->url_slugies(mb_substr($text, 0, 55, "utf-8"));
			} else {
				$slug = $iN->random_code(8);
			}
			if ($userWhoCanSeePost == '4') {
				$premiumPointAmount = mysqli_real_escape_string($db, $_POST['point']);
				if ($premiumPointAmount == '' || !isset($premiumPointAmount) || empty($premiumPointAmount)) {
					exit('201');
				}
				$number = preg_match("/^(?!\.)(?!.*\.$)(?!.*?\.\.)[0-9.]+$/", $premiumPointAmount, $m);

				$premiumPointAmount = isset($m[0]) ? $m[0] : NULL;
				if(!$premiumPointAmount){
                   exit('201');
				}
			} else { $premiumPointAmount = '';}
			$hashT = $iN->iN_hashtag($text);
			$postFromData = $iN->iN_InsertNewPost($userID, $iN->iN_Secure($text), $slug, $file, $userWhoCanSeePost, $iN->url_Hash($hashT), $iN->iN_Secure($premiumPointAmount), $autoApprovePostStatus);

			if ($postFromData) {
				$userPostID = $postFromData['post_id'];
				$userPostOwnerID = $postFromData['post_owner_id'];
				if($ataNewPostPointAmount && $ataNewPostPointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
					$iN->iN_InsertNewPoint($userID,$userPostID,$ataNewPostPointAmount);
				}
				$userPostText = isset($postFromData['post_text']) ? $postFromData['post_text'] : NULL;
                if($userPostText){
                   $iN->iN_InsertMentionedUsersForPost($userID, $userPostText, $userPostID, $userName,$userPostOwnerID);
				}
				$userPostFile = $postFromData['post_file'];
				$userPostCreatedTime = $postFromData['post_created_time'];
				$crTime = date('Y-m-d H:i:s', $userPostCreatedTime);
				$userPostWhoCanSee = $postFromData['who_can_see'];
				if($autoApprovePostStatus == 'yes' && $userPostWhoCanSee == '4'){
					$approveNot = $LANG['congratulations_approved'];
					$postApprover = $iN->iN_GetAdminUserID();
					$approveUpdate = $iN->iN_UpdateApprovePostStatusAuto($postApprover, $iN->iN_Secure($userPostID), $iN->iN_Secure($userPostOwnerID), $iN->iN_Secure($approveNot));
				}
				$planIcon  = NULL;
				$checkPostBoosted=  NULL;
				$userPostWantStatus = $postFromData['post_want_status'];
				$userPostWantedCredit = $postFromData['post_wanted_credit'];
				$userPostStatus = $postFromData['post_status'];
				$userPostOwnerUsername = $postFromData['i_username'];
				$userPostOwnerUserFullName = $postFromData['i_user_fullname'];
				$userPostOwnerUserGender = $postFromData['user_gender'];
				$userPostHashTags = isset($postFromData['hashtags']) ? $postFromData['hashtags'] : NULL;
				$getUserPaymentMethodStatus = isset($postFromData['payout_method']) ? $postFromData['payout_method'] : NULL;
				$userPostCommentAvailableStatus = $postFromData['comment_status'];
				$userPostOwnerUserLastLogin = $postFromData['last_login_time'];
                $userProfileCategory = isset($postFromData['profile_category']) ? $postFromData['profile_category'] : NULL;
				$lastSeen = date("c", $userPostOwnerUserLastLogin);
            	$OnlineStatus = date("c", time());
                $oStatus = time() - 35;
                if ($userPostOwnerUserLastLogin > $oStatus) {
                  $timeStatus = '<div class="userIsOnline flex_ tabing">'.$LANG['online'].'</div>';
                } else {
                  $timeStatus = '<div class="userIsOffline flex_ tabing">'.$LANG['offline'].'</div>';
                }
				$userPostPinStatus = $postFromData['post_pined'];
				$slugUrl = $base_url . 'post/' . $postFromData['url_slug'] . '_' . $userPostID;
				$userPostSharedID = isset($postFromData['shared_post_id']) ? $postFromData['shared_post_id'] : NULL;
				$userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
				$userPostUserVerifiedStatus = $postFromData['user_verified_status'];
				$userProfileFrame = isset($postFromData['user_frame']) ? $postFromData['user_frame'] : NULL;
				if ($userPostOwnerUserGender == 'male') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($userPostOwnerUserGender == 'female') {
					$publisherGender = '<div class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($userPostOwnerUserGender == 'couple') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$userVerifiedStatus = '';
				if ($userPostUserVerifiedStatus == '1') {
					$userVerifiedStatus = '<div class="i_plus_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$profileCategory = $pCt = $profileCategoryLink = '';
                if($userProfileCategory && $userPostUserVerifiedStatus == '1'){
                    $profileCategory = $userProfileCategory;
                    if(isset($PROFILE_CATEGORIES[$userProfileCategory])){
                        $pCt = isset($PROFILE_CATEGORIES[$userProfileCategory]) ? $PROFILE_CATEGORIES[$userProfileCategory] : NULL;
                    }else if(isset($PROFILE_SUBCATEGORIES[$userProfileCategory])){
                        $pCt = isset($PROFILE_SUBCATEGORIES[$userProfileCategory]) ? $PROFILE_SUBCATEGORIES[$userProfileCategory] : NULL;
                    }
                    $profileCategoryLink = '<a class="i_p_categoryp flex_ tabing_non_justify" href="'.$base_url.'creators?creator='.$userProfileCategory.'">'.$iN->iN_SelectedMenuIcon('65').$pCt.'</a>- ';
                }
				$onlySubs = '';
				$premiumPost = '';
                if($userPostWhoCanSee == '1'){
                   $onlySubs = '';
                   $premiumPost = '';
                   $subPostTop = '';
                   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('50').'</div>';
                }else if($userPostWhoCanSee == '2'){
                   $subPostTop = '';
                   $premiumPost = '';
                   $wCanSee = '<div class="i_plus_subs" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('15').'</div>';
                   $onlySubs = '<div class="com_min_height"></div><div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('15').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_followers']).'</div></div></div>';
                }else if($userPostWhoCanSee == '3'){
                   $subPostTop = 'extensionPost';
                   $premiumPost = '<div class="premiumIcon flex_ justify-content-align-items-center">'.$iN->iN_SelectedMenuIcon('40').$LANG['l_premium'].'</div>';
                   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('51').'</div>';
                   $onlySubs = '<div class="com_min_height"></div><div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('56').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_subscribers']).'</div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
                }else if($userPostWhoCanSee == '4'){
                  $subPostTop = 'extensionPost';
                  $premiumPost = '<div class="premiumIcon flex_ justify-content-align-items-center">'.$iN->iN_SelectedMenuIcon('40').$LANG['l_premium'].'</div>';
                  $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
                  $onlySubs = '<div class="com_min_height"></div><div class="onlyPremium onlyPremium"><div class="onlySubsWrapper"><div class="premium_locked"><div class="premium_locked_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div><div class="onlySubs_note"><div class="buyThisPost prcsPost" id="'.$userPostID.'">'.preg_replace( '/{.*?}/', $userPostWantedCredit, $LANG['post_credit']).'</div><div class="buythistext prcsPost" id="'.$userPostID.'">'.$LANG['purchase_post'].'</div></div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
                }
				$postStyle = '';
				if (empty($userPostText)) {
					$postStyle = 'nonePoint';
				}
				/*Comment*/
				$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
				$c = '';
				$TotallyPostComment = '';
				if ($c) {
					if ($getUserComments > 0) {
						$CountTheUniqComment = count($CountUniqPostCommentArray);
						$SecondUniqComment = $CountTheUniqComment - 5;
						if ($CountTheUniqComment > 5) {
							$getUserComments = $iN->iN_GetPostComments($userPostID, 5);
						}
					}
				}
				if ($logedIn == 0) {
					$getFriendStatusBetweenTwoUser = '1';
					$checkPostLikedBefore = '';
					$checkUserPurchasedThisPost = '0';
				} else {
					$getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $userPostOwnerID);
					$checkPostLikedBefore = $iN->iN_CheckPostLikedBefore($userID, $userPostID);
					$checkUserPurchasedThisPost = $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID);
				}
				if ($checkPostLikedBefore) {
					$likeIcon = $iN->iN_SelectedMenuIcon('18');
					$likeClass = 'in_unlike';
				} else {
					$likeIcon = $iN->iN_SelectedMenuIcon('17');
					$likeClass = 'in_like';
				}
				if ($userPostCommentAvailableStatus == '1') {
					$commentStatusText = $LANG['disable_comment'];
				} else {
					$commentStatusText = $LANG['enable_comments'];
				}
				$pPinStatus = '';
				$pPinStatusBtn = $iN->iN_SelectedMenuIcon('29') . $LANG['pin_on_my_profile'];
				if ($userPostPinStatus == '1') {
					$pPinStatus = '<div class="i_pined_post" id="i_pined_post_' . $userPostID . '">' . $iN->iN_SelectedMenuIcon('62') . '</div>';
					$pPinStatusBtn = $iN->iN_SelectedMenuIcon('29') . $LANG['post_pined_on_your_profile'];
				}
				$pSaveStatusBtn = $iN->iN_SelectedMenuIcon('22');
				if ($iN->iN_CheckPostSavedBefore($userID, $userPostID) == '1') {
					$pSaveStatusBtn = $iN->iN_SelectedMenuIcon('63');
				}
				$likeSum = $iN->iN_TotalPostLiked($userPostID);
				if ($likeSum > '0') {
					$likeSum = $likeSum;
				} else {
					$likeSum = '';
				}
				$waitingApprove = '';
				if ($userPostStatus == '2') {
					$waitingApprove = '<div class="waiting_approve flex_">' . $iN->iN_SelectedMenuIcon('10') . $LANG['waiting_for_approve'] . '</div>';
					if ($logedIn == 0) {
						echo '<div class="i_post_body nonePoint body_' . $userPostID . '" id="' . $userPostID . '" data-last="' . $userPostID . '" ></div>';
					} else {
						if ($userID == $userPostOwnerID) {
							if (empty($userPostFile)) {
								include "../themes/$currentTheme/layouts/posts/textPost.php";
							} else {
								include "../themes/$currentTheme/layouts/posts/ImagePost.php";
							}
						} else {
							echo '<div class="i_post_body nonePoint body_' . $userPostID . '" id="' . $userPostID . '" data-last="' . $userPostID . '"></div>';
						}
					}
				} else {
					if (empty($userPostFile)) {
						include "../themes/$currentTheme/layouts/posts/textPost.php";
					} else {
						include "../themes/$currentTheme/layouts/posts/ImagePost.php";
					}
				}
			}
		} else {
			echo '15';
		}
	}
	if ($type == 'p_like') {
		if (isset($_POST['post'])) {
			$postID = mysqli_real_escape_string($db, $_POST['post']);
			$likePost = $iN->iN_LikePost($userID, $postID);
			$status = 'in_like';
			$pLike = $iN->iN_SelectedMenuIcon('17');
			if ($likePost) {
				$status = 'in_unlike';
				$pLike = $iN->iN_SelectedMenuIcon('18');
				if($iN->iN_CheckPostOwner($userID, $postID) === false && $ataNewPostLikePointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
					$iN->iN_InsertNewPostLikePoint($userID,$postID,$ataNewPostLikePointAmount);
				}
			}
			if($status == 'in_like'){
				if($iN->iN_CheckPostOwner($userID, $postID) === false && $ataNewPostLikePointSatus == 'yes'){
					$iN->iN_RemovePointPostLikeIfExist($userID,$postID,$ataNewPostLikePointAmount);
				}
			}
			$likeSum = $iN->iN_TotalPostLiked($postID);
			if ($likeSum == 0) {
				$likeSum = '';
			} else {
				$likeSum = $likeSum;
			}
			$data = array(
				'status' => $status,
				'like' => $pLike,
				'likeCount' => $likeSum,
			);
			$iN->iN_insertPostLikeNotification($userID, $postID);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			$GetPostOwnerIDFromPostDetails = $iN->iN_GetAllPostDetails($postID);
			$likedPostOwnerID = $GetPostOwnerIDFromPostDetails['post_owner_id'];
			$uData = $iN->iN_GetUserDetails($likedPostOwnerID);
			$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
			$lUsername = $uData['i_username'];
			$lUserFullName = $uData['i_user_fullname'];
			$emailNotificationStatus = $uData['email_notification_status'];
			$notQualifyDocument = $LANG['not_qualify_document'];
			$slugUrl = $base_url . 'post/' . $GetPostOwnerIDFromPostDetails['url_slug'] . '_' . $postID;
			if ($emailSendStatus == '1' && $userID != $likedPostOwnerID && $emailNotificationStatus == '1' && $status == 'in_unlike') {
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
				$instagramIcon = $iN->iN_SelectedMenuIcon('88');
				$facebookIcon = $iN->iN_SelectedMenuIcon('90');
				$twitterIcon = $iN->iN_SelectedMenuIcon('34');
				$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
				$someoneLikedYourPost = $iN->iN_Secure($LANG['someone_liked_yourpost']);
				$clickGoPost = $iN->iN_Secure($LANG['click_go_post']);
				$likedYourPost = $iN->iN_Secure($LANG['liked_your_post']);
				include_once '../includes/mailTemplates/postLikeEmailTemplate.php';
				$body = $bodyPostLikeEmail;
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $iN->iN_Secure($LANG['someone_liked_yourpost']);
				$mail->CharSet = 'utf-8';
				$mail->Body    = $body;
				if ($mail->send()) {
					$mail->ClearAddresses();
					return true;
				}
			}
		}
	}
	if ($type == 'p_share') {
		if (isset($_POST['sp'])) {
			$postID = mysqli_real_escape_string($db, $_POST['sp']);
			$checkPostIDExist = $iN->iN_CheckPostIDExist($postID);
			if ($checkPostIDExist == '1') {
				$postFromData = $iN->iN_GetAllPostDetails($postID);
				$userPostID = $postFromData['post_id'];
				$userPostOwnerID = $postFromData['post_owner_id'];
				$userPostText = isset($postFromData['post_text']) ? $postFromData['post_text'] : NULL;
				$userPostFile = $postFromData['post_file'];
				$userPostCreatedTime = $postFromData['post_created_time'];
				$crTime = date('Y-m-d H:i:s', $userPostCreatedTime);
				$userPostWhoCanSee = $postFromData['who_can_see'];
				$userPostWantStatus = $postFromData['post_want_status'];
				$userPostWantedCredit = $postFromData['post_wanted_credit'];
				$userPostStatus = $postFromData['post_status'];
				$userPostOwnerUsername = $postFromData['i_username'];
				$userPostOwnerUserFullName = $postFromData['i_user_fullname'];
				if($fullnameorusername == 'no'){
					$userPostOwnerUserFullName = $userPostOwnerUsername;
				}
				$userPostOwnerUserGender = $postFromData['user_gender'];
				$userPostCommentAvailableStatus = $postFromData['comment_status'];
				$userPostOwnerUserLastLogin = $postFromData['last_login_time'];
				$userPostHashTags = isset($postFromData['hashtags']) ? $postFromData['hashtags'] : NULL;
				$userPostSharedID = isset($postFromData['shared_post_id']) ? $postFromData['shared_post_id'] : NULL;
				$userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
				$userPostUserVerifiedStatus = $postFromData['user_verified_status'];
				if ($userPostOwnerUserGender == 'male') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($userPostOwnerUserGender == 'female') {
					$publisherGender = '<div class="i_plus_gf">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($userPostOwnerUserGender == 'couple') {
					$publisherGender = '<div class="i_plus_g">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$userVerifiedStatus = '';
				if ($userPostUserVerifiedStatus == '1') {
					$userVerifiedStatus = '<div class="i_plus_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$onlySubs = '';
				if($userPostWhoCanSee == '1'){
					$onlySubs = '';
					$subPostTop = '';
					$wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('50').'</div>';
				 }else if($userPostWhoCanSee == '2'){
					$subPostTop = '';
					$wCanSee = '<div class="i_plus_subs" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('15').'</div>';
					$onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('15').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_followers']).'</div></div></div>';
				 }else if($userPostWhoCanSee == '3'){
					$subPostTop = 'extensionPost';
					$wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('51').'</div>';
					$onlySubs = '<div class="onlySubs"><div class="onlySubsWrapper"><div class="onlySubs_icon">'.$iN->iN_SelectedMenuIcon('56').'</div><div class="onlySubs_note">'.preg_replace( '/{.*?}/', $userPostOwnerUserFullName, $LANG['only_subscribers']).'</div></div></div>';
				 }else if($userPostWhoCanSee == '4'){
				   $subPostTop = 'extensionPost';
				   $wCanSee = '<div class="i_plus_public" id="ipublic_'.$userPostID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
				   $onlySubs = '<div class="onlyPremium"><div class="onlySubsWrapper"><div class="premium_locked"><div class="premium_locked_icon">'.$iN->iN_SelectedMenuIcon('56').'</div></div><div class="onlySubs_note"><div class="buyThisPost prcsPost" id="'.$userPostID.'">'.preg_replace( '/{.*?}/', $userPostWantedCredit, $LANG['post_credit']).'</div><div class="buythistext prcsPost" id="'.$userPostID.'">'.$LANG['purchase_post'].'</div></div><div class="fr_subs uSubsModal transition" data-u="'.$userPostOwnerID.'">'.$iN->iN_SelectedMenuIcon('51').$LANG['free_for_subscribers'].'</div></div></div>';
				 }
				$likeSum = $iN->iN_TotalPostLiked($userPostID);
				if ($likeSum > '0') {
					$likeSum = $likeSum;
				} else {
					$likeSum = '1';
				}
				$checkUserPurchasedThisPost = $iN->iN_CheckUserPurchasedThisPost($userID, $userPostID);
				/*Comment*/
				$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
				$c = '';
				$TotallyPostComment = '';
				if ($c) {
					if ($getUserComments > 0) {
						$CountTheUniqComment = count($CountUniqPostCommentArray);
						$SecondUniqComment = $CountTheUniqComment - 5;
						if ($CountTheUniqComment > 5) {
							$getUserComments = $iN->iN_GetPostComments($userPostID, 5);
						}
					}
				}
				if ($logedIn == 0) {
					$getFriendStatusBetweenTwoUser = '1';
					$checkPostLikedBefore = '';
				} else {
					$getFriendStatusBetweenTwoUser = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $userPostOwnerID);
					$checkPostLikedBefore = $iN->iN_CheckPostLikedBefore($userID, $userPostID);
				}
				include "../themes/$currentTheme/layouts/posts/sharePost.php";
			} else {
				echo '404';
			}
		}
	}
	/*Insert Re-Share Post*/
	if ($type == 'p_rshare') {
		if (isset($_POST['sp']) && isset($_POST['pt'])) {
			$reSharePostID = mysqli_real_escape_string($db, $_POST['sp']);
			$reSharePostNewText = mysqli_real_escape_string($db, $_POST['pt']);
			$insertReShare = $iN->iN_ReShare_Post($userID, $reSharePostID, $iN->iN_Secure($reSharePostNewText));
			if ($insertReShare) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	/*Show PopUps*/
	if ($type == 'ialert') {
		if (isset($_POST['al'])) {
			$alertType = mysqli_real_escape_string($db, $_POST['al']);
			include "../themes/$currentTheme/layouts/popup_alerts/popup_alerts.php";
		}
	}
	/*Show Who Can See Settings In PopUp*/
	if ($type == 'wcs') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$whoSee = $iN->iN_GetAllPostDetails($postID);
			if ($whoSee) {
				$whoCSee = $whoSee['who_can_see'];
				include "../themes/$currentTheme/layouts/posts/whoCanSee.php";
			}
		}
	}
	/*Show Who Can See Settings In PopUp*/
	if ($type == 'whcStory') {
		$checkUserIDExist = $iN->iN_CheckUserExist($userID);
		if ($checkUserIDExist) {
		    include "../themes/$currentTheme/layouts/popup_alerts/chooseWhichStory.php";
		}
	}
	/*Update Post Who Can See Status*/
	if ($type == 'uwcs') {
		if (isset($_POST['wci']) && in_array($_POST['wci'], $whoCanSeeArrays) && isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$WhoCS = mysqli_real_escape_string($db, $_POST['wci']);
			$updatePostWhoCanSeeStatus = $iN->iN_UpdatePostWhoCanSee($userID, $postID, $WhoCS);
			if ($updatePostWhoCanSeeStatus) {
				if ($WhoCS == 1) {
					$UpdatedWhoCanSee = $iN->iN_SelectedMenuIcon('50');
				} else if ($WhoCS == 2) {
					$UpdatedWhoCanSee = $iN->iN_SelectedMenuIcon('15');
				} else if ($WhoCS == 3) {
					$UpdatedWhoCanSee = $iN->iN_SelectedMenuIcon('51');
				}
				echo html_entity_decode($UpdatedWhoCanSee);
			} else {
				echo '404';
			}
		}
	}
	/*Show Edit Post In PopUp*/
	if ($type == 'c_editPost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$getPData = $iN->iN_GetAllPostDetails($postID);
			if ($getPData) {
				$posText = isset($getPData['post_text']) ? $getPData['post_text'] : NULL;
				include "../themes/$currentTheme/layouts/posts/editPost.php";
			} else {
				echo '404';
			}
		}
	}
	/*Save Edited Post*/
	if ($type == 'editS') {
		if (isset($_POST['id']) && isset($_POST['text'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$editedText = mysqli_real_escape_string($db, $_POST['text']);
			$editedTextTwo = mysqli_real_escape_string($db, $_POST['text']);
			if (empty($editedText)) {
				$status = 'no';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			$editSlug = $iN->url_slugies($editedText);
			$hashT = $iN->iN_hashtag($editedText);
			$saveEditedPost = $iN->iN_UpdatePost($userID, $postID, $editedTextTwo, $iN->url_Hash($editedText), $editSlug);
			if ($saveEditedPost) {
				$getNewPostFromData = $iN->iN_GetAllPostDetails($postID);
				$status = '200';
				$data = array(
					'status' => $status,
					'text' => $iN->sanitize_output($getNewPostFromData['post_text'], $base_url),
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			} else {
				$status = '404';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
		}
	}
	/*Delete Post Call AlertBox*/
	if ($type == 'ddelPost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteAlert.php";
		}
	}
	/*Delete Post Call AlertBox*/
	if ($type == 'finishLiveStreaming') {
		include "../themes/$currentTheme/layouts/popup_alerts/closeLiveStreaming.php";
	}
	/*Delete Conversation Call AlertBox*/
	if ($type == 'ddelConv') {
		if (isset($_POST['id'])) {
			$conversationID = mysqli_real_escape_string($db, $_POST['id']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteConversationAlert.php";
		}
	}
	/*Delete Message Call AlertBox*/
	if ($type == 'ddelMesage') {
		if (isset($_POST['id'])) {
			$messageID = mysqli_real_escape_string($db, $_POST['id']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteMessageAlert.php";
		}
	}
	/*Delete Story From Database*/
	if($type == 'deleteStorie'){
       if(isset($_POST['id'])){
          $storieID = mysqli_real_escape_string($db, $_POST['id']);
		  $checkStorieIDExist = $iN->iN_CheckStorieIDExist($userID, $storieID);
		  if($checkStorieIDExist){
              $sData = $iN->iN_GetUploadedStoriesData($userID, $storieID);
			  $uploadedFileID = $sData['s_id'];
			  $uploadedFilePath = $sData['uploaded_file_path'];
			  $uploadedTumbnailFilePath = $sData['upload_tumbnail_file_path'];
			  $uploadedFilePathX = $sData['uploaded_x_file_path'];
			  $uploadedStoryType = $sData['story_type'];
			  if($uploadedStoryType != 'textStory'){
				if($uploadedFileID && $digitalOceanStatus == '1'){
					$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
					$my_space->DeleteObject($uploadedFilePath);

					$space_two = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
					$space_two->DeleteObject($uploadedFilePathX);

					$space_tree = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
					$space_tree->DeleteObject($uploadedTumbnailFilePath);
					$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
					if($query){
						echo '200';
					}else{
						echo '404';
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
					$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
					if($query){
						echo '200';
					}else{
						echo '404';
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
					$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
					if($query){
						echo '200';
					}else{
						echo '404';
					}
				  }else{
					@unlink('../' . $uploadedFilePath);
					@unlink('../' . $uploadedFilePathX);
					@unlink('../' . $uploadedTumbnailFilePath);
					$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
					if($query){
						echo '200';
					}else{
						echo '404';
					}
				  }
			  }else{
				$query = mysqli_query($db, "DELETE FROM i_user_stories WHERE s_id = '$uploadedFileID' AND uid_fk = '$userID'");
				if($query){
					echo '200';
				}else{
					echo '404';
				}
			  }

		  }
	   }
	}
	/*Delete Post From Database*/
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
				$deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorage($userID, $postID);
				if($deleteStoragePost){
					if($ataNewPostPointSatus == 'yes'){$iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);}
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
						mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
					}
				}
				$deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorage($userID, $postID);
				if($deleteStoragePost){
				    if($ataNewPostPointSatus == 'yes'){
				        $iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);
				    }
				    if ($iN->iN_CheckIsAdmin($userID) == 1) {
        				mysqli_query($db, "DELETE FROM i_posts WHERE post_id = '$postID'");
        				echo '200';
        			} else {
        				mysqli_query($db, "DELETE FROM i_posts WHERE post_id = '$postID' AND post_owner_id = '$userID'");
        				echo '200';
        			}
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
						mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
					}
				}
				$deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
				$deleteStoragePost = $iN->iN_DeletePostFromDataifStorage($userID, $postID);
				if($deleteStoragePost){
				    if($ataNewPostPointSatus == 'yes'){$iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);}
				    echo '200';
				}else{
					echo '404';
				}
			}else if(!empty($postID)){
				$deletePostFromData = $iN->iN_DeletePost($userID, $postID);
				$deleteActivity = $iN->iN_DeletePostActivity($userID, $postID);
				if ($deletePostFromData) {
				    if($ataNewPostPointSatus == 'yes'){$iN->iN_RemovePointIfExist($userID, $postID, $ataNewPostPointAmount);}
					echo '200';
				} else {
					echo '404';
				}
			}
		}
	}
	/*Share My Storie*/
	if($type == 'shareMyStorie'){
      if(isset($_POST['id'])){
         $storieID = mysqli_real_escape_string($db, $_POST['id']);
		 $storieText = mysqli_real_escape_string($db, $_POST['txt']);
		 if($iN->iN_CheckStorieIDExist($userID, $storieID) == 1){
			$insertStorie = $iN->iN_InsertMyStorie($userID,$storieID, $iN->iN_Secure($storieText));
			if($insertStorie){
               echo '200';
			}else{
			   echo '404';
			}
		 }
	  }
	}
	/*Show More Posts*/
	if ($type == 'moreposts') {
		if (isset($_POST['last'])) {
			$page = $type;
			$files = array(
			1 => 'suggestedusers',
			2 => 'ads');
			shuffle($files);

			for ($i = 0; $i < 1; $i++) {
				include "../themes/$currentTheme/layouts/random_boxs/$files[$i].php";
			}
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Show More Saved Posts*/
	if ($type == 'savedpost') {
		if (isset($_POST['last'])) {
			$page = $type;
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Show More Profile Posts*/
	if ($type == 'profile') {
		if (isset($_POST['last']) && isset($_POST['p'])) {
			$p_profileID = mysqli_real_escape_string($db, $_POST['p']);
			$pCat = mysqli_real_escape_string($db, $_POST['pcat']);
			$page = $type;
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Show More Profile Posts*/
	if ($type == 'hashtag') {
		if (isset($_POST['last']) && isset($_POST['p'])) {
			$pageFor = mysqli_real_escape_string($db, $_POST['p']);
			$page = $type;
			include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
		}
	}
	/*Update Post Comment Status*/
	if ($type == 'updateComentStatus') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$updatePostCommentStatus = $iN->iN_UpdatePostCommentStatus($userID, $postID);
			if ($updatePostCommentStatus == '1') {
				$status = '200';
				$text = $iN->iN_SelectedMenuIcon('31') . $LANG['disable_comment'];
			} else {
				$status = '404';
				$text = $iN->iN_SelectedMenuIcon('31') . $LANG['enable_comments'];
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Update Post Comment Status*/
	if ($type == 'pinpost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$updatePostPinedStatus = $iN->iN_UpdatePostPinedStatus($userID, $postID);
			if ($updatePostPinedStatus == '1') {
				$status = '200';
				$text = '<div class="i_pined_post" id="i_pined_post_' . $postID . '">' . $iN->iN_SelectedMenuIcon('62') . '</div>';
				$btnText = $iN->iN_SelectedMenuIcon('29') . $LANG['post_pined_on_your_profile'];
			} else {
				$status = '404';
				$text = '';
				$btnText = $iN->iN_SelectedMenuIcon('29') . $LANG['pin_on_my_profile'];
			}
			$data = array(
				'status' => $status,
				'text' => $text,
				'btn' => $btnText,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Report Post*/
	if ($type == 'reportPost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$insertPostReport = $iN->iN_InsertReportedPost($userID, $postID);
			if ($insertPostReport) {
				if ($insertPostReport == 'rep') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['report_this_post'];
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Save Post From Saved List*/
	if ($type == 'savePost') {
		if (isset($_POST['id'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$insertPostSave = $iN->iN_SavePostInSavedList($userID, $postID);
			if ($insertPostSave) {
				if ($insertPostSave == 'svp') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('63');
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('22');
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Insert a New Comment*/
	if ($type == 'comment') {
		if (isset($_POST['id']) && isset($_POST['val'])) {
			$postID = mysqli_real_escape_string($db, $_POST['id']);
			$value = mysqli_real_escape_string($db, $_POST['val']);
			$sticker = mysqli_real_escape_string($db, $_POST['sticker']);
			$Gif = mysqli_real_escape_string($db, $_POST['gf']);
			if (empty($value) && empty($sticker) && empty($Gif)) {
				$status = '404';
			} else {
				$insertNewComment = $iN->iN_insertNewComment($userID, $postID, $iN->iN_Secure($value), $iN->iN_Secure($sticker), $iN->iN_Secure($Gif));
				if ($insertNewComment) {
					$commentID = $insertNewComment['com_id'];
					$commentedUserID = $insertNewComment['comment_uid_fk'];
					$Usercomment = $insertNewComment['comment'];
					$commentTime = isset($insertNewComment['comment_time']) ? $insertNewComment['comment_time'] : NULL;
					$corTime = date('Y-m-d H:i:s', $commentTime);
					$commentFile = isset($insertNewComment['comment_file']) ? $insertNewComment['comment_file'] : NULL;
					$stickerUrl = isset($insertNewComment['sticker_url']) ? $insertNewComment['sticker_url'] : NULL;
					$gifUrl = isset($insertNewComment['gif_url']) ? $insertNewComment['gif_url'] : NULL;
					$commentedUserIDFk = isset($insertNewComment['iuid']) ? $insertNewComment['iuid'] : NULL;
					$commentedUserName = isset($insertNewComment['i_username']) ? $insertNewComment['i_username'] : NULL;
					$userPostID = $insertNewComment['comment_post_id_fk'];
					if($iN->iN_CheckPostOwner($userID, $postID) === false && $ataNewCommentPointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
						$iN->iN_InsertNewCommentPoint($userID,$userPostID,$ataNewCommentPointAmount);
					}
					$checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
					$cUType = '';
					if($checkUserIsCreator){
                       $cUType = '<div class="i_plus_public" id="ipublic_'.$commentedUserID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
					}
					$commentedUserFullName = isset($insertNewComment['i_user_fullname']) ? $insertNewComment['i_user_fullname'] : NULL;
					if($fullnameorusername == 'no'){
						$commentedUserFullName = $commentedUserName;
					}
					$commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
					$commentedUserGender = isset($insertNewComment['user_gender']) ? $insertNewComment['user_gender'] : NULL;
					if ($commentedUserGender == 'male') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'female') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'couple') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					}
					$commentedUserLastLogin = isset($insertNewComment['last_login_time']) ? $insertNewComment['last_login_time'] : NULL;
					$commentedUserVerifyStatus = isset($insertNewComment['user_verified_status']) ? $insertNewComment['user_verified_status'] : NULL;
					$cuserVerifiedStatus = '';
					if ($commentedUserVerifyStatus == '1') {
						$cuserVerifiedStatus = '<div class="i_plus_comment_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
					}
					$checkCommentLikedBefore = $iN->iN_CheckCommentLikedBefore($userID, $userPostID, $commentID);
					$commentLikeBtnClass = 'c_in_like';
					$commentLikeIcon = $iN->iN_SelectedMenuIcon('17');
					$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
					if ($checkCommentLikedBefore == '1') {
						$commentLikeBtnClass = 'c_in_unlike';
						$commentLikeIcon = $iN->iN_SelectedMenuIcon('18');
						if ($checkCommentReportedBefore == '1') {
							$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
						}
					}
					$stickerComment = '';
					$gifComment = '';
					if ($stickerUrl) {
						$stickerComment = '<div class="comment_file"><img src="' . $stickerUrl . '"></div>';
					}
					if ($gifUrl) {
						$gifComment = '<div class="comment_gif_file"><img src="' . $gifUrl . '"></div>';
					}
					include "../themes/$currentTheme/layouts/posts/comments.php";
					$GetPostOwnerIDFromPostDetails = $iN->iN_GetAllPostDetails($userPostID);
					$commentedPostOwnerID = $GetPostOwnerIDFromPostDetails['post_owner_id'];
					if ($userID != $commentedPostOwnerID) {
						$iN->iN_InsertNotificationForCommented($commentedUserID, $userPostID);
					}
					if($Usercomment){
						$iN->iN_InsertMentionedUsersForComment($userID, $Usercomment, $userPostID, $commentedUserName,$commentedPostOwnerID);
					 }
					$uData = $iN->iN_GetUserDetails($commentedPostOwnerID);
					$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
					$emailNotificationStatus = $uData['email_notification_status'];
					$notQualifyDocument = $LANG['not_qualify_document'];
					if ($emailSendStatus == '1' && $userID != $commentedPostOwnerID && $emailNotificationStatus == '1') {
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
						$instagramIcon = $iN->iN_SelectedMenuIcon('88');
						$facebookIcon = $iN->iN_SelectedMenuIcon('90');
						$twitterIcon = $iN->iN_SelectedMenuIcon('34');
						$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
						$commentedBelow = $iN->iN_Secure($LANG['commented_below']);
						$commentE = $iN->iN_Secure($Usercomment);
						include_once '../includes/mailTemplates/commentEmailTemplate.php';
						$body = $bodyCommentEmail;
						$mail->setFrom($smtpUserName, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['commented_on_your_post']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if ($mail->send()) {
							$mail->ClearAddresses();
							return true;
						}
					}

				} else {
					echo '404';
				}
			}
		}
	}

	/*Comment Like*/
	if ($type == 'pc_like') {
		if (isset($_POST['post']) && isset($_POST['com'])) {
			$postID = mysqli_real_escape_string($db, $_POST['post']);
			$postCommentID = mysqli_real_escape_string($db, $_POST['com']);
			$likePostComment = $iN->iN_LikePostComment($userID, $postID, $postCommentID);
			$status = 'c_in_like';
			$pcLike = $iN->iN_SelectedMenuIcon('17');
			if ($likePostComment) {
				$status = 'c_in_unlike';
				$pcLike = $iN->iN_SelectedMenuIcon('18');
				$commentLikedSum = $iN->iN_TotalCommentLiked($postCommentID);
				if($iN->iN_CheckCommentOwner($userID, $postID) === false && $ataNewPostCommentLikePointSatus == 'yes' && str_replace(".", "",$iN->iN_TotalEarningPointsInaDay($userID)) < str_replace(".", "",$maximumPointInADay)){
					$iN->iN_InsertNewPostCommentLikePoint($userID,$postID,$ataNewPostCommentLikePointAmount);
				}
			}
			if($status == 'c_in_like'){
				if($iN->iN_CheckCommentOwner($userID, $postID) === false && $ataNewPostCommentLikePointSatus == 'yes'){
					$iN->iN_RemovePointPostCommentLikeIfExist($userID,$postID,$ataNewPostCommentLikePointAmount);
				}
			}
			$data = array(
				'status' => $status,
				'like' => $pcLike,
				'totalLike' => isset($commentLikedSum) ? $commentLikedSum : '0',
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			$cLData = $iN->iN_GetUserIDFromLikedPostID($postCommentID);
			$commendOwnerID = $cLData['comment_uid_fk'];
			if ($userID != $commendOwnerID) {
				$iN->iN_insertCommentLikeNotification($userID, $postID, $postCommentID);
			}
			$GetPostOwnerIDFromPostDetails = $iN->iN_GetAllPostDetails($postID);
			$uData = $iN->iN_GetUserDetails($commendOwnerID);
			$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
			$lUsername = $uData['i_username'];
			$lUserFullName = $uData['i_user_fullname'];
			$emailNotificationStatus = $uData['email_notification_status'];
			$notQualifyDocument = $LANG['not_qualify_document'];
			$slugUrl = $base_url . 'post/' . $GetPostOwnerIDFromPostDetails['url_slug'] . '_' . $postID;
			if ($emailSendStatus == '1' && $userID != $commendOwnerID && $emailNotificationStatus == '1' && $status == 'c_in_unlike') {
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
				$instagramIcon = $iN->iN_SelectedMenuIcon('88');
				$facebookIcon = $iN->iN_SelectedMenuIcon('90');
				$twitterIcon = $iN->iN_SelectedMenuIcon('34');
				$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
				$someoneLikedYourPost = $iN->iN_Secure($LANG['someone_liked_your_comment']);
				$clickGoPost = $iN->iN_Secure($LANG['click_go_comment']);
				$likedYourPost = $iN->iN_Secure($LANG['liked_your_comment']);
				include_once '../includes/mailTemplates/postLikeEmailTemplate.php';
				$body = $bodyPostLikeEmail;
				$mail->setFrom($smtpEmail, $siteName);
				$send = false;
				$mail->IsHTML(true);
				$mail->addAddress($sendEmail, ''); // Add a recipient
				$mail->Subject = $iN->iN_Secure($LANG['someone_liked_your_comment']);
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if ($mail->send()) {
					$mail->ClearAddresses();
					return true;
				}
			}
		}
	}
	/*Delete Comment Call AlertBox*/
	if ($type == 'ddelComment') {
		if (isset($_POST['id']) && isset($_POST['pid'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['id']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$alertType = $type;
			include "../themes/$currentTheme/layouts/popup_alerts/deleteCommentAlert.php";
		}
	}
	/*Delete Comment*/
	if ($type == 'deletecomment') {
		if (isset($_POST['cid']) && isset($_POST['pid'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['cid']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$deleteComment = $iN->iN_DeleteComment($userID, $commentID, $postID);
			if ($deleteComment) {
				if($ataNewCommentPointSatus == 'yes'){$iN->iN_RemovePointCommentIfExist($userID, $postID, $ataNewCommentPointAmount);}
				echo '200';
			} else {
				echo '404';
			}
		}
	}
	/*Report Comment*/
	if ($type == 'reportComment') {
		if (isset($_POST['id']) && isset($_POST['pid'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['id']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$insertCommentReport = $iN->iN_InsertReportedComment($userID, $commentID, $postID);
			if ($insertCommentReport) {
				if ($insertCommentReport == 'rep') {
					$status = '200';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
				} else {
					$status = '404';
					$text = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
				}
			} else {
				$status = '';
				$text = '';
			}
			$data = array(
				'status' => $status,
				'text' => $text,
			);
			$result = json_encode($data, JSON_UNESCAPED_UNICODE);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	/*Show Edit Comment In PopUp*/
	if ($type == 'c_editComment') {
		if (isset($_POST['cid']) && isset($_POST['pid'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['cid']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$getCData = $iN->iN_GetCommentFromID($userID, $commentID, $postID);
			if ($getCData) {
				$commentText = isset($getCData['comment']) ? $getCData['comment'] : NULL;
				include "../themes/$currentTheme/layouts/posts/editComment.php";
			} else {
				echo '404';
			}
		}
	}
	/*Save Edited Comment*/
	if ($type == 'editSC') {
		if (isset($_POST['cid']) && isset($_POST['pid']) && isset($_POST['text'])) {
			$commentID = mysqli_real_escape_string($db, $_POST['cid']);
			$postID = mysqli_real_escape_string($db, $_POST['pid']);
			$editedText = mysqli_real_escape_string($db, $_POST['text']);
			if (empty($editedText)) {
				$status = 'no';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			$saveEditedComment = $iN->iN_UpdateComment($userID, $postID, $commentID, $iN->iN_Secure($editedText));
			if ($saveEditedComment) {
				$getNewPostFromData = $iN->iN_GetCommentFromID($userID, $commentID, $postID);
				$status = '200';
				$data = array(
					'status' => $status,
					'text' => $iN->sanitize_output($getNewPostFromData['comment'], $base_url),
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			} else {
				$status = '404';
				$data = array(
					'status' => $status,
					'text' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
		}
	}
	/*Get Emojis*/
	if ($type == 'emoji') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			$ec = mysqli_real_escape_string($db, $_POST['ec']);
			$importID = '';
			if (!empty($ec)) {
				$importID = 'data-id="' . $ec . '"';
			}
			if ($id == 'emojiBox') {
				$importClass = 'emoji_item';
			} else if ($id == 'emojiBoxC') {
				$importClass = 'emoji_item_c';
			}
			include "../themes/$currentTheme/layouts/widgets/emojis.php";
		}
	}
	/*Get Stickers*/
	if ($type == 'stickers') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/widgets/stickers.php";
		}
	}
	/*Get Gifs*/
	if ($type == 'gifList') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/widgets/gifs.php";
		}
	}
	/*Add Sticker*/
	if ($type == 'addSticker') {
		if (isset($_POST['id'])) {
			$stickerID = mysqli_real_escape_string($db, $_POST['id']);
			$ID = mysqli_real_escape_string($db, $_POST['pi']);
			$getStickerUrlandID = $iN->iN_getSticker($stickerID);
			if ($getStickerUrlandID) {
				$data = array(
					'stickerUrl' => '<div class="in_sticker_wrapper" id="stick_id_' . $getStickerUrlandID['sticker_id'] . '"><img src="' . $getStickerUrlandID['sticker_url'] . '"></div><div class="removeSticker" id="' . $ID . '">' . $iN->iN_SelectedMenuIcon('5') . '</div>',
					'st_id' => $getStickerUrlandID['sticker_id'],
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			}
		}
	}
	/*Get Free Follow PopUP*/
	if ($type == 'follow_free_not') {
		if (isset($_POST['id'])) {
			$uID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				$userDetail = $iN->iN_GetUserDetails($uID);
				$f_userID = $userDetail['iuid'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;
				include "../themes/$currentTheme/layouts/popup_alerts/free_follow_popup.php";
			}
		}
	}
	/*Follow Profile Free*/
	if ($type == 'freeFollow') {
		if (isset($_POST['follow'])) {
			$uID = mysqli_real_escape_string($db, $_POST['follow']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				$checkUserFollowing = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
				if ($checkUserFollowing != 'me') {
					$insertNewFollowingList = $iN->iN_insertNewFollow($userID, $uID);
					if ($insertNewFollowingList == 'flw') {
						$status = '200';
						$not = $insertNewFollowingList;
						$btn = $iN->iN_SelectedMenuIcon('66') . $LANG['unfollow'];
						$iN->iN_InsertNotificationForFollow($userID, $uID);
					} else if ($insertNewFollowingList == 'unflw') {
						$status = '200';
						$not = $insertNewFollowingList;
						$btn = $iN->iN_SelectedMenuIcon('66') . $LANG['follow'];
						$iN->iN_RemoveNotificationForFollow($userID, $uID);
					} else {
						$status = '404';
						$not = '';
						$btn = '';
					}
					$data = array(
						'status' => $status,
						'text' => $not,
						'btn' => $btn,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					$uData = $iN->iN_GetUserDetails($uID);
					$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
					$lUsername = $uData['i_username'];
					$fuserAvatar = $iN->iN_UserAvatar($uID, $base_url);
					$lUserFullName = $userFullName;
					$emailNotificationStatus = $uData['email_notification_status'];
					$notQualifyDocument = $LANG['not_qualify_document'];
					$slugUrl = $base_url . $lUsername;
					if ($emailSendStatus == '1' && $emailNotificationStatus == '1' && $insertNewFollowingList == 'flw') {
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
						$instagramIcon = $iN->iN_SelectedMenuIcon('88');
						$facebookIcon = $iN->iN_SelectedMenuIcon('90');
						$twitterIcon = $iN->iN_SelectedMenuIcon('34');
						$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
						$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
						include_once '../includes/mailTemplates/userFollowingEmailTemplate.php';
						$body = $bodyUserFollowEmailTemplate;
						$mail->setFrom($smtpEmail, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail, ''); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['now_following_your_profile']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if ($mail->send()) {
							$mail->ClearAddresses();
							return true;
						}
					}
				}
			}
		}
	}
	/*Block User PopUp Call*/
	if ($type == 'uBlockNotice') {
		if (isset($_POST['id'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($iuID);
			if ($checkUserExist) {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$f_userfullname = $userDetail['i_user_fullname'];
				include "../themes/$currentTheme/layouts/popup_alerts/userBlockAlert.php";
			}
		}
	}
	/*Block User*/
	if ($type == 'ublock') {
		if (isset($_POST['id']) && in_array($_POST['blckt'], $blockType)) {
			$uID = mysqli_real_escape_string($db, $_POST['id']);
			$uBlockType = mysqli_real_escape_string($db, $_POST['blckt']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				if ($uID != $userID) {
					$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
					$friendsStatusTwo = $iN->iN_GetRelationsipBetweenTwoUsers($uID, $userID);
					$addBlockList = $iN->iN_InsertBlockList($userID, $uID, $uBlockType);
					if ($addBlockList == 'bAdded') {
						$status = '200';
						$redirect = $base_url . 'settings?tab=blocked';
					} else if ($addBlockList == 'bRemoved') {
						$status = '200';
						$redirect = $base_url . 'settings?tab=blocked';
					} else {
						$status = '404';
						$redirect = '';
					}
					if ($addBlockList == 'bAdded' && $uBlockType == '2') {
						if ($friendsStatus == 'subscriber') {
							\Stripe\Stripe::setApiKey($stripeKey);
							$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
							$subscription->cancel();
							$iN->iN_UnSubscriberUser($userID, $uID);
						} else if ($friendsStatus == 'flwr') {
							$iN->iN_insertNewFollow($userID, $uID);
						}
						if ($friendsStatusTwo == 'subscriber') {
							\Stripe\Stripe::setApiKey($stripeKey);
							$getSubsData = $iN->iN_GetSubscribeID($uID, $userID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
							$subscription->cancel();
							$iN->iN_UnSubscriberUser($uID, $userID);
						} else if ($friendsStatusTwo == 'flwr') {
							$iN->iN_insertNewFollow($uID, $userID);
						}
					}
					$data = array(
						'status' => $status,
						'redirect' => $redirect,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				}
			}
		}
	}
	/*Subscribe Modal with Methods*/
	if ($type == 'subsModal') {
		if (isset($_POST['id'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($iuID);
			$p_friend_status = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $iuID);
			if ($checkUserExist && $p_friend_status != 'subscriber') {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$f_userID = $userDetail['iuid'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;
				if($subscriptionType == '2'){
					include "../themes/$currentTheme/layouts/popup_alerts/becomeSubscriberWithPoint.php";
				}else if($subscriptionType == '1' || $subscriptionType == '3'){
					include "../themes/$currentTheme/layouts/popup_alerts/becomeSubscriber.php";
				}
			}
		}
	}
	/*Credit Card popUp*/
	if ($type == 'creditCard') {
		if (isset($_POST['plan']) && isset($_POST['id'])) {
			$planID = mysqli_real_escape_string($db, $_POST['plan']);
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkPlanExist = $iN->iN_CheckPlanExist($planID, $iuID);
			if ($checkPlanExist) {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$f_userID = $userDetail['iuid'];
				$f_PlanAmount = $checkPlanExist['amount'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;
				include "../themes/$currentTheme/layouts/popup_alerts/payWithCreditCard.php";
			}
		}
	}
	/*Credit Card popUp*/
	if ($type == 'creditPoint') {
		if (isset($_POST['plan']) && isset($_POST['id'])) {
			$planID = mysqli_real_escape_string($db, $_POST['plan']);
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkPlanExist = $iN->iN_CheckPlanExist($planID, $iuID);
			if ($checkPlanExist) {
				$userDetail = $iN->iN_GetUserDetails($iuID);
				$planType = $checkPlanExist['plan_type'];
				$f_userID = $userDetail['iuid'];
				$f_PlanAmount = $checkPlanExist['amount'];
				$f_profileAvatar = $iN->iN_UserAvatar($f_userID, $base_url);
				$f_profileCover = $iN->iN_UserCover($f_userID, $base_url);
				$f_username = $userDetail['i_username'];
				$f_userfullname = $userDetail['i_user_fullname'];
				$f_userGender = $userDetail['user_gender'];
				$f_VerifyStatus = $userDetail['user_verified_status'];
				if ($f_userGender == 'male') {
					$fGender = '<div class="i_pr_m">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
				} else if ($f_userGender == 'female') {
					$fGender = '<div class="i_pr_fm">' . $iN->iN_SelectedMenuIcon('13') . '</div>';
				} else if ($f_userGender == 'couple') {
					$fGender = '<div class="i_pr_co">' . $iN->iN_SelectedMenuIcon('58') . '</div>';
				}
				$fVerifyStatus = '';
				if ($f_VerifyStatus == '1') {
					$fVerifyStatus = '<div class="i_pr_vs">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
				}
				$f_profileStatus = $userDetail['profile_status'];
				$f_is_creator = '';
				if ($f_profileStatus == '2') {
					$f_is_creator = '<div class="creator_badge">' . $iN->iN_SelectedMenuIcon('9') . '</div>';
				}
				$fprofileUrl = $base_url . $f_username;

				include "../themes/$currentTheme/layouts/popup_alerts/payWithPoint.php";
			}
		}
	}
	/*Subscribe User (SEND STRIPE AND SAVE DATA)*/
	if ($type == 'subscribeMe') {
		if (isset($_POST['u']) && isset($_POST['pl']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['t'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['u']);
			$planID = mysqli_real_escape_string($db, $_POST['pl']);
			$subscriberName = mysqli_real_escape_string($db, $_POST['name']);
			$subscriberEmail = mysqli_real_escape_string($db, $_POST['email']);
			$stripeTokenID = mysqli_real_escape_string($db, $_POST['t']);
			$planDetails = $iN->iN_CheckPlanExist($planID, $iuID);
			$payment_id = $statusMsg = $api_error = '';
			$checkAlreadySubscribed = $iN->iN_CheckUserIsInSubscriber($userID, $iuID);
			$p_friend_status = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $iuID);
			if($p_friend_status == 'subscriber'){
               exit('Already subscribed.');
			}
			if ($planDetails && $p_friend_status != 'subscriber') {
				$planType = $planDetails['plan_type'];
				$amount = $planDetails['amount'];
				$payment_Type = 'stripe';
				if ($planType == 'weekly') {
					$planName = 'Weekly Subscription';
					$planInterval = 'week';
				} else if ($planType == 'monthly') {
					$planName = 'Monthly Subscription';
					$planInterval = 'month';
				} else if ($planType == 'yearly') {
					$planName = 'Yearly Subscription';
					$planInterval = 'year';
				}
				if (empty($stripeTokenID) || $stripeTokenID == '' || !isset($stripeTokenID) || $stripeTokenID == 'undefined') {
					exit($LANG['fill_all_credit_card_details']);
				}
				// Set API key
				\Stripe\Stripe::setApiKey($stripeKey);
				// Add customer to stripe
				try {
					$customer = \Stripe\Customer::create(array(
						'email' => $subscriberEmail,
						'source' => $stripeTokenID,
					));
				} catch (Exception $e) {
					$api_error = $e->getMessage();
				}
				/******/
				if (empty($api_error) && $customer) {
					// Convert price to cents
					$priceCents = round($amount * 100);

					// Create a plan
					try {
						$plan = \Stripe\Plan::create(array(
							"product" => [
								"name" => $planName,
							],
							"amount" => $priceCents,
							"currency" => $stripeCurrency,
							"interval" => $planInterval,
							"interval_count" => 1,
						));
					} catch (Exception $e) {
						$api_error = $e->getMessage();
					}

					if (empty($api_error) && $plan) {

						// Creates a new subscription
						try {
							$subscription = \Stripe\Subscription::create(array(
								"customer" => $customer->id,
								"items" => array(
									array(
										"plan" => $plan->id,
									),
								),
							));
						} catch (Exception $e) {
							$api_error = $e->getMessage();
						}
						if (empty($api_error) && $subscription) {
							// Retrieve subscription data
							$subsData = $subscription->jsonSerialize();
							// Check whether the subscription activation is successful
							if ($subsData['status'] == 'active') {
								// Subscription info
								$subscrID = $subsData['id'];
								$custID = $subsData['customer'];
								$planIDs = $subsData['plan']['id'];
								$planAmount = ($subsData['plan']['amount'] / 100);
								$planCurrency = $subsData['plan']['currency'];
								$planinterval = $subsData['plan']['interval'];
								$planIntervalCount = $subsData['plan']['interval_count'];
								$plancreated = date("Y-m-d H:i:s", $subsData['created']);
								$current_period_start = date("Y-m-d H:i:s", $subsData['current_period_start']);
								$current_period_end = date("Y-m-d H:i:s", $subsData['current_period_end']);
								$planStatus = $subsData['status'];
								$adminEarning = ($adminFee * $planAmount) / 100;
								$userNetEarning = $planAmount - $adminEarning;
								$insertSubscription = $iN->iN_InsertUserSubscription($userID, $iuID, $payment_Type, $subscriberName, $subscrID, $custID, $planIDs, $planAmount, $adminEarning, $userNetEarning, $planCurrency, $planinterval, $planIntervalCount, $subscriberEmail, $plancreated, $current_period_start, $current_period_end, $planStatus);
								if ($insertSubscription) {
									echo '200';
									$uData = $iN->iN_GetUserDetails($iuID);
									$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
									$lUsername = $uData['i_username'];
									$iN->iN_InsertNotificationForSubscribe($userID, $iuID);
									$fuserAvatar = $iN->iN_UserAvatar($iuID, $base_url);
									$lUserFullName = $uData['i_user_fullname'];
									$emailNotificationStatus = $uData['email_notification_status'];
									$morePostForSubscriber = $LANG['share_something_for_subscriber'];
									$slugUrl = $base_url . $lUsername;
									$gotNewSubscriber = $LANG['got_new_subscriber'];
									if ($emailSendStatus == '1' && $emailNotificationStatus == '1') {

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
										$instagramIcon = $iN->iN_SelectedMenuIcon('88');
										$facebookIcon = $iN->iN_SelectedMenuIcon('90');
										$twitterIcon = $iN->iN_SelectedMenuIcon('34');
										$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
										$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
										include_once '../includes/mailTemplates/newSubscriberEmailTemplate.php';
										$body = $bodyNewSubscriberEmailTemplate;
										$mail->setFrom($smtpEmail, $siteName);
										$send = false;
										$mail->IsHTML(true);
										$mail->addAddress($sendEmail, ''); // Add a recipient
										$mail->Subject = $iN->iN_Secure($LANG['now_following_your_profile']);
										$mail->CharSet = 'utf-8';
										$mail->MsgHTML($body);
										if ($mail->send()) {
											$mail->ClearAddresses();
											return true;
										}

									}
								} else {
									echo iN_HelpSecure($LANG['contact_site_administrator']);
								}
							} else {
								echo iN_HelpSecure($LANG['subscription_activation_failed']);
							}
						} else {
							echo iN_HelpSecure($LANG['subscription_creation_failed']) . $api_error;
						}
					} else {
						echo iN_HelpSecure($LANG['plan_creation_failed']) . $api_error;
					}
				} else {
					echo iN_HelpSecure($LANG['invalid_card_details']) . $api_error;
				}
				/******/
			}
		}
	}
	/*Subscribe User (SUBSCRIBE WITH UPLOADED POINTS)*/
	if($type == 'subWithPoints'){
        if(isset($_POST['pl']) && $_POST['pl'] != '' && !empty($_POST['pl']) && isset($_POST['id']) && $_POST['id'] != '' && !empty($_POST['id'])){
			$planID = mysqli_real_escape_string($db, $_POST['pl']);
			$iuID = mysqli_real_escape_string($db, $_POST['id']);
			$checkPlanExist = $iN->iN_CheckPlanExist($planID, $iuID);
			$planType = isset($checkPlanExist['plan_type']) ? $checkPlanExist['plan_type'] : NULL;
			$planAmount = isset($checkPlanExist['amount']) ? $checkPlanExist['amount'] : NULL;
			if($checkPlanExist && ($userCurrentPoints >= $planAmount)){
				$payment_Type = 'point';
				$adminEarning = $adminFee * $planAmount * $onePointEqual / 100;
				$userNetEarning = $planAmount * $onePointEqual - $adminEarning;
				$planIntervalCount = '1';
				if ($planType == 'weekly') {
					$planName = 'Weekly Subscription';
					$planInterval = 'week';
					$thisTime = strtotime('+7 day', time());
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s", $thisTime);
				    $current_period_end = date("Y-m-d H:i:s", $thisTime);
				} else if ($planType == 'monthly') {
					$planName = 'Monthly Subscription';
					$planInterval = 'month';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s", strtotime('+1 month', time()));
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 month', time()));
				} else if ($planType == 'yearly') {
					$planName = 'Yearly Subscription';
					$planInterval = 'year';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s", strtotime('+1 month', time()));
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 year', time()));
				}
				$uDetails = $iN->iN_GetUserDetails($iuID);
				$subscriberName = mysqli_real_escape_string($db, $uDetails['i_user_fullname']);
			    $subscriberEmail = mysqli_real_escape_string($db, $uDetails['i_user_email']);
				$UpdateCurrentPoint = $userCurrentPoints - $planAmount;
				$planCurrency = $defaultCurrency;
				$planStatus = 'active';
				$insertSubscription = $iN->iN_InsertUserSubscriptionWithPoint($userID, $iuID, $payment_Type, $subscriberName, $planAmount, $adminEarning, $userNetEarning, $planCurrency, $planInterval, $planIntervalCount, $subscriberEmail, $plancreated, $current_period_start, $current_period_end, $planStatus,$UpdateCurrentPoint);
			    if ($insertSubscription) {
					echo '200';
					$uData = $iN->iN_GetUserDetails($iuID);
					$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
					$lUsername = $uData['i_username'];
					$iN->iN_InsertNotificationForSubscribe($userID, $iuID);
					$fuserAvatar = $iN->iN_UserAvatar($iuID, $base_url);
					$lUserFullName = $uData['i_user_fullname'];
					$emailNotificationStatus = $uData['email_notification_status'];
					$morePostForSubscriber = $LANG['share_something_for_subscriber'];
					$slugUrl = $base_url . $lUsername;
					$gotNewSubscriber = $LANG['got_new_subscriber'];
					if ($emailSendStatus == '1' && $emailNotificationStatus == '1') {
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
						$instagramIcon = $iN->iN_SelectedMenuIcon('88');
						$facebookIcon = $iN->iN_SelectedMenuIcon('90');
						$twitterIcon = $iN->iN_SelectedMenuIcon('34');
						$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
						$startedFollow = $iN->iN_Secure($LANG['now_following_your_profile']);
						include_once '../includes/mailTemplates/newSubscriberEmailTemplate.php';
						$body = $bodyNewSubscriberEmailTemplate;
						$mail->setFrom($smtpEmail, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail, ''); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['now_following_your_profile']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if ($mail->send()) {
							$mail->ClearAddresses();
							return true;
						}
					}
				}else{
					exit('404');
				}
			} else{
				exit('302');
			}
		}
	}

	if ($type == 'uploadVerificationFiles') {
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
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit('Upload Failed');
								}
								if ($s3Status == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if ($WasStatus == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if($digitalOceanStatus == '1'){
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
									/*IF DIGITALOCEAN AVAILABLE THEN*/
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									/**/
									@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									if($upload){
										$UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/files/' . $d . '/' . $getFilename;
									 }
									/*/IF DIGITAOCEAN AVAILABLE THEN*/
								 } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedFilesForVerification($userID, $pathFile, NULL, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							/*AMAZON S3*/
							echo '
                    <div class="i_uploaded_item in_' . $theValidateType . ' iu_f_' . $getUploadedFileID['upload_id'] . '" id="' . $getUploadedFileID['upload_id'] . '">
                      ' . $postTypeIcon . '
                      <div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
                          ' . $iN->iN_SelectedMenuIcon('5') . '
                      </div>
                      <div class="i_uploaded_file" style="background-image:url(' . $UploadSourceUrl . ');">
                            <img class="i_file" src="' . $UploadSourceUrl . '" alt="' . $UploadSourceUrl . '">
                      </div>
                    </div>
                ';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	/*Send Account Verificatoun Request*/
	if ($type == 'verificationRequest') {
		if (isset($_POST['cID']) && isset($_POST['cP'])) {
			$cardIDPhoto = mysqli_real_escape_string($db, $_POST['cID']);
			$Photo = mysqli_real_escape_string($db, $_POST['cP']);
			$checkCardIDPhotoExist = $iN->iN_CheckImageIDExist($cardIDPhoto, $userID);
			$checkPhotoExist = $iN->iN_CheckImageIDExist($Photo, $userID);
			if (empty($cardIDPhoto) && empty($Photo) && empty($checkCardIDPhotoExist) && empty($checkPhotoExist)) {
				echo 'both';
				return false;
			}
			if (empty($cardIDPhoto) && empty($checkCardIDPhotoExist)) {
				echo 'card';
				return false;
			}
			if (empty($Photo) && empty($checkPhotoExist)) {
				echo 'photo';
				return false;
			}
			if ($checkCardIDPhotoExist == '1' && $checkPhotoExist == '1') {
				$InsertNewVerificationRequest = $iN->iN_InsertNewVerificationRequest($userID, $cardIDPhoto, $Photo);
				if ($InsertNewVerificationRequest) {
					echo '200';
				}
			} else {
				echo 'both';
			}
		}
	}
	/*Accept Conditions by Clicking Next Button*/
	if ($type == 'acceptConditions') {
		$conditionsAccept = $iN->iN_AcceptConditions($userID);
		if ($conditionsAccept) {
			echo '200';
		}
	}
	if($type == 'vldcd'){
		if(isset($_POST['code']) && $_POST['code'] != '' && !empty($_POST['code'])){
			$cosCode = mysqli_real_escape_string($db, $_POST['code']);
			$vcodeCheck = $iN->iN_PurUCheck($userID, $cosCode, $base_url);
			if($vcodeCheck == base64_decode('b2s=')){
				if($iN->iN_LegDone($cosCode)){
					exit(base64_decode('bmV4dA=='));
				}else{
					exit(base64_decode('RHVyaW5nIHRoZSBpbnN0YWxsYXRpb24gcHJvY2VzcywgYW4gaXNzdWUgaGFzIGFyaXNlbiBjb25jZXJuaW5nIHRoZSBzZXJ2ZXIuIFBsZWFzZSBjcmVhdGUgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1jcmVhdGVUaWNrZXQiPnRpY2tldDwvYT4gZm9yIHByb21wdCBhc3Npc3RhbmNlLiBCZWZvcmUgY3JlYXRpbmcgYSB0aWNrZXQsIGtpbmRseSB0YWtlIGEgbW9tZW50IHRvIHJldmlldyBmb3IgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1mYXFzIj5xdWljayByZXNwb25zZTwvYT4u'));
				}
			} else{
				exit(base64_decode('RHVyaW5nIHRoZSBpbnN0YWxsYXRpb24gcHJvY2VzcywgYW4gaXNzdWUgaGFzIGFyaXNlbiBjb25jZXJuaW5nIHRoZSBzZXJ2ZXIuIFBsZWFzZSBjcmVhdGUgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1jcmVhdGVUaWNrZXQiPnRpY2tldDwvYT4gZm9yIHByb21wdCBhc3Npc3RhbmNlLiBCZWZvcmUgY3JlYXRpbmcgYSB0aWNrZXQsIGtpbmRseSB0YWtlIGEgbW9tZW50IHRvIHJldmlldyBmb3IgYSA8YSBocmVmPSJodHRwczovL3N1cHBvcnQuZGl6enlzY3JpcHRzLmNvbS8/cD1mYXFzIj5xdWljayByZXNwb25zZTwvYT4u'));
			}
		}
	}
	/*Insert Subscription Amount if Amounts are not empty*/
	if ($type == 'setSubscriptionPayments') {
		if (in_array($_POST['wStatus'], $statusValue) && in_array($_POST['mStatus'], $statusValue) && in_array($_POST['yStatus'], $statusValue)) {
			$SubWeekAmount = mysqli_real_escape_string($db, $_POST['wSubWeekAmount']);
			$SubMonthAmount = mysqli_real_escape_string($db, $_POST['mSubMonthAmount']);
			$SubYearAmount = mysqli_real_escape_string($db, $_POST['mSubYearAmount']);
			$weeklySubStatus = mysqli_real_escape_string($db, $_POST['wStatus']);
			$monthlySubStatus = mysqli_real_escape_string($db, $_POST['mStatus']);
			$yearlySubStatus = mysqli_real_escape_string($db, $_POST['yStatus']);
			if (!empty($SubWeekAmount) && $SubWeekAmount !== '') {
				if ($SubWeekAmount >= $subscribeWeeklyMinimumAmount) {
					$iN->iN_InsertWeeklySubscriptionAmountAndStatus($userID, $SubWeekAmount, $weeklySubStatus);
				}
			}
			if (!empty($SubMonthAmount) && $SubMonthAmount !== '') {
				if ($SubMonthAmount >= $subscribeMonthlyMinimumAmount) {
					$iN->iN_InsertMonthlySubscriptionAmountAndStatus($userID, $SubMonthAmount, $monthlySubStatus);
				}
			}
			if (!empty($SubYearAmount) && $SubYearAmount !== '') {
				if ($SubYearAmount >= $subscribeYearlyMinimumAmount) {
					$iN->iN_InsertYearlySubscriptionAmountAndStatus($userID, $SubYearAmount, $yearlySubStatus);
				}
			}
			$updateFeeStatus = $iN->iN_UpdateUserFeeStatus($userID);
			if ($updateFeeStatus) {
				echo '200';
			}
		}
	}
	/*Save Payout Details*/
	if ($type == 'payoutSet') {
		if (in_array($_POST['method'], $defaultPayoutMethods)) {
			$paypalEmail = mysqli_real_escape_string($db, $_POST['paypalEmail']);
			$re_paypalEmail = mysqli_real_escape_string($db, $_POST['paypalReEmail']);
			$bankAccount = mysqli_real_escape_string($db, $_POST['bank']);
			$defaultMethod = mysqli_real_escape_string($db, $_POST['method']);
			if($defaultMethod != 'bank'){
				if ($paypalEmail != $re_paypalEmail) {
					echo 'email_warning';
					exit();
				}
			}

			if ($defaultMethod == 'bank' && $bankAccount == '' && empty($bankAccount)) {
				echo 'bank_warning';
				exit();
			}
			if ($defaultMethod == 'paypal' && $paypalEmail == '' && empty($paypalEmail)) {
				echo 'paypal_warning';
				exit();
			}
			if($defaultMethod != 'bank'){
				if (!filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
					echo 'not_valid_email';
					exit();
				}
		    }
			$insertPayout = $iN->iN_SetPayout($userID, $paypalEmail, $bankAccount, $defaultMethod);
			if ($insertPayout) {
				echo '200';
			}
		}
	}
	/*Check Username Exist*/
	if ($type == 'checkusername') {
		if (isset($_POST['username']) && $_POST['username'] != '' && !empty($_POST['username'])) {
			$new_username = mysqli_real_escape_string($db, $_POST['username']);
			$checkUsernameExist = $iN->iN_CheckUsernameExist($new_username);
			if ($new_username == $userName) {
				exit();
			} else if (strlen($new_username) < 5) {
				echo '4';
			} else if (!preg_match('/^[\w]+$/', $_POST['username'])) {
				echo '3';
			} else if ($checkUsernameExist == 'no') {
				echo '1';
			} else if ($checkUsernameExist == 'yes') {
				echo '2';
			}
		}
	}
	/*Edit May Page*/
	if ($type == 'editMyPage') {
		$fullname = mysqli_real_escape_string($db, $_POST['flname']);
		$newUsername = mysqli_real_escape_string($db, $_POST['uname']);
		$gender = mysqli_real_escape_string($db, $_POST['gender']);
		$bio = mysqli_real_escape_string($db, $_POST['bio']);
		if(isset($_POST['tnot']) && !empty($_POST['tnot']) && $_POST['tnot'] != ''){
			$tipNot = mysqli_real_escape_string($db, $_POST['tnot']);
		}else{
			$tipNot = '';
		}
		   $socialNet = $iN->iN_ShowUserSocialSitesList($userID);
           if($socialNet){
               foreach($socialNet as $snet){
                 $sKey = $snet['skey'];
				 $slID = $snet['id'];
				 if(isset($_POST[$sKey]) && !empty($_POST[$sKey]) && $_POST[$sKey] != ''){
					 $mySkey = mysqli_real_escape_string($db,$_POST[$sKey]);
                     if($iN->iN_IsUrl($mySkey) == '1'){
                        $checkUserExistInSocialLink = mysqli_query($db,"SELECT * FROM i_social_user_profiles WHERE uid_fk = '$userID' AND isw_id_fk = '$slID'") or die(mysqli_error($db));
					    if(mysqli_num_rows($checkUserExistInSocialLink) == 1){
						mysqli_query($db,"UPDATE i_social_user_profiles SET s_link = '$mySkey' WHERE uid_fk = '$userID' AND isw_id_fk = '$slID'") or die(mysqli_error($db));
					 }else{
						mysqli_query($db,"INSERT INTO i_social_user_profiles(s_link,isw_id_fk, uid_fk)VALUES('$mySkey','$slID','$userID')") or die(mysqli_error($db));
					 }
					}
				 }else{
					mysqli_query($db,"UPDATE i_social_user_profiles SET s_link = NULL WHERE uid_fk = '$userID' AND isw_id_fk = '$slID'") or die(mysqli_error($db));
				 }
		        }
	        }
		$birthDay = mysqli_real_escape_string($db, $_POST['birthdate']);
		$profileCategory = mysqli_real_escape_string($db, $_POST['ctgry']);
		$checkUsernameExist = $iN->iN_CheckUsernameExist($newUsername);
		if (strlen($fullname) < 3 || strlen($fullname) > 30 || empty($fullname)) {
			exit('3');
		}
		if (strlen($newUsername) < 5) {
			$newUsername = $userName;
		} else if (!preg_match('/^[\w]+$/', $newUsername)) {
			$newUsername = $userName;
		} else if ($checkUsernameExist == 'yes') {
			$newUsername = $userName;
		}
		if (strlen($fullname) < 5 || strlen($fullname) > 30) {
			$fullname = $userFullName;
		}
		if(!empty($birthDay) && $birthDay != '' && $birthDay != 'undefined'){
			if ($iN->iN_CalculateUserAge($birthDay) < 18) {
				exit('2');
			}
	    }
		if(!empty($birthDay) && $birthDay != '' && $birthDay != 'undefined'){
           if(!$iN->isDate($birthDay)){
               exit('1');
		   }
		}else{
			$birthDay = NULL;
		}

		if (in_array($gender, $genders) && isset($newUsername) && !empty($newUsername) && $newUsername != '' && isset($fullname) && !empty($fullname) && $fullname != '') {
			$updateMyProfile = $iN->iN_UpdateProfile($userID, $iN->iN_Secure($fullname), $iN->iN_Secure($bio), $iN->iN_Secure($newUsername), $iN->iN_Secure($birthDay), $iN->iN_Secure($profileCategory), $iN->iN_Secure($gender),$iN->iN_Secure($tipNot));
			if ($updateMyProfile) {
				echo '1';
			}
		}
	}
	/*Call Avatar and Cover PopUP*/
	if ($type == 'updateAvatarCover') {
		include "../themes/$currentTheme/layouts/popup_alerts/uploadAvatarCoverPhoto.php";
	}
	/*Upload Croped Image*/
	if ($type == 'coverUpload') {
		if (isset($_POST['image']) && $_POST['image'] != '' && !empty($_POST['image'])) {
			$dataImage = mysqli_real_escape_string($db, $_POST['image']);
			$image_array_1 = explode(";", $dataImage);
			$image_array_2 = explode(",", $image_array_1[1]);
			$data = base64_decode($image_array_2[1]);
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$UploadedFileName = "cover_" . $removeMicrotime . '_' . $userID;
			$getFilename = $UploadedFileName . ".png";
			$ext = getExtension($getFilename);
			$valid_formats = explode(',', $availableFileExtensions);
			if (strlen($getFilename)) {
				if (in_array($ext, $valid_formats)) {
					$d = date('Y-m-d');
					if (!file_exists($uploadCover . $d)) {
						$newFile = mkdir($uploadCover . $d, 0755);
					}
					if (file_put_contents($uploadCover . $d . '/' . $getFilename, $data)) {
						$pathFile = 'uploads/covers/' . $d . '/' . $getFilename;
						if ($s3Status == '1') {
							/*Upload Video tumbnail*/
							$thevTumbnail = '../uploads/covers/' . $d . '/' . $UploadedFileName . '.' . $ext;
							$key = basename($thevTumbnail);
							try {
								$result = $s3->putObject([
									'Bucket' => $s3Bucket,
									'Key' => 'uploads/covers/' . $d . '/' . $key,
									'Body' => fopen($thevTumbnail, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$UploadSourceUrl = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
								/*rmdir($uploadFile . $d);*/
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo "There was an error uploading the file.\n";
							}
						}else if ($WasStatus == '1') {
							/*Upload Video tumbnail*/
							$thevTumbnail = '../uploads/covers/' . $d . '/' . $UploadedFileName . '.' . $ext;
							$key = basename($thevTumbnail);
							try {
								$result = $s3->putObject([
									'Bucket' => $WasBucket,
									'Key' => 'uploads/covers/' . $d . '/' . $key,
									'Body' => fopen($thevTumbnail, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$UploadSourceUrl = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
								/*rmdir($uploadFile . $d);*/
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo "There was an error uploading the file.\n";
							}
						} else if($digitalOceanStatus == '1'){
							$thevTumbnail = '../uploads/covers/' . $d . '/' . $UploadedFileName . '.' . $ext;
							/*IF DIGITALOCEAN AVAILABLE THEN*/
							$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
							$upload = $my_space->UploadFile($thevTumbnail, "public");
							$UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/covers/' . $d . '/' . $getFilename;
						} else {
							$UploadSourceUrl = $base_url . 'uploads/covers/' . $d . '/' . $getFilename;
						}
						$coverData = $iN->iN_INSERTUploadedCoverPhoto($userID, $pathFile);
						if ($coverData) {
							$getUploadedFileID = $iN->iN_GetUploadedCoverURL($userID, $coverData);
							if($s3Status == '1'){
								$imgUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $getUploadedFileID;
							}else if($WasStatus == '1'){
								$UploadSourceUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $getUploadedFileID;
							}else if($digitalOceanStatus == '1'){
								$imgUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/'. $getUploadedFileID;
							}else{
								$imgUrl = $base_url . $getUploadedFileID;
							}
							echo $imgUrl;
						} else {
							exit('Something Went Wrong');
						}
					}
				}
			}

		}
	}
	/*Upload Croped Image*/
	if ($type == 'avatarUpload') {
		if (isset($_POST['image']) && $_POST['image'] != '' && !empty($_POST['image'])) {
			$dataImage = mysqli_real_escape_string($db, $_POST['image']);
			$image_array_1 = explode(";", $dataImage);
			$image_array_2 = explode(",", $image_array_1[1]);
			$data = base64_decode($image_array_2[1]);
			$microtime = microtime();
			$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
			$UploadedFileName = "avatar_" . $removeMicrotime . '_' . $userID;
			$getFilename = $UploadedFileName . ".png";
			$ext = getExtension($getFilename);
			$valid_formats = explode(',', $availableFileExtensions);
			if (strlen($getFilename)) {
				if (in_array($ext, $valid_formats)) {
					$d = date('Y-m-d');
					if (!file_exists($uploadAvatar . $d)) {
						$newFile = mkdir($uploadAvatar . $d, 0755);
					}
					if (file_put_contents($uploadAvatar . $d . '/' . $getFilename, $data)) {
						$pathFile = 'uploads/avatars/' . $d . '/' . $getFilename;
						if ($s3Status == '1') {
							/*Upload Video tumbnail*/
							$thevTumbnail = '../uploads/avatars/' . $d . '/' . $UploadedFileName . '.' . $ext;
							$key = basename($thevTumbnail);
							try {
								$result = $s3->putObject([
									'Bucket' => $s3Bucket,
									'Key' => 'uploads/avatars/' . $d . '/' . $key,
									'Body' => fopen($thevTumbnail, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$UploadSourceUrl = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
								/*rmdir($uploadFile . $d);*/
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo "There was an error uploading the file.\n";
							}
						}else if ($WasStatus == '1') {
							/*Upload Video tumbnail*/
							$thevTumbnail = '../uploads/avatars/' . $d . '/' . $UploadedFileName . '.' . $ext;
							$key = basename($thevTumbnail);
							try {
								$result = $s3->putObject([
									'Bucket' => $WasBucket,
									'Key' => 'uploads/avatars/' . $d . '/' . $key,
									'Body' => fopen($thevTumbnail, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$UploadSourceUrl = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
								/*rmdir($uploadFile . $d);*/
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo "There was an error uploading the file.\n";
							}
						} else if($digitalOceanStatus == '1'){
							$thevTumbnail = '../uploads/avatars/' . $d . '/' . $UploadedFileName . '.' . $ext;
							/*IF DIGITALOCEAN AVAILABLE THEN*/
							$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
							$upload = $my_space->UploadFile($thevTumbnail, "public");
							$UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/'. 'uploads/avatars/' . $d . '/' . $getFilename;
						} else {
							$UploadSourceUrl = $base_url . 'uploads/avatars/' . $d . '/' . $getFilename;
						}
						$coverData = $iN->iN_INSERTUploadedAvatarPhoto($userID, $pathFile);
						if ($coverData) {
							$getUploadedFileID = $iN->iN_GetUploadedAvatarURL($userID, $coverData);
							if($s3Status == '1'){
								$imgUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $getUploadedFileID;
							}else if($WasStatus == '1'){
								$imgUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $getUploadedFileID;
							}else if($digitalOceanStatus == '1'){
								$imgUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/'. $getUploadedFileID;
							}else{
								$imgUrl = $base_url . $getUploadedFileID;
							}
							echo $imgUrl;
						} else {
							exit('Something Went Wrong');
						}
					}
				}
			}

		}
	}
	/*Check Email Valid or Exist*/
	if ($type == 'checkemail') {
		if (isset($_POST['newEmail']) && $_POST['newEmail'] != '' && !empty($_POST['newEmail'])) {
			$newEmail = mysqli_real_escape_string($db, $_POST['newEmail']);
			if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
				echo 'no';
				exit();
			} else {
				$checkEmail = $iN->iN_CheckEmail($userID, $newEmail);
				if ($checkEmail) {
					echo '200';
				} else {
					echo '404';
				}
			}
		}
	}
	/*Update Email Address*/
	if ($type == 'editMyEmail') {
		if (isset($_POST['newEmail']) && $_POST['newEmail'] != '' && !empty($_POST['newEmail']) && isset($_POST['currentPass']) && $_POST['currentPass'] != '' && !empty($_POST['currentPass'])) {
			$newEmail = mysqli_real_escape_string($db, $_POST['newEmail']);
			$currentPassword = mysqli_real_escape_string($db, $_POST['currentPass']);
			$checkEmail = $iN->iN_CheckEmail($userID, $newEmail);
			if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
				echo 'no';
				exit();
			} else if ($newEmail != $userEmail) {
				$Change = $iN->iN_CheckUserPasswordAndUpdateIfIsValid($userID, $currentPassword, $newEmail);
				if ($Change) {
					echo '200';
				} else {
					echo '404';
				}
			} else {
				echo 'same';
			}
		}
	}
	if ($type == 'updatePayoutSet') {
		if (in_array($_POST['method'], $defaultPayoutMethods)) {
			$paypalEmail = mysqli_real_escape_string($db, $_POST['paypalEmail']);
			$re_paypalEmail = mysqli_real_escape_string($db, $_POST['paypalReEmail']);
			$bankAccount = mysqli_real_escape_string($db, $_POST['bank']);
			$defaultMethod = mysqli_real_escape_string($db, $_POST['method']);
			if($defaultMethod != 'bank'){
				if ($paypalEmail != $re_paypalEmail) {
					echo 'email_warning';
					exit();
				}
			}
			if ($defaultMethod == 'bank' && $bankAccount == '' && empty($bankAccount)) {
				echo 'bank_warning';
				exit();
			}
			if ($defaultMethod == 'paypal' && $paypalEmail == '' && empty($paypalEmail)) {
				echo 'paypal_warning';
				exit();
			}
			if($defaultMethod != 'bank'){
				if (!filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
					echo 'not_valid_email';
					exit();
				}
		    }
			$insertPayout = $iN->iN_UpdatePayout($userID, $paypalEmail, $bankAccount, $defaultMethod);
			if ($insertPayout) {
				echo '200';
			}
		}
	}
/*Insert Subscription Amount if Amounts are not empty*/
	if ($type == 'updateSubscriptionPayments') {
		if (in_array($_POST['wStatus'], $statusValue) && in_array($_POST['mStatus'], $statusValue) && in_array($_POST['yStatus'], $statusValue)) {
			$SubWeekAmount = mysqli_real_escape_string($db, $_POST['wSubWeekAmount']);
			$SubMonthAmount = mysqli_real_escape_string($db, $_POST['mSubMonthAmount']);
			$SubYearAmount = mysqli_real_escape_string($db, $_POST['mSubYearAmount']);
			$weeklySubStatus = mysqli_real_escape_string($db, $_POST['wStatus']);
			$monthlySubStatus = mysqli_real_escape_string($db, $_POST['mStatus']);
			$yearlySubStatus = mysqli_real_escape_string($db, $_POST['yStatus']);

			$weeklyNO = $monthlyNo = $yearlyNo = '';
			if($subscriptionType == '1'){
				if (!empty($SubWeekAmount) && $SubWeekAmount !== '') {
					if ($SubWeekAmount >= $subscribeWeeklyMinimumAmount) {
						$query = $iN->iN_UpdateWeeklySubscriptionAmountAndStatus($userID, $SubWeekAmount, $weeklySubStatus);
						if ($query) {$weeklyNO = '200';}
					} else {
						$weeklyNO = '404';
					}
				}
				if (!empty($SubMonthAmount) && $SubMonthAmount !== '') {
					if ($SubMonthAmount >= $subscribeMonthlyMinimumAmount) {
						$query = $iN->iN_UpdateMonthlySubscriptionAmountAndStatus($userID, $SubMonthAmount, $monthlySubStatus);
						if ($query) {$monthlyNo = '200';}
					} else {
						$monthlyNo = '404';
					}
				}
				if (!empty($SubYearAmount) && $SubYearAmount !== '') {
					if ($SubYearAmount >= $subscribeYearlyMinimumAmount) {
						$query = $iN->iN_UpdateYearlySubscriptionAmountAndStatus($userID, $SubYearAmount, $yearlySubStatus);
						if ($query) {$yearlyNo = '200';}
					} else {
						$yearlyNo = '404';
					}
				}
			}else if($subscriptionType == '2'){
				if (!empty($SubWeekAmount) && $SubWeekAmount !== '') {
					if ($SubWeekAmount >= $minPointFeeWeekly) {
						$query = $iN->iN_UpdateWeeklySubscriptionAmountAndStatus($userID, $SubWeekAmount, $weeklySubStatus);
						if ($query) {$weeklyNO = '200';}
					} else {
						$weeklyNO = '404';
					}
				}
				if (!empty($SubMonthAmount) && $SubMonthAmount !== '') {
					if ($SubMonthAmount >= $minPointFeeMonthly) {
						$query = $iN->iN_UpdateMonthlySubscriptionAmountAndStatus($userID, $SubMonthAmount, $monthlySubStatus);
						if ($query) {$monthlyNo = '200';}
					} else {
						$monthlyNo = '404';
					}
				}
				if (!empty($SubYearAmount) && $SubYearAmount !== '') {
					if ($SubYearAmount >= $minPointFeeYearly) {
						$query = $iN->iN_UpdateYearlySubscriptionAmountAndStatus($userID, $SubYearAmount, $yearlySubStatus);
						if ($query) {$yearlyNo = '200';}
					} else {
						$yearlyNo = '404';
					}
				}
			}
			$data = array(
				'weekly' => $weeklyNO,
				'monthly' => $monthlyNo,
				'yearly' => $yearlyNo,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
/*Inser Withdrawal*/
	if ($type == 'makewithDraw') {
		if (isset($_POST['amount']) && !empty($_POST['amount']) && $_POST['amount'] != '') {
			$withdrawalAmount = mysqli_real_escape_string($db, $_POST['amount']);
			$checkHavePendingWithdrawal = $iN->iN_CheckUserHavePendingWithdrawal($userID);
			if ($checkHavePendingWithdrawal) {
				echo '5';
				exit();
			}
			if ($withdrawalAmount >= $minimumWithdrawalAmount) {
				if ($userWallet >= $withdrawalAmount) {
					$insertWithdrawal = $iN->iN_InsertWithdrawal($userID, $withdrawalAmount, $payoutMethod, 'withdrawal');
					if ($insertWithdrawal) {
						echo '1';
					} else {
						echo '4';
					}
				} else {
					echo '3';
				}
			} else {
				echo '2';
			}
		}
	}
	if ($type == 'pPurchase') {
		if (isset($_POST['purchase']) && $_POST['purchase'] != '' && !empty($_POST['purchase'])) {
			$purchaseingPostID = mysqli_real_escape_string($db, $_POST['purchase']);
			$getPurchasingPostDetails = $iN->iN_GetAllPostDetails($purchaseingPostID);
			if ($getPurchasingPostDetails) {
				$userPostID = $getPurchasingPostDetails['post_id'];
				$userPostFile = $getPurchasingPostDetails['post_file'];
				$userPostOwnerID = $getPurchasingPostDetails['post_owner_id'];
				$userPostOwnerUserAvatar = $iN->iN_UserAvatar($userPostOwnerID, $base_url);
				$userPostOwnerUsername = $getPurchasingPostDetails['i_username'];
				$userPostOwnerUserFullName = $getPurchasingPostDetails['i_user_fullname'];
				$userPostWantedCredit = $getPurchasingPostDetails['post_wanted_credit'];
				include "../themes/$currentTheme/layouts/popup_alerts/purchase_premium_post.php";
			}
		}
	}
/*Purchase Post*/
	if ($type == 'goWallet') {
		if (isset($_POST['p'])) {
			$PurchasePostID = mysqli_real_escape_string($db, $_POST['p']);
			$checkPostID = $iN->iN_CheckPostIDExist($PurchasePostID);
			if ($checkPostID) {
				$getPurchasingPostDetails = $iN->iN_GetAllPostDetails($PurchasePostID);
				$userPostID = $getPurchasingPostDetails['post_id'];
				$userPostWantedCredit = $getPurchasingPostDetails['post_wanted_credit'];
				$userPostOwnerID = $getPurchasingPostDetails['post_owner_id'];

				$translatePointToMoney = $userPostWantedCredit * $onePointEqual;
				$adminEarning = $translatePointToMoney * ($adminFee / 100);
				$userEarning = $translatePointToMoney - $adminEarning;

				if ($userCurrentPoints >= $userPostWantedCredit && $userID != $userPostOwnerID) {
					$buyPost = $iN->iN_BuyPost($userID, $userPostOwnerID, $PurchasePostID, $translatePointToMoney, $adminEarning, $userEarning, $adminFee, $userPostWantedCredit);
					if ($buyPost) {
						echo iN_HelpSecure($base_url) . 'post/' . $getPurchasingPostDetails['url_slug'] . '_' . $userPostID;
						$approveNot = $LANG['congratulations_you_sold'];
						$iN->iN_SendNotificationForPurchasedPost($userID, $PurchasePostID, $userPostOwnerID,  $approveNot);
						$uData = $iN->iN_GetUserDetails($userPostOwnerID);
						$sendEmail = isset($uData['i_user_email']) ? $uData['i_user_email'] : NULL;
						$lUsername = $uData['i_username'];
						$lUserFullName = $uData['i_user_fullname'];
						$emailNotificationStatus = $uData['email_notification_status'];
						$notQualifyDocument = $LANG['not_qualify_document'];
						$slugUrl = $base_url . 'post/' . $getPurchasingPostDetails['url_slug'] . '_' . $userPostID;
						if ($emailSendStatus == '1' && $userID != $userPostOwnerID && $emailNotificationStatus == '1') {

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
							$instagramIcon = $iN->iN_SelectedMenuIcon('88');
							$facebookIcon = $iN->iN_SelectedMenuIcon('90');
							$twitterIcon = $iN->iN_SelectedMenuIcon('34');
							$linkedinIcon = $iN->iN_SelectedMenuIcon('89');
							$someoneBoughtYourPost = $iN->iN_Secure($LANG['someone_bought_your_post']);
							$clickGoPost = $iN->iN_Secure($LANG['click_go_post']);
							$youEarnMoney = $iN->iN_Secure($LANG['you_earn_money']);
							include_once '../includes/mailTemplates/postBoughtEmailTemplate.php';
							$body = $bodyPostBoughtEmailTemplate;
							$mail->setFrom($smtpEmail, $siteName);
							$send = false;
							$mail->IsHTML(true);
							$mail->addAddress($sendEmail, ''); // Add a recipient
							$mail->Subject = $iN->iN_Secure($LANG['someone_bought_your_post']);
							$mail->CharSet = 'utf-8';
							$mail->MsgHTML($body);
							if ($mail->send()) {
								$mail->ClearAddresses();
								return true;
							}

						}
					} else {
						echo 'Something wrong';
					}
				} else {
					echo iN_HelpSecure($base_url) . 'purchase/purchase_point';
				}
			}
		}
	}
/*Choose Payment Method*/
	if ($type == 'choosePaymentMethod') {
		if (isset($_POST['type']) && $_POST['type'] != '' && !empty($_POST['type'])) {
			$planID = mysqli_real_escape_string($db, $_POST['type']);
			$checkPlanExist = $iN->CheckPlanExist($planID);
			if ($checkPlanExist) {
				$planData = $iN->GetPlanDetails($planID);
				$planAmount = $planData['amount'];
				$planPoint = $planData['plan_amount'];
				if($stripePaymentCurrency == 'JPY'){
                     $planAmount = $planAmount / 100;
				}
				require_once '../includes/payment/vendor/autoload.php';
				if (!defined('INORA_METHODS_CONFIG')) {
					define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
				}
				$configData = configItem();
				$DataUserDetails = [
					'amounts' => [ // at least one currency amount is required
						$payPalCurrency => $planAmount,
						$iyziCoPaymentCurrency => $planAmount,
						$bitPayPaymentCurrency => $planAmount,
						$autHorizePaymentCurrency => $planAmount,
						$payStackPaymentCurrency => $planAmount,
						$stripePaymentCurrency => $planAmount,
						$razorPayPaymentCurrency => $planAmount,
						$mercadoPagoCurrency => $planAmount
					],
					'order_id' => 'ORDS' . uniqid(), // required in instamojo, Iyzico, Paypal, Paytm gateways
					'customer_id' => 'CUSTOMER' . uniqid(), // required in Iyzico, Paytm gateways
					'item_name' => $LANG['point_purchasing'], // required in Paypal gateways
					'item_qty' => 1,
					'item_id' => 'ITEM' . uniqid(), // required in Iyzico, Paytm gateways
					'payer_email' => $userEmail, // required in instamojo, Iyzico, Stripe gateways
					'payer_name' => $userFullName, // required in instamojo, Iyzico gateways
					'description' => $LANG['point_purchasing_from'], // Required for stripe
					'ip_address' => getUserIpAddr(), // required only for iyzico
					'address' => '3234 Godfrey Street Tigard, OR 97223', // required in Iyzico gateways
					'city' => 'Tigard', // required in Iyzico gateways
					'country' => 'United States', // required in Iyzico gateways
				];
				$PublicConfigs = getPublicConfigItem();

				$configItem = $configData['payments']['gateway_configuration'];

				// Get config data
				$configa = getPublicConfigItem();
				// Get app URL
				$paymentPagePath = getAppUrl();

				$gatewayConfiguration = $configData['payments']['gateway_configuration'];
				// get paystack config data
				$paystackConfigData = $gatewayConfiguration['paystack'];
				// Get paystack callback ur
				$paystackCallbackUrl = getAppUrl($paystackConfigData['callbackUrl']);

				// Get stripe config data
				$stripeConfigData = $gatewayConfiguration['stripe'];
				// Get stripe callback ur
				$stripeCallbackUrl = getAppUrl($stripeConfigData['callbackUrl']);

				// Get razorpay config data
				$razorpayConfigData = $gatewayConfiguration['razorpay'];
				// Get razorpay callback url
				$razorpayCallbackUrl = getAppUrl($razorpayConfigData['callbackUrl']);

				// Get Authorize.Net config Data
				$authorizeNetConfigData = $gatewayConfiguration['authorize-net'];
				// Get Authorize.Net callback url
				$authorizeNetCallbackUrl = getAppUrl($authorizeNetConfigData['callbackUrl']);

				// Individual payment gateway url
				$individualPaymentGatewayAppUrl = getAppUrl('individual-payment-gateways');
				// User Details Configurations FINISHED
				include "../themes/$currentTheme/layouts/popup_alerts/paymentMethods.php";
			}
		}
	}
	if ($type == 'process') {
		require_once '../includes/payment/vendor/autoload.php';
		if (!defined('INORA_METHODS_CONFIG')) {
			define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
		}
		include "../includes/payment/payment-process.php";
	}
/*Get Gifs*/
	if ($type == 'chat_gifs') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/chat/gifs.php";
		}
	}
/*Get Stickers*/
	if ($type == 'chat_stickers') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/chat/stickers.php";
		}
	}
/*Get Stickers*/
	if ($type == 'chat_btns') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			include "../themes/$currentTheme/layouts/chat/chat_btns.php";
		}
	}
/*Get Emojis*/
	if ($type == 'memoji') {
		if (isset($_POST['id'])) {
			$id = mysqli_real_escape_string($db, $_POST['id']);
			$importID = '';
			$importClass = 'emoji_item_m';
			include "../themes/$currentTheme/layouts/chat/emojis.php";
		}
	}
/*Insert New Message*/
	if ($type == 'nmessage') {
		if (isset($_POST['id']) && isset($_POST['val'])) {
			$message = mysqli_real_escape_string($db, $_POST['val']);
			$chatID = mysqli_real_escape_string($db, $_POST['id']);
			$sticker = mysqli_real_escape_string($db, $_POST['sticker']);
			$gifSrc = mysqli_real_escape_string($db, $_POST['gif']);
			$fileIDs = mysqli_real_escape_string($db, $_POST['fl'] ?? '');
			$trimMoney = mysqli_real_escape_string($db, $_POST['mo']);
			$mMoney = trim($trimMoney);
			$file = isset($fileIDs) ? $fileIDs : NULL;
			$checkChatIDExist = $iN->iN_CheckChatIDExist($chatID);
			$getStickerURL = $iN->iN_getSticker($sticker);
			$stickerURL = isset($getStickerURL['sticker_url']) ? $getStickerURL['sticker_url'] : NULL;
			$gifUrl = isset($gifSrc) ? $gifSrc : NULL;
			if(!empty($mMoney) || $mMoney != ''){
				if(empty($message) && empty($file)){
                   exit('403');
				}
				if($minimumPointLimit > $mMoney){
				  exit('404');
				}
			 }
			if (empty($message)) {
				if (empty($stickerURL)) {
					if (empty($gifUrl)) {
						if (empty($file)) {
							exit('404');
						}
					}
				}
			}

			if ($checkChatIDExist) {
				$insertData = $iN->iN_InsertNewMessage($userID, $chatID, $iN->iN_Secure($message), $iN->iN_Secure($stickerURL), $iN->iN_Secure($gifUrl), $iN->iN_Secure($file), $iN->iN_Secure($mMoney));
				/**/
				if ($insertData) {
					$cMessageID = $insertData['con_id'];
					$cUserOne = $insertData['user_one'];
					$cUserTwo = $insertData['user_two'];
					$cMessage = $insertData['message'];
					$mSeenStatus = $insertData['seen_status'];
					$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
					$privateStatus = isset($insertData['private_status']) ? $insertData['private_status'] : NULL;
				    $privatePrice = isset($insertData['private_price']) ? $insertData['private_price'] : NULL;
					$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
					$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
					$cMessageTime = $insertData['time'];
					$ip = $iN->iN_GetIPAddress();
					$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
					if ($query && $query['status'] == 'success') {
						date_default_timezone_set($query['timezone']);
					}
					$message_time = date("c", $cMessageTime);
					$convertMessageTime = strtotime($message_time);
					$netMessageHour = date('H:i', $convertMessageTime);
					$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
					$msgDots = '';
					$imStyle = '';
					$seenStatus = '';
					if ($cUserOne == $userID) {
						$mClass = 'me';
						$msgOwnerID = $cUserOne;
						$lastM = '';
						$timeStyle = 'msg_time_me';
						if (!empty($cFile)) {
							$imStyle = 'mmi_i';
						}
						$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						if ($mSeenStatus == '1') {
							$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						}
					} else {
						$mClass = 'friend';
						$msgOwnerID = $cUserOne;
						$lastM = 'mm_' . $msgOwnerID;
						if (!empty($cFile)) {
							$imStyle = 'mmi_if';
						}
						$timeStyle = 'msg_time_fri';
					}
					$styleFor = '';
					if ($cStickerUrl) {
						$styleFor = 'msg_with_sticker';
						$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
					}
					if ($cGifUrl) {
						$styleFor = 'msg_with_gif';
						$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
					}
					$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
					include "../themes/$currentTheme/layouts/chat/newMessage.php";
				}
				/**/
			} else {
				echo '404';
			}
		}
	}
/* Insert Live Message */
if ($type == 'livemessage') {
    if (
        isset($_POST['val']) && !empty($_POST['val']) &&
        isset($_POST['id']) && !empty($_POST['id']) &&
        trim($_POST['val']) !== '' && trim($_POST['id']) !== ''
    ) {
        $liveID = mysqli_real_escape_string($db, $_POST['id']);
        $liveMessageRaw = rawurldecode($_POST['val']);
        $liveMessage = mysqli_real_escape_string($db, $liveMessageRaw);

        if (empty($liveMessage) || trim($liveMessage) == '') {
            exit('404');
        }

        $lmData = $iN->iN_InsertLiveMessage($liveID, $iN->iN_Secure($liveMessage), $userID);

        if ($lmData) {
            $messageID = $lmData['cm_id'];
            $messageLiveID = $lmData['cm_live_id'];
            $messageLiveUserID = $lmData['cm_iuid_fk'];
            $messageLiveTime = $lmData['cm_time'];
            $liveMessage = rawurldecode($lmData['cm_message']); // decode again from DB (if needed)

            $msgData = $iN->iN_GetUserDetails($messageLiveUserID);
            $msgUserName = $msgData['i_username'];
            $msgUserFullName = $msgData['i_user_fullname'];

            // Only return message block, but avoid double-append in JS
            echo '
            <div class="gElp9 flex_ tabing_non_justify eo2As cUq_' . iN_HelpSecure($messageID) . '" id="' . iN_HelpSecure($messageID) . '">
                <a href="' . iN_HelpSecure($msgUserName) . '">' . iN_HelpSecure($msgUserFullName) . '</a>' . $iN->sanitize_output($liveMessage, $base_url) . '
            </div>';
        }
    }
}
/*Add Sticker*/
	if ($type == 'message_sticker') {
		if (isset($_POST['id']) && isset($_POST['pi'])) {
			$stickerID = mysqli_real_escape_string($db, $_POST['id']);
			$chatID = mysqli_real_escape_string($db, $_POST['pi']);
			$getStickerUrlandID = $iN->iN_getSticker($stickerID);
			if ($getStickerUrlandID) {
				$data = array(
					'st_id' => $getStickerUrlandID['sticker_id'],
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			}
		}
	}
	if ($type == 'message_image_upload') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			foreach ($_FILES['ciuploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['ciuploading']['name'][$iname]);
				$size = $_FILES['ciuploading']['size'][$iname];
				$conID = mysqli_real_escape_string($db, $_POST['c']);
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableFileExtensions);
				if (in_array($ext, $valid_formats)) {
				    $maxUploadSizeInBytes = $availableUploadFileSize * 1048576; // 1 MB = 1024 * 1024 = 1048576 byte
					if ($size > 0 && $size <= $maxUploadSizeInBytes) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['ciuploading']['tmp_name'][$iname];
						$mimeType = $_FILES['ciuploading']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'video') {
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
								$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								$convertUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
								$videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.png';
								$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';

								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
								if ($ext == 'mpg') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'mov') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
									//$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -q:v 0 -y $convertUrl 2>&1");
								} else if ($ext == 'wmv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'avi') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'webm') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -crf 1 -c:v libx264 $convertUrl");
								} else if ($ext == 'mpeg') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'flv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:a aac -strict -2 -b:a 128k -c:v libx264 -profile:v baseline $convertUrl");
								} else if ($ext == 'm4v') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy $convertUrl");
								} else if ($ext == 'mkv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl");
								}

								$cmd = shell_exec("$ffmpegPath -ss 00:00:01 -i $UploadedFilePath -c copy -t 00:00:04 $xVideoFirstPath");
								$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -ss 00:00:01.000 -vframes 1 $videoTumbnailPath");
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.png';
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.png';
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.png';
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit('You uploaded a video in '.$ext.' video format and ffmpeg could not create a tumbnail from the video.  You need to contact your server administration about this. ');
								}
								/*CHECK AMAZON S3 AVAILABLE*/
								if ($s3Status == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    // Upload full video
                                    $theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $getFilename);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        // Upload preview video
                                        $thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                        $key = basename($thexName);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body' => fopen($thexName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to /xvideos
                                        $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                        $key = basename($thevTumbnail);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body' => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to /files
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access to uploaded files. Please ensure 'public-read' permission is granted or remove it from the configuration.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }
                                }else if ($WasStatus == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    // Upload full video
                                    $theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $getFilename);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        // Upload preview video
                                        $thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                        $key = basename($thexName);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body' => fopen($thexName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to /xvideos
                                        $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                        $key = basename($thevTumbnail);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/xvideos/' . $d . '/' . $key,
                                                'Body' => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                        // Upload thumbnail to /files
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($thevTumbnail, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public access to uploaded files. This usually happens on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }

                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }
                                }else if($digitalOceanStatus == '1'){
									$theName = '../uploads/files/' . $d . '/' . $getFilename;
									/*IF DIGITALOCEAN AVAILABLE THEN*/
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($theName, "public");
									/**/
									$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thexName, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									if($upload){
										$UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/files/' . $d . '/' . $UploadedFileName . '.png';
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									}
									/*/IF DIGITAOCEAN AVAILABLE THEN*/
								 } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
								}
								/**/
								$ext = 'mp4';
							} else if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
								}else{
									exit('Upload Failed!');
								}
								if ($s3Status == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
										/*rmdir($uploadFile . $d);*/
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if ($WasStatus == '1') {
                                    $publicAccessErrorShown = false; // prevent duplicate error messages

                                    // Upload main thumbnail
                                    $thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                    $key = basename($thevTumbnail);

                                    try {
                                        $result = $s3->putObject([
                                            'Bucket' => $WasBucket,
                                            'Key'    => 'uploads/files/' . $d . '/' . $key,
                                            'Body'   => fopen($thevTumbnail, 'r'),
                                            'CacheControl' => 'max-age=3153600',
                                            // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                        ]);
                                        $UploadSourceUrl = $result->get('ObjectURL');
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        // rmdir($uploadFile . $d);
                                    } catch (Aws\S3\Exception\S3Exception $e) {
                                        $awsMsg = $e->getAwsErrorMessage();
                                        echo "There was an error uploading the file: $awsMsg<br>";
                                        if (!$publicAccessErrorShown && str_contains($awsMsg, 'Public use of objects is not allowed')) {
                                            echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                            $publicAccessErrorShown = true;
                                        }
                                    }

                                    // Upload pixel version
                                    $thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                    $key = basename($thevTumbnail);

                                    try {
                                        $result = $s3->putObject([
                                            'Bucket' => $WasBucket,
                                            'Key'    => 'uploads/pixel/' . $d . '/' . $key,
                                            'Body'   => fopen($thevTumbnail, 'r'),
                                            'CacheControl' => 'max-age=3153600',
                                            // 'ACL' => 'public-read',is intentionally excluded for compatibility
                                        ]);
                                        $UploadSourceUrl = $result->get('ObjectURL');
                                        @unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
                                        // rmdir($xImages . $d);
                                    } catch (Aws\S3\Exception\S3Exception $e) {
                                        $awsMsg = $e->getAwsErrorMessage();
                                        echo "There was an error uploading the file: $awsMsg<br>";
                                        if (!$publicAccessErrorShown && str_contains($awsMsg, 'Public use of objects is not allowed')) {
                                            echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This usually occurs with trial accounts. Please remove 'public-read' settings or contact Wasabi support.</div>";
                                            $publicAccessErrorShown = true;
                                        }
                                    }

                                }else if ($digitalOceanStatus == '1') {
                                	// Initialize the DigitalOcean Spaces connection
                                	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);

                                	// 1. Upload the original file
                                	$localPath1 = '../uploads/files/' . $d . '/' . $getFilename;
                                	$remoteKey1 = 'uploads/files/' . $d . '/' . $getFilename;
                                	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                                	// 2. Upload the .mp4 video file
                                	$localPath2 = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                	$remoteKey2 = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                	$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                                	// 3. Upload the .png thumbnail file
                                	$localPath3 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.png';
                                	$remoteKey3 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.png';
                                	$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                                	// If upload was successful, generate the public URL and delete local temp file
                                	if ($upload) {
                                		$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey3;
                                		@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                	}
                                } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							/**/
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedMessageFiles($userID, $conID, $pathFile, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedMessageFilesIDs($userID, $pathFile);
							/*AMAZON S3*/
							echo iN_HelpSecure($getUploadedFileID['upload_id']) . ',';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}

/*Load More Messages*/
	if ($type == 'moreMessage') {
		if (isset($_POST['ch']) && isset($_POST['last'])) {
			$chatID = mysqli_real_escape_string($db, $_POST['ch']);
			$lastMessageID = mysqli_real_escape_string($db, $_POST['last']);
			$conversationData = $iN->iN_GetChatMessages($userID, $chatID, $lastMessageID, $scrollLimit);
			include "../themes/$currentTheme/layouts/chat/loadMoreMessages.php";
		}
	}
/*Get new Message*/
	if ($type == 'getNewMessage') {
		if (isset($_POST['ci']) && isset($_POST['to']) && isset($_POST['lm'])) {
			$conversationID = mysqli_real_escape_string($db, $_POST['ci']);
			$toUser = mysqli_real_escape_string($db, $_POST['to']);
			$lastMessage = mysqli_real_escape_string($db, $_POST['lm']);
			$insertData = $iN->iN_GetUserNewMessage($userID, $conversationID, $toUser, $lastMessage);
			/**/
			if ($insertData) {
				$cMessageID = $insertData['con_id'];
				$cUserOne = $insertData['user_one'];
				$cUserTwo = $insertData['user_two'];
				$cMessage = $insertData['message'];
				$mSeenStatus = $insertData['seen_status'];
				$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
				$privateStatus = isset($insertData['private_status']) ? $insertData['private_status'] : NULL;
				$privatePrice = isset($insertData['private_price']) ? $insertData['private_price'] : NULL;
				$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
				$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
				$cMessageTime = $insertData['time'];
				$ip = $iN->iN_GetIPAddress();
				$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
				if ($query && $query['status'] == 'success') {
					date_default_timezone_set($query['timezone']);
				}
				$message_time = date("c", $cMessageTime);
				$convertMessageTime = strtotime($message_time);
				$netMessageHour = date('H:i', $convertMessageTime);
				$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
				$msgDots = '';
				$imStyle = '';
				$seenStatus = '';
				if ($cUserOne == $userID) {
					$mClass = 'me';
					$msgOwnerID = $cUserOne;
					$lastM = '';
					$timeStyle = 'msg_time_me';
					if (!empty($cFile)) {
						$imStyle = 'mmi_i';
					}
					$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					if ($mSeenStatus == '1') {
						$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					}
					if($gifMoney){
                        $SGifMoneyText = preg_replace( '/{.*?}/', $cMessage, $LANG['youSendGifMoney']);
                    }
				} else {
					$mClass = 'friend';
					$msgOwnerID = $cUserOne;
					$lastM = 'mm_' . $msgOwnerID;
					if (!empty($cFile)) {
						$imStyle = 'mmi_if';
					}
					if($gifMoney){
                        $msgOwnerFullName = $iN->iN_UserFullName($msgOwnerID);
                        $SGifMoneyText = $iN->iN_TextReaplacement($LANG['sendedGifMoney'],[$msgOwnerFullName , $cMessage]);
                    }
					$timeStyle = 'msg_time_fri';
				}
				$styleFor = '';
				if ($cStickerUrl) {
					$styleFor = 'msg_with_sticker';
					$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
				}
				if ($cGifUrl) {
					$styleFor = 'msg_with_gif';
					$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
				}
				$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
				if($privatePrice && $privateStatus == 'closed' && $mClass != 'me'){
                    include "../themes/$currentTheme/layouts/chat/privateMessage.php";
				}else{
					include "../themes/$currentTheme/layouts/chat/newMessage.php";
				}
			}
			/**/
		}
	}
/*Send User Typing*/
	if ($type == 'utyping') {
		if (isset($_POST['ci']) && isset($_POST['to'])) {
			$conversationID = mysqli_real_escape_string($db, $_POST['ci']);
			$toUserID = mysqli_real_escape_string($db, $_POST['to']);
			$time = time() . '_' . $userID;
			$updateTypingStatus = $iN->iN_UpdateTypingStatus($userID, $conversationID, $time);
		}
	}
/*Check Typeing*/
	if ($type == 'typing') {
		if (isset($_POST['ci']) && isset($_POST['to']) && $_POST['ci'] !== '' && $_POST['to'] !== '' && !empty($_POST['ci']) && !empty($_POST['to'])) {
			$conversationID = mysqli_real_escape_string($db, $_POST['ci']);
			$toUser = mysqli_real_escape_string($db, $_POST['to']);
			$getTypingStatus = $iN->iN_GetTypingStatus($toUser, $conversationID);
			$messageSeenStatus = $iN->iN_CheckLastMessageSeenOrNot($conversationID, $toUser, $userID);
			$iN->iN_UpdateMessageSeenStatus($conversationID, $toUser, $userID);
			$beforeUnderscore = substr($getTypingStatus, 0, strpos($getTypingStatus, "_"));
			$afterUnderscore = substr($getTypingStatus, strrpos($getTypingStatus, '_') + 1);
			$ip = $iN->iN_GetIPAddress();
			$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
			if ($query && $query['status'] == 'success') {
				date_default_timezone_set($query['timezone']);
			}
			$getToUserData = $iN->iN_GetUserDetails($toUser);
			$toUserLastLoginTime = $getToUserData['last_login_time'];
			$lastSeen = date("c", $toUserLastLoginTime);
			$OnlineStatus = date("c", $toUserLastLoginTime);
			/*10 Second Ago for Typing*/
			$SecondBefore = time() - 10;
			/*180 Second Ago for Online - Offline Status*/
			$oStatus = time() - 35;
			$timeStatus = '';
			if ($afterUnderscore != $userID) {
				if ($beforeUnderscore > $SecondBefore) {
					$timeStatus = $LANG['typing'];
				} else {
					if ($toUserLastLoginTime > $oStatus) {
						$timeStatus = $LANG['online'];
					} else {
						$timeStatus = $LANG['last_seen'] . date('H:i', strtotime($OnlineStatus));
					}
				}
			} else {
				$timeStatus = $LANG['last_seen'] . date('H:i', strtotime($OnlineStatus));
			}
			$data = array(
				'timeStatus' => $timeStatus,
				'seenStatus' => $messageSeenStatus,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
	if ($type == 'allPosts' || $type == 'moreexplore' || $type == 'premiums' || $type == 'morepremium' || $type == 'friends' || $type == 'morepurchased' || $type == 'purchasedpremiums' || $type == 'moreboostedposts' || $type == 'boostedposts' || $type == 'trendposts' || $type == 'moretrendposts') {
		$page = $type;
		include "../themes/$currentTheme/layouts/posts/htmlPosts.php";
	}
	if ($type == 'creators') {
		if (isset($_POST['last']) && isset($_POST['p'])) {
			$pageCreator = mysqli_real_escape_string($db, $_POST['p']);
			$lastPostID = mysqli_real_escape_string($db, $_POST['last']);
			include "../themes/$currentTheme/layouts/loadmore/moreCreator.php";
		}
	}
/*More Comment*/
	if ($type == 'moreComment') {
		if (isset($_POST['id'])) {
			$userPostID = mysqli_real_escape_string($db, $_POST['id']);
			$getUserComments = $iN->iN_GetPostComments($userPostID, 0);
			if ($getUserComments) {
				foreach ($getUserComments as $comment) {
					$commentID = $comment['com_id'];
					$commentedUserID = $comment['comment_uid_fk'];
					$Usercomment = $comment['comment'];
					$commentTime = isset($comment['comment_time']) ? $comment['comment_time'] : NULL;
					$corTime = date('Y-m-d H:i:s', $commentTime);
					$commentFile = isset($comment['comment_file']) ? $comment['comment_file'] : NULL;
					$stickerUrl = isset($comment['sticker_url']) ? $comment['sticker_url'] : NULL;
					$gifUrl = isset($comment['gif_url']) ? $comment['gif_url'] : NULL;
					$commentedUserIDFk = isset($comment['iuid']) ? $comment['iuid'] : NULL;
					$commentedUserName = isset($comment['i_username']) ? $comment['i_username'] : NULL;
					$commentedUserFullName = isset($comment['i_user_fullname']) ? $comment['i_user_fullname'] : NULL;
					$commentedUserAvatar = $iN->iN_UserAvatar($commentedUserID, $base_url);
					$commentedUserGender = isset($comment['user_gender']) ? $comment['user_gender'] : NULL;
					if ($commentedUserGender == 'male') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'female') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					} else if ($commentedUserGender == 'couple') {
						$cpublisherGender = '<div class="i_plus_comment_g">' . $iN->iN_SelectedMenuIcon('12') . '</div>';
					}
					$commentedUserLastLogin = isset($comment['last_login_time']) ? $comment['last_login_time'] : NULL;
					$commentedUserVerifyStatus = isset($comment['user_verified_status']) ? $comment['user_verified_status'] : NULL;
					$cuserVerifiedStatus = '';
					if ($commentedUserVerifyStatus == '1') {
						$cuserVerifiedStatus = '<div class="i_plus_comment_s">' . $iN->iN_SelectedMenuIcon('11') . '</div>';
					}
					$commentLikeBtnClass = 'c_in_like';
					$commentLikeIcon = $iN->iN_SelectedMenuIcon('17');
					$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['report_comment'];
					if ($logedIn != 0) {
						$checkCommentLikedBefore = $iN->iN_CheckCommentLikedBefore($userID, $userPostID, $commentID);
						$checkCommentReportedBefore = $iN->iN_CheckCommentReportedBefore($userID, $commentID);
						if ($checkCommentLikedBefore == '1') {
							$commentLikeBtnClass = 'c_in_unlike';
							$commentLikeIcon = $iN->iN_SelectedMenuIcon('18');
						}
						if ($checkCommentReportedBefore == '1') {
							$commentReportStatus = $iN->iN_SelectedMenuIcon('32') . $LANG['unreport'];
						}
					}
					$stickerComment = '';
					$gifComment = '';
					if ($stickerUrl) {
						$stickerComment = '<div class="comment_file"><img src="' . $stickerUrl . '"></div>';
					}
					if ($gifUrl) {
						$gifComment = '<div class="comment_gif_file"><img src="' . $gifUrl . '"></div>';
					}
					$checkUserIsCreator = $iN->iN_CheckUserIsCreator($commentedUserID);
					$cUType = '';
					if($checkUserIsCreator){
						$cUType = '<div class="i_plus_public" id="ipublic_'.$commentedUserID.'">'.$iN->iN_SelectedMenuIcon('9').'</div>';
					}
					include "../themes/$currentTheme/layouts/posts/comments.php";
				}
			}
		}
	}
	if ($type == 'searchCreator') {
		if (isset($_POST['s'])) {
			$searchValue = mysqli_real_escape_string($db, $_POST['s']);
			$searchValueFromData = $iN->iN_GetSearchResult($iN->iN_Secure($searchValue), $showingNumberOfPost, $whicUsers);
			include "../themes/$currentTheme/layouts/header/searchResults.php";
		}
	}
/*Create new Conversation*/
	if ($type == 'newMessageMe') {
		if (isset($_POST['user'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['user']);
			$checkUserExist = $iN->iN_CheckUserExist($iuID);
			if ($checkUserExist) {
				$getToUserData = $iN->iN_GetUserDetails($iuID);
				$f_userfullname = $getToUserData['i_user_fullname'];
				$f_userAvatar = $iN->iN_UserAvatar($iuID, $base_url);
				$checkConversationStartedBeforeBetweenTheseUsers = $iN->iN_CheckConversationStartedBeforeBetweenUsers($userID, $iuID);
				if (empty($checkConversationStartedBeforeBetweenTheseUsers) || $checkConversationStartedBeforeBetweenTheseUsers = '' || !isset($checkConversationStartedBeforeBetweenTheseUsers)) {
					include "../themes/$currentTheme/layouts/popup_alerts/createMessage.php";
				}
			}
		}
	}
/*Createa New First Message Between Two User*/
	if ($type == 'newfirstMessage') {
		if (isset($_POST['u']) && isset($_POST['fm'])) {
			$user = mysqli_real_escape_string($db, $_POST['u']);
			$firstMessage = mysqli_real_escape_string($db, $_POST['fm']);
			if (empty($firstMessage) || $firstMessage == '' || !isset($firstMessage) || strlen(trim($firstMessage)) == 0) {
				exit('404');
			}
			$insertNewMessageAndCreateConversation = $iN->iN_CreateConverationAndInsertFirstMessage($userID, $user, $iN->iN_Secure($firstMessage));
			if ($insertNewMessageAndCreateConversation) {
				echo iN_HelpSecure($base_url) . 'chat?chat_width=' . $insertNewMessageAndCreateConversation;
				$userDeviceKey = $iN->iN_GetuserDetails($user);
				$toUserName = $userDeviceKey['i_username'];
				$oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
				$msgTitle = $iN->iN_Secure($LANG['you_have_a_new_message']);
				$msgBody = $iN->iN_Secure($LANG['click_to_continue_conversation']);
				$URL = iN_HelpSecure($base_url) . 'chat?chat_width=' . $insertNewMessageAndCreateConversation;
				if($oneSignalUserDeviceKey){
				  $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
				}
			} else {
				echo '404';
			}
		}
	}
/*Update Dark to Light or Light to Dark*/
	if ($type == 'updateTheme') {
		if (isset($_POST['theme']) && in_array($_POST['theme'], $themes)) {
			$uTheme = mysqli_real_escape_string($db, $_POST['theme']);
			$updateTheme = $iN->iN_UpdateUserTheme($userID, $uTheme);
			if ($updateTheme) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Get Fixed Mobile Footer Menu*/
	if ($type == 'fixedMenu') {
		include "../themes/$currentTheme/layouts/widgets/mobileFixedMenu.php";
	}
/*Delete Message*/
	if ($type == 'deleteMessage') {
		if (isset($_POST['id']) && isset($_POST['cid'])) {
			$messageID = mysqli_real_escape_string($db, $_POST['id']);
			$conversationID = mysqli_real_escape_string($db, $_POST['cid']);
			$deleteMessage = $iN->iN_DeleteMessageFromData($userID, $messageID, $conversationID);
			if ($deleteMessage) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Delete Conversion*/
	if ($type == 'deleteConversation') {
		if (isset($_POST['id']) && isset($_POST['cid'])) {
			$messageID = mysqli_real_escape_string($db, $_POST['id']);
			$conversationID = mysqli_real_escape_string($db, $_POST['cid']);
			$deleteMessage = $iN->iN_DeleteConversationFromData($userID, $conversationID);
			if ($deleteMessage) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Search User From Chat*/
	if ($type == 'searchUser') {
		if (isset($_POST['key'])) {
			$sKey = mysqli_real_escape_string($db, $_POST['key']);
			$searchUser = $iN->iN_SearchChatUsers($userID, $iN->iN_Secure($sKey));
			if ($searchUser) {
				foreach ($searchUser as $sResult) {
					$resultUserID = $sResult['iuid'];
					$resultUserName = $sResult['i_username'];
					$resultUserFullName = $sResult['i_user_fullname'];
					$profileUrl = $base_url . $resultUserName;
					$resultUserAvatar = $iN->iN_UserAvatar($resultUserID, $base_url);
					include "../themes/$currentTheme/layouts/chat/chatSearch.php";
				}
			}
		}
	}
/*Hide Notification*/
	if ($type == 'hideNotification') {
		if (isset($_POST['id'])) {
			$hideID = mysqli_real_escape_string($db, $_POST['id']);
			$hideNot = $iN->iN_HideNotification($userID, $hideID);
			if ($hideNot) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*UN Block User*/
	if ($type == 'unblock') {
		if (isset($_POST['id']) && isset($_POST['u'])) {
			$unBlockID = mysqli_real_escape_string($db, $_POST['id']);
			$unBlockUserID = mysqli_real_escape_string($db, $_POST['u']);
			$unBlock = $iN->iN_UnBlockUser($userID, $unBlockID, $unBlockUserID);
			if ($unBlock) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
/*Edit May Page*/
	if ($type == 'editMyPass') {
		$currentPassword = mysqli_real_escape_string($db, $_POST['crn_password']);
		$newPassword = mysqli_real_escape_string($db, $_POST['nw_password']);
		$confirmNewPassword = mysqli_real_escape_string($db, $_POST['confirm_pass']);
		if (!empty($currentPassword) && $currentPassword != '' && isset($currentPassword)) {
			$userCurrentPass = $iN->iN_GetUserDetails($userID);
			$passUser = $userCurrentPass['i_password'];
			if (preg_match('/\s/', $currentPassword) || preg_match('/\s/', $newPassword) || preg_match('/\s/', $confirmNewPassword)) {
				exit('6');
			}
			if (sha1(md5($currentPassword)) != $passUser) {
				exit('1');
			}
			if (strlen($newPassword) < 6 || strlen($confirmNewPassword) < 6 || strlen($currentPassword) < 6) {
				exit('5');
			}
			if (!empty($newPassword) && $newPassword != '' && isset($newPassword) && !empty($confirmNewPassword) && $confirmNewPassword != '' && isset($confirmNewPassword)) {
				if ($newPassword != $confirmNewPassword) {
					exit('2');
				} else {
					$newPassword = sha1(md5($newPassword));
					$updateNewPassword = $iN->iN_UpdatePassword($userID, $iN->iN_Secure($newPassword));
					if ($updateNewPassword) {
						echo iN_HelpSecure($base_url) . 'logout.php';
					} else {
						exit('404');
					}
				}
			} else {
				exit('4');
			}
		} else {
			exit('3');
		}
	}
/*Update Preferences*/
	if ($type == 'p_preferences') {
		if (isset($_POST['notit']) && isset($_POST['sType'])) {
			$setValue = mysqli_real_escape_string($db, $_POST['notit']);
			$setType = mysqli_real_escape_string($db, $_POST['sType']);
			if ($setType == 'email_not') {
				$updateEmailStatus = $iN->iN_UpdateEmailNotificationStatus($userID, $setValue);
				if ($updateEmailStatus) {
					echo '200';
				} else {
					echo '404';
				}
			} else if ($setType == 'message_not') {
				$updateMessageStatus = $iN->iN_UpdateMessageSendStatus($userID, $setValue);
				if ($updateMessageStatus) {
					echo '200';
				} else {
					echo '404';
				}
			} else if ($setType == 'show_hide_profile') {
				$updateShowHideProfile = $iN->iN_UpdateShowHidePostsStatus($userID, $setValue);
				if ($updateShowHideProfile) {
					echo '200';
				} else {
					echo '404';
				}
			} else if($setType == 'who_send_message_not'){
				$updateWhoCanSendMessage = $iN->iN_UpdateWhoCanSendYouAMessage($userID, $setValue);
				if ($updateWhoCanSendMessage) {
					echo '200';
				} else {
					echo '404';
				}
			}
		}
	}
/*Call Paid Live Streaming Box*/
	if ($type == 'paidLive') {
		$liveStreamNotForNonCreators = '<div class="ll_live_not flex_ alignItem">' . html_entity_decode($iN->iN_SelectedMenuIcon('32')) . ' ' . iN_HelpSecure($LANG['only_creators_']) . '</div>';
		if ($certificationStatus == '2' && $validationStatus == '2' && $conditionStatus == '2') {
			include "../themes/$currentTheme/layouts/popup_alerts/createaPaidLiveStreaming.php";
		} else {
			$currentTime = time();
			$finishTime = $currentTime + 60 * $freeLiveTime;
			$l_Time = $iN->iN_GetLastLiveFinishTime($userID);
			include "../themes/$currentTheme/layouts/popup_alerts/createaFreeLiveStreaming.php";
		}
	}
/*Call Free Live Streaming Box*/
	if ($type == 'freeLive') {
		$currentTime = time();
		$finishTime = $currentTime + 60 * $freeLiveTime;
		$l_Time = $iN->iN_GetLastLiveFinishTime($userID);
		$liveStreamNotForNonCreators = '';
		include "../themes/$currentTheme/layouts/popup_alerts/createaFreeLiveStreaming.php";
	}
/*Create a Free Live Streaming*/
	if ($type == 'createFreeLiveStream') {
		if (isset($_POST['lTitle']) && !empty($_POST['lTitle'])) {
			$liveStreamingTitle = mysqli_real_escape_string($db, $_POST['lTitle']);
			$rand = rand(1111111, 9999999);
			$channelName = "stream_" . $userID . "_" . $rand;
			if (strlen($liveStreamingTitle) < 5 || strlen($liveStreamingTitle) > 32) {
				$data = array(
					'status' => '4',
					'start' => '',
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			$createFreeLiveStreaming = $iN->iN_CreateAFreeLiveStreaming($userID, $liveStreamingTitle, $freeLiveTime, $channelName);
			if ($createFreeLiveStreaming) {
				if ($s3Status == 1) {
					//$rect = $iN->iN_StartCloudRecording(1, $s3Region, $s3Bucket, $s3Key, $s3SecretKey, $streamingName, $uid, $liveID, $agoraAppID, $agoraCustomerID, $agoraCertificate);
				}
				$data = array(
					'status' => '200',
					'start' => $base_url . 'live/' . $userName,
				);
				$result = json_encode($data, JSON_UNESCAPED_UNICODE);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			} else {
				$data = array(
					'status' => '404',
					'start' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
		} else {
			$data = array(
				'status' => 'require',
				'start' => '',
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
	}
	if ($type == 'l_like') {
		if (isset($_POST['post'])) {
			$postID = mysqli_real_escape_string($db, $_POST['post']);
			$likePost = $iN->iN_LiveLike($userID, $postID);
			$status = 'lin_like';
			$pLike = $iN->iN_SelectedMenuIcon('17');
			if ($likePost) {
				$status = 'lin_unlike';
				$pLike = $iN->iN_SelectedMenuIcon('18');
			}
			$likeSum = $iN->iN_TotalLiveLiked($postID);
			if ($likeSum == 0) {
				$likeSum = '';
			} else {
				$likeSum = $likeSum;
			}
			$data = array(
				'status' => $status,
				'like' => $pLike,
				'likeCount' => $likeSum,
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		}
	}
/*Create a Free Live Streaming*/
	if ($type == 'createPaidLiveStream') {
		if (isset($_POST['lTitle']) && !empty($_POST['lTitle']) && isset($_POST['pointfee']) && !empty($_POST['pointfee'])) {
			$liveStreamingTitle = mysqli_real_escape_string($db, $_POST['lTitle']);
			$liveStreamFee = mysqli_real_escape_string($db, $_POST['pointfee']);
			$rand = rand(1111111, 9999999);
			$channelName = "stream_" . $userID . "_" . $rand;
			if (empty($liveStreamFee) || $liveStreamFee < $minimumLiveStreamingFee) {
				$data = array(
					'status' => 'point',
					'start' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
			if ($certificationStatus == '2' && $validationStatus == '2' && $conditionStatus == '2') {
				$createPaidLiveStreaming = $iN->iN_CreateAPaidLiveStreaming($userID, $liveStreamingTitle, $freeLiveTime, $channelName, $liveStreamFee);
				if ($createPaidLiveStreaming) {
					$data = array(
						'status' => '200',
						'start' => $base_url . 'live/' . $userName,
					);
					$result = json_encode($data, JSON_UNESCAPED_UNICODE);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				} else {
					$data = array(
						'status' => '404',
						'start' => '',
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
					exit();
				}
			} else {
				$data = array(
					'status' => '404',
					'start' => '',
				);
				$result = json_encode($data);
				echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				exit();
			}
		} else {
			$data = array(
				'status' => 'require',
				'start' => '',
			);
			$result = json_encode($data);
			echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
			exit();
		}
	}
/*Purchase Post*/
	if ($type == 'goWalletLive') {
		if (isset($_POST['p']) && isset($_POST['p'])) {
			$purchaseLiveStreamID = mysqli_real_escape_string($db, $_POST['p']);
			$checkLiveID = $iN->iN_CheckLiveIDExist($purchaseLiveStreamID);
			if ($checkLiveID) {
				$liveDetails = $iN->iN_GetLiveStreamingDetailsByID($purchaseLiveStreamID);
				$liveID = $liveDetails['live_id'];
				$liveCreatorWantedCredit = $liveDetails['live_credit'];
				$liveCreator = $liveDetails['live_uid_fk'];
				$liveCreatorDetail = $iN->iN_GetUserDetails($liveCreator);
				$liveCreatorUserName = $liveCreatorDetail['i_username'];

				$translatePointToMoney = $liveCreatorWantedCredit * $onePointEqual;
				$adminEarning = $translatePointToMoney * ($adminFee / 100);
				$userEarning = $translatePointToMoney - $adminEarning;

				if ($userCurrentPoints >= $liveCreatorWantedCredit && $userID != $liveCreator) {
					$buyLiveStream = $iN->iN_BuyLiveStreaming($userID, $liveCreator, $liveID, $translatePointToMoney, $adminEarning, $userEarning, $adminFee, $liveCreatorWantedCredit);
					if ($buyLiveStream) {
						echo iN_HelpSecure($base_url) . 'live/' . $liveCreatorUserName;
					} else {
						echo 'Something wrong';
					}
				} else {
					echo iN_HelpSecure($base_url) . 'purchase/purchase_point';
				}
			}
		}
	}
/*More Paid Live Streamins or Free Paid Live Streamins*/
	if ($type == 'paid' || $type == 'free') {
		if (isset($_POST['last'])) {
			$liveListType = $type;
			include "../themes/$currentTheme/layouts/live/live_list.php";
		}
	}
	if ($type == 'pLivePurchase') {
		if (isset($_POST['purchase']) && $_POST['purchase'] != '' && !empty($_POST['purchase'])) {
			$liveID = mysqli_real_escape_string($db, $_POST['purchase']);
			$checkliveExist = $iN->iN_CheckLiveIDExist($liveID);
			if ($checkliveExist) {
				$liData = $iN->iN_GetLiveStreamingDetailsByID($liveID);
				$liveCreatorID = $liData['live_uid_fk'];
				$liveCreatorAvatar = $iN->iN_UserAvatar($liveCreatorID, $base_url);
				$liveCredit = isset($liData['live_credit']) ? $liData['live_credit'] : NULL;
				if ($userID != $liveCreatorID) {
					include "../themes/$currentTheme/layouts/popup_alerts/purchaseLiveStream.php";
				}
			}
		}
	}
	if ($type == 'unSub') {
		if (isset($_POST['u']) && !empty($_POST['u'])) {
			$ui = mysqli_real_escape_string($db, $_POST['u']);
			$checkUserExist = $iN->iN_CheckUserExist($ui);
			$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $ui);
			if ($friendsStatus == 'subscriber') {
				include "../themes/$currentTheme/layouts/popup_alerts/sureUnSubscribe.php";
			}
		}
	}
	if ($type == 'unSubscribe') {
		if (isset($_POST['id'])) {
			$uID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				if ($uID != $userID) {
					$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
					$status = '404';
					$redirect = $base_url . 'settings?tab=subscriptions';
					if ($friendsStatus == 'subscriber') {
						if($subscriptionType == '1'){
							\Stripe\Stripe::setApiKey($stripeKey);
							$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);

							$subscription = \Stripe\Subscription::retrieve($paymentSubscriptionID);
							$subscription->cancel();
							$iN->iN_UnSubscriberUser($userID, $uID,$unSubscribeStyle);
							$status = '200';
						}else if($subscriptionType == '3'){
                            include_once("../includes/authorizeCancelSubs.php");
							$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
							$paymentSubscriptionID = $getSubsData['payment_subscription_id'];
							$subscriptionID = $getSubsData['subscription_id'];
							$iN->iN_UpdateSubscriptionStatus($subscriptionID);
							$iN->iN_UnSubscriberUser($userID, $uID,$unSubscribeStyle);
							if(!defined('DONT_RUN_SAMPLES'))
                            cancelSubscription($paymentSubscriptionID,$autName, $autKey);
						}
					}
					$data = array(
						'status' => $status,
						'redirect' => $redirect,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				}
			}
		}
	}
	if ($type == 'unSubP') {
		if (isset($_POST['u']) && !empty($_POST['u'])) {
			$ui = mysqli_real_escape_string($db, $_POST['u']);
			$checkUserExist = $iN->iN_CheckUserExist($ui);
			$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $ui);
			if ($friendsStatus == 'subscriber') {
				include "../themes/$currentTheme/layouts/popup_alerts/sureUnSubscribePoint.php";
			}
		}
	}
	if ($type == 'unSubscribePoint') {
		if (isset($_POST['id'])) {
			$uID = mysqli_real_escape_string($db, $_POST['id']);
			$checkUserExist = $iN->iN_CheckUserExist($uID);
			if ($checkUserExist) {
				if ($uID != $userID) {
					$friendsStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $uID);
					$status = '404';
					$redirect = $base_url . 'settings?tab=subscriptions';
					if ($friendsStatus == 'subscriber') {
						$getSubsData = $iN->iN_GetSubscribeID($userID, $uID);
						$subscriptionID = $getSubsData['subscription_id'];
						$iN->iN_UpdateSubscriptionStatus($subscriptionID);
						$iN->iN_UnSubscriberUser($userID, $uID,$unSubscribeStyle);
						$status = '200';
					}
					$data = array(
						'status' => $status,
						'redirect' => $redirect,
					);
					$result = json_encode($data);
					echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
				}
			}
		}
	}
	/*Finish Live Streaming*/
	if($type == 'finishLive'){
      if(isset($_POST['lid']) && !empty($_POST['lid']) && $_POST['lid'] != ''){
         $liveID = mysqli_real_escape_string($db, $_POST['lid']);
		 $finishLiveStreaming = $iN->iN_FinishLiveStreaming($userID, $liveID);
		 if($finishLiveStreaming){
             echo 'finished';
		 }
	  }
	}
	/*Block Country*/
	if($type == 'bCountry'){
      if(isset($_POST['c']) && array_key_exists($_POST['c'],$COUNTRIES)){
         $blockingCountryCode = mysqli_real_escape_string($db, $_POST['c']);
		 $checkCountryCodeBlockedBefore = $iN->iN_CheckCountryBlocked($userID, $blockingCountryCode);
		 if(!$checkCountryCodeBlockedBefore){
            $insertCountryCodeInBlockedList = $iN->iN_InsertCountryInBlockList($userID, $iN->iN_Secure($blockingCountryCode));
			if($insertCountryCodeInBlockedList){
              echo '1';
			}
		 }else{
			$removeCountryCodeInBlockedList = $iN->iN_RemoveCountryInBlockList($userID, $iN->iN_Secure($blockingCountryCode));
			if($removeCountryCodeInBlockedList){
              echo '0';
			}
		 }
	  }
	}
	/*Open Tip Box*/
	if($type == 'p_tips'){
		if(isset($_POST['tip_u']) && !empty($_POST['tip_u']) && $_POST['tip_u'] !== ''){
			$tipingUserID = mysqli_real_escape_string($db, $_POST['tip_u']);
			$tipPostID = mysqli_real_escape_string($db, $_POST['tpid']);
            $tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
			$f_userfullname = $tipingUserDetails['i_user_fullname'];
			include "../themes/$currentTheme/layouts/popup_alerts/sendTipPoint.php";
		}
	}
	/*Send Tip*/
	if($type == 'p_sendTip'){
      if(isset($_POST['tip_u']) && isset($_POST['tipVal']) && $_POST['tip_u'] != '' &&  $_POST['tipVal'] != '' && !empty($_POST['tip_u']) && !empty($_POST['tipVal'])){
         $tiSendingUserID = mysqli_real_escape_string($db, $_POST['tip_u']);
		 $tipAmount = mysqli_real_escape_string($db, $_POST['tipVal']);
		 $tipPostID = mysqli_real_escape_string($db, $_POST['tpid']);
		 $redirect = '';
		 $emountnot = '';
		 $status = '';
		 if($tipAmount < $minimumTipAmount){
            $emountnot = 'notEnough';
		 }else{
			if ($userCurrentPoints >= $tipAmount && $userID != $tiSendingUserID) {

				$netUserEarning = $tipAmount * $onePointEqual;
                $adminEarning = ($adminFee * $netUserEarning) / 100;
				$userNetEarning = $netUserEarning - $adminEarning;

				$UpdateUsersWallet = $iN->iN_UpdateUsersWallets($userID, $tiSendingUserID, $tipAmount, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
				if($UpdateUsersWallet){
                   $status = 'ok';
				}else{
				   $status = '404';
				}
			 }else{
				$status = '';
				$emountnot = 'notEnouhCredit';
				$redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
			 }
		 }
		 $data = array(
			'status' => $status,
			'redirect' => $redirect,
			'enamount' => $emountnot
		 );
		 $result = json_encode($data);
		 echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		 if($status == 'ok'){
			$userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
			$toUserName = $userDeviceKey['i_username'];
			$oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
			$msgBody = $iN->iN_Secure($LANG['send_you_a_tip']);
			$msgTitle = $iN->iN_Secure($LANG['tip_earning']).$currencys[$defaultCurrency]. $netUserEarning;
			$URL = $base_url.'settings?tab=dashboard';
			if($oneSignalUserDeviceKey){
			  $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
			}
		 }
	  }
	}
	/*Coin Payment*/
	if($type == 'cop'){
      if(isset($_POST['p']) && !empty($_POST['p']) && $_POST['p'] != ''){
         $pointTypeID = mysqli_real_escape_string($db, $_POST['p']);
		 $planData = $iN->GetPlanDetails($pointTypeID);
		 $planAmount = isset($planData['amount']) ? $planData['amount'] : NULL;
		 $planPoint = isset($planData['plan_amount']) ? $planData['plan_amount'] : NULL;
		 if($planAmount){
			require_once('../includes/coinPayment/vendor/autoload.php');
            $currency1 = $defaultCurrency;
			$currency2 = $coinPaymentCryptoCurrency;
			try {
				$cps_api = new CoinpaymentsAPI($coinPaymentPrivateKey, $coinPaymentPublicKey, 'json');
				$information = $cps_api->GetBasicInfo();
				$ipn_url = $base_url.'purchase/purchase_point';
				$cancelUrl = $base_url.'purchase/purchase_point';
				$payBtc = $cps_api->CreateSimpleTransactionWithConversion($planAmount, $currency1, $currency2, $userEmail, $ipn_url, $cancelUrl);
				$txnID = isset($payBtc['result']['txn_id']) ? $payBtc['result']['txn_id'] : NULL;
				$time = time();
				if($txnID){
					$query = mysqli_query($db,"INSERT INTO i_user_payments(payer_iuid_fk,order_key,payment_type,payment_option,payment_time,payment_status,credit_plan_id)VALUES('$userID','$txnID','point','coinpayment','$time','pending','$pointTypeID')") or die(mysqli_error($db));
				}else{
					exit('Check your coinpayment settings from your coinpayment dashboard.');
				}

			} catch (Exception $e) {
				echo 'Error: ' . $e->getMessage();
				exit();
			}
			if ($information['error'] == 'ok') {
				$redirectUrl = $payBtc['result']['checkout_url'];
				$status = '200';
			}else{
				$redirectUrl = '';
				$status = '404';
			}
			$data = array(
				'status' => $status,
				'redirect' => $redirectUrl
			 );
			 $result = json_encode($data);
			 echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
		 }
	  }
	}
	if ($type == 'subscribeMeAut') {
		if (isset($_POST['u']) && isset($_POST['pl']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['card'])) {
			$iuID = mysqli_real_escape_string($db, $_POST['u']);
			$planID = mysqli_real_escape_string($db, $_POST['pl']);
			$subscriberName = mysqli_real_escape_string($db, $_POST['name']);
			$subscriberEmail = mysqli_real_escape_string($db, $_POST['email']);
			$creditCardNumber = mysqli_real_escape_string($db, $_POST['card']);
			$expMonth = mysqli_real_escape_string($db, $_POST['exm']);
			$expYear = mysqli_real_escape_string($db, $_POST['exy']);
			$CardCCV = mysqli_real_escape_string($db, $_POST['cccv']);
			$planDetails = $iN->iN_CheckPlanExist($planID, $iuID);
			$expiredData = $expYear.'-'.$expMonth;
			$payment_id = $statusMsg = $api_error = '';
			if ($planDetails) {
				$planType = $planDetails['plan_type'];
				$amount = $planDetails['amount'];
				$planCurrency = $autHorizePaymentCurrency;
				$adminEarning = ($adminFee * $amount) / 100;
				$userNetEarning = $amount - $adminEarning;
				$subscriptionCompleted = $LANG['subscription_description_authorize'];
				$payment_Type = 'authorizenet';
				$planIntervalCount = '1';
				if ($planType == 'weekly') {
					$planName = 'Weekly Subscription';
					$planInterval = 'week';
					$intervalLength = '7';
					$interval_dmy = 'days';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s");
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+7 days'));
				} else if ($planType == 'monthly') {
					$planName = 'Monthly Subscription';
					$planInterval = 'month';
					$intervalLength = '30';
					$interval_dmy = 'days';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s");
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 month'));
				} else if ($planType == 'yearly') {
					$planName = 'Yearly Subscription';
					$planInterval = 'year';
					$intervalLength = '365';
					$interval_dmy = 'days';
					$plancreated = date("Y-m-d H:i:s");
					$current_period_start = date("Y-m-d H:i:s");
				    $current_period_end = date("Y-m-d H:i:s", strtotime('+1 year'));
				}

define("AUTHORIZENET_LOG_FILE", "phplog");

function createSubscription($userID,$iuID,$payment_Type,$planID,$planCurrency, $planInterval,$planIntervalCount,$subscriberEmail,$autName, $autKey, $subscriberName,$userName,$intervalLength,$interval_dmy,$creditCardNumber,$expiredData,$amount,$plancreated,$current_period_start,$current_period_end,$adminEarning,$userNetEarning,$subscriptionCompleted)
{
	global $iN;
	/* Create a merchantAuthenticationType object with authentication details
	retrieved from the constants file */
	$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
	$merchantAuthentication->setName($autName);
	$merchantAuthentication->setTransactionKey($autKey);

	// Set the transaction's refId
	$refId = 'ref' . time();

	// Subscription Type Info
	$subscription = new AnetAPI\ARBSubscriptionType();
	$subscription->setName("Sample Subscription");

	$interval = new AnetAPI\PaymentScheduleType\IntervalAType();
	$interval->setLength($intervalLength);
	$interval->setUnit($interval_dmy);

	$paymentSchedule = new AnetAPI\PaymentScheduleType();
	$paymentSchedule->setInterval($interval);
	$paymentSchedule->setStartDate(new DateTime('now'));
	$paymentSchedule->setTotalOccurrences("12");
	$paymentSchedule->setTrialOccurrences("1");

	$subscription->setPaymentSchedule($paymentSchedule);
	$subscription->setAmount($amount);
	$subscription->setTrialAmount("0.00");

	$creditCard = new AnetAPI\CreditCardType();

	$creditCard->setCardNumber($creditCardNumber);
	$creditCard->setExpirationDate($expiredData);

	$payment = new AnetAPI\PaymentType();
	$payment->setCreditCard($creditCard);
	$subscription->setPayment($payment);

	$order = new AnetAPI\OrderType();
	$order->setInvoiceNumber("1234354");
	$order->setDescription($subscriptionCompleted);
	$subscription->setOrder($order);

	$billTo = new AnetAPI\NameAndAddressType();
	$billTo->setFirstName($subscriberName);
	$billTo->setLastName($userName);

	$subscription->setBillTo($billTo);

	$request = new AnetAPI\ARBCreateSubscriptionRequest();
	$request->setmerchantAuthentication($merchantAuthentication);
	$request->setRefId($refId);
	$request->setSubscription($subscription);
	$controller = new AnetController\ARBCreateSubscriptionController($request);

	$response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);

	if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
	{
		$custID = $response->getSubscriptionId();
		$planStatus = 'active';
		$insertSubscription = $iN->iN_InsertUserSubscription($userID, $iuID, $payment_Type, $subscriberName, $custID, $custID, $planID, $amount, $adminEarning, $userNetEarning, $planCurrency, $planInterval, $planIntervalCount, $subscriberEmail, $plancreated, $current_period_start, $current_period_end, $planStatus);

		 if ($insertSubscription) {
			echo '200';
		} else {
			echo iN_HelpSecure($LANG['contact_site_administrator']);
		}
	}
	else
	{
		echo "ERROR :  Invalid response\n";
		$errorMessages = $response->getMessages()->getMessage();
		echo "Response : " .$errorMessages[0]->getText() . "\n";
	}

	return $response;
}

if(!defined('DONT_RUN_SAMPLES'))
	createSubscription($userID,$iuID,$payment_Type,$planID,$planCurrency, $planInterval,$planIntervalCount,$subscriberEmail,$autName, $autKey,$subscriberName,$userName,$intervalLength,$interval_dmy,$creditCardNumber,$expiredData,$amount,$plancreated,$current_period_start,$current_period_end,$adminEarning,$userNetEarning,$subscriptionCompleted);
    }
 }
}
/*Send Tip*/
if($type == 'p_sendGift'){
	if(isset($_POST['tip_u']) && isset($_POST['tipTyp']) && isset($_POST['lid'])){
	   $giftLiveOwnerUserID = mysqli_real_escape_string($db, $_POST['tip_u']);
	   $giftTypeID = mysqli_real_escape_string($db, $_POST['tipTyp']);
	   $cLiveID = mysqli_real_escape_string($db, $_POST['lid']);
	   if($iN->CheckLivePlanExist($giftTypeID) == '1' && $iN->iN_CheckLiveIDExist($cLiveID) == '1'){
	   $getLiveGiftDataFromID = $iN->GetLivePlanDetails($giftTypeID);
	   $liveWantedCoin = isset($getLiveGiftDataFromID['gift_point']) ? $getLiveGiftDataFromID['gift_point'] : NULL;
	   $liveWantedMoney = isset($getLiveGiftDataFromID['gift_money_equal']) ? $getLiveGiftDataFromID['gift_money_equal'] : NULL;
	   $liveAnimationImage = isset($getLiveGiftDataFromID['gift_money_animation_image']) ? $getLiveGiftDataFromID['gift_money_animation_image'] : NULL;
	   $redirect = '';
	   $emountnot = '';
	   $status = '';
	   $liveGiftAnimationUrl = '';
		if ($userCurrentPoints >= $liveWantedCoin && $userID != $giftLiveOwnerUserID) {
			$translatePointToMoney = $liveWantedMoney;
			$adminEarning = $translatePointToMoney * ($adminFee / 100);
			$userEarning = $translatePointToMoney - $adminEarning;
			$liveGiftAnimation = $base_url.$liveAnimationImage;
			$liveGiftAnimationUrl = '<div class="live_animation_wrapper"><div class="live_an_img"><img src="'.$liveGiftAnimation.'"></div></div>';
			$UpdateUsersWallet = $iN->iN_UpdateUsersWalletsForLiveGift($userID,$cLiveID, $giftLiveOwnerUserID, $giftTypeID, $liveWantedCoin,$adminEarning, $userEarning, $liveWantedMoney);
			$liveOwnUserData = $iN->iN_GetUserDetails($userID);
		    $userCurrentPoints = isset($liveOwnUserData['wallet_points']) ? $liveOwnUserData['wallet_points'] : '0';
			if($UpdateUsersWallet){
				$status = 'ok';
			}else{
				$status = '404';
			}
		}else{
			$status = '';
			$emountnot = 'notEnouhCredit';
			$redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
		}
	   $data = array(
		  'status' => $status,
		  'redirect' => $redirect,
		  'enamount' => $emountnot,
		  'giftAnimation' => $liveGiftAnimationUrl,
		  'current_balance' => number_format($userCurrentPoints)
	   );
	   $result = json_encode($data);
	   echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
	   if($status == 'ok'){
           $userDeviceKey = $iN->iN_GetuserDetails($giftLiveOwnerUserID);
		   $toUserName = $userDeviceKey['i_username'];
		   $oneSignalUserDeviceKey = $userDeviceKey['device_key'];
		   $msgBody = $iN->iN_Secure($LANG['send_you_a_gift']);
		   $msgTitle = $iN->iN_Secure($LANG['your_gift_is']).$currencys[$defaultCurrency]. $userEarning;
		   $URL = $base_url.'live'.$toUserName;
		   if($oneSignalUserDeviceKey){
			 $iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
		   }
	   }
	}
   }
  }
  if($type == 'sndAgCon'){
     /*SEND CONFIRMATIN EMAIL STARTED*/
	 $code = md5(rand(1111, 9999) . time());

		if ($emailSendStatus == '1') {
			$insertNewCode = $iN->iN_InsertNewVerificationCode($iN->iN_Secure($userID), $iN->iN_Secure($code));
			if ($insertNewCode)
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
				$mail->addAddress($userEmail, ''); // Add a recipient
				$mail->Subject = $iN->iN_Secure($LANG['confirm_email']);
				$mail->CharSet = 'utf-8';
				$mail->MsgHTML($body);
				if ($mail->send()) {
					$mail->ClearAddresses();
					echo '8';
					return true;

			}
		} else {
			echo '3';
		}
	 /*SEND CONFIRMATION EMAIL FINISHED*/
  }
  /*Insert OneSignal Device Key*/
  if($type == 'device_key'){
	if(isset($_GET['id']) && $_GET['id'] != ''){
		$userDeviceOneSignalKey = mysqli_real_escape_string($db, $_GET['id']);
		$InsertOneSignalDeviceKey = $iN->iN_OneSignalDeviceKey($userID, $userDeviceOneSignalKey);
		if($InsertOneSignalDeviceKey){
		   echo '1';
		}else{
		   echo '2';
		}
	}
  }
  /*Remove OneSignal Device key*/
  if($type == 'remove_device_key'){
	$InsertOneSignalDeviceKey = $iN->iN_OneSignalDeviceKeyRemove($userID);
  }
  /*Generate a QR Code*/
  if($type == 'generateQRCode'){
    include("../includes/qr.php");
  }
  // Get Mention Users
	if ($type == 'mfriends') {
		if (isset($_POST['menFriend'])) {
			$searchmUser = mysqli_real_escape_string($db, $_POST['menFriend']);
			$GetResultMentionedUser = $iN->iN_SearchMention($userID, $searchmUser);
			if ($GetResultMentionedUser) {
				foreach ($GetResultMentionedUser as $um) {
					 $mentionResultUserID = $um['iuid'];
                     $mentionResultUserUsername = $um['i_username'];
					 $mentionResultUserUserFullName = $um['i_user_fullname'];
					 $mentionResultUserAvatar = $iN->iN_UserAvatar($mentionResultUserID, $base_url);
					echo '
					<div class="i_message_wrapper transition mres_u" data-user="'.$mentionResultUserUsername.'">
						<div class="i_message_owner_avatar">
							<div class="i_message_avatar"><img src="'.$mentionResultUserAvatar.'" alt="newuserhere"></div>
						</div>
						<div class="i_message_info_container">
							<div class="i_message_owner_name">'.$mentionResultUserUserFullName.'</div>
							<div class="i_message_i">@'.$mentionResultUserUsername.'</div>
						</div>
					</div>
					 ';
				}
			}
		}
	}
	if($type == 'stories'){
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			foreach ($_FILES['storieimg']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['storieimg']['name'][$iname]);
				$size = $_FILES['storieimg']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableFileExtensions);
				if (in_array($ext, $valid_formats)) {
					if (convert_to_mb($size) < $availableUploadFileSize) {
						$microtime = microtime();
						$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
						$UploadedFileName = "image_" . $removeMicrotime . '_' . $userID;
						$getFilename = $UploadedFileName . "." . $ext;
						// Change the image ame
						$tmp = $_FILES['storieimg']['tmp_name'][$iname];
						$mimeType = $_FILES['storieimg']['type'][$iname];
						$d = date('Y-m-d');
						if (preg_match('/video\/*/', $mimeType)) {
							$fileTypeIs = 'video';
						} else if (preg_match('/image\/*/', $mimeType)) {
							$fileTypeIs = 'Image';
						}
						if($mimeType == 'application/octet-stream'){
							$fileTypeIs = 'video';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						$wVideos = $serverDocumentRoot . '/uploads/videos/';
						if (!file_exists($wVideos . $d)) {
							$newFile = mkdir($wVideos . $d, 0755);
						}
						if ($fileTypeIs == 'video' && $ffmpegStatus == '0' && !in_array($ext, $nonFfmpegAvailableVideoFormat)) {
							exit('303');
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
						   /*STARTED*/
						   if ($fileTypeIs == 'Image') {
							$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
							$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
							$tumbnails = $serverDocumentRoot . '/uploads/files/' . $d . '/';
							$pathFilea = $base_url . 'uploads/files/' . $d . '/' . $getFilename;

							$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
							$thePathM = '../' . $pathFile;
							if($ext != 'gif'){
								if($watermarkStatus == 'yes'){
									watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}else if($LinkWatermarkStatus == 'yes'){
									watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}
							}
							$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
							if (file_exists($thePath)) {
								try {
									$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
									$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$image = new ImageFilter();
									$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
								} catch (Exception $e) {
									echo '<span class="request_warning">' . $e->getMessage() . '</span>';
								}
							}else{
								exit('Upload Failed!');
							}
							if ($s3Status == '1') {
								/*Upload Video tumbnail*/
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$key = basename($thevTumbnail);
								try {
									$result = $s3->putObject([
										'Bucket' => $s3Bucket,
										'Key' => 'uploads/files/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								/*Upload Video tumbnail*/
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$key = basename($thevTumbnail);
								try {
									$result = $s3->putObject([
										'Bucket' => $s3Bucket,
										'Key' => 'uploads/files/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								try {
									$result = $s3->putObject([
										'Bucket' => $s3Bucket,
										'Key' => 'uploads/pixel/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
									@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								$UploadSourceUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $tumbnailPath;
							}else if ($WasStatus == '1') {
								/*Upload Video tumbnail*/
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$key = basename($thevTumbnail);
								try {
									$result = $s3->putObject([
										'Bucket' => $WasBucket,
										'Key' => 'uploads/files/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								/*Upload Video tumbnail*/
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$key = basename($thevTumbnail);
								try {
									$result = $s3->putObject([
										'Bucket' => $WasBucket,
										'Key' => 'uploads/files/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									/*rmdir($uploadFile . $d);*/
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								try {
									$result = $s3->putObject([
										'Bucket' => $WasBucket,
										'Key' => 'uploads/pixel/' . $d . '/' . $key,
										'Body' => fopen($thevTumbnail, 'r+'),
										'ACL' => 'public-read',
										'CacheControl' => 'max-age=3153600',
									]);
									$UploadSourceUrl = $result->get('ObjectURL');
									@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
								} catch (Aws\S3\Exception\S3Exception $e) {
									echo "There was an error uploading the file.\n";
								}
								$UploadSourceUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $tumbnailPath;
							} else if ($digitalOceanStatus == '1') {
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
								/*IF DIGITALOCEAN AVAILABLE THEN*/
								$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
								$upload = $my_space->UploadFile($thevTumbnail, "public");
								$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
								$upload = $my_space->UploadFile($thevTumbnail, "public");
								$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
								$upload = $my_space->UploadFile($thevTumbnail, "public");
								/**/
								@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
								@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
								@unlink($uploadFile . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext);
								if ($upload) {
									$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $tumbnailPath;
								}
								/*/IF DIGITAOCEAN AVAILABLE THEN*/
							}else{
								$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
							}
						   }else if($fileTypeIs == 'video'){
							$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
							if ($ffmpegStatus == '1') {
								$convertUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
								$videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
								$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
								$textVideoPath = '../uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4';

								$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
								$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
								if ($ext == 'mpg') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'mov') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec copy -acodec copy $convertUrl");
									/*$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:v copy -c:a copy -y $convertUrl");*/
								} else if ($ext == 'wmv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'avi') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec h264 -acodec aac -strict -2 $convertUrl 2>&1");
								} else if ($ext == 'webm') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -crf 1 -c:v libx264 $convertUrl");
								} else if ($ext == 'mpeg') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
								} else if ($ext == 'flv') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:a aac -strict -2 -b:a 128k -c:v libx264 -profile:v baseline $convertUrl");
								} else if ($ext == 'm4v') {
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy $convertUrl");
								} else if ($ext == 'mkv') {
									//$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl 2>&1");
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -codec copy -strict -2 $convertUrl 2>&1");
								}else if($ext == '3gp'){
									$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl 2>&1");
								}

								$up_url = remove_http($base_url).$userName;
								$cmd = shell_exec("$ffmpegPath -ss 00:00:01 -i $convertUrl -c copy -t 00:00:04 $xVideoFirstPath 2>&1");
								$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
								$cmdText = shell_exec("$ffmpegPath -i $convertUrl -vf drawtext=fontfile=../src/droidsanschinese.ttf:text=$up_url:fontcolor=red:fontsize=18:x=10:y=H-th-10 $textVideoPath 2>&1");
								if ($cmdText) {
									//@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
									$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
								}
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.jpg';
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.jpg';
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit('You uploaded a video in '.$ext.' video format and ffmpeg could not create a tumbnail from the video.  You need to contact your server administration about this. ');
								}
							} else {
								$cmd = '';
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
							}
							if ($ffmpegStatus == '1') {
								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
								$thePathM = '../' . $tumbnailPath;
								if($watermarkStatus == 'yes'){
								  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
								}else if($LinkWatermarkStatus == 'yes'){
								  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
								}
							}
							/*CHECK AMAZON S3 AVAILABLE*/
							if ($s3Status == '1') {
								    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									/*Upload Full video*/
									$theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$key = basename($theName);
									if ($ffmpegStatus == '1') {
										try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($theName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$fullUploadedVideo = $result->get('ObjectURL');
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
								    }else{
										$theName = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									    $key = basename($theName);
									    try {
											$result = $s3->putObject([
												'Bucket' => $s3Bucket,
												'Key' => 'uploads/files/' . $d . '/' . $key,
												'Body' => fopen($theName, 'r+'),
												'ACL' => 'public-read',
												'CacheControl' => 'max-age=3153600',
											]);
											$fullUploadedVideo = $result->get('ObjectURL');
										} catch (Aws\S3\Exception\S3Exception $e) {
											echo "There was an error uploading the file.\n";
										}
									}
								if ($cmd) {
									/*Upload First x Second*/
									$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$key = basename($thexName);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/xvideos/' . $d . '/' . $key,
											'Body' => fopen($thexName, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										/*rmdir($xVideos . $d);*/
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/xvideos/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
									@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
									@unlink($uploadFile . $d . '/' . $getFilename);
								} else {
									$UploadSourceUrl = $base_url . 'uploads/web.png';
									/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
									$tumbnailPath = 'uploads/web.png';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
								}

							}else if ($WasStatus == '1') {
								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
								/*Upload Full video*/
								$theName = '../uploads/videos/' . $d . '/' . $getFilename;
								$key = basename($theName);
								if ($ffmpegStatus == '1') {
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/videos/' . $d . '/' . $key,
											'Body' => fopen($theName, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$fullUploadedVideo = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$pathFile = 'uploads/videos/' . $d . '/' . $getFilename;
								}else{
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($theName, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$fullUploadedVideo = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}
								if ($cmd) {
									/*Upload First x Second*/
									$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$key = basename($thexName);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/xvideos/' . $d . '/' . $key,
											'Body' => fopen($thexName, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/xvideos/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
									@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
									@unlink($uploadFile . $d . '/' . $getFilename);
									$pathFile = 'uploads/videos/' . $d . '/' . $getFilename;
								} else {
									$UploadSourceUrl = $base_url . 'uploads/web.png';
									/*IF AMAXZON S3 NOT AVAILABLE THEN JUST SHOW*/
									$tumbnailPath = 'uploads/web.png';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
								}

							} else if ($digitalOceanStatus == '1') {
								$theName = '../uploads/files/' . $d . '/' . $getFilename;
								/*IF DIGITALOCEAN AVAILABLE THEN*/
								$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
								$upload = $my_space->UploadFile($theName, "public");
								/**/
								if ($cmd) {
									$thexName = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thexName, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
								}
								if ($upload) {
									if ($cmd) {
										$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$pathXFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
										$pathXImageFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
										$tumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
										@unlink($pathXImageFile);
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
										@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
									} else {
										$UploadSourceUrl = $base_url . 'img/web.png';
										$tumbnailPath = 'img/web.png';
									}
								}
								/*/IF DIGITAOCEAN AVAILABLE THEN*/
							} else {
								if ($cmd) {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
								} else {
									$UploadSourceUrl = $base_url . 'uploads/web.png';
									$tumbnailPath = 'uploads/web.png';
									$tumbnailPath = $pathFile;
									$pathXFile = 'uploads/web.png';
								}
							}
							$ext = 'mp4';
						   }else{
							   exit('Not a valid format');
						   }
						   /*FINISHED*/
						   $insertFileFromUploadTable = $iN->iN_insertUploadedSotieFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
						   $getUploadedFileID = $iN->iN_GetUploadedStoriesFilesIDs($userID, $pathFile);

							if($fileTypeIs == 'Image'){
								echo '
								<!--Storie-->
								<div class="uploaded_storie_container nonePoint body_'.$getUploadedFileID['s_id'].'">
								<div class="dmyStory" id="'.$getUploadedFileID['s_id'].'"><div class="i_h_in flex_ ownTooltip" data-label="'.iN_HelpSecure($LANG['delete']).'">'.html_entity_decode($iN->iN_SelectedMenuIcon('28')).'</div></div>
								<div class="uploaded_storie_image border_one tabing flex_">
											<img src="'.$UploadSourceUrl.'" id="img'.$getUploadedFileID['s_id'].'">
									</div>
									<div class="add_a_text">
										<textarea class="add_my_text st_txt_'.$getUploadedFileID['s_id'].'" placeholder="Do you want to write something about this storie?"></textarea>
									</div>
									<div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="'.$getUploadedFileID['s_id'].'">
										'.html_entity_decode($iN->iN_SelectedMenuIcon('26')).'<div class="pbtn">'.iN_HelpSecure($LANG['publish']).'</div>
									</div>
								</div>
								<script type="text/javascript">
										(function($) {
										"use strict";
											setTimeout(() => {
												var img = document.getElementById("img'.$getUploadedFileID['s_id'].'");
												if(img.height > img.width){
													$("#img'.$getUploadedFileID['s_id'].'").css("height","100%");
												}else{
													$("#img'.$getUploadedFileID['s_id'].'").css("width","100%");
												}
												$(".uploaded_storie_container").show();
											}, 2000);
										})(jQuery);
								</script>
								<!--/Storie-->
								';
							}else if($fileTypeIs == 'video'){
                                echo '
								<!--Storie-->
								<div class="uploaded_storie_container body_'.$getUploadedFileID['s_id'].'">
								<div class="dmyStory" id="'.$getUploadedFileID['s_id'].'"><div class="i_h_in flex_ ownTooltip" data-label="'.iN_HelpSecure($LANG['delete']).'">'.html_entity_decode($iN->iN_SelectedMenuIcon('28')).'</div></div>
								<div class="uploaded_storie_image border_one tabing flex_">
											<video class="lg-video-object" id="v'.$getUploadedFileID['s_id'].'" controls preload="none" poster="'.$UploadSourceUrl.'">
												<source src="'.$base_url.$getUploadedFileID['uploaded_file_path'].'" preload="metadata" type="video/mp4">
												Your browser does not support HTML5 video.
											</video>
									</div>
									<div class="add_a_text">
										<textarea class="add_my_text st_txt_'.$getUploadedFileID['s_id'].'" placeholder="Do you want to write something about this storie?"></textarea>
									</div>
									<div class="share_story_btn_cnt flex_ tabing transition share_this_story" id="'.$getUploadedFileID['s_id'].'">
										'.html_entity_decode($iN->iN_SelectedMenuIcon('26')).'<div class="pbtn">'.iN_HelpSecure($LANG['publish']).'</div>
									</div>
								</div>
								<!--/Storie-->
								';
							}else{
								echo 'File format not allowed';
							}
						}
					}else{
						echo iN_HelpSecure($size);
					}
				}else{
					exit('No valid Format');
				}
			}
		}
	}
	/*Delete Storie Alert*/
	if($type == 'delete_storie_alert'){
       if(isset($_POST['id']) && $_POST['id'] != ''){
		   $postID = mysqli_real_escape_string($db, $_POST['id']);
		   $alertType = $type;
		   $checkStorieIDExist = $iN->iN_CheckStorieIDExist($userID, $postID);
		   if($checkStorieIDExist){
			 include "../themes/$currentTheme/layouts/popup_alerts/deleteStoryAlert.php";
		   }
	   }
	}
	/*Storie Seen*/
	if($type == 'storieSeen'){
     if(isset($_POST['id']) && $_POST['id'] != ''){
         $storieID = mysqli_real_escape_string($db, $_POST['id']);
		 $checkStorieID = $iN->iN_CheckStorieIDExistJustID($userID, $storieID);
		 if($checkStorieID){
            $insertSee = $iN->iN_InsertStorieSeen($userID, $storieID);
		 }
	 }
	}
	/*Show StorieViewers*/
	if($type == 'storieViewers'){
		if(isset($_POST['id']) && $_POST['id'] != ''){
			$storieID = mysqli_real_escape_string($db, $_POST['id']);
			$checkStorieID = $iN->iN_CheckStorieIDExistJustID($userID, $storieID);
			if($checkStorieID){
				$swData = $iN->iN_GetUploadedStoriesSeenData($userID,$storieID);
				include "../themes/$currentTheme/layouts/popup_alerts/storieViewers.php";
			}
		}

	}
	if ($type == 'pr_upload') {
		//$availableFileExtensions
		if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
			foreach ($_FILES['uploading']['name'] as $iname => $value) {
				$name = stripslashes($_FILES['uploading']['name'][$iname]);
				$size = $_FILES['uploading']['size'][$iname];
				$ext = getExtension($name);
				$ext = strtolower($ext);
				$valid_formats = explode(',', $availableFileExtensions);
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
						if($mimeType == 'application/octet-stream'){
							$fileTypeIs = 'video';
						}
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						$wVideos = $serverDocumentRoot . '/uploads/videos/';
						if (!file_exists($wVideos . $d)) {
							$newFile = mkdir($wVideos . $d, 0755);
						}
						if ($fileTypeIs == 'video' && $ffmpegStatus == '0' && !in_array($ext, $nonFfmpegAvailableVideoFormat)) {
							exit('303');
						}
						$uploadTumbnail = '';
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'video') {
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
								$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								if ($ffmpegStatus == '1') {
									$convertUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$videoTumbnailPath = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
									$xVideoFirstPath = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									$textVideoPath = '../uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4';

									$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
									if ($ext == 'mpg') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mov') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec copy -acodec copy $convertUrl");
										$cmd = shell_exec("$ffmpegPath -ss 00:00:01.000 -i $convertUrl -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'wmv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'avi') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -vcodec h264 -acodec aac -strict -2 $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'webm') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -crf 1 -c:v libx264 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mpeg') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy -map 0 $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'flv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c:a aac -strict -2 -b:a 128k -c:v libx264 -profile:v baseline $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'm4v') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -c copy $convertUrl");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									} else if ($ext == 'mkv') {
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -codec copy -strict -2 $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}else if($ext == '3gp'){
										$cmd = shell_exec("$ffmpegPath -i $UploadedFilePath -acodec copy -vcodec copy $convertUrl 2>&1");
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}else{
										$cmd = shell_exec("$ffmpegPath -i $convertUrl -ss 00:00:01.000 -vframes 1 $videoTumbnailPath 2>&1");
									}

									$up_url = remove_http($base_url).$userName;
									$cmd = shell_exec("$ffmpegPath -ss 00:00:01 -i $convertUrl -c copy -t 00:00:04 $xVideoFirstPath 2>&1");
									if($drawTextStatus == '1'){
										$cmdText = shell_exec("$ffmpegPath -i $convertUrl -vf drawtext=fontfile=../src/droidsanschinese.ttf:text=$up_url:fontcolor=red:fontsize=18:x=10:y=H-th-10 $textVideoPath 2>&1");
									}else{
										$cmdText = shell_exec("$ffmpegPath -i $convertUrl -c:a copy -c:v libx264 -preset superfast -profile:v baseline $textVideoPath 2>&1");
									}
									if ($cmdText) {
										$pathFile = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
									}
									$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.jpg';
									if (file_exists($thePath)) {
										try {
											$dir = "../uploads/xvideos/" . $d . "/" . $UploadedFileName . '.jpg';
											$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
											$image = new ImageFilter();
											$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");

										} catch (Exception $e) {
											echo '<span class="request_warning">' . $e->getMessage() . '</span>';
										}
									}else{
										exit('You uploaded a video in '.$ext.' video format and ffmpeg could not create a tumbnail from the video.  You need to contact your server administration about this. ');
									}
								} else {
									$cmd = '';
									$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
									$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
								}
								if ($ffmpegStatus == '1') {
    								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
    								$thePathM = '../' . $tumbnailPath;
									if($watermarkStatus == 'yes'){
    								  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}else if($LinkWatermarkStatus == 'yes'){
									  watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									}
								}
								/*CHECK AMAZON S3 AVAILABLE*/
								if ($s3Status == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    $theName = '../uploads/files/' . $d . '/' . $getFilename;
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access. Please remove 'public-read' or configure your bucket policy accordingly.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $fullUploadedVideo = $result->get('ObjectURL');
                                            @unlink($uploadFile . $d . '/' . $getFilename);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access. Please remove 'public-read' or configure your bucket policy accordingly.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        $uploads = [
                                            ['path' => '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/files/'],
                                        ];

                                        foreach ($uploads as $upload) {
                                            $key = basename($upload['path']);
                                            try {
                                                $result = $s3->putObject([
                                                    'Bucket' => $s3Bucket,
                                                    'Key' => $upload['target'] . $d . '/' . $key,
                                                    'Body' => fopen($upload['path'], 'r'),
                                                    'CacheControl' => 'max-age=3153600',
                                                    // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                                ]);
                                                $UploadSourceUrl = $result->get('ObjectURL');
                                                @unlink($upload['path']);
                                            } catch (Aws\S3\Exception\S3Exception $e) {
                                                $msg = $e->getAwsErrorMessage();
                                                echo "There was an error uploading the file: $msg<br>";
                                                if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                    echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public access. Please remove 'public-read' or configure your bucket policy accordingly.</div>";
                                                    $publicAccessErrorShown = true;
                                                }
                                            }
                                        }
                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }

                                } else if ($WasStatus == '1') {
                                    $tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                    $publicAccessErrorShown = false;

                                    $theName = '../uploads/files/' . $d . '/' . $getFilename;
                                    $key = basename($theName);

                                    if ($ffmpegStatus == '1') {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This typically happens with trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    } else {
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key' => 'uploads/files/' . $d . '/' . $key,
                                                'Body' => fopen($theName, 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This typically happens with trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    if ($cmd) {
                                        $uploads = [
                                            ['path' => '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/xvideos/'],
                                            ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg', 'target' => 'uploads/files/'],
                                        ];

                                        foreach ($uploads as $upload) {
                                            $key = basename($upload['path']);
                                            try {
                                                $result = $s3->putObject([
                                                    'Bucket' => $WasBucket,
                                                    'Key' => $upload['target'] . $d . '/' . $key,
                                                    'Body' => fopen($upload['path'], 'r'),
                                                    'CacheControl' => 'max-age=3153600',
                                                    // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                                ]);
                                                $UploadSourceUrl = $result->get('ObjectURL');
                                                @unlink($upload['path']);
                                            } catch (Aws\S3\Exception\S3Exception $e) {
                                                $msg = $e->getAwsErrorMessage();
                                                echo "There was an error uploading the file: $msg<br>";
                                                if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                    echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This typically happens with trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                    $publicAccessErrorShown = true;
                                                }
                                            }
                                        }

                                        // Remove local temporary files
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                        @unlink($xVideos . $d . '/' . $UploadedFileName . '.jpg');
                                        @unlink($uploadFile . $d . '/' . $getFilename);
                                        @unlink($serverDocumentRoot . '/uploads/videos/' . $d . '/' . $UploadedFileName . '.mp4');
                                    } else {
                                        $UploadSourceUrl = $base_url . 'uploads/web.png';
                                        $tumbnailPath = 'uploads/web.png';
                                        $pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                    }
                                } else if ($digitalOceanStatus == '1') {
                                	// Initialize DigitalOcean Spaces client once
                                	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);

                                	// 1. Upload the original file
                                	$localPath1 = '../uploads/files/' . $d . '/' . $getFilename;
                                	$remoteKey1 = 'uploads/files/' . $d . '/' . $getFilename;
                                	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                                	if ($cmd) {
                                		// 2. Upload .mp4 video to xvideos folder
                                		$localPath2 = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$remoteKey2 = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                                		// 3. Upload .jpg thumbnail
                                		$localPath3 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                		$remoteKey3 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
                                		$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                                		// 4. Upload .mp4 main video (again, in files folder)
                                		$localPath4 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$remoteKey4 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.mp4';
                                		$upload = $my_space->UploadFile($localPath4, "public", $remoteKey4);
                                	}

                                	// If any upload was successful
                                	if ($upload) {
                                		if ($cmd) {
                                			// Set final thumbnail URL and delete local temporary files
                                			$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey3;
                                			$pathXFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.mp4';
                                			$pathXImageFile = '../uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
                                			$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';

                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.jpg');
                                			@unlink($pathXImageFile);
                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                			@unlink($uploadFile . $d . '/' . $UploadedFileName . '.mp4');
                                			@unlink($xVideos . $d . '/' . $UploadedFileName . '.mp4');
                                		} else {
                                			// Fallback for non-video content
                                			$UploadSourceUrl = $base_url . 'img/web.png';
                                			$tumbnailPath = 'img/web.png';
                                		}
                                	}
                                } else {
									if ($cmd) {
										$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '.jpg';
										$pathXFile = 'uploads/xvideos/' . $d . '/' . $UploadedFileName . '.jpg';
									} else {
										$UploadSourceUrl = $base_url . 'uploads/web.png';
										$tumbnailPath = 'uploads/web.png';
										$tumbnailPath = $pathFile;
										$pathXFile = 'uploads/web.png';
									}
								}
								$ext = 'mp4';
								/**/
							} else if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$tumbnails = $serverDocumentRoot . '/uploads/files/' . $d . '/';
								$pathFilea = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								$pathFileC = '../uploads/files/' . $d . '/' . $getFilename;
								$width = 500;
								$height = 500;
								$file = $pathFilea;
								//indicate the path and name for the new resized file
								$resizedFile = $tumbnails . $UploadedFileName . '_' . $userID . '.' . $ext;
								$resizedFileTwo = $tumbnails . $UploadedFileName . '__' . $userID . '.' . $ext;
								$tb = new ThumbAndCrop();
								$tb->openImg($pathFileC);
								$newHeight = $tb->getRightHeight(500);
								$tb->creaThumb(500, $newHeight);
								$tb->setThumbAsOriginal();
								$tb->creaThumb(500, $newHeight);
								$tb->saveThumb($resizedFileTwo);

								$thePathM = '../' . $pathFile;
								$tumbnailPath = 'uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext;
								if($ext != 'gif'){
									if($watermarkStatus == 'yes'){
										watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									  }else if($LinkWatermarkStatus == 'yes'){
										watermark_image($thePathM, $siteWatermarkLogo, $LinkWatermarkStatus, $base_url.$userName);
									  }
								}
								if (file_exists($thePathM)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }
								$publicAccessErrorShown = false;

                                if ($s3Status == '1') {
                                    $uploads = [
                                        ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext, 'target' => 'uploads/files/'],
                                        ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext, 'target' => 'uploads/files/'],
                                        ['path' => '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext, 'target' => 'uploads/pixel/'],
                                    ];

                                    foreach ($uploads as $upload) {
                                        $key = basename($upload['path']);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $s3Bucket,
                                                'Key'    => $upload['target'] . $d . '/' . $key,
                                                'Body'   => fopen($upload['path'], 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($upload['path']);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Amazon S3 bucket policy does not allow public file access. Please remove 'public-read' or update the policy to allow it.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    $UploadSourceUrl = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/uploads/files/' . $d . '/' . basename($uploads[1]['path']);

                                } else if ($WasStatus == '1') {
                                    $uploads = [
                                        ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '__' . $userID . '.' . $ext, 'target' => 'uploads/files/'],
                                        ['path' => '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext, 'target' => 'uploads/files/'],
                                        ['path' => '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext, 'target' => 'uploads/pixel/'],
                                    ];

                                    foreach ($uploads as $upload) {
                                        $key = basename($upload['path']);
                                        try {
                                            $result = $s3->putObject([
                                                'Bucket' => $WasBucket,
                                                'Key'    => $upload['target'] . $d . '/' . $key,
                                                'Body'   => fopen($upload['path'], 'r'),
                                                'CacheControl' => 'max-age=3153600',
                                                // 'ACL' => 'public-read', is intentionally excluded for compatibility
                                            ]);
                                            $UploadSourceUrl = $result->get('ObjectURL');
                                            @unlink($upload['path']);
                                        } catch (Aws\S3\Exception\S3Exception $e) {
                                            $msg = $e->getAwsErrorMessage();
                                            echo "There was an error uploading the file: $msg<br>";
                                            if (!$publicAccessErrorShown && str_contains($msg, 'Public use of objects is not allowed')) {
                                                echo "<div class='request_warning'>Warning: Your Wasabi account does not allow public file access. This typically occurs on trial accounts. Please remove 'public-read' or contact Wasabi support.</div>";
                                                $publicAccessErrorShown = true;
                                            }
                                        }
                                    }

                                    $UploadSourceUrl = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/uploads/files/' . $d . '/' . basename($uploads[1]['path']);
                                } else if ($digitalOceanStatus == '1') {
                                	// Initialize the DigitalOcean Spaces client
                                	$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);

                                	// 1. Upload file with userID suffix
                                	$localPath1 = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
                                	$remoteKey1 = 'uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
                                	$upload = $my_space->UploadFile($localPath1, "public", $remoteKey1);

                                	// 2. Upload standard file
                                	$localPath2 = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                	$remoteKey2 = 'uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                	$upload = $my_space->UploadFile($localPath2, "public", $remoteKey2);

                                	// 3. Upload pixel version
                                	$localPath3 = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                	$remoteKey3 = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
                                	$upload = $my_space->UploadFile($localPath3, "public", $remoteKey3);

                                	// Delete local temporary files
                                	@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
                                	@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
                                	@unlink($uploadFile . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext);

                                	// Set the public URL and path for thumbnail
                                	if ($upload) {
                                		$UploadSourceUrl = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $remoteKey2;
                                		$tumbnailPath = $pathFile;
                                	}
                                } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							/**/
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedFiles($userID, $pathFile, $tumbnailPath, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							mysqli_query($db,"UPDATE i_user_uploads SET upload_type = 'product' WHERE upload_id = '".$getUploadedFileID['upload_id']."'") or die(mysqli_error($db));
							if ($fileTypeIs == 'video') {
								$uploadTumbnail = '
								<div class="v_custom_tumb">
									<label for="vTumb_' . $getUploadedFileID['upload_id'] . '">
										<div class="i_image_video_btn"><div class="pbtn pbtn_plus">' . $LANG['custom_tumbnail'] . '</div>
										<input type="file" id="vTumb_' . $getUploadedFileID['upload_id'] . '" class="imageorvideo cTumb editAds_file" data-id="' . $getUploadedFileID['upload_id'] . '" name="uploading[]" data-id="tupload">
									</label>
								</div>
								';
							}
							if ($fileTypeIs == 'video' || $fileTypeIs == 'Image') {
								/*AMAZON S3*/
								echo '
									<div class="i_uploaded_item iu_f_' . $getUploadedFileID['upload_id'] . ' ' . $fileTypeIs . '" id="' . $getUploadedFileID['upload_id'] . '">
									' . $postTypeIcon . '
									<div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
										' . $iN->iN_SelectedMenuIcon('5') . '
									</div>
									<div class="i_uploaded_file" id="viTumb' . $getUploadedFileID['upload_id'] . '" style="background-image:url(' . $UploadSourceUrl . ');">
											<img class="i_file" id="viTumbi' . $getUploadedFileID['upload_id'] . '" src="' . $UploadSourceUrl . '" alt="tumbnail">
									</div>
									' . $uploadTumbnail . '
									</div>
								';
							}
						}else{
							echo 'Something Wrong';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
/*Insert New product*/
if($type == 'createScratch' || $type == 'createBookaZoom'){
   if(isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf']) && isset($_POST['vals'])){
      $productName = mysqli_real_escape_string($db, $_POST['prnm']);
	  $productPrice = mysqli_real_escape_string($db, $_POST['prprc']);
	  $productDescription = mysqli_real_escape_string($db, $_POST['prdsc']);
	  $productDescriptionInfo = mysqli_real_escape_string($db, $_POST['prdscinf']);
	  $productFiles = mysqli_real_escape_string($db, $_POST['vals']);
	  $productLimitSlots = mysqli_real_escape_string($db, $_POST['lmSlot']);
	  $productAskQuestion = mysqli_real_escape_string($db, $_POST['askQ']);
	  $productFiles = implode(',',array_unique(explode(',', $productFiles)));
	    if($productFiles != '' && !empty($productFiles) && $productFiles != 'undefined'){
			$trimValue = rtrim($productFiles, ',');
			$explodeFiles = explode(',', $trimValue);
			$explodeFiles = array_unique($explodeFiles);
			foreach($explodeFiles as $explodeFile){
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
				if(empty($uploadedFileID)){
				    exit('204');
				}
			}
	    }
		if($productLimitSlots == 'ok'){
			$productLimSlots = mysqli_real_escape_string($db, $_POST['lSlot']);
			if(preg_replace('/\s+/', '',$productLimSlots) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'345');
			}
		}else{$productLimSlots = '';}
		if($productAskQuestion == 'ok'){
			$productQuestion = mysqli_real_escape_string($db, $_POST['qAsk']);
			if(preg_replace('/\s+/', '',$productQuestion) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'123');
			}
		}else{$productQuestion = '';}

	  if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == '' || preg_replace('/\s+/', '',$productFiles) == ''){
         exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
	  }
	  if($type == 'createScratch'){
         $productType = 'scratch';
	  }else if($type == 'createBookaZoom'){
		$productType = 'bookazoom';
	  }else if($type == 'createartcommission'){
		$productType = 'artcommission';
	  }else if($type == 'createjoininstagramclosefriends'){
		$productType = 'joininstagramclosefriends';
	  }
      $slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
	  $insertNewProduct = $iN->iN_InsertNewProduct($userID, $iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($productFiles), $iN->iN_Secure($slug), $iN->iN_Secure($productType), $iN->iN_Secure($productLimSlots), $iN->iN_Secure($productQuestion));
	  if($insertNewProduct){
        exit('200');
	  }else{
		exit('404');
	  }
   }
}
if($type == 'productStatus'){
   if(isset($_POST['mod']) && in_array($_POST['mod'], $statusValue) && isset($_POST['id'])){
      $productID = mysqli_real_escape_string($db, $_POST['id']);
	  $newStatus = mysqli_real_escape_string($db, $_POST['mod']);
	  $updateProductStatus = $iN->iN_UpdateProductStatus($userID, $productID, $newStatus);
	  if($updateProductStatus){
        exit('200');
	  }else{
		exit('404');
	  }
   }
}
if($type == 'saveEditPr'){
	if(isset($_POST['prnm']) && isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf'])){
		$productID = mysqli_real_escape_string($db, $_POST['prid']);
		$productName = mysqli_real_escape_string($db, $_POST['prnm']);
		$productPrice = mysqli_real_escape_string($db, $_POST['prprc']);
		$productDescription = mysqli_real_escape_string($db, $_POST['prdsc']);
		$productDescriptionInfo = mysqli_real_escape_string($db, $_POST['prdscinf']);
		$productLimitSlots = mysqli_real_escape_string($db, $_POST['lmSlot']);
		$productAskQuestion = mysqli_real_escape_string($db, $_POST['askQ']);
		if($productLimitSlots == 'ok'){
			$productLimSlots = mysqli_real_escape_string($db, $_POST['lSlot']);
			if(preg_replace('/\s+/', '',$productLimSlots) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'345');
			}
		}else{$productLimSlots = '';}
		if($productAskQuestion == 'ok'){
			$productQuestion = mysqli_real_escape_string($db, $_POST['qAsk']);
			if(preg_replace('/\s+/', '',$productQuestion) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'123');
			}
		}else{$productQuestion = '';}
		if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == ''){
		   exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
		}
		$slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
		$insertNewProduct = $iN->iN_InsertUpdatedProduct($userID, $iN->iN_Secure($productID),$iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($slug), $iN->iN_Secure($productLimSlots), $iN->iN_Secure($productQuestion));
		if($insertNewProduct){
		  exit('200');
		}else{
		  exit('404');
		}
	 }
}
/*Get Free Follow PopUP*/
if ($type == 'delete_product') {
	if (isset($_POST['id'])) {
		$productID = mysqli_real_escape_string($db, $_POST['id']);
		$checkproductExist = $iN->iN_CheckProductIDExist($userID, $productID);
		if ($checkproductExist) {
			include "../themes/$currentTheme/layouts/popup_alerts/deleteProduct.php";
		}
	}
}
/*Delete Story From Database*/
if ($type == 'deleteProduct') {
	if (isset($_POST['id'])) {
		$productID = mysqli_real_escape_string($db, $_POST['id']);
		if(!empty($productID) && $digitalOceanStatus == '1'){
			$getPostFileIDs = $iN->iN_ProductDetails($userID, $productID);
			$postFileIDs = isset($getPostFileIDs['pr_files']) ? $getPostFileIDs['pr_files'] : NULL;
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
			$deleteStoragePost = $iN->iN_DeleteProductFromDataifStorage($userID, $productID);
			if($deleteStoragePost){
				echo '200';
			}else{
				echo '404';
			}
		}else if(!empty($productID) && $s3Status == '1'){
			$getPostFileIDs = $iN->iN_ProductDetails($userID,$productID);
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
					mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
				}
			}
			$deleteStoragePost = $iN->iN_DeleteProductFromDataifStorage($userID, $productID);
			if($deleteStoragePost){
				echo '200';
			}else{
				echo '404';
			}
		}else if(!empty($productID) && $WasStatus == '1'){
			$getPostFileIDs = $iN->iN_ProductDetails($userID,$productID);
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
					mysqli_query($db, "DELETE FROM i_user_uploads WHERE upload_id = '$uploadedFileID' AND iuid_fk = '$userID'");
				}
			}
			$deleteStoragePost = $iN->iN_DeleteProductFromDataifStorage($userID, $productID);
			if($deleteStoragePost){
				echo '200';
			}else{
				echo '404';
			}
		}else if(!empty($productID)){
			$deletePostFromData = $iN->iN_DeleteProduct($userID, $productID);
			if ($deletePostFromData) {
				echo '200';
			} else {
				echo '404';
			}
		}
	}
}
/*UPload Downloadable File*/
if ($type == 'prd_upload') {
	$availableFileExtensions = 'pdf,zip,PDF,ZIP';
	//$availableFileExtensions
	if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
		foreach ($_FILES['uploading']['name'] as $iname => $value) {
			$name = stripslashes($_FILES['uploading']['name'][$iname]);
			$size = $_FILES['uploading']['size'][$iname];
			$ext = getExtension($name);
			$ext = strtolower($ext);
			$valid_formats = explode(',', $availableFileExtensions);
			if (in_array($ext, $valid_formats)) {
				if (convert_to_mb($size) < $availableUploadFileSize) {
					$microtime = microtime();
					$removeMicrotime = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', $microtime);
					$UploadedFileName = "file_" . $removeMicrotime . '_' . $userID;
					$getFilename = $UploadedFileName . "." . $ext;
					// Change the image ame
					$tmp = $_FILES['uploading']['tmp_name'][$iname];
					$mimeType = $_FILES['uploading']['type'][$iname];
					$d = date('Y-m-d');

					if (!file_exists($uploadFile . $d)) {
						$newFile = mkdir($uploadFile . $d, 0755);
					}
					$uploadTumbnail = '';
					if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
						/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
						$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('52') . '</div>';
						$UploadedFilePath = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
						$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
						$pathXFile = 'uploads/files/' . $d . '/' . $getFilename;
						/*CHECK AMAZON S3 AVAILABLE*/
						if ($s3Status == '1') {
							/*Upload Full video*/
							$theName = '../uploads/files/' . $d . '/' . $getFilename;
							$key = basename($theName);

							try {
								$result = $s3->putObject([
									'Bucket' => $s3Bucket,
									'Key' => 'uploads/files/' . $d . '/' . $key,
									'Body' => fopen($theName, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$fullUploadedVideo = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $getFilename);
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo "There was an error uploading the file.\n";
							}
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						}else if ($WasStatus == '1') {
							/*Upload Full video*/
							$theName = '../uploads/files/' . $d . '/' . $getFilename;
							$key = basename($theName);

							try {
								$result = $s3->putObject([
									'Bucket' => $WasBucket,
									'Key' => 'uploads/files/' . $d . '/' . $key,
									'Body' => fopen($theName, 'r+'),
									'ACL' => 'public-read',
									'CacheControl' => 'max-age=3153600',
								]);
								$fullUploadedVideo = $result->get('ObjectURL');
								@unlink($uploadFile . $d . '/' . $getFilename);
							} catch (Aws\S3\Exception\S3Exception $e) {
								echo "There was an error uploading the file.\n";
							}
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						} else if ($digitalOceanStatus == '1') {
							$theName = '../uploads/files/' . $d . '/' . $getFilename;
							/*IF DIGITALOCEAN AVAILABLE THEN*/
							$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
							$upload = $my_space->UploadFile($theName, "public");
							if($upload){
								@unlink($uploadFile . $d . '/' . $getFilename);
							}
							/*/IF DIGITAOCEAN AVAILABLE THEN*/
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						} else {
							$status = 'ok';
							$UploadSourceUrl = $UploadedFilePath;
						}
						/**/
						if($ext == 'pdf'){
                           $fileIcon = html_entity_decode($iN->iN_SelectedMenuIcon('166'));
						}else{
						   $fileIcon = html_entity_decode($iN->iN_SelectedMenuIcon('167'));
						}
						if($UploadSourceUrl){
							$data = array(
								'status' => $status,
								'fileUrl' => $UploadSourceUrl,
								'filePath' => $pathFile,
								'fileIcon' => $fileIcon,
								'fileName' => $getFilename
							);
							$result = json_encode($data);
							echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
						}
					}else{
						echo 'Something Wrong';
					}
				} else {
					echo iN_HelpSecure($size);
				}
			}
		}
	}
}
if($type == 'createDigitalDownload'){
	if(isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf']) && isset($_POST['vals']) && isset($_POST['dFile'])){
		$productName = mysqli_real_escape_string($db, $_POST['prnm']);
		$productPrice = mysqli_real_escape_string($db, $_POST['prprc']);
		$productDescription = mysqli_real_escape_string($db, $_POST['prdsc']);
		$productDescriptionInfo = mysqli_real_escape_string($db, $_POST['prdscinf']);
		$productFiles = mysqli_real_escape_string($db, $_POST['vals']);
		$productDownloadableFile = mysqli_real_escape_string($db, $_POST['dFile']);
		$productFiles = implode(',',array_unique(explode(',', $productFiles)));
		  if($productFiles != '' && !empty($productFiles) && $productFiles != 'undefined'){
			  $trimValue = rtrim($productFiles, ',');
			  $explodeFiles = explode(',', $trimValue);
			  $explodeFiles = array_unique($explodeFiles);
			  foreach($explodeFiles as $explodeFile){
				  $theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				  $uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
				  if(empty($uploadedFileID)){
					  exit('204');
				  }
			  }
		  }
		if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == '' || preg_replace('/\s+/', '',$productFiles) == '' || preg_replace('/\s+/', '',$productDownloadableFile) == ''){
		   exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
		}
		$productType = 'digitaldownload';

		$slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
		$insertNewProduct = $iN->iN_InsertNewProductDownloadable($userID, $iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($productFiles), $iN->iN_Secure($slug), $iN->iN_Secure($productType), $iN->iN_Secure($productDownloadableFile));
		if($insertNewProduct){
		  exit('200');
		}else{
		  exit('404');
		}
	 }
}
/*Insert New product*/
if($type == 'createliveeventticket' || $type == 'createartcommission' || $type == 'createjoininstagramclosefriends'){
	if(isset($_POST['prnm']) && isset($_POST['prprc']) && isset($_POST['prdsc']) && isset($_POST['prdscinf']) && isset($_POST['vals'])){
	    $productName = mysqli_real_escape_string($db, $_POST['prnm']);
	    $productPrice = mysqli_real_escape_string($db, $_POST['prprc']);
	    $productDescription = mysqli_real_escape_string($db, $_POST['prdsc']);
	    $productDescriptionInfo = mysqli_real_escape_string($db, $_POST['prdscinf']);
	    $productFiles = mysqli_real_escape_string($db, $_POST['vals']);
	    $productLimitSlots = mysqli_real_escape_string($db, $_POST['lmSlot']);
	    $productAskQuestion = mysqli_real_escape_string($db, $_POST['askQ']);
	    $productFiles = implode(',',array_unique(explode(',', $productFiles)));
		if($productFiles != '' && !empty($productFiles) && $productFiles != 'undefined'){
			$trimValue = rtrim($productFiles, ',');
			$explodeFiles = explode(',', $trimValue);
			$explodeFiles = array_unique($explodeFiles);
			foreach($explodeFiles as $explodeFile){
				$theFileID = $iN->iN_GetUploadedFileDetails($explodeFile);
				$uploadedFileID = isset($theFileID['upload_id']) ? $theFileID['upload_id'] : NULL;
				if(empty($uploadedFileID)){
					exit('204');
				}
			}
		}
		if($productLimitSlots == 'ok'){
			$productLimSlots = mysqli_real_escape_string($db, $_POST['lSlot']);
			if(preg_replace('/\s+/', '',$productLimSlots) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'345');
			}
		}else{$productLimSlots = '';}
		if($productAskQuestion == 'ok'){
			$productQuestion = mysqli_real_escape_string($db, $_POST['qAsk']);
			if(preg_replace('/\s+/', '',$productQuestion) == ''){
				exit(iN_HelpSecure($LANG['please_fill_in_all_informations']).'123');
			}
		}else{$productQuestion = '';}
	    if(preg_replace('/\s+/', '',$productName) == '' || preg_replace('/\s+/', '',$productPrice) == '' || preg_replace('/\s+/', '',$productDescription) == '' || preg_replace('/\s+/', '',$productDescriptionInfo) == '' || preg_replace('/\s+/', '',$productFiles) == ''){
			exit(iN_HelpSecure($LANG['please_fill_in_all_informations']));
	    }
		if($type == 'createliveeventticket'){
			$productType = 'liveeventticket';
		} else if($type == 'createartcommission'){
			$productType = 'artcommission';
		} else if($type == 'createjoininstagramclosefriends'){
			$productType = 'joininstagramclosefriends';
		}
		$slug = $iN->url_slugies(mb_substr($productName, 0, 55, "utf-8"));
		$insertNewProduct = $iN->iN_InsertNewProductLiveEventTicket($userID, $iN->iN_Secure($productName), $iN->iN_Secure($productPrice), $iN->iN_Secure($productDescription), $iN->iN_Secure($productDescriptionInfo), $iN->iN_Secure($productFiles), $iN->iN_Secure($slug), $iN->iN_Secure($productType), $iN->iN_Secure($productLimSlots), $iN->iN_Secure($productQuestion));
		if($insertNewProduct){
			exit('200');
		}else{
			exit('404');
		}
	}
}

if($type == 'shareMyTextStory'){
   if(isset($_POST['id']) && !empty($_POST['id']) && isset($_POST['stext']) && !empty($_POST['stext']) && $_POST['stext'] != ''){
      $bgID = mysqli_real_escape_string($db,$_POST['id']);
	  $storyText = mysqli_real_escape_string($db, $_POST['stext']);
	  if(preg_replace('/\s+/', '',$storyText) == ''){
        exit(iN_HelpSecure($LANG['please_add_text_in_your_story']));
	  }
	  $insertTextStory = $iN->iN_InsertTextStory($userID, $iN->iN_Secure($bgID), $iN->iN_Secure($storyText));
	  if($insertTextStory){
        exit('200');
	  }else{
		exit('404');
	  }
   }
}
if ($type == 'buyProduct') {
	if (isset($_POST['type']) && $_POST['type'] != '' && !empty($_POST['type'])) {
		$productID = mysqli_real_escape_string($db, $_POST['type']);
	    $checkproductID = $iN->iN_CheckProductIDExistFromURL($productID);
		if($checkproductID == TRUE){
			$prData = $iN->iN_GetProductDetailsByID($productID);
			$planAmount = $prData['pr_price'];
			$ProductOwnerID = $prData['iuid_fk'];

			if($ProductOwnerID == $userID){
              exit('me');
			}
			$planPoint = '';
			if($stripePaymentCurrency == 'JPY'){
				 $planAmount = $planAmount / 100;
			}
			require_once '../includes/payment/vendor/autoload.php';
			if (!defined('INORA_METHODS_CONFIG')) {
				define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
			}
			$configData = configItem();
			$DataUserDetails = [
				'amounts' => [ // at least one currency amount is required
					$payPalCurrency => $planAmount,
					$iyziCoPaymentCurrency => $planAmount,
					$bitPayPaymentCurrency => $planAmount,
					$autHorizePaymentCurrency => $planAmount,
					$payStackPaymentCurrency => $planAmount,
					$stripePaymentCurrency => $planAmount,
					$razorPayPaymentCurrency => $planAmount,
				],
				'order_id' => 'ORDS' . uniqid(), // required in instamojo, Iyzico, Paypal, Paytm gateways
				'customer_id' => 'CUSTOMER' . uniqid(), // required in Iyzico, Paytm gateways
				'item_name' => $LANG['point_purchasing'], // required in Paypal gateways
				'item_qty' => 1,
				'item_id' => 'ITEM' . uniqid(), // required in Iyzico, Paytm gateways
				'payer_email' => $userEmail, // required in instamojo, Iyzico, Stripe gateways
				'payer_name' => $userFullName, // required in instamojo, Iyzico gateways
				'description' => $LANG['point_purchasing_from'], // Required for stripe
				'ip_address' => getUserIpAddr(), // required only for iyzico
				'address' => '3234 Godfrey Street Tigard, OR 97223', // required in Iyzico gateways
				'city' => 'Tigard', // required in Iyzico gateways
				'country' => 'United States', // required in Iyzico gateways
			];
			$PublicConfigs = getPublicConfigItem();

			$configItem = $configData['payments']['gateway_configuration'];

			// Get config data
			$configa = getPublicConfigItem();
			// Get app URL
			$paymentPagePath = getAppUrl();

			$gatewayConfiguration = $configData['payments']['gateway_configuration'];
			// get paystack config data
			$paystackConfigData = $gatewayConfiguration['paystack'];
			// Get paystack callback ur
			$paystackCallbackUrl = getAppUrl($paystackConfigData['callbackUrl']);

			// Get stripe config data
			$stripeConfigData = $gatewayConfiguration['stripe'];
			// Get stripe callback ur
			$stripeCallbackUrl = getAppUrl($stripeConfigData['callbackUrl']);

			// Get razorpay config data
			$razorpayConfigData = $gatewayConfiguration['razorpay'];
			// Get razorpay callback url
			$razorpayCallbackUrl = getAppUrl($razorpayConfigData['callbackUrl']);

			// Get Authorize.Net config Data
			$authorizeNetConfigData = $gatewayConfiguration['authorize-net'];
			// Get Authorize.Net callback url
			$authorizeNetCallbackUrl = getAppUrl($authorizeNetConfigData['callbackUrl']);

			// Individual payment gateway url
			$individualPaymentGatewayAppUrl = getAppUrl('individual-payment-gateways');
			// User Details Configurations FINISHED
			include "../themes/$currentTheme/layouts/popup_alerts/paymentMethodsForPurchaseProduct.php";
		}
	}
}
if ($type == 'processProduct') {
	require_once '../includes/payment/vendor/autoload.php';
	if (!defined('INORA_METHODS_CONFIG')) {
		define('INORA_METHODS_CONFIG', realpath('../includes/payment/paymentConfig.php'));
	}
	include "../includes/payment/payment-process-product.php";
}
if($type == 'downloadMyProduct'){
   if(isset($_POST['myp']) && !empty($_POST['myp']) && $_POST['myp'] != ''){
      $productID = mysqli_real_escape_string($db, $_POST['myp']);
	  $checkProductPurchasedBefore = $iN->iN_CheckItemPurchasedBefore($userID, $productID);
	  if($checkProductPurchasedBefore){
		$productData = $iN->iN_GetProductDetailsByID($productID);
		$uProductDownloadableFiles = $productData['pr_downlodable_files'];
		$thefile = $uProductDownloadableFiles;
		$file = $uProductDownloadableFiles;
		$ext = substr($file, strrpos($file, '.') + 1);
        $fake = 'aa.'.$ext;
		if (file_exists($thefile)) {
			$iN->download($file,$fake);
		}
	  }
   }
}
if($type == 'gotAnnouncement'){
   if(isset($_POST['aid']) && $_POST['aid'] != ''){
       $announceID = mysqli_real_escape_string($db, $_POST['aid']);
	   $announcementReaded = $iN->iN_AnnouncementAccepted($userID, $announceID);
	   if($announcementReaded){
         exit('200');
	   }else{
		 exit('404');
	   }
   }
}
if($type == 'mrProduct'){
    if(isset($_POST['last']) && isset($_POST['ty'])){
       $productID = mysqli_real_escape_string($db, $_POST['last']);
       $categoryKey = mysqli_real_escape_string($db, $_POST['ty']);
       $productData = $iN->iN_AllUserProductPosts($categoryKey, $productID, $showingNumberOfPost);
	   include "../themes/$currentTheme/layouts/loadmore/moreProduct.php";
	}
}
if($type == 'moveMyAffilateBalance'){
  if(isset($_POST['myp']) && $_POST['myp'] != '' && !empty($_POST['myp'])){
	  $moveMyPoint = $iN->iN_MoveMyPoint($userID);
  }
}
/*Open Profile Tip Box*/
if($type == 'p_p_tips'){
	if(isset($_POST['tp_u']) && !empty($_POST['tp_u']) && $_POST['tp_u'] !== ''){
		$tipingUserID = mysqli_real_escape_string($db, $_POST['tp_u']);
		$tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
		$f_userfullname = $tipingUserDetails['i_user_fullname'];
		include "../themes/$currentTheme/layouts/popup_alerts/sendProfileTipPoint.php";
	}
}
/*Open Profile Frame Box*/
if($type == 'p_p_frame'){
	if(isset($_POST['tp_u']) && !empty($_POST['tp_u']) && $_POST['tp_u'] !== ''){
		$tipingUserID = mysqli_real_escape_string($db, $_POST['tp_u']);
		$tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
		$f_userfullname = $tipingUserDetails['i_user_fullname'];
		include "../themes/$currentTheme/layouts/popup_alerts/sendProfileFrame.php";
	}
}
if($type == 'p_p_tips_message'){
	if(isset($_POST['tp_u']) && !empty($_POST['tp_u']) && $_POST['tp_u'] !== ''){
		$tipingUserID = mysqli_real_escape_string($db, $_POST['tp_u']);
		$tipingUserDetails = $iN->iN_GetUserDetails($tipingUserID);
		$f_userfullname = $tipingUserDetails['i_user_fullname'];
		include "../themes/$currentTheme/layouts/popup_alerts/sendMessageTipPoint.php";
	}
}
/*Send Tip*/
if($type == 'p_sendTipProfile'){
	if(isset($_POST['tip_u']) && isset($_POST['tipVal']) && $_POST['tip_u'] != '' &&  $_POST['tipVal'] != '' && !empty($_POST['tip_u']) && !empty($_POST['tipVal'])){
	   $tiSendingUserID = mysqli_real_escape_string($db, $_POST['tip_u']);
	   $tipAmount = mysqli_real_escape_string($db, $_POST['tipVal']);
	   $redirect = '';
	   $emountnot = '';
	   $status = '';
	   if($tipAmount < $minimumTipAmount){
		  $emountnot = 'notEnough';
	   }else{
		  if ($userCurrentPoints >= $tipAmount && $userID != $tiSendingUserID) {

			  $netUserEarning = $tipAmount * $onePointEqual;
			  $adminEarning = ($adminFee * $netUserEarning) / 100;
			  $userNetEarning = $netUserEarning - $adminEarning;

			  $UpdateUsersWallet = $iN->iN_UpdateUsersWallets($userID, $tiSendingUserID, $tipAmount, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
			  if($UpdateUsersWallet){
				 $status = 'ok';
			  }else{
				 $status = '404';
			  }
		   }else{
			  $status = '';
			  $emountnot = 'notEnouhCredit';
			  $redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
		   }
	   }
	   $data = array(
		  'status' => $status,
		  'redirect' => $redirect,
		  'enamount' => $emountnot
	   );
	   $result = json_encode($data);
	   echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $result);
	   if($status == 'ok'){
		  $userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
		  $toUserName = $userDeviceKey['i_username'];
		  $oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
		  $msgBody = $iN->iN_Secure($LANG['send_you_a_tip']);
		  $msgTitle = $iN->iN_Secure($LANG['tip_earning']).$currencys[$defaultCurrency]. $netUserEarning;
		  $URL = $base_url.'settings?tab=dashboard';
		  if($oneSignalUserDeviceKey){
			$iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
		  }
	    }
	}
}

/*Send Tip*/
if($type == 'p_sendTipMessage'){
	if(isset($_POST['tip_u']) && isset($_POST['tipVal']) && $_POST['tip_u'] != '' &&  $_POST['tipVal'] != '' && !empty($_POST['tip_u']) && !empty($_POST['tipVal'])){
	   $tiSendingUserID = mysqli_real_escape_string($db, $_POST['tip_u']);
	   $tipAmount = mysqli_real_escape_string($db, $_POST['tipVal']);
	   $chatID = mysqli_real_escape_string($db, $_POST['chID']);
	   $redirect = '';
	   $emountnot = '';
	   $status = '';
	   if($tipAmount < $minimumTipAmount){
		  exit('notEnough');
	   }else{
		  if ($userCurrentPoints >= $tipAmount && $userID != $tiSendingUserID) {

			  $netUserEarning = $tipAmount * $onePointEqual;
			  $adminEarning = ($adminFee * $netUserEarning) / 100;
			  $userNetEarning = $netUserEarning - $adminEarning;

			  $UpdateUsersWallet = $iN->iN_UpdateUsersWallets($userID, $tiSendingUserID, $tipAmount, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
			  if($UpdateUsersWallet){
				 $status = 'ok';
			  }else{
				 exit('404');
			  }
		   }else{
			  exit('notEnouhCredit');
			  $redirect =  iN_HelpSecure($base_url) . 'purchase/purchase_point';
		   }
	   }

	   if($status == 'ok'){
		  $userDeviceKey = $iN->iN_GetuserDetails($tiSendingUserID);
		  $toUserName = $userDeviceKey['i_username'];
		  $oneSignalUserDeviceKey = isset($userDeviceKey['device_key']) ? $userDeviceKey['device_key'] : NULL;
		  $msgBody = $iN->iN_Secure($LANG['send_you_a_tip']);
		  $msgTitle = $iN->iN_Secure($LANG['tip_earning']).$currencys[$defaultCurrency]. $netUserEarning;
		  $URL = $base_url.'settings?tab=dashboard';
		  if($oneSignalUserDeviceKey){
			$iN->iN_OneSignalPushNotificationSend($msgBody, $msgTitle, $url, $oneSignalUserDeviceKey, $oneSignalApi, $oneSignalRestApi);
		  }
		  $message = $userNetEarning;
		  $sendedGiftMoney = $tipAmount;
		  $insertData = $iN->iN_InsertNewTipMessage($userID, $chatID, $message, $sendedGiftMoney);
			if ($insertData) {
				$cMessageID = $insertData['con_id'];
				$cUserOne = $insertData['user_one'];
				$cUserTwo = $insertData['user_two'];
				$cMessage = $insertData['message'];
				$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
				$mSeenStatus = $insertData['seen_status'];
				$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
				$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
				$cMessageTime = $insertData['time'];
				$ip = $iN->iN_GetIPAddress();
				$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
				if ($query && $query['status'] == 'success') {
					date_default_timezone_set($query['timezone']);
				}
				$message_time = date("c", $cMessageTime);
				$convertMessageTime = strtotime($message_time);
				$netMessageHour = date('H:i', $convertMessageTime);
				$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
				$msgDots = '';
				$imStyle = '';
				$seenStatus = '';
				if ($cUserOne == $userID) {
					$mClass = 'me';
					$msgOwnerID = $cUserOne;
					$lastM = '';
					$timeStyle = 'msg_time_me';
					if (!empty($cFile)) {
						$imStyle = 'mmi_i';
					}
					$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					if ($mSeenStatus == '1') {
						$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
					}
					if($gifMoney){
                        $SGifMoneyText = preg_replace( '/{.*?}/', $cMessage, $LANG['youSendGifMoney']);
                    }
				} else {
					$mClass = 'friend';
					$msgOwnerID = $cUserOne;
					$lastM = 'mm_' . $msgOwnerID;
					if (!empty($cFile)) {
						$imStyle = 'mmi_if';
					}
					if($gifMoney){
                        $msgOwnerFullName = $iN->iN_UserFullName($msgOwnerID);
                        $SGifMoneyText = $iN->iN_TextReaplacement($LANG['sendedGifMoney'],[$msgOwnerFullName , $cMessage]);
                    }
					$timeStyle = 'msg_time_fri';
				}
				$styleFor = '';
				if ($cStickerUrl) {
					$styleFor = 'msg_with_sticker';
					$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
				}
				if ($cGifUrl) {
					$styleFor = 'msg_with_gif';
					$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
				}
				$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
				include "../themes/$currentTheme/layouts/chat/newMessage.php";
			}

	   }
	}
}
  /*Buy Video Call*/
  if($type == 'buyVideoCall'){
     if(isset($_POST['calledID']) && $_POST['calledID'] !== '' && !empty($_POST['calledID']) && isset($_POST['callName']) && $_POST['callName'] !== '' && !empty($_POST['callName'])){
		$calledUserID = mysqli_real_escape_string($db, $_POST['calledID']);
		$videoCallName = mysqli_real_escape_string($db, $_POST['callName']);
		$callerDetails = $iN->iN_GetUserDetails($calledUserID);
		$callerUserFullName = $callerDetails['i_user_fullname'];
		$callerUserName = $callerDetails['i_username'];
		$videoCallPrice = $callerDetails['video_call_price'];
		$whoCanVideoCall = $callerDetails['who_can_call'];
		$subStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $calledUserID);
		$checkUserIsCreator = $iN->iN_CheckUserIsCreator($calledUserID);
		$callerUserAvatar = $iN->iN_UserAvatar($calledUserID, $base_url);
		if($isVideoCallFree == 'no'){
			include "../themes/$currentTheme/layouts/popup_alerts/buyVideoCall.php";
		}else if($isVideoCallFree == 'yes'){
			$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
			include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
		}else{
			exit('404');
		}
	 }
  }
  /*Create a video call*/
  if($type == 'createVideoCall'){
      if(isset($_POST['calledID']) && $_POST['calledID'] !== '' && !empty($_POST['calledID']) && isset($_POST['callName']) && $_POST['callName'] !== '' && !empty($_POST['callName'])){
		    $calledUserID = mysqli_real_escape_string($db, $_POST['calledID']);
			$videoCallName = mysqli_real_escape_string($db, $_POST['callName']);
			$callerDetails = $iN->iN_GetUserDetails($calledUserID);
			$callerUserFullName = $callerDetails['i_user_fullname'];
			$callerUserName = $callerDetails['i_username'];
			$videoCallPrice = $callerDetails['video_call_price'];
			$whoCanVideoCall = $callerDetails['who_can_call'];
			$callerUserAvatar = $iN->iN_UserAvatar($calledUserID, $base_url);
			if($whoCanVideoCall == '0'){
				$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
				include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
			}else{
				if($userCurrentPoints < $videoCallPrice && $userID != $calledUserID && $isVideoCallFree == 'no'){
					exit();
				}else if($isVideoCallFree == 'no'){
					$netUserEarning = $videoCallPrice * $onePointEqual;
					$adminEarning = ($adminFee * $netUserEarning) / 100;
					$userNetEarning = $netUserEarning - $adminEarning;
					$UpdateUsersWallet = $iN->iN_UpdateUsersWalletsForVideoCall($userID, $calledUserID, $videoCallPrice, $netUserEarning,$adminFee, $adminEarning, $userNetEarning);
					if($UpdateUsersWallet){
						$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
						include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
					}else{
						exit('404');
					}
				}else{
					$insertChannelName = $iN->iN_InsertVideoCall($userID, $videoCallName, $calledUserID);
					include "../themes/$currentTheme/layouts/popup_alerts/videoCalling.php";
				}
		    }
	  }
  }
  /*Video Call Alert*/
  if($type == 'videoCallAlert'){
      if(isset($_POST['call']) && !empty($_POST['call']) && $_POST['call'] !== ''){
          $callID = mysqli_real_escape_string($db, $_POST['call']);
		  $callDetails = $iN->iN_VideoCallDetails($callID);
		  $callerUserID = $callDetails['caller_uid_fk'];
		  $chatUrl = $callDetails['vc_id'];
		  $callerDetails = $iN->iN_GetUserDetails($callerUserID);
		  $callerUserFullName = $callerDetails['i_user_fullname'];
		  $callerUserName = $callerDetails['i_username'];
		  $callerUserAvatar = $iN->iN_UserAvatar($callerUserID, $base_url);
		  if($fullnameorusername == 'no'){
			$callerUserFullName = $callerUserName;
		  }
		  include "../themes/$currentTheme/layouts/popup_alerts/videocallalert.php";
	  }
  }
  /*Video Call Accept*/
  if($type == 'call_accepted'){
    if(isset($_POST['accID']) && !empty($_POST['accID']) && $_POST['accID'] !== ''){
		$callID = mysqli_real_escape_string($db, $_POST['accID']);
		$callDetails = $iN->iN_VideoCallAcceptDetails($callID);
		$chatUrl = $callDetails['chat_id_fk'];
		echo iN_HelpSecure($base_url) . 'chat?chat_width=' . $chatUrl;
	}
  }
if ($type == 'liveVideoMute') {
    if (isset($_POST['chName']) && !empty($_POST['chName'])) {
        $channelName = mysqli_real_escape_string($db, $_POST['chName']);

        $callQuery = mysqli_query($db, "SELECT vc_id, video_muted FROM i_video_call WHERE voice_call_name = '$channelName' LIMIT 1");

        if ($callQuery && mysqli_num_rows($callQuery) > 0) {
            $call = mysqli_fetch_assoc($callQuery);
            $currentStatus = (int)$call['video_muted'];
            $newStatus = $currentStatus === 1 ? 0 : 1;

            $update = mysqli_query($db, "UPDATE i_video_call SET video_muted = '$newStatus' WHERE vc_id = '{$call['vc_id']}'");

            echo json_encode([
                'status' => $update ? 'success' : 'error',
                'muted' => $newStatus,
                'message' => $update ? 'Updated successfully' : 'Update failed'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Channel not found'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing channel name'
        ]);
    }
    exit(); // her durumda çık
}
  /*Live Call End*/
  if($type == 'liveEnd'){
     if(isset($_POST['chName']) && !empty($_POST['chName']) && $_POST['chName'] !== ''){
        $channelName = mysqli_real_escape_string($db, $_POST['chName']);
		$checkAndDeleteCall = $iN->iN_CheckAndDeleteCall($userID, $channelName);
		if($checkAndDeleteCall){
           exit('200');
		}else{
			exit('404');
		}
	 }
  }
  /*Video Call Decline*/
  if($type == 'call_declined'){
    if(isset($_POST['accID']) && !empty($_POST['accID']) && $_POST['accID'] !== ''){
		$callID = mysqli_real_escape_string($db, $_POST['accID']);
		$callDetails = $iN->iN_VideoCallDeclineDetails($callID);
	}
  }
  /*Update Video Call fee*/
  if($type == 'vCost'){
     if(isset($_POST['vCostFee']) && !empty($_POST['vCostFee']) && $_POST['vCostFee'] !== ''){
          $videoCost = mysqli_real_escape_string($db, $_POST['vCostFee']);
		  if($videoCost == '0'){
            exit('not');
		  }
		$insertVideoCost = $iN->iN_UpdateVideoCost($userID, $videoCost);
	 }else{
		 exit('not');
	 }
  }
  if($type == 'moveMyEarnedPoints'){
	if(isset($_POST['myp']) && $_POST['myp'] != '' && !empty($_POST['myp'])){
		$totalEarned = mysqli_real_escape_string($db, $_POST['myp']);
		if($totalEarned < 1){
           exit('You don\'t have enough points to calculate yet.');
		}
		$moveMyPoint = $iN->iN_MovePointEarningsToPointBalance($userID, $totalEarned);
		if($moveMyPoint){
			exit('ok');
		}else{
			exit('me');
		}
	}else{
		exit('You don\'t have enough points to calculate yet.');
	}
  }
  /*Unlock Message*/
  if($type == 'unlockMessage'){
    if(isset($_POST['mi']) && !empty($_POST['mi']) && $_POST['mi'] != '' && isset($_POST['ci']) && !empty($_POST['ci']) && $_POST['ci'] != ''){
       $messageID = mysqli_real_escape_string($db, $_POST['mi']);
	   $chatID = mysqli_real_escape_string($db, $_POST['ci']);
	   $getMData = $iN->iN_GetMessageDetailsByID($messageID, $chatID);
	   $messagePrice = isset($getMData['private_price']) ? $getMData['private_price'] : NULL;
	   $userOne = isset($getMData['user_one']) ? $getMData['user_one'] : NULL;
	   $userTwo = isset($getMData['user_two']) ? $getMData['user_two'] : NULL;
	   if($userOne == $userID){
         $messageOwnerID = $userTwo;
	   }else{
		 $messageOwnerID = $userOne;
	   }
	   if($userCurrentPoints >= $messagePrice){
		    $translatePointToMoney = $messagePrice * $onePointEqual;
			$adminEarning = $translatePointToMoney * ($adminFee / 100);
			$userEarning = $translatePointToMoney - $adminEarning;
			$insertData = $iN->iN_UnLockMessage($userID, $messageID, $chatID, $adminEarning, $userEarning,$messageOwnerID, $translatePointToMoney, $adminFee, $messagePrice);
			if($insertData){
					$cMessageID = $insertData['con_id'];
					$cUserOne = $insertData['user_one'];
					$cUserTwo = $insertData['user_two'];
					$cMessage = $insertData['message'];
					$mSeenStatus = $insertData['seen_status'];
					$gifMoney = isset($insertData['gifMoney']) ? $insertData['gifMoney'] : NULL;
					$privateStatus = isset($insertData['private_status']) ? $insertData['private_status'] : NULL;
				    $privatePrice = isset($insertData['private_price']) ? $insertData['private_price'] : NULL;
					$cStickerUrl = isset($insertData['sticker_url']) ? $insertData['sticker_url'] : NULL;
					$cGifUrl = isset($insertData['gifurl']) ? $insertData['gifurl'] : NULL;
					$cMessageTime = $insertData['time'];
					$ip = $iN->iN_GetIPAddress();
					$query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
					if ($query && $query['status'] == 'success') {
						date_default_timezone_set($query['timezone']);
					}
					$message_time = date("c", $cMessageTime);
					$convertMessageTime = strtotime($message_time);
					$netMessageHour = date('H:i', $convertMessageTime);
					$cFile = isset($insertData['file']) ? $insertData['file'] : NULL;
					$msgDots = '';
					$imStyle = '';
					$seenStatus = '';
					if ($cUserOne == $userID) {
						$mClass = 'me';
						$msgOwnerID = $cUserOne;
						$lastM = '';
						$timeStyle = 'msg_time_me';
						if (!empty($cFile)) {
							$imStyle = 'mmi_i';
						}
						$seenStatus = '<span class="seenStatus flex_ notSeen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						if ($mSeenStatus == '1') {
							$seenStatus = '<span class="seenStatus flex_ seen">' . $iN->iN_SelectedMenuIcon('94') . '</span>';
						}
					} else {
						$mClass = 'friend';
						$msgOwnerID = $cUserOne;
						$lastM = 'mm_' . $msgOwnerID;
						if (!empty($cFile)) {
							$imStyle = 'mmi_if';
						}
						$timeStyle = 'msg_time_fri';
					}
					$styleFor = '';
					if ($cStickerUrl) {
						$styleFor = 'msg_with_sticker';
						$cMessage = '<img class="mStick" src="' . $cStickerUrl . '">';
					}
					if ($cGifUrl) {
						$styleFor = 'msg_with_gif';
						$cMessage = '<img class="mGifM" src="' . $cGifUrl . '">';
					}
					$msgOwnerAvatar = $iN->iN_UserAvatar($msgOwnerID, $base_url);
					include "../themes/$currentTheme/layouts/chat/unLockedMessage.php";
			}else{
			  exit('403');
			}
	   }else{
		  exit('404');
	   }
	}
  }
	/*Show PopUps*/
	if ($type == 'camAlert') {
		if (isset($_POST['al'])) {
			$alertType = mysqli_real_escape_string($db, $_POST['al']);
			include "../themes/$currentTheme/layouts/popup_alerts/popup_alerts.php";
		}
	}
	if ($type == 'getBoostList') {
		if(isset($_POST['bp']) && !empty($_POST['bp'])){
           $boostPostID = mysqli_real_escape_string($db, $_POST['bp']);
		   include "../themes/$currentTheme/layouts/popup_alerts/getBoostList.php";
		}
	}
	if($type =='boostThisPlan'){
		if(isset($_POST['pbID']) && !empty($_POST['bpID'])){
			$boostPlanID = mysqli_real_escape_string($db, $_POST['pbID']);
			$boostPostID = mysqli_real_escape_string($db, $_POST['bpID']);
		    $CheckboostIDExist = $iN->CheckBoostPlanExist($boostPlanID);
            if($CheckboostIDExist){
				$boostDetails = $iN->iN_GetBoostPostDetails($boostPlanID);
                $planAmount = $boostDetails['plan_amount'];
				$viewTime = $boostDetails['view_time'];
			    $checkPostBoostedeBefore = $iN->iN_CheckPostBoostedBefore($userID, $boostPostID);
				if($checkPostBoostedeBefore){
                   $getPostDetails = $iN->iN_GetAllPostDetails($boostPostID);
				   $boostedPostSlugUrl = isset($getPostDetails['url_slug']) ? $getPostDetails['url_slug'] : NULL;
				   $redirectThisURL = $base_url.'post/'.$boostedPostSlugUrl.'_'.$boostPostID;
				   echo iN_HelpSecure($redirectThisURL);
				   exit();
				}
				if($planAmount < $userCurrentPoints){
				   $boostInsert = $iN->iN_BoostInsert($userID, $boostPostID, $planAmount,$boostPlanID,$viewTime);
				   if($boostInsert){
						$getPostDetails = $iN->iN_GetAllPostDetails($boostPostID);
						$boostedPostSlugUrl = isset($getPostDetails['url_slug']) ? $getPostDetails['url_slug'] : NULL;
						$redirectThisURL = $base_url.'post/'.$boostedPostSlugUrl.'_'.$boostPostID;
				        echo iN_HelpSecure($redirectThisURL);
				   }
				}else{
					exit('404');
				}
			}
		}
	}
	/*Update Boost Status*/
	if($type == 'updateBoostStatus'){
		if(isset($_POST['bpid']) && !empty($_POST['bpid']) && isset($_POST['mod']) && in_array($_POST['mod'], $yesOrNo)){
		   $bPostID = isset($_POST['bpid']) ? $_POST['bpid'] : NULL;
		   $bpStatus = isset($_POST['mod']) ? $_POST['mod'] : NULL;
           $updateBoostPostStatus = $iN->iN_UpdateBoosPostStatus($userID, $bPostID, $bpStatus);
		   if($updateBoostPostStatus){
              exit('200');
		   }else{
			  exit('404');
		   }
		}
	}
	if ($type == 'uploadPaymentSuccessImage') {
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
						if (!file_exists($uploadFile . $d)) {
							$newFile = mkdir($uploadFile . $d, 0755);
						}
						if (!file_exists($xImages . $d)) {
							$newFile = mkdir($xImages . $d, 0755);
						}
						if (!file_exists($xVideos . $d)) {
							$newFile = mkdir($xVideos . $d, 0755);
						}
						if (move_uploaded_file($tmp, $uploadFile . $d . '/' . $getFilename)) {
							/*IF FILE FORMAT IS VIDEO THEN DO FOLLOW*/
							if ($fileTypeIs == 'Image') {
								$pathFile = 'uploads/files/' . $d . '/' . $getFilename;
								$pathXFile = 'uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
								$postTypeIcon = '<div class="video_n">' . $iN->iN_SelectedMenuIcon('53') . '</div>';
								$thePath = '../uploads/files/' . $d . '/'.$UploadedFileName . '.' . $ext;
								if (file_exists($thePath)) {
									try {
										$dir = "../uploads/pixel/" . $d . "/" . $getFilename;
										$fileUrl = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
										$image = new ImageFilter();
										$image->load($fileUrl)->pixelation($pixelSize)->saveFile($dir, 100, "jpg");
									} catch (Exception $e) {
										echo '<span class="request_warning">' . $e->getMessage() . '</span>';
									}
							    }else{
									exit('Upload Failed');
								}
								if ($s3Status == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $s3Bucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if ($WasStatus == '1') {
									/*Upload Video tumbnail*/
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$key = basename($thevTumbnail);
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/files/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									try {
										$result = $s3->putObject([
											'Bucket' => $WasBucket,
											'Key' => 'uploads/pixel/' . $d . '/' . $key,
											'Body' => fopen($thevTumbnail, 'r+'),
											'ACL' => 'public-read',
											'CacheControl' => 'max-age=3153600',
										]);
										$UploadSourceUrl = $result->get('ObjectURL');
										@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									} catch (Aws\S3\Exception\S3Exception $e) {
										echo "There was an error uploading the file.\n";
									}
								}else if($digitalOceanStatus == '1'){
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '_' . $userID . '.' . $ext;
									/*IF DIGITALOCEAN AVAILABLE THEN*/
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/files/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									$thevTumbnail = '../uploads/pixel/' . $d . '/' . $UploadedFileName . '.' . $ext;
									$my_space = new SpacesConnect($oceankey, $oceansecret, $oceanspace_name, $oceanregion);
									$upload = $my_space->UploadFile($thevTumbnail, "public");
									/**/
									@unlink($xImages . $d . '/' . $UploadedFileName . '.' . $ext);
									@unlink($uploadFile . $d . '/' . $UploadedFileName . '.' . $ext);
									if($upload){
										$UploadSourceUrl = 'https://'.$oceanspace_name.'.'.$oceanregion.'.digitaloceanspaces.com/uploads/files/' . $d . '/' . $getFilename;
									 }
									/*/IF DIGITAOCEAN AVAILABLE THEN*/
								 } else {
									$UploadSourceUrl = $base_url . 'uploads/files/' . $d . '/' . $getFilename;
								}
							}
							$insertFileFromUploadTable = $iN->iN_INSERTUploadedScreenShotForPaymentComplete($userID, $pathFile, NULL, $pathXFile, $ext);
							$getUploadedFileID = $iN->iN_GetUploadedFilesIDs($userID, $pathFile);
							/*AMAZON S3*/
							echo '
								<div class="i_uploaded_item in_' . $theValidateType . ' iu_f_' . $getUploadedFileID['upload_id'] . '" id="' . $getUploadedFileID['upload_id'] . '">
								' . $postTypeIcon . '
								<div class="i_delete_item_button" id="' . $getUploadedFileID['upload_id'] . '">
									' . $iN->iN_SelectedMenuIcon('5') . '
								</div>
								<div class="i_uploaded_file" style="background-image:url(' . $UploadSourceUrl . ');">
										<img class="i_file" src="' . $UploadSourceUrl . '" alt="' . $UploadSourceUrl . '">
								</div>
								</div>
							';
						}
					} else {
						echo iN_HelpSecure($size);
					}
				}
			}
		}
	}
	/*Send Account Verificatoun Request*/
	if ($type == 'verificationRequestForBankPayment') {
		if (isset($_POST['cP']) && isset($_POST['pID'])) {
			$cardIDPhoto = mysqli_real_escape_string($db, $_POST['cP']);
			$planID = mysqli_real_escape_string($db, $_POST['pID']);
			$planData = $iN->GetPlanDetails($planID);
			$planAmount = isset($planData['amount']) ? $planData['amount'] : NULL;
		    $planPoint = isset($planData['plan_amount']) ? $planData['plan_amount'] : NULL;
			$checkCardIDPhotoExist = $iN->iN_CheckImageIDExist($cardIDPhoto, $userID);
			if (empty($cardIDPhoto) && empty($checkCardIDPhotoExist)) {
				echo 'card';
				return false;
			}
			if ($checkCardIDPhotoExist == '1') {
				$InsertNewVerificationRequest = $iN->iN_InsertNewBankPaymentVerificationRequest($userID, $cardIDPhoto, $planAmount, $planPoint,$planID);
				if ($InsertNewVerificationRequest) {
					echo '200';
				}
			} else {
				echo 'both';
			}
		}
	}
	/*Purchase The Frame*/
	if($type == 'buyFrameGift'){
	   if(isset($_POST['type']) && $_POST['type'] != '' && !empty($_POST['type']) && isset($_POST['pUf']) && $_POST['pUf'] != '' && !empty($_POST['pUf'])){
	       $frameID = mysqli_real_escape_string($db, $_POST['type']);
	       $purchaseForThisUser = mysqli_real_escape_string($db, $_POST['pUf']);
	       $checFrameExist = $iN->CheckFramePlanExist($frameID);
	       $frameData = $iN->GetFramePlanDetails($frameID);
	       $framePrice = isset($frameData['f_price']) ? $frameData['f_price'] : '0';
	       if($checFrameExist && $framePrice < $userCurrentPoints){
	           $insertPurchase = $iN->iN_PurchaseFrame($userID, $purchaseForThisUser, $frameID,$onePointEqual);
	           if($insertPurchase){
	               exit('200');
	           }else{
	               exit('404');
	           }
	       }else {
	       	  exit('505');
	       }
	   }
	}
	/*Update Frame*/
	if($type == 'UpdateMyFrame'){
	    if(isset($_POST['frameID'])){
	        $frameID = mysqli_real_escape_string($db, $_POST['frameID']);
	        $updateFrame = $iN->iN_UpdateFrame($userID, $frameID);
	        if($updateFrame){
	            exit('200');
	        }else{
	            exit('400');
	        }
	    }
	}
	if ($type == 'aiBox') {
		include "../themes/$currentTheme/layouts/popup_alerts/aiBox.php";
	}
	if ($type == 'generateAiContent' && $openAiStatus == '1') {
        if (isset($_POST['uPrompt']) && !empty($_POST['uPrompt'])) {
            $userPrompt = trim(strip_tags($_POST['uPrompt']));
            $aiContent = callOpenAI($userPrompt, $opanAiKey);
            if ($aiContent != 'no') {
                $walletDone = $iN->iN_AiUsed($userID, $perAiUse);
                if ($walletDone) {
                    exit($aiContent);
                } else {
                    exit('no_enough_credit');
                }
            } else {
                exit(iN_HelpSecure($LANG['please_check_api_key']));
            }
        }
    }
	/*Delete My User Account*/
    if ($type == 'deleteMyAccount') {
        if (isset($_POST['deleteMe']) && isset($_POST['crn_password'])) {
            $deleteThisUserAccount = mysqli_real_escape_string($db, $_POST['deleteMe']);
            $currentPassword = mysqli_real_escape_string($db, $_POST['crn_password']);

            if ($deleteThisUserAccount == $userID) {
                $isValidPassword = $iN->iN_VerifyCurrentPassword($userID, $currentPassword);
                if ($isValidPassword) {
                    $deleteMyAccount = $iN->iN_DeleteMyUserAccount($userID, $deleteThisUserAccount);
                    if ($deleteMyAccount) {
                        session_destroy();
                        exit('200');
                    } else {
                        exit('500');
                    }
                } else {
                    exit('403');
                }
            } else {
                exit('401');
            }
        }
    }
} elseif (isset($_POST['f'])) {
	$loginFormClass = '';
	$type = mysqli_real_escape_string($db, $_POST['f']);
	if ($type == 'searchCreator') {
		if (isset($_POST['s'])) {
			$searchValue = mysqli_real_escape_string($db, $_POST['s']);
			$searchValueFromData = $iN->iN_GetSearchResult($iN->iN_Secure($searchValue), $showingNumberOfPost, $whicUsers);
			include "../themes/$currentTheme/layouts/header/searchResults.php";
		}
	}
	if ($type == 'forgotPass') {
		if (isset($_POST['email']) && !empty($_POST['email'])) {
			$sendEmail = mysqli_real_escape_string($db, $_POST['email']);
			$checkEmailExist = $iN->iN_CheckEmailExistForRegister($iN->iN_Secure($sendEmail));
			if ($checkEmailExist) {
				$code = md5(rand(1111, 9999) . time());
				if ($emailSendStatus == '1') {
					$insertNewCode = $iN->iN_InsertNewForgotPasswordCode($iN->iN_Secure($sendEmail), $iN->iN_Secure($code));
					$activateLink = $base_url . 'reset_password?active=' . $code;
					$wrapperStyle = "width:100%; border-radius:3px; background-color:#fafafa; text-align:center; padding:50px 0; overflow:hidden;";
                    $containerStyle = "width:100%; max-width:600px; border:1px solid #e6e6e6; margin:0 auto; background-color:#ffffff; padding:30px; border-radius:3px;";
                    $logoBoxStyle = "width:100%; max-width:100px; margin:0 auto 30px auto; overflow:hidden;";
                    $imgStyle = "width:100%; display:block;";
                    $titleStyle = "width:100%; position:relative; display:inline-block; padding-bottom:10px;";
                    $buttonBoxStyle = "width:100%; position:relative; padding:10px; background-color:#20B91A; max-width:350px; margin:0 auto; color:#ffffff;";
                    $linkStyle = "text-decoration:none; color:#ffffff; font-weight:500; font-size:18px; display:inline-block;";
					if ($insertNewCode) {
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

                                <div style="' . $titleStyle . '">
                                  <strong>Forgot your Password?</strong> reset it below:
                                </div>

                                <div style="' . $buttonBoxStyle . '">
                                  <a href="' . $activateLink . '" style="' . $linkStyle . '">
                                    Reset Password
                                  </a>
                                </div>

                              </div>
                            </div>';
						$mail->setFrom($smtpEmail, $siteName);
						$send = false;
						$mail->IsHTML(true);
						$mail->addAddress($sendEmail, ''); // Add a recipient
						$mail->Subject = $iN->iN_Secure($LANG['forgot_password']);
						$mail->CharSet = 'utf-8';
						$mail->MsgHTML($body);
						if ($mail->send()) {
							$mail->ClearAddresses();
							echo '200';
							return true;
						}
					}
				} else {
					echo '3';
				}
			} else {
				echo '2';
			}
		} else {
			exit('1');
		}
	}

	/*Reset Password*/
	if ($type == 'iresetpass') {
		$activationCode = mysqli_real_escape_string($db, $_POST['ac']);
		$newPassword = mysqli_real_escape_string($db, $_POST['pnew']);
		$confirmNewPassword = mysqli_real_escape_string($db, $_POST['repnew']);
		$checkCodeExist = $iN->iN_CheckCodeExist($activationCode);
		if ($checkCodeExist) {
			if (strlen($newPassword) < 6 || strlen($confirmNewPassword) < 6) {
				exit('5');
			}
			if (!empty($newPassword) && $newPassword != '' && isset($newPassword) && !empty($confirmNewPassword) && $confirmNewPassword != '' && isset($confirmNewPassword)) {
				if ($newPassword != $confirmNewPassword) {
					exit('2');
				} else {
					$newPassword = sha1(md5($newPassword));
					$updateNewPassword = $iN->iN_ResetPassword($iN->iN_Secure($activationCode), $iN->iN_Secure($newPassword));
					if ($updateNewPassword) {
						exit('200');
					} else {
						exit('404');
					}
				}
			} else {
				exit('4');
			}
		}
	}
	/*Check Claim*/
	if ($type == 'claim') {
    	if (isset($_POST['clnm']) && !empty($_POST['clnm'])) {
    		$checkUserNameExist = $iN->iN_CheckUsernameExistForRegister($_POST['clnm']);

    		if ($checkUserNameExist) {
    			echo json_encode(['status' => '2']);
    			exit;
    		}

    		if (!preg_match('/^[\w]+$/', $_POST['clnm'])) {
    			echo json_encode(['status' => '5']);
    			exit;
    		}

    		echo json_encode(['status' => '200']);
    		exit;
    	} else {
    		echo json_encode(['status' => '3']);
    		exit;
    	}
    }
}
?>
