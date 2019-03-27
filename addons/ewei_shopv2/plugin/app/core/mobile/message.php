<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Message_EweiShopV2Page extends AppMobilePage{
     //收集formid
     public function collect(){
         global $_GPC;
         global $_W;
         $data["openid"]=$_GPC["openid"];
         if (empty($data["openid"])){
             app_error(AppError::$ParamsError);
         }
         $data["time"]=strtotime('+7 day');
         $data["formid"]=$_GPC["formid"];
         $data["create_time"]=time();
         if (empty($data['formid'])){
             app_error(-1,"formid不可为空");
         }
         pdo_insert('ewei_shop_member_formid', $data);
         app_error(0,"提交成功");
     }
     
     public function message(){
         $touser="sns_wa_owRAK43dDy1s6i0_rbVfZUqgx854";
         $template_id="_z-2ZdOYhmyqTEnByOjyWPhkux8Sw0LpUDs9Dwfq2qo";
         
         $postdata=array(
             'keyword1'=>array(
                 'value'=>"11",
                 'color' => '#ff510'
             ),
             'keyword2'=>array(
                 'value'=>"22",
                 'color' => '#ff510'
             ),
             'keyword3'=>array(
                 'value'=>"3",
                 'color' => '#ff510'
             ),
             'keyword4'=>array(
                 'value'=>"4",
                 'color' => '#ff510'
             ),
             'keyword5'=>array(
                 'value'=>"5",
                 'color' => '#ff510'
             ),
             'keyword6'=>array(
                 'value'=>"6",
                 'color' => '#ff510'
             ),
             'keyword6'=>array(
                 'value'=>"6",
                 'color' => '#ff510'
             ),
             
         );
         
         
         $resualt=p("app")->mysendNotice($touser, $postdata,  '', $template_id);
         var_dump($resualt); 
     }
     public function cs(){
         $path_1=IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/1.png";
         $src = imagecreatefromstring(file_get_contents($path_1));
         $path_2=IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/erwei.jpeg";
         //创建点的实例
         $des = imagecreatefromjpeg($path_2);
         
         //获取点图片的宽高
         list($point_w, $point_h) = getimagesize($path_2);
         
         //重点：png透明用这个函数
         imagecopy($src, $des, 320, 620, 0, 0, $point_w, $point_h);
         imagecopy($src, $des, 320, 620, 0, 0, $point_w, $point_h);
         
         $name="erstyle".time();
         $img=IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/".$name.".jpg";
         imagejpeg($src,$img);
     }
     public function createposter()
     {
         global $_W;
        
         set_time_limit(0);
         $goods = array( );
         $openid="sns_wa_owRAK467jWfK-ZVcX2-XxcKrSyng";
         $member =pdo_fetch("select * from ".tablename("ewei_shop_member")." where openid=:openid",array(':openid'=>$openid));
        
         @ini_set("memory_limit", "256M");
         $path = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/" ;
         if( !is_dir($path) )
         {
             load()->func("file");
             mkdirs($path);
         }
         $md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"], "openid" => $member["openid"], "goodstitle" => $goods["title"], "goodprice" => $goods["minprice"], "version" => 1 )));
         $filename = time() . ".png";
         $filepath = $path . $filename;

         $backgroup=IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/1.png";
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
          $starttime=strtotime(date("Y-m-d",strtotime('-1 day')));
          $endtime=strtotime(date("Y-m-d",strtotime('+1 day')));
          $count=pdo_fetchcolumn("select sum(num) from ".tablename("mc_credits_record")." where openid=:openid and credittype=:credittype and createtime>=:starttime and createtime<=:endtime and remark!=:remark and num>0",array(':openid'=>$openid,':credittype'=>"credit1",":starttime"=>$starttime,':endtime'=>$endtime,':remark'=>"签到获取"));
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
           $count=pdo_fetchcolumn("select sum(num) from ".tablename("mc_credits_record")." where openid=:openid and credittype=:credittype and num>0",array(':openid'=>$openid,':credittype'=>"credit1"));
           if (empty($count)){
              $count=0;
           }
           $count=round($count,1);
           $name="收入（元）";
           $nameColor = imagecolorallocate($target, 51, 51, 51);
           imagettftext($target, 20, 0, 300, 1090, $nameColor, $PINGFANG_LIGHT, $name);
           $nameColor = imagecolorallocate($target, 51, 51, 51);
           imagettftext($target, 18, 0, 298, 1128, $nameColor, $PINGFANG_BOLD, $count);
         
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
         return $_W["siteroot"] . "addons/ewei_shopv2/data/poster_wxapp/sport"."/" . $filename . "?v=1.0";
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
     
}