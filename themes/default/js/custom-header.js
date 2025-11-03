/* 
This file is intentionally left blank.
If the admin adds custom JS via the panel, it will be written here dynamically.
This helps maintain separation of logic and avoid inline scripts.
Add here your JavaScript Code. Note. the code entered here will be added in  tag  Example:  var x, y, z;  x = 5;  y = 6;  z = x + y;
*/

window.OneSignal = window.OneSignal || [];
OneSignal.push(function() {
  function hideBellIfEnabled() {
    OneSignal.isPushNotificationsEnabled().then(function(isEnabled) {
      if (isEnabled) {
        const bell = document.getElementById('onesignal-bell-container');
        if (bell) {
          bell.style.display = 'none';
        } else {
          setTimeout(hideBellIfEnabled, 1000); // retry if not yet loaded
        }
      }
    });
  }
  hideBellIfEnabled();
});


