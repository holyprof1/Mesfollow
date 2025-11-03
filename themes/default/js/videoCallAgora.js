(function ($) {
    "use strict";

    let appConfig = {};

    document.addEventListener("DOMContentLoaded", function () {
        const configElement = document.getElementById("chat-config");
        if (!configElement) { 
            return;
        }
        appConfig = JSON.parse(configElement.textContent);

        initAgoraVideoCall(appConfig);
    });

    function initAgoraVideoCall(appConfig) {
        let client = null;
        const localTracks = { videoTrack: null, audioTrack: null };
        const localTrackState = { videoTrackMuted: false, audioTrackMuted: false };
        let remoteUsers = {};
        let initialized = false;

        const options = {
            appid: appConfig.agoraAppID,
            channel: appConfig.videoCall.channelName || appConfig.randomChannelName,
            uid: null,
            token: null
        };

        $(document).ready(function () {
            if (appConfig.videoCall.exists && appConfig.videoCall.channelName && !$(".crVidCall").length) {
                JoinVideoCall();
            }

            $(document).on("click", ".leave", leave);
            $(document).on("click", ".joinVideoCall", JoinVideoCall);
            $(document).on("click", ".crVidCall", buyVideoCall);
            $(document).on("click", "#mute-audio", () => localTrackState.audioTrackMuted ? unmuteAudio() : muteAudio());
            $(document).on("click", "#mute-video", toggleVideoAndAudioMute);

            typingPoll();
            setupTypingTrigger();
            scrollLoadMessages();
            getNewMessageLoop('');
        });

        async function JoinVideoCall() {
            try {
                if (!initialized) {
                    client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
                    client.on("user-published", handleUserPublished);
                    client.on("user-unpublished", handleUserUnpublished);
                    initialized = true;
                }

                await joinAgora();
                $("#notification-sound-call")[0]?.pause();
                $(".live_pp_camera_container").show();
            } catch (e) {
                // Error suppressed intentionally for production
            } finally {
                $("#leave").attr("disabled", false);
            }
        }

        async function joinAgora() {
            [options.uid, localTracks.audioTrack, localTracks.videoTrack] = await Promise.all([
                client.join(options.appid, options.channel, options.token || null, options.uid || null),
                AgoraRTC.createMicrophoneAudioTrack(),
                AgoraRTC.createCameraVideoTrack()
            ]);

            localTracks.videoTrack.play("local-player");
            $("#local-player-name").text(`localVideo(${options.uid})`);
            await client.publish(Object.values(localTracks));

            if (!appConfig.videoCall.exists) {
                $.post(appConfig.siteurl + "requests/request.php", {
                    f: "createVideoCall",
                    calledID: appConfig.conversationUserID,
                    callName: options.channel
                }, response => {
                    if (response !== '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $('#notification-sound-call')[0]?.play();
                        }, 200);
                    }
                });
            }
        }

        async function leave() {
            try {
                for (let trackName in localTracks) {
                    const track = localTracks[trackName];
                    if (track) {
                        track.stop();
                        track.close();
                        localTracks[trackName] = null;
                    }
                }

                if (client) {
                    await client.unpublish();
                    await client.leave();
                }

                for (let uid in remoteUsers) {
                    $(`#player-wrapper-${uid}`).remove();
                }
                remoteUsers = {};

                $("#remote-playerlist").html("");
                $("#local-player-name").text("");
                $(".live_pp_camera_container").hide();
                $("#notification-sound-call")[0]?.pause();

                $.post(appConfig.siteurl + "requests/request.php", {
                    f: "liveEnd",
                    chName: options.channel
                });
            } catch (err) {
                // Error suppressed intentionally for production
            }
        }

        async function toggleVideoAndAudioMute() {
            const player = $("#local-player .agora_video_player");

            try {
                if (!localTrackState.videoTrackMuted || !localTrackState.audioTrackMuted) {
                    if (localTracks.audioTrack) {
                        await localTracks.audioTrack.setMuted(true);
                        localTrackState.audioTrackMuted = true;
                        $("#mute-audio").addClass("activated_btn");
                    }
                    if (localTracks.videoTrack) {
                        await localTracks.videoTrack.setMuted(true);
                        localTrackState.videoTrackMuted = true;
                        $("#mute-video").addClass("activated_btn");
                        player.css("filter", "blur(5px) brightness(0)");
                    }
                } else {
                    if (localTracks.audioTrack) {
                        await localTracks.audioTrack.setMuted(false);
                        localTrackState.audioTrackMuted = false;
                        $("#mute-audio").removeClass("activated_btn");
                    }
                    if (localTracks.videoTrack) {
                        await localTracks.videoTrack.setMuted(false);
                        localTrackState.videoTrackMuted = false;
                        $("#mute-video").removeClass("activated_btn");
                        player.css("filter", "none");
                    }
                }
            } catch (e) {
                // Error suppressed intentionally for production
            }
        }

        function buyVideoCall() {
            $.post(appConfig.siteurl + "requests/request.php", {
                f: "buyVideoCall",
                calledID: appConfig.conversationUserID,
                callName: options.channel
            }, function (response) {
                if (response !== '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            });
        }

        async function subscribe(user, mediaType) {
            const uid = user.uid;
            await client.subscribe(user, mediaType);

            if (mediaType === 'video') {
                const player = $(`<div id="player-wrapper-${uid}">
                    <div id="player-${uid}" class="player_friend"></div>
                </div>`);
                $("#remote-playerlist").append(player);
                $(".live_pp_camera_container").show();
                $("#notification-sound-call")[0]?.pause();
                $(".videoCall").remove();
                user.videoTrack.play(`player-${uid}`);
            }

            if (mediaType === 'audio') {
                user.audioTrack.play();
            }
        }

        function handleUserPublished(user, mediaType) {
            remoteUsers[user.uid] = user;
            subscribe(user, mediaType);
        }

        function handleUserUnpublished(user, mediaType) {
            const uid = user.uid;

            if ($(`#player-wrapper-${uid}`).length) {
                $(`#player-wrapper-${uid}`).remove();
            }

            if (remoteUsers[uid]) {
                delete remoteUsers[uid];
            }

            if (Object.keys(remoteUsers).length === 0) {
                $(".live_pp_camera_container").hide();
                $("#notification-sound-call")[0]?.pause();
                leave();
            }
        }

        async function muteAudio() {
            if (!localTracks.audioTrack) return;
            await localTracks.audioTrack.setMuted(true);
            localTrackState.audioTrackMuted = true;
            $("#mute-audio").addClass("activated_btn");
        }

        async function unmuteAudio() {
            if (!localTracks.audioTrack) return;
            await localTracks.audioTrack.setMuted(false);
            localTrackState.audioTrackMuted = false;
            $("#mute-audio").removeClass("activated_btn");
        }

        async function muteVideo() {
            if (!localTracks.videoTrack) return;
            await localTracks.videoTrack.setMuted(true);
            localTrackState.videoTrackMuted = true;
            $("#mute-video").addClass("activated_btn");
        }

        async function unmuteVideo() {
            if (!localTracks.videoTrack) return;
            await localTracks.videoTrack.setMuted(false);
            localTrackState.videoTrackMuted = false;
            $("#mute-video").removeClass("activated_btn");
        }

        function getNewMessageLoop(x) {
            const lastMessage = $(`.mm_${appConfig.conversationUserID}:last`);
            const lastID = lastMessage.attr("data-id");

            if (!lastID) {
                setTimeout(() => getNewMessageLoop(x), 3000);
                return;
            }

            $.ajax({
                type: 'POST',
                url: appConfig.siteurl + 'requests/request.php',
                data: {
                    f: 'getNewMessage',
                    ci: appConfig.cID,
                    to: appConfig.conversationUserID,
                    lm: lastID
                },
                success: function (response) {
                    if (response && response.trim()) {
                        $(".all_messages").stop().animate({ scrollTop: $(".all_messages")[0].scrollHeight }, 100);
                        $(".all_messages_container").append(response);
                        setTimeout(() => getNewMessageLoop(x), 300);
                    } else {
                        setTimeout(() => getNewMessageLoop(x), 5000);
                    }
                },
                error: function () {
                    setTimeout(() => getNewMessageLoop(x), 5000);
                }
            });
        }

        function typingPoll() {
            $.post(appConfig.siteurl + "requests/request.php", {
                f: "typing",
                ci: appConfig.cID,
                to: appConfig.conversationUserID
            }, function (response) {
                if (response.timeStatus) {
                    $(".c_u_time").html(response.timeStatus);
                }
                if (response.seenStatus === "1") {
                    $(".seenStatus").removeClass("notSeen").addClass("seen");
                }
                const player = $(".friendsCam .player_friend .agora_video_player");
                if (player.length > 0) {
                    player.css("filter", "none");
                    unmuteAudio();
                }
                setTimeout(typingPoll, 8000);
            }).fail(() => setTimeout(typingPoll, 8000));
        }

        function setupTypingTrigger() {
            let typingTimer;
            const doneTypingInterval = 5000;

            $("body").on("focus", ".mSize", function () {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => {
                    $.post(appConfig.siteurl + 'requests/request.php', {
                        f: 'utyping',
                        ci: appConfig.cID,
                        to: appConfig.conversationUserID
                    });
                }, doneTypingInterval);
            });

            $("body").on('keydown', ".mSize", function () {
                clearTimeout(typingTimer);
            });
        }

        function scrollLoadMessages() {
            const container = $(".all_messages");
            container.scrollTop(container[0].scrollHeight);

            container.on("scroll", function () {
                if (container.scrollTop() === 0 && !$(".seen_all").length) {
                    const lastID = $(".msg:first-child").data("id");
                    $.post(appConfig.siteurl + "requests/request.php", {
                        f: "moreMessage",
                        ch: appConfig.cID,
                        last: lastID
                    }, function (html) {
                        if (html.trim()) {
                            $(".all_messages_container").prepend(html);
                        } else {
                            $(".all_messages_container").prepend('<div class="seen_all flex_ tabing"><div class="nmore">No more messages</div></div>');
                        }
                    });
                }
            });
        }

        $("body").on("click", ".sendSecretMessage", function () {
            $(".i_write_secret_post_price").toggleClass("boxD");
        });
    }
     
    $(document).ready(function(){
        $(".dynamic-bg").each(function () {
          const bg = $(this).data("img");
          if (bg) {
            $(this).css("background-image", "url(" + bg + ")");
          }
        });
    }); 

})(jQuery);