
export async function setEventHandlers(){
    
    const waitForAud = new Promise(resolve => {
        let observer = new MutationObserver( () => {
            let audioScripts = document.querySelectorAll("audio");
            let playbuttons = document.querySelectorAll(".play-btn");
            if(audioScripts.length > 0 && playbuttons.length > 0){
                resolve([audioScripts, playbuttons]);
            }
        });
        observer.observe(document.body, {childList: true, subtree: true});
    });
    waitForAud
    .then( tags => {
        let [audioScripts, playbuttons] = tags;
        if(audioScripts.length === 0){
            console.log("AUD scripts not found");
            return;
        }
        const audioPlayer = document.querySelector("#audio-player");
        if(!audioPlayer){
            console.log("audio player not found");
            throw Error("");
        }        

        //let playbuttons = document.querySelectorAll(".play-btn");
        playbuttons.forEach( (button) => {
            button.addEventListener("click", async (event) => {
                
                let btn = event.target;
                let btnIcon = null;
                if(btn.classList.contains("play-btn")){
                    btnIcon = btn.querySelector("i");
                    if(!btnIcon){
                        console.log("button icon not found");
                        return;
                    }
                }
                else if(btn.classList.contains("fas") || btn.classList.contains("fa")){
                    console.log("icon clicked");
                    btnIcon = btn;
                }
                if(btnIcon){
                    //
                    if(btnIcon.classList.contains("fa-play")){
                        if(audioPlayer.src.length > 0){
                            let playingIcon = document.querySelector(`.fa-pause[data-trackid="${audioPlayer.dataset.id}"]`);
                            if(playingIcon){
                                changeBtnUI(playingIcon);
                                await audioControl(audioPlayer,"pause");
                            } else {
                                console.log("playing icon not found: ", audioPlayer.dataset.id);
                            }
                        } else {
                            console.log(audioPlayer.src.length);
                        }
                        if(btnIcon.dataset.trackid != audioPlayer.id) {
                            audioPlayer.src = btnIcon.dataset.src;
                        	audioPlayer.dataset.id = btnIcon.dataset.trackid;
                        }
                        
                        let resp = await audioControl(audioPlayer,"play")
                        if(resp) btnIcon.className = "fas fa-pause pause";
                    }
                    else if(btnIcon.classList.contains("fa-pause")){
                        if(audioPlayer.src.length < 0){
                            console.log("audio player has no source");
                        }
                        let resp = await audioControl(audioPlayer,"pause");
                        if(resp) btnIcon.className = "fas fa-play play";
                    }
                }

            });
        })
    } );
    
}

function pauseAllTracks(btnIcons){}

const audioControl = async (audioTag, action) => {
    //const audioTag = document.querySelector(`#${audioTagId}`);
    
    if(audioTag){
        switch(action){
            case "play": return await playAudio(audioTag);
            case "pause": return await pauseAdio(audioTag);
            default : console.log("unknown action");
        }
    }
    else{
        console.log(`no such audio tag with id ${audioTagId}`);
    }
}

const handleTimeUpdate = (event) => {
    const targetAUD = event.currentTarget;
    const durationDiv = document.querySelector(`.music-duration.track-${targetAUD.dataset.id}`);
    const progressDiv = document.querySelector(`.progress.track-${targetAUD.dataset.id}`);
    if(durationDiv && progressDiv){
        const currentTime = formatTime(targetAUD.currentTime);
        const duration = formatTime(targetAUD.duration);
        const progr = Math.floor(100 * (targetAUD.currentTime / targetAUD.duration));

        // обновляем время
        durationDiv.textContent = `${currentTime}/${duration}`;
        // обновляем черто
        progressDiv.style.width = `${progr}%`;
    }
    else{
        console.log("progress div and duration div are not found");
        return;
    }
}

const playAudio = async (tag) => {
    try{
        if( tag.src.length > 0 ){
            await tag.play();
            tag.addEventListener("timeupdate", handleTimeUpdate);
            return true;
        } else {
            console.log("audio file " + tag.id + " has no active source");
        }
    }catch(error){
        console.log(error.message);
    }
    return false;
}
const pauseAdio = async (tag) => {
    await tag.pause();
    tag.removeEventListener("timeupdate", handleTimeUpdate);
    return true;
}

function changeBtnUI(btnIcon){
    if(btnIcon.classList.contains("fa-play")){
        btnIcon.className = "fas fa-pause pause";
    }
    else if(btnIcon.classList.contains("fa-pause")){
        btnIcon.className = "fas fa-play play";
    }
}

function formatTime(seconds){
    const min = Math.floor(seconds / 60);
    const sec = Math.floor(seconds % 60);

    return `${isNaN(min) ? 0 : min}:${isNaN(sec) ? 0 : sec.toString().padStart(2, '0')}`;
}

//document.addEventListener("DOMContentLoaded", setEventHandlers);