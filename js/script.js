$(function(){
  let introHandle,currentLine=-1, clientScrollHandle, partnersScrollHandle,currentSlideNumber=0;
  const animateIntro=function () {
    let length=$('.intro p').length;

    let handle=setInterval(function(){
      if(currentLine===length){
        currentLine=-1;
        $('.intro p').css('opacity',0);
        $('.intro p').css('transition','transform 0s');
        $('.intro p').css('transform','translate(0,100%)');
      };
      currentLine+=1;
      if(currentLine===0){ timeoutInterval=50}else{timeoutInterval=0};
      setTimeout(function() {
        $('.intro p').css('opacity',1);
        $('.intro p').css('transition','transform 1s ease-out');
          $('.intro p').css('transform',`translate(0,${-100*currentLine}%)`);
      },timeoutInterval);
    },2000);
    return handle;
  };


 introHandle=animateIntro();

  const onScrolling=function(){
    $(`#scrollBar li`).css('background','none');
    if(currentSlideNumber===3 || currentSlideNumber=== 6){
      $(`#scrollBar li`).css('border','1px solid #595959');
      $(`#scrollBar li:nth-child(${currentSlideNumber+1})`).css('background','#595959');
      $(`#scrollBar li`).hover(function(){$(this).css({'background':'#595959'})},function(){$(this).css({'background':'none'})});
      $(`#scrollBar li:nth-child(${currentSlideNumber+1})`).hover(function(){$(this).css({'background':'#595959'})},function(){$(this).css({'background':'#595959'})});

    }else{
      $(`#scrollBar li`).css('border','1px solid #f39f07');
      $(`#scrollBar li`).hover(function(){$(this).css({'background':'#f39f07'})},function(){$(this).css({'background':'none'})});
      $(`#scrollBar li:nth-child(${currentSlideNumber+1})`).css('background','#f39f07');
      $(`#scrollBar li:nth-child(${currentSlideNumber+1})`).hover(function(){$(this).css({'background':'#f39f07'})},function(){$(this).css({'background':'#f39f07'})});

    };
    if(currentSlideNumber!==0){
      pauseLanding();
    }else {
      setTimeout(function(){resumeLanding();},1000);
    };
    if(currentSlideNumber===6){
      clientScroll();
      partnersScroll();
    }else if(clientScrollHandle!==-1){
      clearInterval(clientScrollHandle);
      clearInterval(partnersScrollHandle);
      clientScrollHandle=-1;
    };

  };

  const checkForOverlays= function(){
    let str=$('#roi').css('transform').split(',');
    let len=str.length;
    let roiState=parseInt(str[len-2]);
    let state=roiState<-screen.width*0.9 && $('#cForm').css('visibility')==='hidden';
    return state
  }
  function parallax(){
      // ------------- VARIABLES ------------- //
    let ticking = false;
    let scrollSensitivitySetting = 30; //Increase/decrease this number to change sensitivity to trackpad gestures (up = less sensitive; down = more sensitive)
    let slideDurationSetting = 600; //Amount of time for which slide is "locked"
    let lastSlideNumber
    let totalSlideNumber = $(".pages").length;
    // ------------- DETERMINE DELTA/SCROLL DIRECTION ------------- //
    function parallaxScroll(evt) {
        delta = evt.wheelDelta || -evt.deltaY*12;
        //console.log(delta);
        lastSlideNumber=currentSlideNumber;

      if (ticking != true && checkForOverlays() &&screen.width>1020) {
        $('#pseudoB').prop('checked',false);
        if (delta <= -scrollSensitivitySetting) {
          //Down scroll
          ticking = true;
          if (currentSlideNumber !== totalSlideNumber - 1) {
            currentSlideNumber++;

            nextItem();
          }
          slideDurationTimeout(slideDurationSetting);
        }
        if (delta >= scrollSensitivitySetting) {
          //Up scroll
          ticking = true;
          if (currentSlideNumber !== 0) {
            currentSlideNumber--;
          }
          previousItem();
          slideDurationTimeout(slideDurationSetting);
        }
      }
      if(lastSlideNumber!==currentSlideNumber){
        onScrolling();
      };
    };

    // ------------- SET TIMEOUT TO TEMPORARILY "LOCK" SLIDES ------------- //
    function slideDurationTimeout(slideDuration) {
      setTimeout(function() {
        ticking = false;
      }, slideDuration);
    }
    // ------------- ADD EVENT LISTENER ------------- //
    if(screen.width>1020){
      window.addEventListener('wheel', _.throttle(parallaxScroll, 60), false);
      $('#contactScrollTop').click(function(){
      let handle;
      let evt={wheelDelta:31};
      slideDurationSetting=20;
      handle=setInterval(function(){
        console.log("stillrunning");
        parallaxScroll(evt);
        if(currentSlideNumber===0){
          $('#cForm').css({'transition': 'visibility 0.8s linear, opacity 0.3s linear','visibility': 'visible', 'opacity':'1'});
          scrollOff();
          slideDurationSetting=600;
          clearInterval(handle);
        };
      },100);

    });
      $(".down").click(function(){
        let evt={wheelDelta:-31};
        parallaxScroll(evt);
      });
      $("#scrollBar li").click(function(evnt){
        let finalSlide=$(evnt.target).index();
        for(i=currentSlideNumber;i<=finalSlide;i++){

        }
        let handle;
        slideDurationSetting=20;
        handle=setInterval(function(){
          if(finalSlide>=currentSlideNumber){
            let evt={wheelDelta:-31};
            parallaxScroll(evt);
          }else{
            let evt={wheelDelta:31};
            parallaxScroll(evt);
          };
          if(finalSlide===currentSlideNumber){
            slideDurationSetting=600;
            clearInterval(handle);
          };
        },200);

      });
    };




    // ------------- SLIDE MOTION ------------- //
    function nextItem() {
      let $previousSlide = $(".pages").eq(currentSlideNumber - 1);
      $previousSlide.removeClass("up-scroll").addClass("down-scroll");
    }

    function previousItem() {
      let $currentSlide = $(".pages").eq(currentSlideNumber);
      $currentSlide.removeClass("down-scroll").addClass("up-scroll");


    }
    };

  function clientScroll(){

      let childCount=1, storePosL=0;
      let scroll= function(moveBy=2){
        let parent=document.querySelector('#pageEnd .clients .Scrollwrapper');
        let posL=parent.scrollLeft;
        parent.scrollBy(moveBy,0);
        if (posL===parent.scrollLeft){
          childCount=parent.childNodes.length;
          arrange(parent, posL);

          }
        }

      let arrange = function(parent, posL){
        let leftStart=0, margin=0;
        if(childCount===1){
          storePosL=posL;
        };
        target=parent.childNodes[0];
        if (childCount==2){
          parent.childNodes[0].remove();
          target=parent.childNodes[0];
        };
        target.style.left='0px';
        parent.scrollBy(storePosL,0);
        let width=target.offsetWidth;
        let clone=target.cloneNode(true);
        let left=width+margin+leftStart;
        clone.style.left=`${left}px`;
        parent.appendChild(clone);

      };



    function startLoop(delay,moveBy){
      clientScrollHandle=setInterval(function(){scroll(moveBy);},delay);
    }
    clearInterval(clientScrollHandle);
    startLoop(22,2);
    let scrollDiv=document.querySelector('#pageEnd .clients .Scrollwrapper');

    };

  function partnersScroll(){
      let childCount=1, storePosL=0;
      let scroll= function(moveBy=2){
        let parent=document.querySelector('#pageEnd .partners .Scrollwrapper');
        let posL=parent.scrollLeft;
        parent.scrollBy(moveBy,0);
        if (posL===parent.scrollLeft){
          childCount=parent.childNodes.length;
          arrange(parent, posL);
          }
        }
      let arrange = function(parent, posL){
        let leftStart=0, margin=0;
        if(childCount===1){
          storePosL=posL;
        };
        target=parent.childNodes[0];
        if (childCount==2){
          parent.childNodes[0].remove();
          target=parent.childNodes[0];
        };
        target.style.left='0px';
        parent.scrollBy(storePosL,0);
        let width=target.offsetWidth;
        let clone=target.cloneNode(true);
        let left=width+margin+leftStart;
        clone.style.left=`${left}px`;
        parent.appendChild(clone);

      };


      function startLoop(delay,moveBy){
        partnersScrollHandle=setInterval(function(){scroll(moveBy);},delay);
      }
      clearInterval(partnersScrollHandle);
      startLoop(22,2);
      let scrollDiv=document.querySelector('#pageEnd .partners .Scrollwrapper');
      };

  if(screen.width>1020){
    parallax();
  };
  let animationHandle,hubHandle, savedX,savedY, botPaused;

  const animateHub=function(){
    let parent=document.querySelector("#page2 .solar-robot .system .hub");
    let current=parent.children[0],last=parent.children[2];
    let i=1;
    hubHandle=setInterval(function() {
      last.style.opacity=0;
      current.style.opacity=1;
      last=current;
      current=parent.children[i];
      i=i+1;
      if(i===4){
        i=0;
      };
    },500);
  };

  const hubReset=function(){
    let parent=document.querySelector("#page2 .solar-robot .system .hub");
    let current=parent.children[0],last=parent.children[2];
    let i=1;
    parent.children[0].style.opacity=1;
    parent.children[1].style.opacity=0;
    parent.children[2].style.opacity=0;
    parent.children[3].style.opacity=0;
  };

  const bot_animate=function(start){

    canvas=document.querySelector("#canvas");
    const ctx = canvas.getContext("2d");
    parent=canvas.parentNode;
    let widthC=parent.clientWidth;
    let heightC=parent.clientHeight;
    canvas.width = widthC;
    canvas.height = heightC;

    let scaleW =widthC/578;
    let scaleH =heightC/500;

    ctx.scale(scaleW,scaleH);
    ctx.fillStyle="white";
    ctx.fillRect(0,0,widthC/scaleW ,heightC/scaleH);
    const zero=function(ctx,xOrigin, yOrigin,start){
      const xGap=15;
      const yGap=15;
      const pWidth=50;
      const pHeight=100;
      let rowCount=0
      const frame=5;
      const xOffset=150;
      let flag="cleaning";
      const botWidth=90;
      const botHeight= 150;
      const xBay=xOrigin-xOffset-10;
      const xEnd=xOrigin+pWidth*4+xGap*4-pWidth/2;
      const yBay=yOrigin-(botHeight-pHeight)/2+pHeight*3+yGap*3;
      const yEnd=yOrigin-(botHeight-pHeight)/2;
      const tWidth=70;
      const tSize=8;
      const tHeight=pHeight*4+yGap*3;
      let xCleanTill=0;
      let yCleanTill=0;
      let status="dirty";
      let x, y, nextRow, handle, efficiency=50, efficiencyOld, rowsCleaned=0;




      const drawTrackPanels=function(xCleanTill,yCleanTill){
        x=xOrigin;
        y=yOrigin;
        for(i=1; i<=20;i++){
          ctx.strokeStyle = "#595959";
          ctx.lineWidth = frame;

          if ( (y>=yCleanTill && x<=xCleanTill) || y>yCleanTill) {
            ctx.fillStyle="aqua";
          }else {
            ctx.fillStyle="#6b8585";
          }

          ctx.strokeRect(x+frame/2, y+frame/2, pWidth-frame, pHeight-frame);
          ctx.fillRect(x+frame, y+frame, pWidth-2*frame, pHeight-2*frame);
          x+=pWidth+xGap;
          rowCount+=1;
          if(rowCount===5){
            y+=pHeight+yGap;
            x=xOrigin;
            rowCount=0;
          }
        };
        x=xOrigin-xOffset;
        y=yOrigin;

        ctx.strokeStyle = "#595959";
        ctx.lineWidth = tSize;
        ctx.strokeRect(x+tSize/2, y+tSize/2, tWidth-tSize, tHeight-tSize);
        for(i=1;i<=4;i++){
          ctx.fillStyle="#595959";
          ctx.fillRect(x+tWidth, y, xOffset-tWidth, tSize);
          ctx.fillRect(x+tWidth, y+pHeight-tSize, xOffset-tWidth, tSize);
          y+=pHeight+yGap;
        };
      };

      const drawBot=function(xBot,yBot){

        ctx.fillStyle="#f39f07";
        ctx.fillRect(xBot,yBot,botWidth,botHeight);
      };

      const animate=function(){
        ctx.fillStyle="white";
        ctx.fillRect(0,0,widthC/scaleW,heightC/scaleH);

        if(flag==="cleaning" && status==="dirty"){
          xCleanTill=xBot+botWidth/2;
          yCleanTill=yBot+(botHeight-pHeight)/2;
          if(xBot>xOrigin-botWidth){
            efficiencyOld=efficiency;
            efficiency=(1-((xEnd-xBot-botWidth/2)/393))*12.5 +12.5*rowsCleaned +50;
            efficiency=efficiency>100?100:efficiency;
            efficiency=efficiency.toFixed(1)+'%';
            if(efficiencyOld!==efficiency){
              document.querySelector('#page2 .solar-robot .details .efficiency .effBar span').innerHTML=efficiency;
              document.querySelector('#page2 .solar-robot .details .efficiency .effBar span').style.width=efficiency;
            };
          };

         };

        drawTrackPanels(xCleanTill,yCleanTill);

        drawBot(xBot, yBot);
        if(flag==="goToBay" && yBot>=yBay){
          clearInterval(animationHandle);
          clearInterval(hubHandle);
          savedX=xBay;
          savedY=yBay;
          botPaused=1;
          flag="cleaning";
          status="cleaned";
          hubReset();
          rowsCleaned=0;
          document.querySelector('#page2 .solar-robot .details .menu-button span').innerHTML="Start Cleaning";
        }else if(flag==="goUp" && yBot<=nextRow){
          flag="cleaning";
          yBot=nextRow;
          xBot+=2;
          document.querySelector('#page2 .solar-robot .details .menu-button span').innerHTML=`Cleaning String ${rowsCleaned+1}`

        }else if (flag==="returning" && yBot<=yEnd && xBot<=xBay){
          yBot=(yOrigin-(botHeight-pHeight)/2)-2;
          flag="goToBay";
        }else if(flag==="cleaning" && xBot>=xEnd){
          flag="returning";
          xBot-=2;
          rowsCleaned+=1;
        }else if(flag==="returning" && xBot<=xBay){
          flag="goUp";
          nextRow=yBot-(pHeight+yGap);
        }else if(flag==="cleaning") {
          xBot+=2;
        }else if (flag==="returning") {
          xBot-=2;
        }else if(flag==="goToBay"){
          yBot+=2;
        }else if(flag==="goUp"){
          yBot-=2;
        };

      };

      drawTrackPanels();
      drawBot(xBay,yBay);
      xBot=savedX || xBay;
      yBot=savedY || yBay;

      if(start){

        animationHandle=setInterval(()=>{animate();},20);
        botPaused=0;
        $('#page2 .solar-robot .details .menu-button').click(function(evnt){
          if(!botPaused){
            savedX=xBot;
            savedY=yBot;
            clearInterval(animationHandle);
            clearInterval(hubHandle);
            hubReset();
            document.querySelector('#page2 .solar-robot .details .menu-button span').innerHTML="Stopped";
            botPaused=1;
          }else if(botPaused){
            xBot=savedX;
            yBot=savedY;
            animationHandle=setInterval(()=>{animate();},20);
            animateHub();
            botPaused=0;
          };
        });
      };
    };

    zero(ctx,180,25,start);
  };

  bot_animate(0);
  window.addEventListener('resize', function(){ bot_animate(0);});
let landingState=1 ;
const pauseLanding=function(){
    clearInterval(introHandle);
    document.querySelector("#video video").pause();
    landingState=0;
};

const resumeLanding=function(){
  if(landingState===0){
    introHandle=animateIntro();
    document.querySelector("#video video").play();
    landingState=1;
  };
};

const scrollOn=function(){

  $('#scrollBar').css('display','block');
  $('#scrollBar').css('opacity','1');
  $('body').css('overflow-y','scroll');
};

const scrollOff=function(){
  $('#scrollBar').css('opacity','0');
  setTimeout(function(){$('#scrollBar').css('display','none')},500);

  let handle;
  let y=document.querySelector('body').scrollTop;
  handle=setInterval(function(){

    if(y<=0){
      clearInterval(handle);
    };
    y=y-400;
    document.body.scrollTop = y;
    document.documentElement.scrollTop = y;
  },20);
  $('body').css('overflow-y','hidden');
};


  $('.imgWrapperH').click(function(){
    $('#roi').css('transform', 'translate(-100%,0)');
    scrollOn();


  });

  $('#video .wrap-button div:nth-child(1)').click(function(){
    $('#roi').css('transform', 'none');
    scrollOff();
  });

  $('#roiMessage p:nth-child(2) span').click(function(){
    $('#roi').css('transform', 'translate(-100%,0)');
    $('#cForm').css({'transition': 'visibility 0.8s linear, opacity 0.3s linear','visibility': 'visible', 'opacity':'1'});
  });

  $('#video .wrap-button div:nth-child(2)').click(function(){
    $('#cForm').css({'transition': 'visibility 0.8s linear, opacity 0.3s linear','visibility': 'visible', 'opacity':'1'});
    scrollOff();
  });

  $('#cForm .formBox .xButtonH').click(function(){
    $('#cForm').css({'visibility': 'hidden', 'opacity':'0'});
    scrollOn();
  });

$('#sideNavContact').click(function() {
  $('#pseudoB').prop('checked',false);
  $('#cForm').css({'transition': 'visibility 0.8s linear, opacity 0.3s linear','visibility': 'visible', 'opacity':'1'});
  scrollOff();
});

$('#pseudoB').click(function(){
  if($(this).is(':checked')){
    scrollOff();
  }else{
    scrollOn();

  };
})


  $('#page5 .wrapper div img:not(a>img)').hover(evnt=>{
    $('#page5 .wrapper2 .team').css({'opacity' :'0','display':'none'});
    let target=$(evnt.target);
    let elclass='.'+target.attr('class');
    $(`#page5 .wrapper2 ${elclass}`).css({'opacity' :'1','display':'block'});
    });

  $('#page5 .wrapper div img').mouseleave(function(){
    $('#page5 .wrapper2 p:not(.team)').css({'opacity' :'0', 'display':'none' });
    $('#page5 .wrapper2 .team').css({'opacity' :'1' ,'display':'block'});
  });
  $('#page6 .wrapper div img:not(a>img)').hover(evnt=>{
    $('#page6 .wrapper2 .team').css({'opacity' :'0','display':'none'});
    let target=$(evnt.target);
    let elclass='.'+target.attr('class');
    $(`#page6 .wrapper2 ${elclass}`).css({'opacity' :'1','display':'block'});
    });

  $('#page6 .wrapper div img').mouseleave(function(){
    $('#page6 .wrapper2 p:not(.team)').css({'opacity' :'0', 'display':'none' });
    $('#page6 .wrapper2 .team').css({'opacity' :'1' ,'display':'block'});
  });


  $('#page2 .solar-robot .details .menu-button').click(
    function() {
      if(!animationHandle){
        document.querySelector('#page2 .solar-robot .details .menu-button span').innerHTML="Cleaning String 1";
        bot_animate(1);
        animateHub();

      };
    });


  $('.faqs .wrapper .wrapper-faq .nthFaq .downGrey').click(function(evnt){
    let parent=$(this).parent();
    let down=$(evnt.target).parent();

    let target=parent.children().eq(0);
    let sibling=parent.children().eq(1);
    if(sibling.height()<=0){
      down.css({'transform':'rotate(-180deg)','transition': 'transform 0.2s ease-in'});

      sibling.css('height','auto');
      sibling.css('padding-bottom','10px');


    }else{
      down.css({'transform':'rotate(0deg)','transition': 'transform 0.2s ease-in'});

      sibling.css('padding-bottom','0');
      sibling.height(0);
    }

    });

  $('.faqs .wrapper .navigation .button').click(function(evnt){
    let indx=$(this).index()+1;
    let siblings=$(`.faqs .wrapper .wrapper-faq`);
    let target=$(`.faqs .wrapper .wrapper-faq:eq(${indx-1})`);
    siblings.css('display','none');
    target.css('display','flex');
    $(this).css({'background':'#595959FF','color':'white'});
    $(this).siblings().css({'background':'#E5E5E5','color':'#595959FF'});


  });

  $('.faqs .wrapper .closeButton').click(function(evnt){

    $('.faqs').css({'transition': 'visibility 0.8s linear, opacity 0.3s linear','visibility': 'hidden', 'opacity':'0'});
    setTimeout(function(){$('.faqs').css('display','none');},800);

  });
  $('#slide-nav li:nth-child(3)').click(function() {
      $('.faqs').css('display','block');
    setTimeout(function(){
      $('.faqs').css({'transition': 'visibility 0.8s linear, opacity 0.3s linear','visibility': 'visible', 'opacity':'1'});
    },200);

  });

  $(window).blur(function() {
    pauseLanding();
    clearInterval(clientScrollHandle);
    clearInterval(partnersScrollHandle);

  });
  $(window).focus( function() {
      if(currentSlideNumber===0 || screen.width<1020){
        if(landingState!==1){
          resumeLanding();
          clientScroll();
        };
        partnersScroll();
      }else if(currentSlideNumber===6){
        clientScroll();
        partnersScroll();
      };
  });

  if(!checkForOverlays()){
    $('body').css('overflow-y','hidden');
  };
  if(screen.width<1020){
    clientScroll();
    partnersScroll();
    $('#contactScrollTop').click(function(){
      scrollOff();
      $('#cForm').css({'transition': 'visibility 0.8s linear, opacity 0.3s linear','visibility': 'visible', 'opacity':'1'});

    });
  };

});
