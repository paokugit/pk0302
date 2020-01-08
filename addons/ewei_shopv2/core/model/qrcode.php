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
	public function createImage($imgurl)
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

    /**
     * @param string $openid
     * @param string $backgroup
     * @return string
     */
    public function createposter($openid="",$backgroup="")
    {
        global $_W;
        set_time_limit(0);
        $goods = array( );
        $member = m('member')->getMember($openid);
        @ini_set("memory_limit", "256M");
        $path = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/" ;
        if( !is_dir($path) )
        {
            load()->func("file");
            mkdirs($path);
        }
        $md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"], "openid" => $member["openid"], "goodstitle" => $goods["title"], "goodprice" => $goods["minprice"], "version" => 1 )));
        $filename = $member['openid']."_".time() . ".png";
        $filepath = $path . $filename;

        $target = imagecreatetruecolor(750, 1334);
        //imagecolorallocate ( resource $image , int $red , int $green , int $blue )  为一幅图像分配颜色
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);

        //填充背景图
        $thumb = $this->createImage(tomedia($backgroup));
        //imagecopyresized — 拷贝部分图像并调整大小  dst_image  目标图象连接资源。 src_image源图象连接资源。 dst_x x-coordinate of destination point.dst_y y-coordinate of destination point. src_x x-coordinate of source point. src_y y-coordinate of source point. dst_w Destination width. dst_h Destination height.  src_w源图象的宽度。src_h 源图象的高度。
        imagecopyresized($target, $thumb, 0, 0, 0, 0, 750, 1200, imagesx($thumb), imagesy($thumb));

        //添加底部字体
        //imagecolorallocate ( resource $image , int $red , int $green , int $blue ) 为一幅图像分配颜色
        $nameColor = imagecolorallocate($target, 102, 102, 102);
        //imagettftext ( resource $image , float $size , float $angle , int $x , int $y , int $color , string $fontfile , string $text )
        $PINGFANG_LIGHT = IA_ROOT . "/addons/ewei_shopv2/static/fonts/PINGFANG_LIGHT.ttf";
        if( !is_file($PINGFANG_LIGHT) )
        {
            $PINGFANG_LIGHT = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
        }
        $footer="长按识别小程序码进入跑库，开启健康小收入";
        imagettftext($target, 18, 0, 144, 1274, $nameColor, $PINGFANG_LIGHT, $footer);

        //添加底部背景
        $footer_backgroup = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/floor.png";
        $thumb = $this->createImage(tomedia($footer_backgroup));
        imagecopyresized($target, $thumb, 34, 926, 0, 0, 680, 260, imagesx($thumb), imagesy($thumb));

        //头像
        //imageistruecolor() 检查 image 图像是否为真彩色图像。
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
        $name = "我叫".$member["nickname"];
        imagettftext($target, 20, 0, 144, 966, $nameColor, $PINGFANG_BOLD, $name);


        //卡路里
        //获取今日已兑换的卡路里
        $starttime = strtotime(date("Y-m-d 23:59:59",strtotime('-1 day')));
        $endtime = strtotime(date("Y-m-d 00:00:00",strtotime('+1 day')));
        $count_list = pdo_fetchall("select num from ".tablename("mc_credits_record")." where (openid=:openid or user_id = :user_id) and credittype=:credittype and createtime>=:starttime and createtime<=:endtime and num>0 and (remark_type=1 or remark_type=4) order by id desc",array(':openid'=>$member['openid'],':user_id'=>$member['id'],':credittype'=>"credit3",":starttime"=>$starttime,':endtime'=>$endtime));

        $count = array_sum(array_column($count_list, 'num'));
        if (empty($count)){
            $count=0;
        }
        $count = round($count,1);
        $name = "今日步数已兑换".$count."折扣宝=";
        $nameColor = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 18, 0, 144, 998, $nameColor, $PINGFANG_LIGHT, $name);
        $name = $count."元";
        $nameColor = imagecolorallocate($target, 176, 6, 16);
        imagettftext($target, 18, 0, 446, 998, $nameColor, $PINGFANG_BOLD, $name);
        //获取剩余卡路里未兑换
        $exchange = m("member")->exchange_step($member['openid']);
        $surplus = $exchange - $count;
        if ($surplus<0){
            $surplus = 0;
        }
        $name = "剩余".$surplus."元"."未兑换";
        $nameColor = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 18, 0, 144, 1028, $nameColor, $PINGFANG_LIGHT, $name);
        //签到天数
        $sign = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_member_getstep")." where (openid = :openid or user_id = :user_id) and type=2",array(':openid'=>$member['openid'],':user_id'=>$member['id']));
        $name = "签到（天）";
        $nameColor = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 20, 0, 80, 1090, $nameColor, $PINGFANG_LIGHT, $name);
        $nameColor = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 18, 0, 108, 1128, $nameColor, $PINGFANG_BOLD, $sign);


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
        return "addons/ewei_shopv2/data/poster_wxapp/sport"."/" . $filename;
    }

    /**
     * @param $goods
     * @return float|int
     */
    public function goodsminprice($goods){
        if($goods['deduct']>=$goods['minprice']) return 0;
        if($goods['deduct']>0){
            return floatval($goods['minprice']-$goods['deduct']);
        }
        return $goods['minprice'];
    }

    /**
     * 金主商品海报
     * @param array $goods
     * @param array $member
     * @return string
     */
    public function createDevote($goods = array( ), $member = array( ))
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

    /**
     * @param bool $target
     * @param array $data
     * @param string $imgurl
     * @param bool $local
     * @return bool
     */
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

    /**
     * @param $filename
     * @return string
     */
    private function getImgUrl($filename)
    {
        global $_W;
        return $_W["siteroot"] . "addons/ewei_shopv2/data/poster_wxapp/goods/" . $_W["uniacid"] . "/" . $filename . "?v=1.0";
    }

    /**
     * @param array $goods
     * @param array $member
     * @return string
     */
    public function createPosternew($goods = array( ), $member = array( ))
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

    /**
     * @param $text
     * @return string
     */
    private function memberName($text)
    {
        $textLen = mb_strlen($text, "UTF8");
        if( 5 <= $textLen )
        {
            $text = mb_substr($text, 0, 5, "utf-8") . "...";
        }
        return $text;
    }

    /**
     * @param $text
     * @param int $fontsize
     * @param string $font
     * @param int $width
     * @return array
     */
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
}

if (!defined('IN_IA')) {
	exit('Access Denied');
}

?>
