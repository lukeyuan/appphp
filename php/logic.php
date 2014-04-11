<?php

class Logic
{
    public $sql;
    function __construct()
    {
        # code...
        $this->sql = new DB();
        $this->sql->connect();
        // $this->sql->set_names('utf8');
        $this->sm = new State_Machine();
        $this->build();
    }

    public function build() {
        $this->sm->add_edge('null', '1', '//');

        $this->sm->add_edge('1', '11', '/1/');
        $this->sm->add_edge('11', '1', '/(00)|3|(返回)/');

        $this->sm->add_edge('1', '12', '/2/');
        $this->sm->add_edge('12', '1', '/(00)|3|(返回)/');

        $this->sm->add_edge('1', '13', '/3/');
        $this->sm->add_edge('13', '1', '/(00)|3|(返回)/');

        $this->sm->add_edge('11', '111', '/1/');
        $this->sm->add_edge('11', '112', '/2/');

        $this->sm->add_edge('12', '121', '/1/');
        $this->sm->add_edge('12', '122', '/2/');

        $this->sm->add_edge('13', '131', '/1/');
        $this->sm->add_edge('13', '132', '/2/');

        $this->sm->add_edge('111', '1111', '//');
        $this->sm->add_edge('112', '1121', '/^1[0-9]{10}$/');

        $this->sm->add_edge('121', '1211', '//');
        $this->sm->add_edge('122', '1221', '/^[0-9]{11}$/');

        $this->sm->add_edge('131', '1311', '/^1[0-9]{10}$/');
        $this->sm->add_edge('132', '1321', '/^[0-9]{11}$/');

        $this->sm->add_edge('1111', '1', '//');
        $this->sm->add_edge('1121', '1', '//');

        $this->sm->add_edge('1211', '1', '//');
        $this->sm->add_edge('1221', '1', '//');

        $this->sm->add_edge('1311', '1', '//');
        $this->sm->add_edge('1321', '1', '//');
    }

    public function mf($name, $str) {
        $handle = fopen($name, 'w');
        fwrite($handle, $str);
        fclose($handle);
    }

    public function all($para)
    {
        $fromUsername = $para['FromUserName'];
        $msgType = $para['MsgType'];
        $select = '';
        $state = '';

        if($msgType == 'text')      //用户发过来的是文本
        {        
            $select = trim($para['Content']);
            $state = $this->sm->get_state($fromUsername);
            $to_state = $this->sm->go_with($fromUsername, $select);
            if($to_state == '00') {
                $select = $this->sm->get_select($fromUsername);
            }
            else {
                if($to_state == '1') {
                    $this->sm->reset_state($fromUsername);
                    $this->sm->go_with($fromUsername, $select);
                }
                if(strlen($state) - strlen($to_state) == 1) {
                    $this->sm->rollback($fromUsername);
                }
                else {
                    $this->sm->save_state($fromUsername, $to_state, $select);
                }
                $state = $to_state;                
            }
        }
        else {
            $state = '99';
        }
        $result = $this->result_arr($state, $select, $para);
        $apptext = array('AppText' => $result);
        $result = array_merge($result, $apptext);
        return $result;
    }//end response function

