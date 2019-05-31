jQuery("a.user_doplay").click(function(){
    var x = document.getElementById("myAudio");
    if (x.paused) {
     jQuery("a.user_doplay").find('img').attr('src','http://sandbox.runjs.cn/uploads/rs/424/shsayjwv/play.png');
     jQuery(this).find('img').attr('src','http://sandbox.runjs.cn/uploads/rs/424/shsayjwv/stop.png');
     jQuery(this).attr("name","playing");
     x.play(); //播放
    } else if (x.play && jQuery(this).attr("name") == "stoped") {
     jQuery('#myAudio').attr('src',jQuery(this).attr('id') + '.mp3');//修改音频路径
     jQuery("a.user_doplay").find('img').attr('src','http://sandbox.runjs.cn/uploads/rs/424/shsayjwv/play.png');
     jQuery(this).find('img').attr('src','http://sandbox.runjs.cn/uploads/rs/424/shsayjwv/stop.png');
     jQuery("#play_list_ol").find('a').attr('name','stoped');
     jQuery(this).attr("name","playing");
     x.play(); //播放
    } else if (x.play && jQuery(this).attr("name") == "playing") {
     jQuery(this).find('img').attr('src','http://sandbox.runjs.cn/uploads/rs/424/shsayjwv/play.png');
     jQuery("#play_list_ol").find('a').attr('name','stoped');
     x.pause(); //暂停
    } else {
     alert("这个提示不应该出现");
    }
   });