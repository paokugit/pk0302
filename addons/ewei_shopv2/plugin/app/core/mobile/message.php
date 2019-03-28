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
         $touser="sns_wa_owRAK467jWfK-ZVcX2-XxcKrSyng";
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
         
         
         $resualt=p("app")->mysendNotice($touser, $postdata,  50, "PJlt5K7VTo9AaLWG4EM2pOTdxpNc6Ua029yKWhDYl6E");
         var_dump($resualt["meta"]); 
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
         $target = imagecreatetruecolor(555, 888);
         $white = imagecolorallocate($target, 255, 255, 255);
         imagefill($target, 0, 0, $white);
         
         //填充背景图
         $thumb = $this->createImage(tomedia($backgroup));
         imagecopyresized($target, $thumb, 0, 0, 0, 0, 555, 888, imagesx($thumb), imagesy($thumb));
         
         
         $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/pingfang.ttf";
         if( !is_file($font) )
         {
             $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
         }
         //头像
         $avatartarget = imagecreatetruecolor(70, 70);
         $avatarwhite = imagecolorallocate($avatartarget, 255, 255, 255);
         imagefill($avatartarget, 0, 0, $avatarwhite);
         $memberthumb = tomedia($member["avatar"]);
         $avatar = preg_replace("/\\/0\$/i", "/96", $memberthumb);
        
         $image = $this->mergeImage($avatartarget, array( "type" => "avatar", "style" => "circle" ), $avatar);
         //imagecopyresized — 拷贝部分图像并调整大小 
         //dst_image  目标图象连接资源。 src_image源图象连接资源。 dst_x x-coordinate of destination point.dst_y y-coordinate of destination point. src_x x-coordinate of source point. src_y y-coordinate of source point. dst_w Destination width. dst_h Destination height.  src_w源图象的宽度。src_h 源图象的高度。
         imagecopyresized($target, $image, 32, 30, 0, 0, 70, 70, 70, 70);
         imagepng($target,$filepath);
         var_dump("11");
         die;
         
         
         $name = $this->memberName($member["nickname"]);
         //imagecolorallocate ( resource $image , int $red , int $green , int $blue )
         //为一幅图像分配颜色
         $nameColor = imagecolorallocate($target, 82, 134, 207);
         imagettftext($target, 26, 0, 126, 80, $nameColor, $font, $name);
         $shareColor = imagecolorallocate($target, 56, 56, 56);
         //imagettfbbox — 取得使用 TrueType 字体的文本的范围
         //size 像素单位的字体大小 angle将被度量的角度大小  
         $textbox = imagettfbbox(26, 0, $font, $name);
         $textwidth = (136 + $textbox[4]) - $textbox[6];
         imagettftext($target, 26, 0, $textwidth, 80, $shareColor, $font, "分享给你一个商品");
         
         $pricecolor = imagecolorallocate($target, 248, 88, 77);
         imagettftext($target, 52, 0, 56, 1016, $pricecolor, $font, $goods["minprice"]);
         imagettftext($target, 26, 0, 30, 1016, $pricecolor, $font, "￥");
         $titles = $this->getGoodsTitles($goods["title"], 28, $font, 690);
         $black = imagecolorallocate($target, 0, 0, 0);
         imagettftext($target, 28, 0, 30, 872, $black, $font, $titles[0]);
         imagettftext($target, 28, 0, 30, 922, $black, $font, $titles[1]);
         $boxstr = file_get_contents(IA_ROOT . "/addons/ewei_shopv2/plugin/app/static/images/poster/goodsbox.png");
         $box = imagecreatefromstring($boxstr);
         //imagecopyresampled() 将一幅图像中的一块正方形区域拷贝到另一个图像中，平滑地插入像素值，因此，尤其是，减小了图像的大小而仍然保持了极大的清晰度
         //dst_image 目标图象连接资源。src_image 源图象连接资源。dst_x 目标 X 坐标点。dst_y  目标 Y 坐标点。src_x 源的 X 坐标点。src_y源的 Y 坐标点。dst_w目标宽度。dst_h目标高度。src_w 源图象的宽度。src_h源图象的高度。
         imagecopyresampled($target, $box, 546, 934, 0, 0, 150, 150, 176, 176);
         $qrcode = p("app")->getCodeUnlimit(array( "scene" => "id=" . $goods["id"] . "&mid=" . $member["id"], "page" => "pages/goods/detail/index" ));
         if( !is_error($qrcode) )
         {
             $qrcode = imagecreatefromstring($qrcode);
             imagecopyresized($target, $qrcode, 546, 934, 0, 0, 150, 150, imagesx($qrcode), imagesy($qrcode));
         }
         $gary2 = imagecolorallocate($target, 152, 152, 152);
         imagettftext($target, 24, 0, 30, 1070, $gary2, $font, "长按识别小程序码访问");
         imagepng($target, $filepath);
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