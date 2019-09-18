<?php
class Qrcode_EweiShopV2Model
{
	/**
     * 商城二维码
     * @global type $_W
     * @param type $mid
     * @return string
     */
	public function createShopQrcode($mid = 0, $posterid = 0)
	{
		global $_W;
		global $_GPC;
		$path = IA_ROOT . '/addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/';

		if (!is_dir($path)) {
			load()->func('file');
			mkdirs($path);
		}

		$url = mobileUrl('', array('mid' => $mid), true);

		if (!empty($posterid)) {
			$url .= '&posterid=' . $posterid;
		}

		$file = 'shop_qrcode_' . $posterid . '_' . $mid . '.png';
		$qrcode_file = $path . $file;

		if (!is_file($qrcode_file)) {
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
		}

		return $_W['siteroot'] . 'addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/' . $file;
	}

	/**
     * 产品二维码
     * @global type $_W
     * @param type $goodsid
     * @return string
     */
	public function createGoodsQrcode($mid = 0, $goodsid = 0, $posterid = 0)
	{
		global $_W;
		global $_GPC;
		$path = IA_ROOT . '/addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'];

		if (!is_dir($path)) {
			load()->func('file');
			mkdirs($path);
		}

		$url = mobileUrl('goods/detail', array('id' => $goodsid, 'mid' => $mid), true);

		if (!empty($posterid)) {
			$url .= '&posterid=' . $posterid;
		}

		$file = 'goods_qrcode_' . $posterid . '_' . $mid . '_' . $goodsid . '.png';
		$qrcode_file = $path . '/' . $file;

		if (!is_file($qrcode_file)) {
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
		}

