const settings = {
    "contact": {},
    "footer": {},
    "social": {},
}

async function loadSettings(){
    try{
        for(const k of Object.keys(settings)){
            const response = await fetch('/php/dbReader.php?q=siteSettings&group=' + k);
            const data = await response.json();
            if(data.success){
                data.settings.forEach(s => {
                    settings[k][s['setting_key']] = s['setting_value'];
                });
            }
        }
    } catch(error){
        console.log(error.message);
    }
}
loadSettings();
export default settings;