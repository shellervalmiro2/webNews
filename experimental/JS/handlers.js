

export function handleShare(){
    const allShareIcons = document.querySelectorAll('.share-btn .media-ico');
    const shareMenu = document.getElementById('shareMenu');
    let url = "";
    let share_text = "konektem";
    //localStorage.getItem('currentUser') === 'guest'
    if(window.currentUser === 'guest') return;
    if(allShareIcons.length > 0){
        allShareIcons.forEach( icon => {
            icon.addEventListener('click', async ()=>{
                if(!icon.dataset.itemid && !icon.dataset.itemname){
                    console.log("no id information about the shared item");
                    return;
                }
                url = `https://konektem.net/?p=${icon.dataset.itemname}&id=${icon.dataset.itemid}`;
                share_text += ` ${icon.dataset.itemname}`;
                share();
            })
        });
        
        async function share(){
            if (navigator.share){
                
                try{
                    await navigator.share({
                        title: document.title,
                        text: 'Konektem news',
                        url: url
                    });
                    return;
                } catch(error){
                    console.log('error while sharing: ' + icon.dataset.artclid, error);
                }
            } else {
                shareMenu.style.display = (shareMenu.style.display === "flex") ? "none" : "flex";
            }
        }
    } else {
        console.log("no share icons found");
    }
}