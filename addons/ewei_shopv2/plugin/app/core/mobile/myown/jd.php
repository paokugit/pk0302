<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Jd_EweiShopV2Page extends AppMobilePage{
    //商品池编码
    public function index(){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getPageNum";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        $res=$res["result"];
        foreach ($res as $k=>$v){
            $d["name"]=$v["name"];
            $d["page_num"]=$v["page_num"];
            pdo_insert("ewei_shop_jdcate",$d);
            var_dump(pdo_insertid());
        }
    }
    //商品池商品id
    public function goodsid(){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getSkuByPage";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_jdcate")." order by id asc");
//         var_dump(unserialize($list[0]["skuIds"]));
//         $data["pageNum"]=112;
//         $res=$this->posturl($url, $data);
//         var_dump($res["result"]["skuIds"]);die;
        foreach ($list as $k=>$v){
            $data["pageNum"]=$v["page_num"];
            $res=$this->posturl($url, $data);
            $d["skuIds"]=serialize($res["result"]["skuIds"]);
            pdo_update("ewei_shop_jdcate",$d,array("id"=>$v["id"]));
           
            var_dump($res["result"]["skuIds"]);
        }
       
    }
    //商品--详情
    public function getdetail(){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getDetail";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["queryExts"]="appintroduce,shouhou";
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_jdcate")." where id>=15  order by id asc");
        foreach ($list as $k=>$v){
            $skuId=unserialize($v["skuIds"]);
            foreach ($skuId as $kk=>$vv){
            $data["sku"]=$vv;
            $res=$this->posturl($url, $data);
            $d=$res["result"];
            pdo_insert("ewei_shop_jdgoods",$d);
            var_dump(pdo_insertid());
            }
            
        }
       
    }
    
    //商品价格
    public function price(){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getSellPrice";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["sku"]="6265408,6265409,6265410";
        $data["queryExts"]="containsTax,marketPrice";
        var_dump($this->posturl($url, $data));
        
    }
    function posturl($url,$data){
        $data  = json_encode($data);
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);
    }
    
 
}