		return $_W['siteroot'] . 'addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/' . $file;
	}

	public function createQrcode($url)
	{
		global $_W;
		global $_GPC;
		$path = IA_ROOT . '/addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/';

		if (!is_dir($path)) {
			load()->func('file');
			mkdirs($path);
		}

		$file = md5(base64_encode($url)) . '.jpg';
		$qrcode_file = $path . $file;

		if (!is_file($qrcode_file)) {
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
		}

		return $_W['siteroot'] . 'addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/' . $file;
	}

	/**
	 *  商城收款二维码
	 * @param int $mid
	 * @param int $r
	 * @param int $posterid
	 * @param string $background
	 * @return string
	 */
	public function createSQrcode($mid = 0, $r = 0,$background = "",$posterid = 0)
	{
		global $_W;
		//查询商家信息
		$merch = pdo_fetch('select merchname,logo from '.tablename('ewei_shop_merch_user').' where id = "'.$mid.'"');
		$path = IA_ROOT . '/addons/ewei_shopv2/data/merch/' . $_W['uniacid'] . '/';
		if (!is_dir($path)) {
			load()->func('file');
			mkdirs($path);
		}
		//设置二维码的URL路径
		if(!empty($r)){
			$url = mobileUrl($r, array('mid' => $mid), true);
		}
		if (!empty($posterid)) {
			$url .= '&posterid=' . $posterid;
		}
		//生成二维码
		$file = md5('shop_qrcode_' . $posterid . $mid . $background .$merch['merchname']).'.png';
		$qr_file = md5('shop_qr_' . $posterid . $mid . $background .$merch['merchname']).'.png';
		$qrcode_file = $path . $file;
		$code_file = $path . $qr_file;
		if (!is_file($qrcode_file)) {
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 5);
		}else{
			$qrcode = $_W['siteroot'] . 'addons/ewei_shopv2/data/merch/' . $_W['uniacid'] . '/' . $file;
			$qr = $_W['siteroot'] . 'addons/ewei_shopv2/data/merch/' . $_W['uniacid'] . '/' . $qr_file;
			return ['qrcode'=>$qrcode,'qr'=>$qr];
		}
		//把二维码放在设定好的背景图里面  $logo二维码的背景图   imagecopyresampled 设置二维码在背景图的位置
		$logo = IA_ROOT . '/addons/ewei_shopv2/static/images/'.$background.'.png';
		$center = $merch['logo']?:IA_ROOT . '/addons/ewei_shopv2/static/images/logo.png';
		//把二维码  小logo 和背景logo  从字符串中的图像流新建一图像
		$qr = imagecreatefromstring(file_get_contents($qrcode_file));
		$logo = imagecreatefromstring(file_get_contents($logo));
		$center = imagecreatefromstring(file_get_contents($center));
		//先把小logo放在二维码中  生成新图  再把生成的放在背景图里
		//imagecopyresampled($qr,$center,80,80,0,0,36,36,imagesx($center), imagesy($center));
		imagecopyresampled($logo,$qr,238,311,0,0,638,638,imagesx($qr), imagesy($qr));
		//设置字体
		$font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/PINGFANG_MEDIUM.TTF";
		if(!is_file($font))
		{
			$font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
		}
		//设置字体颜色
		$color = imagecolorallocate($logo, 255, 255, 255);
		//把商家的名字写在二维码下面
		imagettftext($logo,60, 0, 416, 1136, $color, $font,mb_substr($merch['merchname'],0,4));
		//输出图片
		imagepng($logo,$qrcode_file);
		imagepng($qr,$code_file);
		//设置二维码的路径
		$qrcode = $_W['siteroot'] . 'addons/ewei_shopv2/data/merch/' . $_W['uniacid'] . '/' . $file;
		$qr= $_W['siteroot'] . 'addons/ewei_shopv2/data/merch/' . $_W['uniacid'] . '/' . $qr_file;
		return ['qrcode'=>$qrcode,'qr'=>$qr];
	}

	/**
	 * 商家收款小程序码
	 * @param array $member
	 * @param $mid
	 * @return array
	 */
	public function createHelpPoster($member = [],$mid)
	{
		global $_W;
		set_time_limit(0);
		@ini_set("memory_limit", "256M");
		//如果$mid是数字 就查商家信息 如果是用户信息
		if(is_numeric($mid)){
			$merch = pdo_fetch('select merchname,id from '.tablename('ewei_shop_merch_user').' where id = "'.$mid.'"');
			$file = $merch['id'];
		}else{
			$merch = pdo_fetch('select nickname as merchname,id from '.tablename('ewei_shop_member').' where openid = "'.$mid.'"');
			$file = $merch['id']."own";
		}
		//设置图片目录
		$path = IA_ROOT . "/addons/ewei_shopv2/data/merch/".$file."/";
		if( !is_dir($path) )
		{
			load()->func("file");
			mkdirs($path);
		}
		//$qrcode = md5(json_encode(array( "siteroot" => $_W["siteroot"], "mid" => $mid , 'url'=>$member['url'] , 'merchname'=>$merch['merchname'],'back'=>$member['back'],'cate'=>$member['cate'],'type'=>'qrcode')));
		$qrcode = md5(json_encode(array( "siteroot" => $_W["siteroot"], "mid" => $mid , 'merchname'=>$merch['merchname'],'back'=>$member['back'],'cate'=>$member['cate'],'type'=>'qrcode')));
		//$qr = md5(json_encode(array( "siteroot" => $_W["siteroot"], "mid" => $mid ,'url'=>$member['url'] ,'merchname'=>$merch['merchname'], 'back'=>$member['back'],'cate'=>$member['cate'])));
		$qr = md5(json_encode(array( "siteroot" => $_W["siteroot"], "mid" => $mid ,'merchname'=>$merch['merchname'], 'back'=>$member['back'],'cate'=>$member['cate'])));
		$filename = $qrcode . ".png";
		$qr_filename = $qr . ".png";
		$filepath = $path . $filename;
		$qr_filepath = $path . $qr_filename;
		if( is_file($filepath) && is_file($qr_filepath))
		{
			$qrcode_url = $_W["siteroot"] . "addons/ewei_shopv2/data/merch/".$file."/".$filename . "?v=1.0";
			$qr_url = $_W["siteroot"] . "addons/ewei_shopv2/data/merch/".$file."/".$qr_filename . "?v=1.0";
			return ['qrcode'=>$qrcode_url,'qr'=>$qr_url];
		}
		//这是背景图
		$thumb = "/addons/ewei_shopv2/static/images/".$member['back'].'.png';
		$target = $this->createImage(tomedia($thumb));
		//这是字体设置
		$font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/PINGFANG_MEDIUM.TTF";
		if( !is_file($font) )
		{
			$font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
		}
		$white = imagecolorallocate($target, 255, 255, 255);
		//把商家的名字写在二维码下面
		imagettftext($target,60, 0, 416, 1136, $white, $font,mb_substr($merch['merchname'],0,5));
		//生成小程序码
		$qrcode = p("app")->getCodeUnlimit(array( "scene" => "&mid=" . $file ."&cate=".$member['cate'],"page" => $member['url'] ));
		if( !is_error($qrcode) )
		{
			$qrcode = imagecreatefromstring($qrcode);
			imagecopyresampled($target, $qrcode, 238, 311, 0, 0, 638, 638, imagesx($qrcode), imagesy($qrcode));
		}
		imagepng($target, $filepath);
		imagepng($qrcode, $qr_filepath);
		//所有的目录 以前的$mid   改成了$file
		$qrcode_url = $_W["siteroot"] . "addons/ewei_shopv2/data/merch/".$file."/".$filename . "?v=1.0";
		$qr_url = $_W["siteroot"] . "addons/ewei_shopv2/data/merch/".$file."/".$qr_filename . "?v=1.0";
		return ['qrcode'=>$qrcode_url,'qr'=>$qr_url];
	}

	/**
	 * @param $imgurl
	 * @return false|resource|string
	 */
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

    public function HelpPoster($member = array(),$mid,$content = [])
    {
        global $_W;
        set_time_limit(0);
        @ini_set("memory_limit", "256M");
        $path = IA_ROOT . "/addons/ewei_shopv2/data/".$content['type']."/";
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
            return $_W["siteroot"] . "addons/ewei_shopv2/data/".$content['type']."/".$filename;
        }
        $target = imagecreatetruecolor(550, 978);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        //$thumb = "/addons/ewei_shopv2/static/images/1.png";
        $thumb = $this->createImage(tomedia($content['back']));
        imagecopyresized($target, $thumb, 0, 0, 0, 0, 550, 978, imagesx($thumb), imagesy($thumb));

        $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/pingfang.ttf";
        if( !is_file($font) )
        {
            $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
        }
        $black = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 22, 0, 32, 782, $black, $font, $content['title']);
        imagettftext($target, 16, 0, 32, 820, $black, $font, $content['desc']);
        //lihanwen
        $qrcode = p("app")->getCodeUnlimit(array( "scene" => "&mid=" . $mid ,"page" => $content['url'] ));

        //var_dump($qrcode);exit;
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
        $image = $this->mergeImage($avatartarget, array( "type" => "avatar", "style" => "circle" ), $avatar);
        imagecopyresized($target, $image, 32, 850, 0, 0, 70, 70, 70, 70);

        imagettftext($target, 16, 0, 110, 875 , $black, $font, $this->subtext($member["nickname"],8));
        $nameColor = imagecolorallocate($target, 102, 102, 102);
        imagettftext($target, 12, 0, 110, 900 , $nameColor, $font, $content['con']);
        imagepng($target, $filepath);
        imagedestroy($target);
        return $_W["siteroot"] . "addons/ewei_shopv2/data/".$content['type']."/".$filename . "?v=1.0";
    }

    /**
     * @param bool $image
     * @param int $zoom
     * @return resource
     */
    public function imageZoom($image = false, $zoom = 2)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $target = imagecreatetruecolor($width * $zoom, $height * $zoom);
        imagecopyresampled($target, $image, 0, 0, 0, 0, $width * $zoom, $height * $zoom, $width, $height);
        imagedestroy($image);
        return $target;
    }

    /**
     * @param bool $target
     * @param bool $circle
     * @param bool $rounded
     * @return resource
     */
    public function imageRadius($target = false, $circle = false,$rounded=false)
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

    /**
     * @param bool $target
     * @param array $data
     * @param string $imgurl
     * @param bool $local
     * @return bool
     */
    public function mergeImage($target = false, $data = array( ), $imgurl = "", $local = false)
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

    /**
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
}

if (!defined('IN_IA')) {
	exit('Access Denied');
}

?>
