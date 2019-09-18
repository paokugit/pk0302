<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Poster_EweiShopV2Page extends AppMobilePage 
{
	public function getimage() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC["id"]);
		if( empty($id) ) 
		{
			app_error(AppError::$ParamsError, "参数错误");
		}
		$goods = pdo_fetch("select * from " . tablename("ewei_shop_goods") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));
		if( empty($goods) ) 
		{
			app_error(AppError::$GoodsNotFound, "商品未找到");
		}
		$member = $this->member;
		if( empty($member) ) 
		{
			$member = array( );
		}
		if($id==1467){//金主海报
            $imgurl = $this->createDevote($goods, $member);
        }else{
            $imgurl = $this->createPosternew($goods, $member);
        }
		if( empty($imgurl) )
		{
			app_error(AppError::$PosterCreateFail, "海报生成失败");
		}
		app_json(array( "url" => $imgurl ));
	}
	private function createPoster($goods = array( ), $member = array( )) 
	{
		global $_W;
		set_time_limit(0);
		@ini_set("memory_limit", "256M");
		$path = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/goods/" . $_W["uniacid"] . "/";
		if( !is_dir($path) ) 
		{
			load()->func("file");
			mkdirs($path);
		}
		$md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"], "openid" => $member["openid"], "goodstitle" => $goods["title"], "goodprice" => $goods["minprice"], "version" => 1 )));
		$filename = $md5 . ".png";
		$filepath = $path . $filename;
		if( is_file($filepath) ) 
		{
			return $this->getImgUrl($filename);
		}
		$target = imagecreatetruecolor(750, 1127);
		$white = imagecolorallocate($target, 255, 255, 255);
		imagefill($target, 0, 0, $white);
		if( !empty($goods["thumb"]) )
		{
			if( stripos($goods["thumb"], "//") === false ) 
			{
				$thumb = $this->createImage(tomedia($goods["thumb"]));
			}
			else 
			{
				$thumbStr = substr($goods["thumb"], stripos($goods["thumb"], "//"));
				$thumb = $this->createImage(tomedia("https:" . $thumbStr));
			}
			imagecopyresized($target, $thumb, 30, 124, 0, 0, 690, 690, imagesx($thumb), imagesy($thumb));
		}
		$font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/pingfang.ttf";
		if( !is_file($font) ) 
		{
			$font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
		}
		$avatartarget = imagecreatetruecolor(70, 70);
		$avatarwhite = imagecolorallocate($avatartarget, 255, 255, 255);
		imagefill($avatartarget, 0, 0, $avatarwhite);
		$memberthumb = tomedia($member["avatar"]);
		$avatar = preg_replace("/\\/0\$/i", "/96", $memberthumb);
		$image = $this->mergeImage($avatartarget, array( "type" => "avatar", "style" => "circle" ), $avatar);
		imagecopyresized($target, $image, 32, 30, 0, 0, 70, 70, 70, 70);
		$name = $this->memberName($member["nickname"]);
		$nameColor = imagecolorallocate($target, 82, 134, 207);
		imagettftext($target, 26, 0, 126, 80, $nameColor, $font, $name);
		$shareColor = imagecolorallocate($target, 56, 56, 56);
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
		return $_W["siteroot"] . "addons/ewei_shopv2/data/poster_wxapp/goods/" . $_W["uniacid"] . "/" . $filename . "?v=1.0";
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
		if( 11 < $textLen && $width < $textWidth )
		{
			$titleLen1 = 11;
			for( $i = 11; $i <= $textLen; $i++ )
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
			$titleLen2 = 11;
			for( $i = 11; $i <= $textLen; $i++ )
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
	private function imageRadius($target = false, $circle = false,$rounded=false)
	{
		$w = imagesx($target);
		$h = imagesy($target);
		$w = min($w, $h);
		$h = $w;
		$img = imagecreatetruecolor($w, $h);
		imagesavealpha($img, true);
		$bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
		imagefill($img, 0, 0, $bg);
		if($rounded){
            $radius = 180;
        }else{
            $radius = ($circle ? $w / 2 : 20);
        }
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


    private function createHelpPoster($member = array(),$mid)
    {
        global $_W;
        set_time_limit(0);
        @ini_set("memory_limit", "256M");
        $path = IA_ROOT . "/addons/ewei_shopv2/data/helpposter/";
        if( !is_dir($path) )
        {
            load()->func("file");
            mkdirs($path);
        }
        $md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"], "openid" => $member["openid"],"code"=>10)));
        $filename = $md5 . ".png";
        $filepath = $path . $filename;
        if( is_file($filepath) )
        {
           return $_W["siteroot"] . "addons/ewei_shopv2/data/helpposter/".$filename;
        }
        $target = imagecreatetruecolor(550, 978);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        $thumb = "/addons/ewei_shopv2/static/images/1.png";
        $thumb = $this->createImage(tomedia($thumb));
        imagecopyresized($target, $thumb, 0, 0, 0, 0, 550, 978, imagesx($thumb), imagesy($thumb));

        $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/pingfang.ttf";
        if( !is_file($font) )
        {
            $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
        }
        $black = imagecolorallocate($target, 0, 0, 0);
        imagettftext($target, 26, 0, 32, 782, $black, $font, '快来帮我助力一下');
        imagettftext($target, 16, 0, 32, 820, $black, $font, '微信步数兑现金，收入可提现！');
        //lihanwen
        $qrcode = p("app")->getCodeUnlimit(array( "scene" => "&mid=" . $mid,"page" => "packageA/pages/helphand/helpshare/helpshare" ));

        //var_dump($qrcode);
        if( !is_error($qrcode) )
        {
            $qrcode = imagecreatefromstring($qrcode);
            imagecopyresized($target, $qrcode, 400, 785, 0, 0, 110, 110, imagesx($qrcode), imagesy($qrcode));
        }

        //微信头像显示
        $avatartarget = imagecreatetruecolor(70, 70);
        $avatarwhite = imagecolorallocate($avatartarget, 255, 255, 255);
        imagefill($avatartarget, 0, 0, $avatarwhite);
        $memberthumb = tomedia($member["avatar"]);
        $avatar = preg_replace("/\\/0\$/i", "/96", $memberthumb);
        $image = m('qrcode')->mergeImage($avatartarget, array( "type" => "avatar", "style" => "circle" ), $avatar);
        imagecopyresized($target, $image, 32, 860, 0, 0, 70, 70, 70, 70);
        
        imagettftext($target, 16, 0, 110, 875 , $black, $font, $this->subtext($member["nickname"],8));
        $nameColor = imagecolorallocate($target, 102, 102, 102);
        imagettftext($target, 12, 0, 110, 900 , $nameColor, $font, '每一步，都值得鼓励');
        imagepng($target, $filepath);
        imagedestroy($target);
        return $_W["siteroot"] . "addons/ewei_shopv2/data/helpposter/".$filename . "?v=1.0";
    }

    /**
     * 作废了
     * @param $text
     * @param $length
     * @return string
     */
    public function subtext($text, $length)
    {
        if(mb_strlen($text, 'utf8') > $length) {
            return mb_substr($text, 0, $length, 'utf8').'...';
        } else {
            return $text;
        }

    }



    public function gethelpimage()
    {
        global $_GPC;
        $mid = $_GPC['mids'];
        $member = $this->member;
        if( empty($member) )
        {
            $member = array( );
        }
        //$imgurl = $this->createHelpPoster( $member,$mid);
        $imgurl = m('qrcode')->HelpPoster($member,$mid,['back'=>'/addons/ewei_shopv2/static/images/1.png','type'=>"helpposter",'title'=>'快来帮我助力一下','desc'=>'微信步数兑现金，收入可提现！','con'=>'每一步，都值得鼓励','url'=>'packageA/pages/helphand/helpshare/helpshare']);
        if( empty($imgurl))
        {
            app_error(AppError::$PosterCreateFail, "海报生成失败");
        }
       app_json(array( "url" => $imgurl ));
    }


    public function getShopOwnerPoster()
    {
        global $_GPC;
        global $_W;
        set_time_limit(0);
        @ini_set("memory_limit", "256M");
        $path = IA_ROOT . "/addons/ewei_shopv2/data/shopownercode/";
        if( !is_dir($path) )
        {
            load()->func("file");
            mkdirs($path);
        }
        $md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"], "mid" => $_GPC['mid'],"id" => $_GPC['id'])));
        $filename = $md5 . ".png";
        $filepath = $path . $filename;
        if( is_file($filepath) )
        {
            $imgurl = $_W["siteroot"] . "addons/ewei_shopv2/data/shopownercode/".$filename;
            app_json(array( "url" => $imgurl ));
        }
        $target = imagecreatetruecolor(690, 850);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        $thumb = "/addons/ewei_shopv2/static/images/shopowner.png";
        $thumb = $this->createImage(tomedia($thumb));
        imagecopyresized($target, $thumb, 0, 0, 0, 0, 690, 850, imagesx($thumb), imagesy($thumb));
        $qrcode = p("app")->getCodeUnlimit(array( "scene" => "id=" . $_GPC['id'] . "&mid=" . $_GPC['mid'], "page" => "pages/goods/detail/index" ));
        if( !is_error($qrcode) )
        {
            $qrcode = imagecreatefromstring($qrcode);
            imagecopyresized($target, $qrcode, 174, 281, 0, 0, 334, 334, imagesx($qrcode), imagesy($qrcode));
        }

        imagepng($target, $filepath);
        imagedestroy($target);

        $imgurl =  $_W["siteroot"] . "addons/ewei_shopv2/data/shopownercode/".$filename . "?v=1.0";
        app_json(array( "url" => $imgurl ));
    }

    /**
     * 分享商品海报图生成
     */
    public function sharegoodsimg(){
        global $_GPC;
        global $_W;
        set_time_limit(0);
        @ini_set("memory_limit", "256M");
        $path = IA_ROOT . "/addons/ewei_shopv2/data/sharegoods/";
        if( !is_dir($path) )
        {
            load()->func("file");
            mkdirs($path);
        }
        $goods = pdo_fetch("select * from " . tablename("ewei_shop_goods") . " where id=:id limit 1", array( ":id" => $_GPC['id'] ));

        $md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"],"id" => $_GPC['id'],'minprice'=>$goods['minprice'])));
        $filename = $md5 . ".png";
        $filepath = $path . $filename;
        if( is_file($filepath) )
        {
            $imgurl = $_W["siteroot"] . "addons/ewei_shopv2/data/sharegoods/".$filename;
            app_json(array( "url" => $imgurl ));
        }
        //底部图
        $target = imagecreatetruecolor(450,360);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        $thumb = "/addons/ewei_shopv2/static/images/sharegoodsbg.png";
        $thumb = $this->createImage(tomedia($thumb));
        imagecopyresized($target, $thumb, 0, 0, 0, 0, 450, 360, imagesx($thumb), imagesy($thumb));

        //商品图
        if( !empty($goods["thumb"]) )
        {
            if( stripos($goods["thumb"], "//") === false )
            {
                $thumb = $this->createImage(tomedia($goods["thumb"]));
            }
            else
            {
                $thumbStr = substr($goods["thumb"], stripos($goods["thumb"], "//"));
                $thumb = $this->createImage(tomedia("https:" . $thumbStr));
            }
            imagecopyresized($target, $thumb, 11, 11, 0, 0, 280, 280, imagesx($thumb), imagesy($thumb));
        }

        //价格
        $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/PINGFANG_BOLD.TTF";
        if( !is_file($font) )
        {
            $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
        }
        $goodsprice = $this->goodsminprice($goods);
        if($goodsprice==0){
            $red = imagecolorallocate($target, 248, 5, 4);
            imagettftext($target, 32, 0, 297, 124, $red, $font,'免费兑' );
            //imagettftext($target, 32, 0, 318, 120, $red, $font, floatval($goods['minprice']));
        }else{
            //现价
            $red = imagecolorallocate($target, 248, 5, 4);
            imagettftext($target, 28, 0, 297, 124, $red, $font,'¥' );
            imagettftext($target, 32, 0, 318, 120, $red, $font, floatval($goodsprice));
        }
       //原价
        $black = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 20, 0, 297, 170, $black, $font,'¥'.floatval($goods['productprice']) );

        imagepng($target, $filepath);
        imagedestroy($target);

        $imgurl =  $_W["siteroot"] . "addons/ewei_shopv2/data/sharegoods/".$filename . "?v=1.0";
        app_json(array( "url" => $imgurl ));

    }

     public function goodsminprice($goods){
         if($goods['deduct']>=$goods['minprice']) return 0;
         if($goods['deduct']>0){
             return floatval($goods['minprice']-$goods['deduct']);
         }
         return $goods['minprice'];
     }



    private function createPosternew($goods = array( ), $member = array( ))
    {
        global $_W;
        set_time_limit(0);
        @ini_set("memory_limit", "256M");
        $path = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/goods/" . $_W["uniacid"] . "/";
        if( !is_dir($path) )
        {
            load()->func("file");
            mkdirs($path);
        }
        $md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"], "openid" => $member["openid"], "goodstitle" => $goods["title"], "goodprice" => $goods["minprice"], "version" => 1 )));
        $filename = $md5 . ".png";
        $filepath = $path . $filename;
        if( is_file($filepath) )
        {
            return $this->getImgUrl($filename);
        }
        $target = imagecreatetruecolor(750, 1360);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);

        $thumb = "/addons/ewei_shopv2/static/images/goodsshare.png";
        $thumb = $this->createImage(tomedia($thumb));
        imagecopyresized($target, $thumb, 0, 0, 0, 0, 750, 1360, imagesx($thumb), imagesy($thumb));

        if( !empty($goods["thumb"]) )
        {
            if( stripos($goods["thumb"], "//") === false )
            {
                $thumb = $this->createImage(tomedia($goods["thumb"]));
            }
            else
            {
                $thumbStr = substr($goods["thumb"], stripos($goods["thumb"], "//"));
                $thumb = $this->createImage(tomedia("https:" . $thumbStr));
            }
//            $avatartarget = imagecreatetruecolor(650, 650);
//            $avatarwhite = imagecolorallocate($avatartarget, 255, 255, 255);
//            imagefill($avatartarget, 0, 0, $avatarwhite);
//            $memberthumb = tomedia($goods["thumb"]);
//            $avatar = preg_replace("/\\/0\$/i", "/96", $memberthumb);
//            $image = $this->mergegoodsImage($avatartarget, array( "type" => "avatar", "style" => "circle" ), $avatar);
            imagecopyresized($target, $thumb, 48, 332, 0, 0, 650, 650, imagesx($thumb), imagesy($thumb));
        }
        $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/PINGFANG_BOLD.TTF";
        if( !is_file($font) )
        {
            $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
        }
        $avatartarget = imagecreatetruecolor(98, 98);
        $avatarwhite = imagecolorallocate($avatartarget, 255, 255, 255);
        imagefill($avatartarget, 0, 0, $avatarwhite);

        $memberthumb = tomedia($member["avatar"]);
        $avatar = preg_replace("/\\/0\$/i", "/96", $memberthumb);
        $image = $this->mergeImage($avatartarget, array( "type" => "avatar", "style" => "circle" ), $avatar);
        imagecopyresized($target, $image, 53, 184, 0, 0, 98, 98, 98, 98);

        $name = $this->memberName($member["nickname"]);
        $nameColor = imagecolorallocate($target, 138, 138, 138);
        imagettftext($target, 34, 0, 180, 215, $nameColor, $font, $name);
        $shareColor = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 28, 0, 180, 280, $shareColor, $font, "推荐给你一个好物！");

        $thumb = "/addons/ewei_shopv2/static/images/1pxbg.png";
        $thumb = $this->createImage(tomedia($thumb));
        imagecopyresized($target, $thumb, 46, 1042, 0, 0, 300, 3, 300, 3);
        //原价
        $ypricecolor = imagecolorallocate($target, 140, 140, 140);
        imagettftext($target, 30, 0, 52, 1052, $ypricecolor, $font, '原价:￥'.$goods["productprice"]);
        $pricecolor = imagecolorallocate($target, 249, 53, 51);

        $useprice = round($goods["minprice"]-$goods['deduct'],2);
        imagettftext($target, 54, 0, 100, 1130, $pricecolor, $font, $useprice);
        imagettftext($target, 38, 0, 52, 1126, $pricecolor, $font, "￥");

        $titles = $this->getGoodsTitles($goods["title"], 30, $font, 400);
        $black = imagecolorallocate($target, 0, 0, 0);
        imagettftext($target, 30, 0, 60, 1237, $black, $font, $titles[0]);
        imagettftext($target, 30, 0, 60, 1287, $black, $font, $titles[1]);
        $qrcode = p("app")->getCodeUnlimit(array( "scene" => "id=" . $goods["id"] . "&mid=" . $member["id"], "page" => "pages/goods/detail/index" ));
        if( !is_error($qrcode) )
        {
            $qrcode = imagecreatefromstring($qrcode);
            imagecopyresized($target, $qrcode, 502, 1100, 0, 0, 220, 220, imagesx($qrcode), imagesy($qrcode));
        }
        $gary2 = imagecolorallocate($target, 140, 140, 140);
        imagettftext($target, 30, 0, 523, 1085, $gary2, $font, "长按识别");
        imagepng($target, $filepath);
        imagedestroy($target);
        return $this->getImgUrl($filename);
    }



    private function mergegoodsImage($target = false, $data = array( ), $imgurl = "", $local = false)
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
        $sizes = array( "width" => 650, "height" => 650 );
        if( $data["style"] == "radius" || $data["style"] == "circle" )
        {
            $image = $this->imageZoom($image, 4);
            $image = $this->imageRadius($image, $data["style"] == "circle",'true');
            $sizes_default = array( "width" => $sizes_default["width"] * 4, "height" => $sizes_default["height"] * 4 );
        }
        imagecopyresampled($target, $image, intval($data["left"]) * 2, intval($data["top"]) * 2, 0, 0, $sizes["width"], $sizes["height"], $sizes_default["width"], $sizes_default["height"]);
        imagedestroy($image);
        return $target;
    }


    /**
     * 金主商品海报
     * @param array $goods
     * @param array $member
     * @return string
     */
    private function createDevote($goods = array( ), $member = array( ))
    {
        global $_W;
        set_time_limit(0);
        @ini_set("memory_limit", "256M");
        $path = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/goods/" . $_W["uniacid"] . "/";
        if( !is_dir($path) )
        {
            load()->func("file");
            mkdirs($path);
        }
        $md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"], "openid" => $member["openid"], "goodstitle" => $goods["title"], "goodprice" => $goods["minprice"], "version" => 1 )));
        $filename = $md5 . ".png";
        $filepath = $path . $filename;
        if( is_file($filepath) )
        {
           return $this->getImgUrl($filename);
        }
        $target = imagecreatetruecolor(750, 1300);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);

        $thumb = "/addons/ewei_shopv2/static/images/jzbg@2x.png";
        $thumb = $this->createImage(tomedia($thumb));
        imagecopyresized($target, $thumb, 0, 0, 0, 0, 750, 1300, imagesx($thumb), imagesy($thumb));

        $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/PINGFANG_BOLD.TTF";
        if( !is_file($font) )
        {
            $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
        }
        $avatartarget = imagecreatetruecolor(150, 150);
        $avatarwhite = imagecolorallocatealpha($avatartarget, 0, 0, 0,127);
        imagefill($avatartarget, 0, 0, $avatarwhite);

        //头像
        $memberthumb = tomedia($member["avatar"]);
        $avatar = preg_replace("/\\/0\$/i", "/96", $memberthumb);
        $image = $this->mergeDevoteImage($avatartarget, array( "type" => "avatar", "style" => "circle" ), $avatar);
        imagecopyresized($target, $image, 58, 67, 0, 0, 150, 150, 150, 150);

        $nameColor = imagecolorallocate($target, 255, 255, 255);
        imagettftext($target, 34, 0, 228, 110, $nameColor, $font, $member["nickname"]);
        $shareColor = imagecolorallocate($target, 225, 225, 225);
        imagettftext($target, 34, 0, 228, 190, $shareColor, $font, "邀请您开通");
        $qrcode = p("app")->getCodeUnlimit(array( "scene" => "id=" . $goods["id"] . "&mid=" . $member["id"], "page" => "pages/goods/detail/index" ));
        if( !is_error($qrcode) )
        {
            $qrcode = imagecreatefromstring($qrcode);
            imagecopyresized($target, $qrcode, 522, 1085, 0, 0, 180, 180, imagesx($qrcode), imagesy($qrcode));
        }
        imagepng($target, $filepath);
        imagedestroy($target);
        return $this->getImgUrl($filename);
    }


    private function mergeDevoteImage($target = false, $data = array( ), $imgurl = "", $local = false)
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
        $sizes = array( "width" => 150, "height" => 150 );
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
?>