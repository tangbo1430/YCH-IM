<?php

namespace app\api\controller;

use think\Controller;
use think\db;

/**
 * 首页接口
 */
class Index extends Controller
{


    /**
     * 首页
     *
     */
    public function index()
    {
        $this->success('请求成功');
    }
    public function getfriendlist()
    {
        $params=$this->request->param();
        //$TLSSigAPIv2 =  new \TLSSigAPIv2('1600088222','1e0d939fb55dde6d128f959481091d44903aef907e2091a58e4305b6b5b17ed4');
        //$res=$TLSSigAPIv2->genUserSig('25786');

        /* echo '<pre>';
        print_r($res);
        exit; */

        //导入腾讯im该用户
        $administrator_usersig='eJwtzc0KgkAUBeB3mXXUdXRsENpMLaIMFyYktBmYKa7-jFNa0btn6vJ858D5kHMYL5-akIDQJZDFmFHpyuINR5aqxApba6StzTxoVS6bBhUJHB8AOKeUTo3uGzR6cMYYHSqY2GL5xzXzfJdyd9YW78PB4brq-fTxUnspdrno6iJy0jCpwqzo3slRXuLTNosAPCH4hnx-w*E1Sg__';
        $user_usersig='eJwtzF0LgjAUxvHvcq5Dz*Y2h9CFUAiVEr1deSNsrkOszKSE6Ltn6eXz*8PzhsNmHzxtCwnwAGH232TstaOaRpaxVlN4mEvVNGQgYQoRteacj8X2DbV2cCklHxKO3JH-YSyFiiKm1XRDbjh2Iju1XHhzM6liuz4nFvulqLf4qs5qXYa5yxb3VRkWxRHn8PkCGP0wQA__';
        //随机8位数字
        $rondom_num=rand(10000000,99999999);
        //获取好友列表
        $tencent_url='https://console.tim.qq.com/v4/sns/friend_get?sdkappid=1600088222&identifier=administrator&usersig='.$administrator_usersig.'&random='.$rondom_num.'&contenttype=json';

        //MsgTime 需要下载的消息记录的时间段，2015120121表示获取2015年12月1日21:00 - 21:59的消息的下载地址。该字段需精确到小时。每次请求只能获取某天某小时的所有单发或群组消息记录
        //获取近7天每一个小时的MsgTime数组 格式：2025080812
      
        $friendall=[];
       


        $params=[
            'From_Account'=>$params['uid'],
            'StartIndex'=>0,
        ];
        //向腾讯im发送请求
        $res=$this->http_post_data($tencent_url,json_encode($params));
        $res=json_decode($res,true);
        if($res['ActionStatus']=='OK' && isset($res['UserDataItem'])){
            $friendlist=$res['UserDataItem'];
             foreach($friendlist as $key=>$val){
                //获取用户昵称
                $info=db('user')->where('uid',$val['To_Account'])->find();
                if($info){
                    $nickname=$info['nick_name'];
                }else{
                    $nickname='';
                }

                $temp=[
                    'uid'=>$val['To_Account'],            
                    'nickName'=>$nickname,
                ];
                $friendall[]=$temp;
            }
            if($res['CompleteFlag']==0){
                //递归获取好友列表
                $friendall=$this->getfriendlistall($params['userid'],$tencent_url,$res['NextStartIndex'],$friendall);
            }
           
        }
        if(!empty($friendall)){
           foreach($friendall as $key=>$val){
            echo 'id:'.$val['uid'].'   昵称:'.$val['nickName'].'<br>';
           }
        }
    }
    //递归获取好友列表并返回好友列表数组
    public function getfriendlistall($userid,$tencent_url,$startindex,$friendall){
        
        $params=[
            'From_Account'=>$userid,
            'StartIndex'=>$startindex,
        ];
     
        //向腾讯im发送请求
        $res=$this->http_post_data($tencent_url,json_encode($params));
        $res=json_decode($res,true);
        if($res['ActionStatus']=='OK' && isset($res['UserDataItem'])){
            $friendlist=$res['UserDataItem'];
            foreach($friendlist as $key=>$val){
                //获取用户昵称
                $info=db('user')->where('uid',$val['To_Account'])->find();
                if($info){
                    $nickname=$info['nick_name'];
                }else{
                    $nickname='';
                }

                $temp=[
                    'uid'=>$val['To_Account'],            
                    'nickName'=>$nickname,
                ];
                $friendall[]=$temp;
            }
            if($res['CompleteFlag']==0){
                //递归获取好友列表
                $friendall=$this->getfriendlistall($params['userid'],$tencent_url,$res['NextStartIndex'],$friendall);
            }
            
            return $friendall;
        }
    }

    
    //向腾讯im发送请求
    function http_post_data($url, $data_string)
    {
          $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string)
            )
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $return_content;
    }
    //获取聊天记录下载地址
    public function get_chat_log_list()
    {
        $chat_type = input('chat_type', 'C2C');
        // $chat_type = 'C2C';
        // $msg_time = date('YmdH');
        $now_time = time();
        
        // $res = model('api/Tencent')->get_history($chat_type, $msg_time);
        // dump($res);die;
        // $data = [];
        for($i=1; $i<= 168; $i++) {
            $msg_time = $now_time - ($i * 3600);
            $msg_times = date('YmdH', $msg_time);
            // dump($msg_times);
            $res = model('api/Tencent')->get_history($chat_type, $msg_times);
            if($res['code'] == 200) {
                echo $msg_times;
                echo "<br>";
                echo $res['data'];
                echo "<br>----------";
            }
            
            // $now_time = $msg_time;
        }
    }
}
