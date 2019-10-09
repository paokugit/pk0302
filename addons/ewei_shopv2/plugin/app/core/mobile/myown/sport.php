<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
//fanbeibei

class Sport_EweiShopV2Page extends AppMobilePage{
    
    public function cs(){
        $path = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/" ;
        file_put_contents($path."1.txt", "22");
    }
    
    public function  css(){
        $openid="sns_wa_owRAK45elISHPXVTLz4DJ977pDR0";
        $starttime=strtotime(date("Y-m-d 23:59:59",strtotime('-1 day')));
        var_dump($starttime);
        
        $endtime=strtotime(date("Y-m-d 00:00:00",strtotime('+1 day')));
        var_dump($endtime);
        $count=pdo_fetchcolumn("select sum(num) from ".tablename("mc_credits_record")." where openid=:openid and credittype=:credittype and createtime>=:starttime and createtime<=:endtime and remark!=:remark and num>0",array(':openid'=>$openid,':credittype'=>"credit1",":starttime"=>$starttime,':endtime'=>$endtime,':remark'=>"签到获取"));
        if (empty($count)){
            $count=0;
        }
        $count=round($count,1);
        var_dump($count);
    }
    public function sports_poster(){
        
        global $_GPC;
        global $_W;
        $uniacid=$_W["uniacid"];
        $openid = $_GPC['openid'];
        $num=(int)$_GPC['num'];
        if (empty($openid)) {
            app_error(AppError::$ParamsError, '系统错误');
        }
        $member=m("member")->getMember($openid);
     
        if (empty($member)){
            app_error(-1,"用户不存在");
        }
        $day=date("Y-m-d",time());
        //获取今日模板
        $sport_style=pdo_fetch("select * from ".tablename("ewei_shop_member_sport")." where date=:day and is_default!=1",array(':day'=>$day));
        
//         var_dump($sport_style);die;
        if (empty($num)){
            //获取今天生成的海报
            $log=pdo_fetch("select * from ".tablename("ewei_shop_member_sportlog")." where openid=:openid and day=:day order by num desc limit 1",array(':openid'=>$openid,':day'=>$day));
           
            if ($log){
                $num=$log["num"]+1;
                //获取兑换步数
                $getstep=pdo_fetchall("select * from ".tablename("mc_credits_record")."where openid=:openid and credittype=:credittype and num>:num and uniacid=:uniacid and createtime>:createtime and (remark like :remark1 or remark like :remark2)",array(':openid'=>$openid,':credittype'=>'credit1',':num'=>0,':uniacid'=>$uniacid,':createtime'=>$log["create_time"],':remark1'=>'%步数兑换%',':remark2'=>'%好友助力%'));

//                 var_dump($getstep);die;
                if ($getstep){
                  //新生成模板
                    if (empty($sport_style)){
                        //获取默认模板
                        $sport_styledefault=pdo_fetch("select * from ".tablename("ewei_shop_member_sport")."where is_default=:is_default order by id asc limit 1",array(':is_default'=>1));
                        $sport_id=$sport_styledefault["id"];
                        $url=$this->createposter($openid,$sport_styledefault["thumb"]);
                    }else{
                        $sport_id=$sport_style["id"];
                        $url=$this->createposter($openid,$sport_style["thumb"]);
                    }
                }else{
                    if (empty($sport_style)){
                        $sport_id=$log["sport_id"];
                        $url=$log["url"];
                        
                    }else{
                        $default_log=pdo_fetch("select * from ".tablename("ewei_shop_member_sportlog")." where openid=:openid and day=:day and sport_id=:sport_id order by num desc limit 1",array(':openid'=>$openid,':day'=>$day,':sport_id'=>$sport_style["id"]));
                        if ($default_log){
                        $sport_id=$default_log["sport_id"];
                        $url=$default_log["url"];
                        }else{
                            $sport_id=$sport_style["id"];
                            $url=$this->createposter($openid,$sport_style["thumb"]);
                        }
                    }
                    
                }
            }else{
                //无今日记录
                $num=1;
                if (empty($sport_style)){
                    //获取默认
                    $sport_styledefault=pdo_fetch("select * from ".tablename("ewei_shop_member_sport")."where is_default=:is_default order by id asc limit 1",array(':is_default'=>1));
                    $sport_id=$sport_styledefault["id"];
                    $url=$this->createposter($openid,$sport_styledefault["thumb"]);
                }else{
                    $sport_id=$sport_style["id"];
                    $url=$this->createposter($openid,$sport_style["thumb"]);
                }
            }
        }else{
            //传递有num
            //获取今天生成的海报
            $log=pdo_fetchall("select * from ".tablename("ewei_shop_member_sportlog")." where openid=:openid and day=:day and num=:num",array(':openid'=>$openid,':day'=>$day,':num'=>$num));
            $sportids=array();
            foreach ($log as $k=>$v){
                $sportids[$k]=$v["sport_id"];
            }
           
            //获取不在这个海报中样式
            $style=pdo_fetch("select * from ".tablename("ewei_shop_member_sport")." where id not in(".implode(",", $sportids).") and (is_default=1 or date=:day)",array(':day'=>$day));
//             var_dump($style);
//             die;
              //所有模板已生成
            if (empty($style)){
                
                //获取兑换步数
                //获取今天生成的海报
                $logg=pdo_fetch("select * from ".tablename("ewei_shop_member_sportlog")." where openid=:openid and day=:day and num=:num order by create_time desc limit 1",array(':openid'=>$openid,':day'=>$day,':num'=>$num));
                
                $getstep=pdo_fetchall("select * from ".tablename("mc_credits_record")."where openid=:openid and credittype=:credittype and num>:num and uniacid=:uniacid and createtime>:createtime and (remark like :remark1 or remark like :remark2)",array(':openid'=>$openid,':credittype'=>'credit1',':num'=>0,':uniacid'=>$uniacid,':createtime'=>$logg["create_time"],':remark1'=>'%步数兑换%',':remark2'=>'%好友助力%'));
                
                $num=$num+1;
                if ($getstep){
                    //新生成模板
                    if (empty($sport_style)){
                        //获取默认模板
                        $sport_styledefault=pdo_fetch("select * from ".tablename("ewei_shop_member_sport")."where is_default=:is_default order by id asc limit 1",array(':is_default'=>1));
                        $sport_id=$sport_styledefault["id"];
                        $url=$this->createposter($openid,$sport_styledefault["thumb"]);
                    }else{
                        $sport_id=$sport_style["id"];
                        $url=$this->createposter($openid,$sport_style["thumb"]);
                    }
                }else{
                    if (empty($sport_style)){
                        $sport_id=$logg["sport_id"];
                        $url=$logg["url"];
                        
                    }else{
                        $default_log=pdo_fetch("select * from ".tablename("ewei_shop_member_sportlog")." where openid=:openid and day=:day and sport_id=:sport_id order by num desc limit 1",array(':openid'=>$openid,':day'=>$day,':sport_id'=>$sport_style["id"]));
                        if ($default_log){
                            $sport_id=$default_log["sport_id"];
                            $url=$default_log["url"];
                        }else{
                            $sport_id=$sport_style["id"];
                            $url=$this->createposter($openid,$sport_style["thumb"]);
                        }
                    }
                }
            }else{
               if ($num==1){
                   //生成新海报
                   $sport_id=$style["id"];
                   $url=$this->createposter($openid,$style["thumb"]);
                   
               }else{
                   //获取上次生成海报
                   $last_sport=pdo_fetch("select * from ".tablename("ewei_shop_member_sportlog")." where openid=:openid and num=:num and sport_id=:sport_id and day=:day",array(':openid'=>$openid,':num'=>$num-1,':sport_id'=>$style["id"],':day'=>$day));
                   
                   $getstep=pdo_fetchall("select * from ".tablename("mc_credits_record")."where openid=:openid and credittype=:credittype and num>:num and uniacid=:uniacid and createtime>:createtime",array(':openid'=>$openid,':credittype'=>'credit1',':num'=>0,':uniacid'=>$uniacid,':createtime'=>$last_sport["create_time"]));
                   
                   if ($getstep){
                       //生成新海报
                       $sport_id=$style["id"];
                       $url=$this->createposter($openid,$style["thumb"]);
                   }else{
                       if ($last_sport){
                           $sport_id=$last_sport["sport_id"];
                           $url=$last_sport["url"];
                       }else{
                           
                           $sport_id=$style["id"];
                           $url=$this->createposter($openid,$style["thumb"]);
                           
                       }
                   }
                   
               }
                
            }
        }
        //记录
        $data["openid"]=$openid;
        $data["sport_id"]=$sport_id;
        $data["num"]=$num;
        $data["url"]=$url;
        $data["day"]=$day;
        $data["create_time"]=time();
        pdo_insert("ewei_shop_member_sportlog",$data);
        
        $resault["url"]=$_W["siteroot"] .$url;
        $resault["num"]=$num;
        app_error(0,$resault);
        
       
    }
    public function ew(){
       // header( "Content-type: image/png");
        $path = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/" ;
        if( !is_dir($path) )
        {
            load()->func("file");
            mkdirs($path);
        }
        
        $filename = "1_".time() . ".png";
        $filepath = $path . $filename;
        $qrcode = p("app")->getCodeUnlimit(array( "scene" =>1, "page" => "pages/index/index" ));
        imagecreatefromstring($qrcode);
        imagepng($qrcode,$filepath);
       
    }
    public function createposter($openid="",$backgroup="")
    {
        global $_W;
        
        set_time_limit(0);
        $goods = array( );
//         $openid="sns_wa_owRAK467jWfK-ZVcX2-XxcKrSyng";
        $member =pdo_fetch("select * from ".tablename("ewei_shop_member")." where openid=:openid",array(':openid'=>$openid));
        
        @ini_set("memory_limit", "256M");
        $path = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/" ;
        if( !is_dir($path) )
        {
            load()->func("file");
            mkdirs($path);
        }
        $md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"], "openid" => $member["openid"], "goodstitle" => $goods["title"], "goodprice" => $goods["minprice"], "version" => 1 )));
        $filename = $openid."_".time() . ".png";
        $filepath = $path . $filename;
        
//         $backgroup=IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/1.png";
        $target = imagecreatetruecolor(750, 1334);
        /*imagecolorallocate ( resource $image , int $red , int $green , int $blue )
         为一幅图像分配颜色*/
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        
        //填充背景图
        $thumb = $this->createImage(tomedia($backgroup));
        /*imagecopyresized — 拷贝部分图像并调整大小
         dst_image  目标图象连接资源。 src_image源图象连接资源。 dst_x x-coordinate of destination point.dst_y y-coordinate of destination point. src_x x-coordinate of source point. src_y y-coordinate of source point. dst_w Destination width. dst_h Destination height.  src_w源图象的宽度。src_h 源图象的高度。
         */
        imagecopyresized($target, $thumb, 0, 0, 0, 0, 750, 1200, imagesx($thumb), imagesy($thumb));
        
        //添加底部字体
        /*imagecolorallocate ( resource $image , int $red , int $green , int $blue )
         为一幅图像分配颜色*/
        $nameColor = imagecolorallocate($target, 102, 102, 102);
        /*imagettftext ( resource $image , float $size , float $angle , int $x , int $y , int $color , string $fontfile , string $text )*/
        $PINGFANG_LIGHT = IA_ROOT . "/addons/ewei_shopv2/static/fonts/PINGFANG_LIGHT.ttf";
        if( !is_file($PINGFANG_LIGHT) )
        {
            $PINGFANG_LIGHT = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
        }
        $footer="长按识别小程序码进入跑库，开启健康小收入";
        imagettftext($target, 18, 0, 144, 1274, $nameColor, $PINGFANG_LIGHT, $footer);
        
        //添加底部背景
        $footer_backgroup=IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/floor.png";
        $thumb=$this->createImage(tomedia($footer_backgroup));
        imagecopyresized($target, $thumb, 34, 926, 0, 0, 680, 260, imagesx($thumb), imagesy($thumb));
        
        //头像
        /*imageistruecolor() 检查 image 图像是否为真彩色图像。
         * *
         */
        $avatartarget = imagecreatetruecolor(70, 70);
        $avatarwhite = imagecolorallocate($avatartarget, 255, 255, 255);
        imagefill($avatartarget, 0, 0, $avatarwhite);
        $memberthumb = tomedia($member["avatar"]);
        $avatar = preg_replace("/\\/0\$/i", "/96", $memberthumb);
        $image = $this->mergeImage($avatartarget, array( "type" => "avatar", "style" => "circle" ), $avatar);
        //imagecopyresized — 拷贝部分图像并调整大小
        //dst_image  目标图象连接资源。 src_image源图象连接资源。 dst_x x-coordinate of destination point.dst_y y-coordinate of destination point. src_x x-coordinate of source point. src_y y-coordinate of source point. dst_w Destination width. dst_h Destination height.  src_w源图象的宽度。src_h 源图象的高度。
        imagecopyresized($target, $image, 54, 946, 0, 0, 70, 70, 70, 70);
        
        //名称
        $nameColor = imagecolorallocate($target, 51, 51, 51);
        /*imagettftext ( resource $image , float $size , float $angle , int $x , int $y , int $color , string $fontfile , string $text )*/
        $PINGFANG_BOLD = IA_ROOT . "/addons/ewei_shopv2/static/fonts/PINGFANG_BOLD.ttf";
        if( !is_file($PINGFANG_BOLD) )
        {
            $PINGFANG_BOLD = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
        }
        $name="我叫".$member["nickname"];
        imagettftext($target, 20, 0, 144, 966, $nameColor, $PINGFANG_BOLD, $name);
        
        
        //卡路里
        //获取今日已兑换的卡路里
        $starttime=strtotime(date("Y-m-d 23:59:59",strtotime('-1 day')));
        $endtime=strtotime(date("Y-m-d 00:00:00",strtotime('+1 day')));
        $count_list=pdo_fetchall("select num from ".tablename("mc_credits_record")." where openid=:openid and credittype=:credittype and createtime>=:starttime and createtime<=:endtime and num>0 and (remark like :remark1 or remark like :remark2) order by id desc",array(':openid'=>$openid,':credittype'=>"credit1",":starttime"=>$starttime,':endtime'=>$endtime,':remark1'=>'%步数兑换%',':remark2'=>'%好友助力%'));
        //var_dump($count_list);
        $count=array_sum(array_column($count_list, 'num'));
        if (empty($count)){
            $count=0;
        }
        $count=round($count,1);
        $name="今日步数已兑换".$count."卡路里=";
        $nameColor = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 18, 0, 144, 998, $nameColor, $PINGFANG_LIGHT, $name);
        $name=$count."元";
        $nameColor = imagecolorallocate($target, 176, 6, 16);
        imagettftext($target, 18, 0, 446, 998, $nameColor, $PINGFANG_BOLD, $name);
        //获取剩余卡路里未兑换
        $exchange=m("member")->exchange_step($openid);
        $surplus=$exchange-$count;
        if ($surplus<0){
            $surplus=0;
        }
        $name="剩余".$surplus."元"."未兑换";
        $nameColor = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 18, 0, 144, 1028, $nameColor, $PINGFANG_LIGHT, $name);
        //签到天数
        $sign=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_member_getstep")." where openid=:openid and type=2",array(':openid'=>$openid));
        $name="签到（天）";
        $nameColor = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 20, 0, 80, 1090, $nameColor, $PINGFANG_LIGHT, $name);
        $nameColor = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 18, 0, 108, 1128, $nameColor, $PINGFANG_BOLD, $sign);
        //收入

     //   $count=pdo_fetchcolumn("select sum(num) from ".tablename("mc_credits_record")." where openid=:openid and credittype=:credittype and num>0",array(':openid'=>$openid,':credittype'=>"credit1"));
