<?php
namespace app\controllers;
use Yii;
use yii\web\Controller;
use app\commands\Helper;
class DtController extends Controller{
            
    
    public function actionIndex(){
      
        $xml_str = file_get_contents('php://input');
        file_put_contents('./dt/xml.xml', $xml_str);
//        $xml_str = file_get_contents('./dt/xml.xml');
        $xml = simplexml_load_string($xml_str);
        switch($xml->MsgType){
            case "event": $this->event($xml);break;
            case "text": $this->text($xml);break;
        }
            
    }
 
    public function event($xml){
        
        
        switch($xml->Event){
            
            case "CLICK":
              
                switch($xml->EventKey){
                
                
                //查询
                   case "w":
                       $sql = "select * from user where openid = '$xml->FromUserName'";
                       $res = yii::$app->db->createCommand($sql)->queryOne();
                     
                       echo $this->fun($xml,'w',$res);
                //开始答题
                    case "d":
                          
                        //查询题目数据表
                        $sql = "select id from st";
                        $res = yii::$app->db->createCommand($sql)->queryAll();
 
                        $arr = [];
                        foreach($res as $key => $val){
                            $arr[] = $val['id'];
                        }
                        //随机取一条数据,返回给用户
                        $flip = array_flip($arr);
                        $un = array_rand($flip,1);
                        //将当前题目id存入答题数据表中
                        $sql1 = "insert into dt(`openid`,`s_id`) values('$xml->FromUserName',$un)";
                        $data = yii::$app->db->createCommand($sql1)->execute();
               
                        //根据id进行查询
                        $sql = "select * from st where id=$un";
                        $res = yii::$app->db->createCommand($sql)->queryOne();
                        
                        echo $this->fun($xml,'text',$res);
                
                }
            
        }
        
    }
    public function text($xml){
       //查询出答题的id
        $sql = "select s_id from dt order by id desc limit 1";
        $res = yii::$app->db->createCommand($sql)->queryOne();
         $id =  $res['s_id'];
        //查询题的答案
        $sql = "select `ok` from st where id =$id";
        $res = yii::$app->db->createCommand($sql)->queryOne();
        
        if($res['ok'] == strtolower($xml->Content)){
            
            $sql = "select openid from `user` where `openid`='$xml->FromUserName'";
            $res = yii::$app->db->createCommand($sql)->queryOne();
          
            if(!empty($res)){
               
                $sql = "update user set ok_num=ok_num+1 where openid='$xml->FromUserName'";
                $res = yii::$app->db->createCommand($sql)->execute();
                echo $this->fun($xml,'t','回答正确');
            }else{
               
                $sql = "insert into  user(`openid`,`ok_num`) values('$xml->FromUserName',1)";
                $res = yii::$app->db->createCommand($sql)->execute();
                echo $this->fun($xml,'t','回答正确');
            }
           
           
        }else{
                   
            $sql = "select openid from user where openid='$xml->FromUserName'";
            $res = yii::$app->db->createCommand($sql)->queryOne();
            
            if(!empty($res)){
            
                $sql = "update user set no_num=no_num+1 where openid='$xml->FromUserName'";
                $res = yii::$app->db->createCommand($sql)->execute();
               
                 echo $this->fun($xml,'t','回答错误');
                
            }else{
              
                $sql = "insert into user(`openid`,`no_num`) values('$xml->FromUserName',1)";
                $res = yii::$app->db->createCommand($sql)->execute();
                 echo $this->fun($xml,'t','回答错误');
            }
            
            
        }
        
        
    }
    public function fun($xml,$type,$res){
        $str = "<xml><ToUserName><![CDATA[$xml->FromUserName]]></ToUserName><FromUserName><![CDATA[$xml->ToUserName]]></FromUserName><CreateTime>time()</CreateTime>";
        switch($type){
            case "t": $str .="<MsgType><![CDATA[text]]></MsgType><Content><![CDATA[$res]]></Content></xml>";break;
            case "text": $str .="<MsgType><![CDATA[$type]]></MsgType><Content><![CDATA[{$res['t_name']}\n\nA:{$res['a']}\nB:{$res['b']}]]></Content></xml>";break;
            case "w": $str .="<MsgType><![CDATA[text]]></MsgType><Content><![CDATA[你答对了{$res['ok_num']}题,答错{$res['no_num']}题]]></Content></xml>";break;
        }
        return $str;
    }
//    public function actionCd(){
//         $button = [
//            "button"=>[
//                ["name"=>"答题","type"=>"click","key"=>"d"],
//                ["name"=>"我的成绩","type"=>"click","key"=>"w"]   
//            ]
//        ];
//        $data = json_encode($button, JSON_UNESCAPED_UNICODE);
//        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".Helper::getToken(); 
//        Helper::getCurl($url,'post',$data);
//    }
}
    
