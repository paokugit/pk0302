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
		$qrcode_file = $path . $file;
		if (!is_file($qrcode_file)) {
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
		}else{
			return $_W['siteroot'] . 'addons/ewei_shopv2/data/merch/' . $_W['uniacid'] . '/' . $file;
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
		imagedestroy($logo);
		//设置二维码的路径
		$qrcode = $_W['siteroot'] . 'addons/ewei_shopv2/data/merch/' . $_W['uniacid'] . '/' . $file;
		return $qrcode;
	}
}

if (!defined('IN_IA')) {
	exit('Access Denied');
}

?>
