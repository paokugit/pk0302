<?php
if (!(defined('IN_IA'))) {
    exit('Access Denied');
}


require EWEI_SHOPV2_PLUGIN . 'merchmanage/core/inc/page_merchmanage.php';

class Homepage_EweiShopV2Page extends MerchmanageMobilePage
{
    public function img(){
        global $_W;
        global $_GPC;
        $merchid = $_W['merchmanage']['merchid'];
        if ($_POST){
            $images=$_POST["images"];
            $images=serialize($images);
            pdo_update("ewei_shop_merch_user",array("shopimg"=>$images),array("id"=>$merchid));
            header('location: ' . mobileUrl('merchmanage/home/homepage/img'));
            exit();
        }
        $merch=pdo_get("ewei_shop_merch_user",array("id"=>$merchid));
        $piclist=unserialize($merch["shopimg"]);
        include $this->template();
    }
    //上传视频
    public function video(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;

        $merchid = $_W['merchmanage']['merchid'];
         if ($_POST){

             $data["shopvideo"]=$_GPC["video"];
              $data["img"]=$_GPC["img"];
              if (pdo_update("ewei_shop_merch_user",array("shopvideo"=>$data["shopvideo"],"shopvideo_img"=>$data["img"]),array("id"=>$merchid))){
                  $res["status"]=0;
                  $res["message"]="成功";
                  echo json_encode($res);
              }else{
                  $res["status"]=1;
                  $res["message"]="失败";
                  echo json_encode($res);
              }

         }
        include $this->template();

    }
    
    //上传视频
    public function  upload_video(){
        header('Access-Control-Allow-Origin:*');
        $field = $_FILES["file"];
        $resault=$this->upload_file($field,"./attachment",1);
        //成功
        if ($resault["status"]==0){
            //获取封面图
            
            //视频绝对路径
            $lujing=ATTACHMENT_ROOT.$resault["message"];
            $t=time().rand(10,99);
            $pt_lj="videos/img/".$t.".jpg";
            //保存图片绝对路径
            $pt=ATTACHMENT_ROOT.$pt_lj;
            $root=ATTACHMENT_ROOT."videos/ffmpeg.exe";
            //视频截图
            $cmd="$root -i $lujing -f image2 -ss 0.01 -s 400*300 -vframes 1 $pt";
            file_put_contents('./1.txt', $cmd);
            exec($cmd);
            file_put_contents('./1.txt', exec($cmd));
//             if (is_file($pt)) {
                $resault["img"]=$pt_lj;
//             }
                $resault["addr"]=tomedia($resault["message"]);
        }
        echo json_encode($resault);
        
    }
    
    //上传图片
    public function upload_img(){
        header('Access-Control-Allow-Origin:*');
        $field = $_FILES["file"];
        $resault=$this->upload_file($field,"./attachment",2);
        if ($resault["status"]==0){
            $resault["addr"]=tomedia($resault["message"]);
        }
        echo json_encode($resault);
    }
    
    //商家主页图片
    public function imgapi(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $merchid = $_W['merchmanage']['merchid'];
        $img=$_GPC["img"];
        if (empty($img)){
            $re["status"]=1;
            $re["message"]="图片不可为空";
            echo json_encode($re);
        }
       $img=explode(",", $img);
       $img=serialize($img);
       if (pdo_update("ewei_shop_merch_user",array("shopimg"=>$img),array("id"=>$merchid))){
           $re["status"]=0;
           $re["message"]="成功";
       }else{
           $re["status"]=1;
           $re["message"]="失败";
       }
       echo json_encode($re);
    }
    
    //获取商家主页图片
    public function hqimg(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $merchid = $_W['merchmanage']['merchid'];
        $merch=pdo_get("ewei_shop_merch_user",array("id"=>$merchid));
        if (empty($merch)){
            $re["status"]=1;
            $re["message"]="不存在该商家";
        }else{
        $piclist=unserialize($merch["shopimg"]);
           $re["status"]=0;
           $re["message"]=$piclist;
           $re["video"]=$merch["shopvideo"];
           foreach ($piclist as $k=>$v){
               $re["imgaddr"][$k]=tomedia($v);
           }
        }
	$re['videoaddr'] = tomedia($re['video']);
        echo json_encode($re);
    }
    //截取视屏封面图
    public function cs(){
        //视频绝对路径
        $lujing="E:/work/pk0302/attachment/videos/e240c050a92a2d1b768a65763a5baba4.mp4";
        $t=time().rand(10,99);
        $pt_lj="videos/img/".$t.".jpg";
        //保存图片绝对路径
        $pt="E:/work/pk0302/attachment/".$pt_lj;
        //视频截图
        $cmd="E:/ffmpeg.exe -i $lujing -f image2 -ss 0.01 -s 400*300 -vframes 1 $pt";
        file_put_contents('./1.txt', $cmd);
        exec($cmd);
        
        if (is_file($pt)) {
            var_dump($pt_lj);
        }

    }
    //1表示视频 2表示图片
    function upload_file($files, $path = "./attachment",$type=1)
    
    {
       
        if($type==1){
            $imagesExt=['rm', 'rmvb', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4','mov','3gp'];
            $path = "videos/";
        }else{
            $imagesExt=['jpg','jpeg','gif','png'];
            $path = "videos/img/";
         }
        //mkdirs(ATTACHMENT_ROOT . '/' . $path);
        // 判断错误号
        if (@$files['error'] == 00) {
            // 判断文件类型
            $ext = strtolower(pathinfo(@$files['name'],PATHINFO_EXTENSION));
            if (!in_array($ext,$imagesExt)){
                $resault["status"]=1;
                $resault["message"]="非法文件类型";
                return $resault;
            }
            // 判断是否存在上传到的目录
            if (!is_dir(ATTACHMENT_ROOT . '/' .$path)){
                mkdir($path,0777,true);
            }
            // 生成唯一的文件名
            $fileName = md5(uniqid(microtime(true),true)).'.'.$ext;
            // 将文件名拼接到指定的目录下
            $destName =ATTACHMENT_ROOT.'/'.$path.$fileName;
            // 进行文件移动
            if (!move_uploaded_file($files['tmp_name'],$destName)){
                $resault["status"]=1;
                $resault["message"]="文件上传失败！";
                return $resault;
            }
            $resault["status"]=0;
            $resault["message"]=$path.$fileName;
            return $resault;
        } else {
            // 根据错误号返回提示信息
            switch (@$files['error']) {
                case 1:
                    echo "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值";
                    break;
                case 2:
                    echo "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值";
                    break;
                case 3:
                    echo "文件只有部分被上传";
                    break;
                case 4:
                    echo "没有文件被上传";
                    break;
                case 6:

                case 7:
                    echo "系统错误";
                    break;
            }
        }
    }

    public function homeset()
    {
        include $this->template();
    }

    public function storeimage()
    {
        include $this->template();
    }

    public function storevideo()
    {
        include $this->template();
    }
}