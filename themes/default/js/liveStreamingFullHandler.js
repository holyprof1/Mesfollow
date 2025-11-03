// Replace the entire liveStreamingFullHandler.js with this fixed version

(function ($) {
  "use strict";

  // ---------- STATE ----------
  let client = null;
  window.rtcClient = null;

  const localTracks = { videoTrack: null, audioTrack: null };
  const localTrackState = { videoTrackMuted: false, audioTrackMuted: false };
  const remoteUsers = {};

  const options = {
    appid: window.liveAppID || null,
    channel: window.liveChannel || null,
    uid: null,
    token: "",
    role: (String(window.liveUserID) === String(window.liveCreator)) ? "host" : "audience",
    audienceLatency: 2
  };

  let mics = [], cams = [], currentMic = null, currentCam = null;

  // ---------- DYNAMIC LAYOUT MANAGER ----------
  function updateLayout() {
    const container = document.querySelector('.live_vide__holder');
    if (!container) return;

    const localDiv = document.getElementById('local-player');
    const remoteDiv = document.getElementById('remote-playerlist');
    
    // Count total participants
    let totalParticipants = Object.keys(remoteUsers).length;
    if (options.role === "host" && localTracks.videoTrack) {
      totalParticipants += 1; // Include myself
    }

    console.log('Layout update: total participants =', totalParticipants);

    // Remove old layout classes
    container.classList.remove('layout-1', 'layout-2', 'layout-3');

    if (totalParticipants === 1) {
      // Single view - full screen
      container.classList.add('layout-1');
      if (localDiv) localDiv.style.display = 'block';
      if (remoteDiv) remoteDiv.style.display = 'none';
    } else if (totalParticipants === 2) {
      // Two people - side by side
      container.classList.add('layout-2');
      if (localDiv) localDiv.style.display = 'block';
      if (remoteDiv) remoteDiv.style.display = 'block';
    } else if (totalParticipants >= 3) {
      // 3+ people - main + thumbnails
      container.classList.add('layout-3');
      if (localDiv) localDiv.style.display = 'block';
      if (remoteDiv) remoteDiv.style.display = 'flex';
    }

    updateCounter();
  }

  // ---------- COUNTER ----------
  function updateCounter() {
    let total = Object.keys(remoteUsers).length;
    if (options.role === "host") total += 1;
    $(".sumonline").text(total);
  }

  // ---------- ENSURE REMOTE TILE AND PLAY ----------
  function ensureRemoteTileAndPlay(user) {
    try {
      // For audience: use big slot for first remote video (host)
      if (options.role !== "host") {
        const big = document.getElementById("local-player");
        const hasBig = big && big.querySelector("video");
        if (!hasBig && user.videoTrack) {
          console.log('Playing host video in main view');
          user.videoTrack.play("local-player", { fit: "cover" });
          if (user.audioTrack) user.audioTrack.play();
          updateLayout();
          return;
        }
      }
    } catch (e) {
      console.warn("Big view play failed:", e);
    }

    // Create thumbnail tile for additional users
    let remoteWrap = document.getElementById("remote-playerlist");
    if (!remoteWrap) {
      remoteWrap = document.createElement("div");
      remoteWrap.id = "remote-playerlist";
      document.querySelector(".live_vide__holder")?.appendChild(remoteWrap);
    }

    const id = `user-${user.uid}`;
    let tile = document.getElementById(id);
    if (!tile) {
      tile = document.createElement("div");
      tile.id = id;
      tile.className = "op-tile";
      remoteWrap.appendChild(tile);
      console.log('Created tile for user', user.uid);
    }

    if (user.videoTrack) user.videoTrack.play(tile, { fit: "cover" });
    if (user.audioTrack) user.audioTrack.play();
    
    updateLayout();
  }

  // ---------- ELAPSED TIMER ----------
  function formatElapsed(sec) {
    sec = Math.max(0, Math.floor(sec));
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    return h > 0
      ? [h, m, s].map(v => String(v).padStart(2, "0")).join(":")
      : [m, s].map(v => String(v).padStart(2, "0")).join(":");
  }

  let __elapsedBase = null;
  let __elapsedTimer = null;

  function startElapsedTicker() {
    if (__elapsedBase == null) {
      const hud = document.querySelector(".tthud");
      const ds = hud ? parseInt(hud.getAttribute("data-start"), 10) : NaN;
      __elapsedBase = (!Number.isNaN(ds) && ds > 0) ? ds : Math.floor(Date.now() / 1000);
    }
    if (__elapsedTimer) return;
    __elapsedTimer = setInterval(function () {
      const now = Math.floor(Date.now() / 1000);
      const diff = now - __elapsedBase;
      $("#tthudElapsed").text(formatElapsed(diff));
    }, 1000);
  }

  function maybeSyncElapsedFromServer(res) {
    const ts = (res && (res.startedAt ?? res.startTs ?? res.start_time));
    if (typeof ts === "number" && ts > 0) {
      __elapsedBase = ts;
    }
  }

  // ---------- JOIN / LEAVE ----------
  async function join() {
    try {
      console.log('Starting join process...');
      console.log('AppID:', options.appid);
      console.log('Channel:', options.channel);
      console.log('Role:', options.role);

      if (!options.appid || !options.channel) {
        throw new Error('Missing appid or channel');
      }

      client = AgoraRTC.createClient({ mode: "live", codec: "vp8" });
      window.rtcClient = client;

      if (options.role === "audience") {
        await client.setClientRole("audience", { level: options.audienceLatency });
        console.log('Set role to audience');
      } else {
        await client.setClientRole("host");
        console.log('Set role to host');
      }

      // Remote media handlers
      client.on('user-published', async (user, mediaType) => {
        try {
          console.log('User published:', user.uid, mediaType);
          await client.subscribe(user, mediaType);
          
          // Store user reference
          if (mediaType === "video") {
            remoteUsers[String(user.uid)] = user;
          }
          
          ensureRemoteTileAndPlay(user);
        } catch (e) {
          console.warn('subscribe/play failed', e);
        }
      });

      client.on('user-unpublished', (user, mediaType) => {
        console.log('User unpublished:', user.uid, mediaType);
        if (mediaType === "video") {
          const el = document.getElementById(`user-${user.uid}`);
          if (el && el.parentNode) el.parentNode.removeChild(el);
          delete remoteUsers[String(user.uid)];
          updateLayout();
        }
      });

      client.on('user-joined', async (user) => {
        console.log('User joined:', user.uid);
        try {
          if (user.hasVideo) await client.subscribe(user, "video");
          if (user.hasAudio) await client.subscribe(user, "audio");
          
          if (user.hasVideo) {
            remoteUsers[String(user.uid)] = user;
          }
          
          ensureRemoteTileAndPlay(user);
        } catch (e) {
          console.warn('user-joined subscribe failed', e);
        }
      });

      client.on('user-left', (user) => {
        console.log('User left:', user.uid);
        const el = document.getElementById(`user-${user.uid}`);
        if (el && el.parentNode) el.parentNode.removeChild(el);
        delete remoteUsers[String(user.uid)];
        updateLayout();
      });

      client.on("connection-state-change", (curState, prevState) => {
        console.log('Connection state:', prevState, '->', curState);
        updateCounter();
      });

      // Join the channel
      console.log('Joining channel...');
      options.uid = await client.join(options.appid, options.channel, options.token || null, options.uid || null);
      console.log('Joined with UID:', options.uid);

      // Subscribe to existing users
      console.log('Checking for existing users:', client.remoteUsers.length);
      for (const u of client.remoteUsers) {
        try {
          if (u.hasVideo) {
            await client.subscribe(u, "video");
            remoteUsers[String(u.uid)] = u;
          }
          if (u.hasAudio) await client.subscribe(u, "audio");
          ensureRemoteTileAndPlay(u);
        } catch (e) {
          console.warn("initial subscribe failed", e);
        }
      }

      // Host: create and publish local tracks
      if (options.role === "host") {
        console.log('Creating local tracks...');
        
        try {
          // Request permissions first
          const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
          stream.getTracks().forEach(track => track.stop()); // Stop temporary stream
          
          // Now create Agora tracks
          localTracks.audioTrack = await AgoraRTC.createMicrophoneAudioTrack();
          localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack();
          
          console.log('Local tracks created successfully');
          
          // Play local video
          console.log('Playing local video...');
          localTracks.videoTrack.play("local-player", { fit: "cover" });
          console.log('Local video playing');
          
          // Publish tracks
          console.log('Publishing tracks...');
          await client.publish([localTracks.audioTrack, localTracks.videoTrack]);
          console.log('Tracks published');
          
        } catch (err) {
          console.error('Failed to create/publish tracks:', err);
          alert('Camera/microphone access denied. Please allow permissions and refresh.');
          throw err;
        }
      }

      updateLayout();
      console.log('Join complete');
      
    } catch (error) {
      console.error("Join error:", error);
      alert('Failed to join live stream: ' + error.message);
    }
  }

  async function leave() {
    console.log('Leaving...');
    for (const k of Object.keys(localTracks)) {
      const t = localTracks[k];
      if (t) {
        t.stop();
        t.close();
        localTracks[k] = null;
      }
    }

    Object.keys(remoteUsers).forEach((uid) => {
      $(`#user-${uid}`).remove();
      delete remoteUsers[uid];
    });
    
    $("#remote-playerlist").empty();

    if (client) {
      await client.leave();
      client = null;
    }
    window.rtcClient = null;

    updateCounter();
  }

  // ---------- MUTE / SWITCH DEVICES ----------
  async function muteAudio() {
    if (!localTracks.audioTrack) return;
    await localTracks.audioTrack.setMuted(true);
    localTrackState.audioTrackMuted = true;
    $("#mute-audio").text("Unmute Audio");
  }

  async function unmuteAudio() {
    if (!localTracks.audioTrack) return;
    await localTracks.audioTrack.setMuted(false);
    localTrackState.audioTrackMuted = false;
    $("#mute-audio").text("Mute Audio");
  }

  async function muteVideo() {
    if (!localTracks.videoTrack) return;
    await localTracks.videoTrack.setMuted(true);
    localTrackState.videoTrackMuted = true;
    $("#mute-video").text("Unmute Video");
  }

  async function unmuteVideo() {
    if (!localTracks.videoTrack) return;
    await localTracks.videoTrack.setMuted(false);
    localTrackState.videoTrackMuted = false;
    $("#mute-video").text("Mute Video");
  }

  async function switchCameraByLabel(label) {
    if (!localTracks.videoTrack) return;
    const t = cams.find((c) => c.label === label);
    if (!t) return;
    currentCam = t;
    await localTracks.videoTrack.setDevice(currentCam.deviceId);
  }

  async function switchMicrophoneByLabel(label) {
    if (!localTracks.audioTrack) return;
    const t = mics.find((m) => m.label === label);
    if (!t) return;
    currentMic = t;
    await localTracks.audioTrack.setDevice(currentMic.deviceId);
  }

  async function enumerateDevicesAndFillMenus() {
    mics = await AgoraRTC.getMicrophones();
    cams = await AgoraRTC.getCameras();
    currentMic = mics[0] || null;
    currentCam = cams[0] || null;

    const $micList = $(".mic-list").empty();
    const $camList = $(".cam-list").empty();

    mics.forEach((mic) => $micList.append(`<a class="dropdown-item mic-item" data-label="${mic.label}" href="javascript:void(0)">${mic.label || 'Microphone'}</a>`));
    cams.forEach((cam) => $camList.append(`<a class="dropdown-item cam-item" data-label="${cam.label}" href="javascript:void(0)">${cam.label || 'Camera'}</a>`));
  }

  // ---------- CHAT / POLLS / HELPERS ----------
  function ScrollBottomLiveChat() {
    const box = $(".live_right_in_right_in");
    if (box.length) box.stop().animate({ scrollTop: box[0].scrollHeight }, 100);
  }

  function startPollers() {
    setInterval(function () {
      $.ajax({
        type: "POST",
        url: window.siteurl + "requests/live.php",
        data: { f: "live_calcul", lid: window.theLiveID },
        dataType: "json",
        success: function (res) {
          if (res && typeof res === "object") {
            const serverCount = (res.onlineCount ?? res.viewers ?? res.viewersCount ?? res.online ?? res.count);
            if (typeof serverCount === "number" && !Number.isNaN(serverCount)) {
              $(".sumonline").text(serverCount);
            } else {
              updateCounter();
            }

            maybeSyncElapsedFromServer(res);

            if (res.time !== undefined) $(".count_time").html(res.time);
            if (res.likeCount !== undefined) $(".lp_sum_l").text(res.likeCount);
            if (res.finished) window.location.href = res.finished;
          }
        }
      });
    }, 15000);

    setInterval(function () {
      const lastCom = $(".eo2As:last").attr("id") || "";
      $.post(
        window.siteurl + "requests/live.php",
        { f: "liveLastMessage", idc: window.theLiveID, lc: lastCom },
        function (response) {
          if (!response || response.trim() === "" || response.indexOf("no new live messages") !== -1) return;
          if ($(".gElp9").length === 0) {
            $(".live_right_in_right_in").append(response);
          } else {
            $(".cUq_" + lastCom).after(response);
          }
        }
      );
    }, 6000);
  }

  function wireUI() {
    $("body").on("click", "#leave", leave);

    $("body").on("click", "#mute-audio", function () {
      localTrackState.audioTrackMuted ? unmuteAudio() : muteAudio();
    });
    $("body").on("click", "#mute-video", function () {
      localTrackState.videoTrackMuted ? unmuteVideo() : muteVideo();
    });

    $("body").on("click", ".camera_chs", () => $(".camList").toggleClass("camListOpen"));
    $("body").on("click", ".mick_chs", () => $(".micList").toggleClass("camListOpen"));
    $("body").on("mouseup touchend", function (e) {
      const $lists = $(".camList, .micList");
      if (!$lists.is(e.target) && $lists.has(e.target).length === 0) $lists.removeClass("camListOpen");
    });

    $("body").on("click", ".cam-item", function () { switchCameraByLabel($(this).data("label")); });
    $("body").on("click", ".mic-item", function () { switchMicrophoneByLabel($(this).data("label")); });

    $("body").on("click", ".live_gift_call", function () {
      $(".live_footer_holder").addClass("live_footer_holder_show");
      $(".live__live_video_holder").append("<div class='appendBoxLive'></div>");
    });
    $("body").on("click", ".appendBoxLive", function () {
      $(".live_footer_holder").removeClass("live_footer_holder_show");
      $(this).remove();
    });

    $("body").on("click", ".livesendmes", function () {
      const v = $(".lmSize").val();
      if (v.trim()) LiveMessage(window.theLiveID, v, "livemessage");
    });
    $(document).on("keydown", ".lmSize", function (e) {
      if (e.which === 13) {
        const v = $(this).val();
        if (v.trim()) LiveMessage(window.theLiveID, v, "livemessage");
        e.preventDefault();
      }
    });

    $("body").on("click", ".getMEmojisa", function () {
      if (!$(".Message_stickersContainer").length) {
        $.post(window.siteurl + "requests/request.php", { f: "memoji", id: $(this).data("type") }, function (res) {
          $(".nanos").css("height", "348px").append(res);
        });
      } else {
        $(".Message_stickersContainer").remove();
        $(".nanos").css("height", "0px");
      }
    });
    $("body").on("click", ".emoji_item_m", function () {
      const emoji = $(this).data("emoji");
      $(".lmSize").val(($(".lmSize").val() + " " + emoji + " ").trim() + " ");
    });

    $("body").on("click", ".camcloseCall", function () {
      $.post(window.siteurl + "requests/request.php", { f: "finishLiveStreaming", id: $(this).attr("id") }, function (resp) {
        if (resp !== "404") {
          $("body").append(resp);
          setTimeout(() => $(".i_modal_bg_in").addClass("i_modal_display_in"), 200);
        } else {
          PopUPAlerts("sWrong", "ialert");
        }
      });
    });
    $("body").on("click", ".camclose", function () {
      $.post(window.siteurl + "requests/request.php", { f: "finishLive", lid: window.theLiveID }, async function (resp) {
        await leave();
        if (resp === "finished") setTimeout(() => { window.location.href = window.siteurl; }, 2000);
      });
    });
    if (String(window.liveUserID) === String(window.liveCreator)) {
      $("body").on("click", ".camera_close", function () {
        $.post(window.siteurl + "requests/request.php", { f: "finishLive", lid: window.theLiveID }, function (resp) {
          if (resp === "finished") window.location.href = window.siteurl;
        });
      });
    }

    function deviceResizeFunction() {
      const vW = $(window).width();

      $(".live_left").toggle(vW >= 1300);
      $(".header").toggle(vW >= 1050);
      $(".live_wrapper_tik").css("padding-top", vW < 1050 ? "0px" : "72px");
      $(".live__live_video_holder").toggleClass("max_height_live_mobile", vW < 1050);
      $(".live_video_header").toggleClass("live_video_header_mobile", vW < 1050);
      $(".exen, .sumonline").toggleClass("loi", vW < 1050);
      $(".i_header_btn_item").toggleClass("i_header_btn_item_live_mobile", vW < 1050);
      $(".live_footer_holder").toggle(vW >= 1050);
      $(".live_right_in_right").toggleClass("live_right_in_right_mobile", vW < 1050);
      $(".live_holder_plus_in").toggleClass("live_plus_mobile", vW < 1050);
      $(".live_gift_call").toggle(vW < 1050);
      $(".tthud-quit").toggle(vW < 769);

      if (vW < 700) $(".mobile_footer_fixed_menu_container").remove();
    }

    $(window).on("resize", deviceResizeFunction);
    deviceResizeFunction();

    setInterval(updateCounter, 10000);
  }

  function LiveMessage(ID, value, type) {
    $.post(window.siteurl + "requests/request.php", { f: type, id: ID, val: encodeURIComponent(value) }, function (response) {
      if (response !== "404") {
        $(".live_right_in_right_in").append(response);
        ScrollBottomLiveChat();
      }
      $(".lmSize").val("");
      $(".Message_stickersContainer").remove();
      $(".nanos").css("height", "0px");
    });
  }

  // Make functions available globally
  window.updateLiveLayout = updateLayout;
  window.ensureRemoteTileAndPlay = ensureRemoteTileAndPlay;

  // ---------- INIT ----------
  $(document).ready(async function () {
    console.log('=== Live Stream Initializing ===');
    console.log('Window globals:', {
      liveAppID: window.liveAppID,
      liveChannel: window.liveChannel,
      liveUserID: window.liveUserID,
      liveCreator: window.liveCreator
    });
    
    try {
      if (options.role === "host") {
        console.log('Enumerating devices...');
        await enumerateDevicesAndFillMenus();
      }
      
      await join();
      wireUI();
      startPollers();
      startElapsedTicker();
      ScrollBottomLiveChat();
      
      console.log('=== Initialization Complete ===');
    } catch (e) {
      console.error("=== Live join error ===", e);
      alert('Failed to start live stream. Check console for details.');
    }
  });

})(jQuery);