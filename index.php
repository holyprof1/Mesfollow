<?php  
// Load required core files and application configuration
include_once "includes/inc.php";

// Break down the request URI to determine the current page
$requestUrl  = explode('/', $_SERVER["REQUEST_URI"]);
$activePage  = end($requestUrl);
$requestUri  = $_SERVER['REQUEST_URI'];

// Initialize variables to capture URI components
$paramsOffset   = strpos($requestUri, '?');
$requestPath    = $page = '';
$requestParams  = [];



// Update the user's last activity time if they are logged in
if ($logedIn == '1') {
    $updateLastSeen = $iN->iN_UpdateLastSeen($userID);
}

// Extract GET parameters from the URL if present
if ($paramsOffset > -1) {
    $requestPath = substr($requestUri, 0, $paramsOffset);
    $params = explode('&', substr($requestUri, $paramsOffset + 1));
    
    foreach ($params as $value) {
        $keyValue = explode('=', $value);
        $requestParams[$keyValue[0]] = isset($keyValue[1]) ? $keyValue[1] : '';
    }
} else {
    $requestPath = $requestUri;
}

// If maintenance mode is enabled, show maintenance page to standard users
if ($logedIn == '1') {
    if ($userType != '2') {
        if ($maintenanceMode == '1') {
            include 'sources/maintenance.php';
            exit();
        }
    }
}

// Force unverified users to verify their email address (unless already on verify page)
if (preg_match('~([[\w.-]+)~u', urldecode($requestUri), $match)) {
    $tag = $match[1];
    $thePage = mysqli_real_escape_string($db, $match[1]);

    if ($userEmailVerificationStatus == 'no' && $thePage != 'verify' && !empty($smtpEmail)) {
        if ($userType != '2') {
            if ($emailSendStatus == '1') {
                include 'sources/verifyme.php';
                exit();
            }
        }
    }
}

// Decode a specific base64-encoded page (for security reasons or obfuscation)
if (preg_match('~([[\w.-]+)~u', urldecode($requestUri), $match)) {
    $tag = $match[1];
    $thePage = mysqli_real_escape_string($db, $match[1]);
    if ($thePage == base64_decode('YmVsZWdhbA==')) {
        include('sources/' . $thePage . '.php');
        exit();
    }
}

// Handle direct routing to the sharer page
if (preg_match('~([[\w.-]+)~u', urldecode($requestUri), $match)) {
    $tag = $match[1];
    $thePage = mysqli_real_escape_string($db, $match[1]);
    if ($thePage == 'sharer') {
        include('sources/sharer.php');
        exit();
    }
}