//         if (empty($count)){
//             $count=0;
//         }
//         $count=round($count,1);
//         $name="收入（元）";
//         $nameColor = imagecolorallocate($target, 51, 51, 51);
//         imagettftext($target, 20, 0, 300, 1090, $nameColor, $PINGFANG_LIGHT, $name);
//         $nameColor = imagecolorallocate($target, 51, 51, 51);
//         imagettftext($target, 18, 0, 298, 1128, $nameColor, $PINGFANG_BOLD, $count);

        //二维码
        $boxstr = file_get_contents(IA_ROOT . "/addons/ewei_shopv2/plugin/app/static/images/poster/goodsbox.png");
        $box = imagecreatefromstring($boxstr);
        //imagecopyresampled() 将一幅图像中的一块正方形区域拷贝到另一个图像中，平滑地插入像素值，因此，尤其是，减小了图像的大小而仍然保持了极大的清晰度
        //dst_image 目标图象连接资源。src_image 源图象连接资源。dst_x 目标 X 坐标点。dst_y  目标 Y 坐标点。src_x 源的 X 坐标点。src_y源的 Y 坐标点。dst_w目标宽度。dst_h目标高度。src_w 源图象的宽度。src_h源图象的高度。
        imagecopyresampled($target, $box, 546, 1004, 0, 0, 140, 140, 176, 176);
        $qrcode = p("app")->getCodeUnlimit(array( "scene" =>$member["id"], "page" => "pages/index/index" ));
        
        if( !is_error($qrcode) )
        {
            $qrcode = imagecreatefromstring($qrcode);
            imagecopyresized($target, $qrcode, 546, 1004, 0, 0, 140, 140, imagesx($qrcode), imagesy($qrcode));
        }
        
        imagepng($target,$filepath);
        
        imagedestroy($target);
        return $this->getImgUrl($filename);
    }
    private function getImgUrl($filename)
    {
        global $_W;
        return  "addons/ewei_shopv2/data/poster_wxapp/sport"."/" . $filename;
    }
    private function createImage($imgurl)
    {
        if( empty($imgurl) )
        {
            return "";
        }
        load()->func("communication");
        $resp = ihttp_request($imgurl);
        if( $resp["code"] == 200 && !empty($resp["content"]) )
        {
            return imagecreatefromstring($resp["content"]);
        }
        for( $i = 0; $i < 3; $i++ )
        {
            $resp = ihttp_request($imgurl);
            if( $resp["code"] == 200 && !empty($resp["content"]) )
            {
                return imagecreatefromstring($resp["content"]);
            }
        }
        return "";
    }
    private function getGoodsTitles($text, $fontsize = 30, $font = "", $width = 100)
    {
        $titles = array( "", "" );
        $textLen = mb_strlen($text, "UTF8");
        $textWidth = imagettfbbox($fontsize, 0, $font, $text);
        $textWidth = $textWidth[4] - $textWidth[6];
        if( 19 < $textLen && $width < $textWidth )
        {
            $titleLen1 = 19;
            for( $i = 19; $i <= $textLen; $i++ )
            {
                $titleText1 = mb_substr($text, 0, $i, "UTF8");
                $titleWidth1 = imagettfbbox($fontsize, 0, $font, $titleText1);
                if( $width < $titleWidth1[4] - $titleWidth1[6] )
                {
                    $titleLen1 = $i - 1;
                    break;
                }
            }
            $titles[0] = mb_substr($text, 0, $titleLen1, "UTF8");
            $titleLen2 = 19;
            for( $i = 19; $i <= $textLen; $i++ )
            {
                $titleText2 = mb_substr($text, $titleLen1, $i, "UTF8");
                $titleWidth2 = imagettfbbox($fontsize, 0, $font, $titleText2);
                if( $width < $titleWidth2[4] - $titleWidth2[6] )
                {
                    $titleLen2 = $i - 1;
                    break;
                }
            }
            $titles[1] = mb_substr($text, $titleLen1, $titleLen2, "UTF8");
            if( $titleLen1 + $titleLen2 < $textLen )
            {
                $titles[1] = mb_substr($titles[1], 0, $titleLen2 - 1, "UTF8");
                $titles[1] .= "...";
            }
        }
        else
        {
            $titles[0] = $text;
        }
        return $titles;
    }
    private function memberName($text)
    {
        $textLen = mb_strlen($text, "UTF8");
        if( 5 <= $textLen )
        {
            $text = mb_substr($text, 0, 5, "utf-8") . "...";
        }
        return $text;
    }
    private function imageZoom($image = false, $zoom = 2)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $target = imagecreatetruecolor($width * $zoom, $height * $zoom);
        imagecopyresampled($target, $image, 0, 0, 0, 0, $width * $zoom, $height * $zoom, $width, $height);
        imagedestroy($image);
        return $target;
    }
    private function imageRadius($target = false, $circle = false)
    {
        $w = imagesx($target);
        $h = imagesy($target);
        $w = min($w, $h);
        $h = $w;
        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true);
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $radius = ($circle ? $w / 2 : 20);
        $r = $radius;
        for( $x = 0; $x < $w; $x++ )
        {
            for( $y = 0; $y < $h; $y++ )
            {
                $rgbColor = imagecolorat($target, $x, $y);
                if( $radius <= $x && $x <= $w - $radius || $radius <= $y && $y <= $h - $radius )
                {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
                else
                {
                    $y_x = $r;
                    $y_y = $r;
                    if( ($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y) <= $r * $r )
                    {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    $y_x = $w - $r;
                    $y_y = $r;
                    if( ($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y) <= $r * $r )
                    {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    $y_x = $r;
                    $y_y = $h - $r;
                    if( ($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y) <= $r * $r )
                    {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    $y_x = $w - $r;
                    $y_y = $h - $r;
                    if( ($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y) <= $r * $r )
                    {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }
        return $img;
    }
    private function mergeImage($target = false, $data = array( ), $imgurl = "", $local = false)
    {
        if( empty($data) || empty($imgurl) )
        {
            return $target;
        }
        if( !$local )
        {
            $image = $this->createImage($imgurl);
        }
        else
        {
            $image = imagecreatefromstring($imgurl);
        }
        $sizes = $sizes_default = array( "width" => imagesx($image), "height" => imagesy($image) );
        $sizes = array( "width" => 70, "height" => 70 );
        if( $data["style"] == "radius" || $data["style"] == "circle" )
        {
            $image = $this->imageZoom($image, 4);
            $image = $this->imageRadius($image, $data["style"] == "circle");
            $sizes_default = array( "width" => $sizes_default["width"] * 4, "height" => $sizes_default["height"] * 4 );
        }
        imagecopyresampled($target, $image, intval($data["left"]) * 2, intval($data["top"]) * 2, 0, 0, $sizes["width"], $sizes["height"], $sizes_default["width"], $sizes_default["height"]);
        imagedestroy($image);
        return $target;
    }
    
    //绑定关系
    public function binding(){
        global $_W;
        global $_GPC;
        $login=(int)$_GPC["login"];
        $parent_id=(int)$_GPC["parent_id"];
        $openid=$_GPC["openid"];
        $openid=str_replace("sns_wa_", '', $openid);
        //绑定测试
        $bind["login"]=$login;
        $bind["parent_id"]=$parent_id;
        $bind["openid"]=$openid;
        $bind["create_time"]=date("Y-m-d",time());
        pdo_insert("ewei_member_bind",$bind);
       
        if (empty($openid)){
            app_error(AppError::$ParamsError);
        }
        if (empty($parent_id)){
            show_json(1,"推荐人不可为空");
        }
        $member=m("member")->getmember("sns_wa_".$openid);

            if ($member&&$member["agentid"]==0&&$member["id"]!=$parent_id){
                
                //推荐人
                if ($parent_id!=0&&!empty($parent_id)){
                    
                    pdo_update("ewei_shop_member",array('agentid'=>$parent_id),array('openid'=>"sns_wa_".$openid));
                    
                    $parent=m("member")->getmember($parent_id);

                    //添加绑定日志
                    $add = ['openid'=>$member['openid'],'item'=>'myown','value'=>'绑定上级:'.$member['openid'].'/'.$member['nickname'].',绑定上级id:'.$parent_id.'-'.$parent['nickname'],'create_time'=>date('Y-m-d H:i:s',time())];
                    m('memberoperate')->addlog($add);
                    if (!empty($parent)){
                        //                     $cd=$this->prize();
                        //                     m('member')->setCredit($parent["openid"], 'credit1', $cd,"推荐新用户获取");
                       
                        //判断今日推荐奖励是否达到50名·
                        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
                        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
                        $count=pdo_fetchcolumn("select count(*) from ".tablename("mc_credits_record")." where openid=:openid and credittype=:credittype and createtime>=:beginToday and createtime<=:endToday and remark like :remark",array(":openid"=>$parent["openid"],":credittype"=>"credit1",":beginToday"=>$beginToday,":endToday"=>$endToday,':remark'=>"推荐新用户获取"));
                        if ($count<50){
                        m('member')->setCredit($parent["openid"], 'credit1', 1,"推荐新用户获取");
                        }
                        
                        //贡献值奖励
                        m("devote")->rewardtwo($parent_id);
                    }
                    //粉丝
                    $my=pdo_get("ewei_shop_member",array("openid"=>$new_openid));
                    m("member")->fans($member["id"],$parent_id);
                    
                }
            }elseif (empty($member)){
                
                $m = array("uniacid" => $_W["uniacid"],"agentid"=>$parent_id,"uid" => 0, "openid" =>"sns_wa_".$openid, "openid_wa" =>$openid, "comefrom" => "sns_wa", "createtime" => time(), "status" => 0);
                pdo_insert("ewei_shop_member", $m);
                $myid=pdo_insertid();
                //推荐人
                if ($parent_id!=0&&!empty($parent_id)){
                    $parent=m("member")->getmember($parent_id);
                    if (!empty($parent)){
                        //                     $cd=$this->prize();
                        //                     m('member')->setCredit($parent["openid"], 'credit1', $cd,"推荐新用户获取");
                       
                        //判断今日推荐奖励是否达到50名·
                        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
                        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
                        $count=pdo_fetchcolumn("select count(*) from ".tablename("mc_credits_record")." where openid=:openid and credittype=:credittype and createtime>=:beginToday and createtime<=:endToday and remark like :remark",array(":openid"=>$parent["openid"],":credittype"=>"credit1",":beginToday"=>$beginToday,":endToday"=>$endToday,':remark'=>"推荐新用户获取"));
                        if ($count<50){
                            m('member')->setCredit($parent["openid"], 'credit1', 1,"推荐新用户获取");
                        }
                        
                        
                        //贡献值奖励
                        m("devote")->rewardtwo($parent_id);
                    }
                    
                    //添加绑定日志
                    $add = ['openid'=>"sns_wa_".$openid,'item'=>'myown','value'=>'绑定上级:'."sns_wa_".$openid.'/'."暂未获取".',绑定上级id:'.$parent_id.'-'.$parent['nickname'],'create_time'=>date('Y-m-d H:i:s',time())];
                    m('memberoperate')->addlog($add);
                    //粉丝
                    m("member")->fans($myid,$parent_id);
                }
            }
            
            
            show_json(0,"成功");
        
    }
    
    //概率算法
    public function get_rand($proArr) {
        
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }
    
    public function prize(){
        $arr=array(90,5,4,1);
        $rid = $this->get_rand($arr); //根据概率获取奖项id
        if ($rid==0){
            $resault=1;
        }elseif ($rid==1){
            $resault=rand(2,5);
        }elseif ($rid==2){
            $resault=rand(6,9);
        }else{
            $resault=10;
        }
        //         var_dump($resault);
        return $resault;
    }
    //更新login
    public function update_login(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $openid=str_replace("sns_wa_", '', $openid);
        $member=m("member")->getmember("sns_wa_".$openid);
        if ($member){
            pdo_update("ewei_shop_member",array('is_login'=>1),array('openid'=>"sns_wa_".$openid));
        }else{
           
            $m = array("uniacid" => $_W["uniacid"],"is_login"=>1,"uid" => 0, "openid" => "sns_wa_".$openid, "openid_wa" =>$openid, "comefrom" => "sns_wa", "createtime" => time(), "status" => 0);
            pdo_insert("ewei_shop_member", $m);
        }
        m('member')->setCredit("sns_wa_".$openid, 'credit3', 100, "新人专享金");
        show_json(0, "success");
    }
}