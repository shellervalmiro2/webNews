
let slideInterval = null;
let generator = null;
let container = null;
let resumeTimer = null;
let scrollDirection = 'right';
let scrollEdge = null;
let slideDuration = 800;
let slideimg = null;
let slideWidth = 0;
let scrollStep = 0;

export default function slideContainer(){
  const waitForSlides = new Promise((resolve) => {
      const observer = new MutationObserver(function(){
          console.log('waiting for bottom slides');
          let slides = document.querySelector('.bottom-slide');
          if(slides){
              observer.disconnect();
              resolve(slides);
          }
      });
      observer.observe(document.body, {childList: true, subtree: true});
  });
  waitForSlides.then( slides => {
      try{
          console.log("bottom slides found");
          container = document.querySelector('.bottom-slide');
          slideWidth = container.children[0].clientWidth;

          if( !container ){
            console.log("slideContainer: couldnt find container Div");
            return;
          }

          let scrollW = container.scrollWidth;
          scrollEdge = container.scrollWidth - container.clientWidth;
          scrollStep = scrollEdge / 5;

          slideInterval = setInterval( scrollAnimation, 2000);
      } catch(error){
          console.log(error);
      }
  });
}

function scrollAnimation(){    
  if( scrollDirection === 'right' ) container.scrollLeft += scrollStep;
  else if(scrollDirection === 'left') container.scrollLeft -= scrollStep;
  if(container.scrollLeft === scrollEdge) scrollDirection = 'left';
  else if(container.scrollLeft === 0) scrollDirection = 'right';
}

function pauseAndResume(){
    clearInterval(slideInterval);
    slideInterval = null;
    setTimeout(() => {
        slideInterval = setInterval( scrollAnimation, 2000);
    },1000)
}
