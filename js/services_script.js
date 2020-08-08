$(function(){

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


});
