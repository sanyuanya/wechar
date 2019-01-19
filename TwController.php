<?php
namespace app\controllers;
use Yii;
use yii\web\Controller;
use app\commands\Helper;
class TwController extends Controller{
    
    public function actionIndex(){
       
//        
        $xml_str = file_get_contents('php://input');
        file_put_contents('./tw/xml.xml', $xml_str);
//        $xml_str = file_get_contents('./tw/xml.xml');
        $xml = simplexml_load_string($xml_str);
        switch($xml->MsgType){
            case "event":$this->event($xml);break;
        }
        
        
        
    }
    
    public function event($xml){
     
        switch($xml->Event){
            
            case "CLICK":
                
                switch($xml->EventKey){
                    
                    case "z":
                       $sql = "select * from tw";
                       $res = yii::$app->db->createCommand($sql)->queryAll();
                      
                       echo $this->fun($xml,'news',$res);
                        
                }
        }
    }
    public function fun($xml,$type,$res){
   
        $str = "<xml><ToUserName><![CDATA[$xml->FromUserName]]></ToUserName><FromUserName><![CDATA[$xml->ToUserName]]></FromUserName><CreateTime>".time()."</CreateTime>";
        switch ($type){
            
               case "news":    
                   
                $str .= "<MsgType><![CDATA[".$type."]]></MsgType><ArticleCount>".count($res)."</ArticleCount><Articles>";
                foreach($res as  $val){
                     $str .= "<item><Title><![CDATA[".$val['title']."]]></Title><Description><![CDATA[".$val['area']."]]></Description><PicUrl><![CDATA[".$val['link']."]]></PicUrl><Url><![CDATA[".$val['url']."]]></Url></item>";
                }
                 $str .= "</Articles></xml>";break;
                
        }
        return $str;
    }
    
    
    
    
    
    
    
    public function actionAdd(){
        if(yii::$app->request->isPost){
           
            $data = yii::$app->request->post();
            $sql = "insert tw(`title`,`area`,`link`,`url`) values('".$data['title']."','".$data['area']."','".$data['link']."','".$data['url']."')";
            $res = yii::$app->db->createCommand($sql)->execute();    
        }else{
              return $this->render("index");
        }
    }
    
    public function actionCd(){
        
        $button = [
            "button"=>[
                ["name"=>"最新爆料","type"=>"click","key"=>"z"]
            ]
        ];
        $res = json_encode($button,JSON_UNESCAPED_UNICODE);
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".Helper::getToken();
        echo  Helper::getCurl($url,'post',$res);
    }
}
     