// Handle access to admin panel – only allowed for admin users
if (preg_match('~/(admin)/([[\w.-]+)~', urldecode($requestUri), $match)) {
    if ($userType == '1') {
        header('Location:' . $base_url . '');
        exit();
    } else {
        $tag = $match[1];
        $pageFor = mysqli_real_escape_string($db, $match[2]);
        include 'admin/' . $adminTheme . '/index.php';
    }

// Handle routing for content types like posts, products, and media
} else if (preg_match('~/(photos|videos|albums|post|product)/([[\w.-]+)~u', urldecode($requestUri), $match)) {
    $urlMatch = mysqli_real_escape_string($db, $match[1]);
    $slugyUrl = mysqli_real_escape_string($db, $match[2]);
    $checkUsername = $iN->iN_CheckUserName($urlMatch);

    if ($urlMatch == 'post') {
        include 'sources/post.php';
    } else if ($urlMatch == 'product') {
        include 'sources/product.php';
    }

// Handle hashtag pages, explore, live, creator and purchase routes
} else if (preg_match('~/(hashtag|explore|creator|purchase|live)/([[\w.-_]+)~u', urldecode($requestUri), $match)) {
    $tag = $match[1];
    $urlMatch = mysqli_real_escape_string($db, $match[1]);
    $pageFor = mysqli_real_escape_string($db, $iN->url_Hash($match[2]));
    $pageForPage = mysqli_real_escape_string($db, $match[2]);
    $hst = NULL;

    if ($urlMatch != 'live') {
        $hst = $iN->iN_GetHashTagsSearch($pageFor, NULL, $showingNumberOfPost);
    }

    // Check for special cases such as becoming a creator or purchasing points
    if ($pageForPage == 'becomeCreator') {
        include 'sources/becomeCreator.php';
    } else if ($pageForPage == 'purchase_point') {
        include 'sources/purchase_point.php';
    } else if ($hst) {
        include 'sources/hashtag.php';
    } else {
       // Fallback to user profile or live stream check
$pageFor = preg_replace('/[ ,]+/', '_', trim($pageFor));
$checkUsername = $iN->iN_CheckUserName($pageFor);


		
		
		
		
		// Fallback to user profile or live stream check
$pageFor = preg_replace('/[ ,]+/', '_', trim($pageFor));
$checkUsername = $iN->iN_CheckUserName($pageFor);

if ($checkUsername) {
    $getUserID = $iN->iN_GetUserDetailsFromUsername($pageFor);
    $lUserID = $getUserID['iuid'];

    /*******************************************************************/
    /* START: THIS IS THE EXACT WORKING LOGIC FROM YOUR PROFILE PAGE   */
    /*******************************************************************/
    $isUserActuallyLive = false;
    try {
        $dbh = isset($iN->db) ? $iN->db : $db;
        $sql = "SELECT started_at, finish_time FROM i_live WHERE live_uid_fk = ? ORDER BY live_id DESC LIMIT 1";
        $row = null;
        if ($dbh instanceof mysqli) {
            if ($st = $dbh->prepare($sql)) {
                $st->bind_param("i", $lUserID);
                if ($st->execute()) {
                    $res = $st->get_result();
                    $row = $res ? $res->fetch_assoc() : null;
                }
                $st->close();
            }
        }

        if ($row) {
            $now = time();
            $sta = (int)($row['started_at'] ?? 0);
            $fin = (int)($row['finish_time'] ?? 0);
            // Condition 1: Stream finished in the last 2 minutes (grace period)
            if ($fin >= $now - 120) {
                $isUserActuallyLive = true;
            // Condition 2: Stream has no finish time AND started in the last 6 hours
            } elseif ($fin == 0 && $sta > $now - 21600) {
                $isUserActuallyLive = true;
            }
        }
    } catch (Throwable $e) {
        $isUserActuallyLive = false;
    }
    /*******************************************************************/
    /* END: COPIED LOGIC                                               */
    /*******************************************************************/

    // Now, we use our reliable variable to make the decision
    if ($isUserActuallyLive) {
        // If the user is live, we now fetch the FULL details needed for the page to work.
        // THIS LINE FIXES THE BROKEN AVATAR ON THE PURCHASE POPUP.
        $liveDetails = $iN->iN_GetLiveStreamingDetails($lUserID);
        if ($liveDetails) {
             include 'sources/live.php';
        } else {
             // Safety fallback in case details can't be fetched
             include("themes/$currentTheme/live-ended-page.php");
             exit();
        }
    } else {
        // If the user is not live, show the ended page.
        include("themes/$currentTheme/live-ended-page.php");
        exit();
    }
} else {
    header('Location:' . $base_url . '404');
}
		
		
		
		
}
// Handle static routes and fallback to default content pages
} else if (preg_match('~/([[\w.-]+)~', $requestUri, $match)) {
    $urlMatch = mysqli_real_escape_string($db, $match[1]);
    $pageGet = $pageCreator = $pageCategory = '';

    // Optional query string filtering
    if (isset($_GET['tab'])) {
        $pageGet = mysqli_real_escape_string($db, $_GET['tab']);
    }
    if (isset($_GET['cat'])) {
        $pageCategory = mysqli_real_escape_string($db, $_GET['cat']);
    }
    if (isset($_GET['creator'])) {
        $pageCreator = mysqli_real_escape_string($db, $_GET['creator']);
    }

    $checkUsername = $iN->iN_CheckUserName($urlMatch);

    // Handle query-based or profile page routing
    if ($pageGet) {
        include 'sources/settings.php';
    } else if ($pageCreator) {
        include 'sources/creators.php';
    } else if ($checkUsername) {
        include 'sources/profile.php';
    } else if ($pageCategory) {
        include 'sources/marketplace.php';
    } else { 
        $routes = [
            'index'             => 'home.php',
            'index.php'         => 'home.php',
            'settings'          => 'settings.php',
            'chat'              => 'chat.php',
            'chat.php'          => 'chat.php',
            'notifications'     => 'notifications.php',
            'payment-success'   => 'payment-success.php',
            'payment-success.php' => 'payment-success.php',
            'payment-failed'    => 'payment-failed.php',
            'payment-failed.php'=> 'payment-failed.php',
            'payment-response'  => 'payment-response.php',
            'creators'          => 'creators.php',
            'creators.php'      => 'creators.php',
			'videos'            => 'videos.php',
            'marketplace'       => 'marketplace.php',
            'marketplace.php'   => 'marketplace.php',
            'saved'             => 'saved.php',
            'googleLogin'       => 'googleLogin.php',
            'twitterLogin'      => 'twitterLogin.php',
            'register'          => 'register.php',
            'reset_password'    => 'reset_password.php',
            'live_streams'      => 'live_streams.php',
            'verify'            => 'verify.php',
            'createStory'       => 'createStory.php',
            'friends_stories'   => 'friends_stories.php',
			'login'             => 'login.php',
        ];

        // Match route and include corresponding page or fallback
        $page = $routes[$match[1]] ?? null;

        if ($page && file_exists("sources/$page")) {
            include "sources/$page";
        } else {
            include "sources/page.php";
        }
    }

// Handle root (/) path by showing the homepage
} else if ($requestPath == '/') {
    include "sources/home.php";
    exit();

// If no route is matched, return 404 error
} else {
    header('HTTP/1.0 404 Not Found');
    echo "<h1>404 Not Found</h1>";
    echo "The page that you have requested could not be found.";
}
?>













