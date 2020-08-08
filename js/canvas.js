window.addEventListener('DOMContentLoaded',function(){

canvas=document.querySelector("canvas");
parent=canvas.parentNode;

const dpr =1;
const padding = 20;
canvas.width = parent.clientWidth * dpr;
canvas.height = parent.clientHeight * dpr;
const ctx = canvas.getContext("2d");
ctx.scale(dpr, dpr);
ctx.fillStyle="white";
ctx.fillRect(0,0,10000,100000);


const zero=function(ctx,xOrigin, yOrigin){


  let xGap=15;
  let yGap=15;
  let pWidth=50;
  let pHeight=100;
  let rowCount=0
  let frame=5;
  let xOffset=150;
  let flag="cleaning";
  let botWidth=90;
  let botHeight= 150;
  let xBay=xOrigin-xOffset-10;
  let xEnd=xOrigin+pWidth*4+xGap*4-pWidth/2;
  let yBay=yOrigin-(botHeight-pHeight)/2+pHeight*3+yGap*3;
  let yEnd=yOrigin-(botHeight-pHeight)/2;
  let tWidth=70;
  let tSize=8;
  let tHeight=pHeight*4+yGap*3;
  let xCleanTill=xOrigin;
  let yCleanTill=yOrigin;
  let x, y, xBot, yBot, nextRow, handle;




  const drawTrackPanels=function(xCleanTill,yCleanTill){
    x=xOrigin;
    y=yOrigin;
    for(i=1; i<=20;i++){
      ctx.strokeStyle = "#595959";
      ctx.lineWidth = frame;
    //  ctx.fillStyle=(x>=xCleanTill || y<yCleanTill)?"grey":"aqua";

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
  let i=0;
  const animate=function(){
    ctx.fillStyle="white";
    ctx.fillRect(0,0,10000,100000);

    if(flag==="cleaning"){
      xCleanTill=xBot+botWidth/2;
      yCleanTill=yBot+(botHeight-pHeight)/2;
    };
    drawTrackPanels(xCleanTill,yCleanTill);
    drawBot(xBot, yBot);
    if(flag==="goToBay" && yBot>=yBay){
      clearInterval(handle);
    }else if(flag==="goUp" && yBot<=nextRow){
      flag="cleaning";
      yBot=nextRow;
      xBot+=2;
    }else if (flag==="returning" && yBot<=yEnd && xBot<=xBay){
      yBot=(yOrigin-(botHeight-pHeight)/2)-2;
      flag="goToBay";
    }else if(flag==="cleaning" && xBot>=xEnd){
      flag="returning";
      xBot-=2;
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

//  drawTrackPanels();
//  drawBot(xBay,yBay-(pHeight+yGap));
  xBot=xBay;
  yBot=yBay;
  handle=setInterval(function(){animate();},20);

};

zero(ctx, 300,50);









});
