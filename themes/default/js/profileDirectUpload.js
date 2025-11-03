(function($) {
  'use strict';
  
  if (window._profileUploadBound) return;
  window._profileUploadBound = true;

  const siteUrl = window.siteurl || '/';
  
  // Loader HTML
  const loader = '<div class="upload-loader" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.7);color:white;padding:15px 25px;border-radius:8px;z-index:999;font-size:14px;">Uploading...</div>';

  // COVER UPLOAD
  $('body').on('click', '.editProfileCoverBtn', function(e) {
    e.preventDefault();
    console.log('📷 Cover edit clicked');
    $('#profile_cover_upload').click();
  });

  $('#profile_cover_upload').on('change', function() {
    const file = this.files[0];
    if (!file) return;
    
    console.log('📁 Cover file selected:', file.name);
    
    // Show loader
    $('.i_profile_cover').css('position', 'relative').append(loader);
    
    // Read file as base64
    const reader = new FileReader();
    reader.onload = function(e) {
      const base64 = e.target.result;
      console.log('✅ File read, uploading...');
      
      // Upload to server
      $.ajax({
        type: 'POST',
        url: siteUrl + 'requests/request.php',
        data: { 
          f: 'coverUpload', 
          image: base64 
        },
        timeout: 30000,
        success: function(response) {
          console.log('📨 Response:', response);
          
          // Parse URL from response
          let url = '';
          try {
            const cleaned = String(response).trim().replace(/^(200|404)\s*/i, '');
            if (cleaned.includes('{')) {
              const json = JSON.parse(cleaned);
              url = json.cover || json.url || '';
            } else if (cleaned.includes('/')) {
              url = cleaned;
            }
          } catch(e) {
            if (String(response).includes('/')) {
              url = String(response).trim();
            }
          }
          
          $('.upload-loader').remove();
          
          if (url) {
            console.log('✅ Cover updated:', url);
            // Update cover image
            $('.i_profile_cover').css('background-image', 'url(' + url + ')');
            // Show success message
            showSuccessMessage('Cover photo updated!');
          } else {
            console.error('❌ No URL in response');
            alert('Upload failed. Please try again.');
          }
        },
        error: function(xhr, status, error) {
          console.error('❌ Upload error:', error);
          $('.upload-loader').remove();
          alert('Upload failed: ' + error);
        }
      });
    };
    
    reader.onerror = function() {
      $('.upload-loader').remove();
      alert('Error reading file');
    };
    
    reader.readAsDataURL(file);
  });

  // AVATAR UPLOAD
  $('body').on('click', '.editProfilePictureBtn', function(e) {
    e.preventDefault();
    console.log('📷 Avatar edit clicked');
    $('#profile_avatar_upload').click();
  });

  $('#profile_avatar_upload').on('change', function() {
    const file = this.files[0];
    if (!file) return;
    
    console.log('📁 Avatar file selected:', file.name);
    
    // Show loader
    $('.i_profile_picture').css('position', 'relative').append(loader);
    
    // Read file as base64
    const reader = new FileReader();
    reader.onload = function(e) {
      const base64 = e.target.result;
      console.log('✅ File read, uploading...');
      
      // Upload to server
      $.ajax({
        type: 'POST',
        url: siteUrl + 'requests/request.php',
        data: { 
          f: 'avatarUpload', 
          image: base64 
        },
        timeout: 30000,
        success: function(response) {
          console.log('📨 Response:', response);
          
          // Parse URL from response
          let url = '';
          try {
            const cleaned = String(response).trim().replace(/^(200|404)\s*/i, '');
            if (cleaned.includes('{')) {
              const json = JSON.parse(cleaned);
              url = json.avatar || json.url || '';
            } else if (cleaned.includes('/')) {
              url = cleaned;
            }
          } catch(e) {
            if (String(response).includes('/')) {
              url = String(response).trim();
            }
          }
          
          $('.upload-loader').remove();
          
          if (url) {
            console.log('✅ Avatar updated:', url);
            // Update avatar image
            $('.i_profile_picture img').attr('src', url);
            // Update header avatar if exists
            $('.i_header_user_avatar img').attr('src', url);
            // Show success message
            showSuccessMessage('Profile picture updated!');
          } else {
            console.error('❌ No URL in response');
            alert('Upload failed. Please try again.');
          }
        },
        error: function(xhr, status, error) {
          console.error('❌ Upload error:', error);
          $('.upload-loader').remove();
          alert('Upload failed: ' + error);
        }
      });
    };
    
    reader.onerror = function() {
      $('.upload-loader').remove();
      alert('Error reading file');
    };
    
    reader.readAsDataURL(file);
  });

  // Success message helper
  function showSuccessMessage(msg) {
    const $msg = $('<div class="upload-success-msg" style="position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#10b981;color:white;padding:12px 24px;border-radius:8px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);font-size:14px;"></div>').text(msg);
    $('body').append($msg);
    setTimeout(function() {
      $msg.fadeOut(300, function() { $(this).remove(); });
    }, 3000);
  }

})(jQuery);