    public function result_arr($state, $select, $para) {
        $p = array();
        array_push($p, 'http://ww1.sinaimg.cn/mw690/8289643dgw1edhrl4i7z4j20a005kt91.jpg');
        array_push($p, 'http://ww4.sinaimg.cn/mw690/8289643dgw1edhrl0u6e1j20a005kdg3.jpg');
        array_push($p, 'http://ww1.sinaimg.cn/mw690/8289643dgw1edhrks6ktrj20a005kmxq.jpg');
        array_push($p, 'http://ww4.sinaimg.cn/mw690/8289643dgw1edhrkqnih4j20a005k3yy.jpg');
        array_push($p, 'http://ww2.sinaimg.cn/mw690/8289643dgw1edhrkpcphnj20a005kglx.jpg');
        array_push($p, 'http://ww1.sinaimg.cn/mw690/8289643dgw1eedkx6yvp5j20a005kta1.jpg');
        $r1 = rand(0, 5);
        $q = array();
        array_push($q, 'http://ww1.sinaimg.cn/mw690/8289643dgw1eedj7qlvwej205k05k0su.jpg');
        array_push($q, 'http://ww1.sinaimg.cn/mw690/8289643dgw1eedj7p6r1ij205k05kdfx.jpg');         
        array_push($q, 'http://ww2.sinaimg.cn/mw690/8289643dgw1eedj7o3k5wj205k05kjs3.jpg');         
        array_push($q, 'http://ww1.sinaimg.cn/mw690/8289643dgw1eedj7mv0qcj205k05k0t6.jpg');         
        array_push($q, 'http://ww4.sinaimg.cn/mw690/8289643dgw1eedj7lgjbdj205k05kdg7.jpg');         
        array_push($q, 'http://ww4.sinaimg.cn/mw690/8289643dgw1eedj7kcgx2j205k05kdg8.jpg');
        $r2 = rand(0, 5);

        if($state == '1') {
            return $this->get_result_para(4, array(
                array('信息查询', '', $p[$r1], ''),
                array('【1】查学号', '', $q[$r2], ''),
                array('【2】查手机', '', $q[$r2], ''),
                array('【3】查姓名', '', $q[$r2], '')
            ));
        }
        else if($state == '11') {
            return $this->get_result_para(4, array(
                array('查学号', '', $p[$r1], ''),
                array('【1】根据姓名查学号', '', $q[$r2], ''),
                array('【2】根据手机查学号', '', $q[$r2], ''),
                array('【3】返回上一层', '', $q[$r2], '')
            ));
        }
        else if($state == '12') {
            return $this->get_result_para(4, array(
                array('查手机', '', $p[$r1], ''),
                array('【1】根据姓名查手机', '', $q[$r2], ''),
                array('【2】根据学号查手机', '', $q[$r2], ''),
                array('【3】返回上一层', '', $q[$r2], '')
            ));
        }
        else if($state == '13') {
            return $this->get_result_para(4, array(
                array('查姓名', '', $p[$r1], ''),
                array('【1】根据手机查姓名', '', $q[$r2], ''),
                array('【2】根据学号查姓名', '', $q[$r2], ''),
                array('【3】返回上一层', '', $q[$r2], '')
            ));
        }
        else if($state == '111') {
            return $this->get_result_para(2, array(
                array('查学号', '', $p[$r1], ''),
                array('请输入姓名', '', $q[$r2], '')
            ));
        }
        elseif ($state == '1111') {
            $message = $this->get_content('number', 'name', $select);
            return $this->get_result_para(2, array(
                array('学号是：', '', $p[$r1], ''),
                array($message, '', $q[$r2], '')
            ));            
        }
        elseif($state == '112') {
            return $this->get_result_para(2, array(
                array('查学号', '', $p[$r1], ''),
                array('请输入手机', '', $q[$r2], '')
            ));
        }
        elseif ($state == '1121') {
            $message = $this->get_content('number', 'phone', $select);
            return $this->get_result_para(2, array(
                array('学号是：', '', $p[$r1], ''),
                array($message, '', $q[$r2], '')
            ));            
        }
        elseif($state == '121') {
            return $this->get_result_para(2, array(
                array('查手机', '', $p[$r1], ''),
                array('请输入姓名', '', $q[$r2], '')
            ));
        }
        elseif ($state == '1211') {
            $message = $this->get_content('phone', 'name', $select);
            return $this->get_result_para(2, array(
                array('手机是：', '', $p[$r1], ''),
                array($message, '', $q[$r2], '')
            ));            
        }
        elseif($state == '122') {
            return $this->get_result_para(2, array(
                array('查手机', '', $p[$r1], ''),
                array('请输入学号', '', $q[$r2], '')
            ));
        }
        elseif ($state == '1221') {
            $message = $this->get_content('phone', 'number', $select);
            return $this->get_result_para(2, array(
                array('手机是：', '', $p[$r1], ''),
                array($message, '', $q[$r2], '')
            ));            
        }
        elseif($state == '131') {
            return $this->get_result_para(2, array(
                array('查姓名', '', $p[$r1], ''),
                array('请输入手机', '', $q[$r2], '')
            ));
        }
        elseif ($state == '1311') {
            $message = $this->get_content('name', 'phone', $select);
            return $this->get_result_para(2, array(
                array('姓名是：', '', $p[$r1], ''),
                array($message, '', $q[$r2], '')
            ));            
        }
        elseif($state == '132') {
            return $this->get_result_para(2, array(
                array('查姓名', '', $p[$r1], ''),
                array('请输入学号', '', $q[$r2], '')
            ));
        }
        elseif ($state == '1321') {
            $message = $this->get_content('name', 'number', $select);
            return $this->get_result_para(2, array(
                array('姓名是：', '', $p[$r1], ''),
                array($message, '', $q[$r2], '')
            ));            
        }
        elseif ($state == '99') {
            return $this->get_result_para(1, array(
                array('小超知天下：', '欢迎关注小超知天下，或许我能为你提供你所需要的信息哦。。#^_^。。回复任意文字开始吧。。', $p[$r1], '')
            ));                        
        }

    }

    public function get_result_para($count, $arr = array()) {
        $result = array();
        $result['MsgType'] = 'news';
        $result['ArticleCount'] = $count;
        for ($i = 1; $i <= $count; $i++) { 
            $result["Title$i"] = $arr[$i-1][0];
            $result["Description$i"] = $arr[$i-1][1];
            $result["PicUrl$i"] = $arr[$i-1][2];
            $result["Url$i"] = $arr[$i-1][3];
        }
        return $result;
    }

    public function get_content($target, $accord, $val) {
        $data = $this->sql->select('messages', "$accord == $val", array($target));
        foreach ($data as $key => $value) {
            return $value->$target;
        }
        return '520';
    }

}//end class

?>