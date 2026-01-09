let container = null;
let player;
export default function embedYTVid() {
    container = document.querySelector("#rp-vid-container");
    if (!container) {
        console.log("embedYTVid: vid container not found");
        return;
    }

    /*const firstScriptTag = document.querySelectorAll("script")[0];
    let scriptTag = document.createElement("script");
    scriptTag.src = "https://www.youtube.com/iframe_api";
    width="560" height="315" */
    container.innerHTML += `
    <iframe class="full-wh" src="https://www.youtube.com/embed/qUfA_j2weEI?si=FRr-x22LFESfNGN0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>`;

    /*document.body.insertAdjacentElement("beforeend", scriptTag);
    if (firstScriptTag) {
        firstScriptTag.parentElement.insertBefore(scriptTag, firstScriptTag);
    } else {
        document.body.insertAdjacentElement("beforeend", scriptTag);
    }*/
}

window.onYouTubeIframeAPIReady = function() {
    const container = document.querySelector("#rp-vid-container");
    player = new YT.Player(container, {
        videoId: "qUfA_j2weEI",
        playerVars: {
            muted: 1,
            autoplay: 0,
            controls: 1,
            playsinline: 1
        },
        events: {
            onReady: onPlayerReady
        }
    });
}

function onPlayerReady() {
    const playButton = document.querySelector("#play-button");
    if (playButton) {
        playButton.addEventListener('click', () => {
            player.playVideo();
        });
    } else {
        console.log("Play button not found");
    }
}
