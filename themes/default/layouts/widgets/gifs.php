<div class="stickersContainer">
    <div class="stickers_wrapper">
        <div class="giphy_results_container"> 
            <?php  
            if (!isset($giphyKey) || empty($giphyKey)) {
                echo '<div class="no_gif_found">API key is missing.</div>';
                exit();
            }

            $encodedKey = urlencode($giphyKey);
            $apiUrl = "https://api.giphy.com/v1/gifs/trending?api_key=" . $encodedKey . "&limit=25&rating=pg"; 
             
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);

            if (curl_errno($ch) || !$response) {
                echo '<div class="no_gif_found">Could not retrieve GIFs. Please try again later.</div>';
                curl_close($ch);
                exit();
            }

            curl_close($ch);

            $json = json_decode($response);

            if (!isset($json->data) || !is_array($json->data) || count($json->data) === 0) {
                echo '<div class="no_gif_found">No trending GIFs found.</div>';
                exit();
            }

            foreach ($json->data as $gif) {
                $giphyImageUrl = $gif->images->fixed_height->url ?? '';
                if ($giphyImageUrl) {
                    echo '<img class="rGif transition" data-id="' . iN_HelpSecure($id) . '" src="' . iN_HelpSecure($giphyImageUrl) . '">';
                }
            }
            ?>
        </div>
    </div> 
</div>