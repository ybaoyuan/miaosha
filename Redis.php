<?php

namespace app\miaosha\controller;


class Redis
{
    public $redis;

    //构造函数，连接Redis
    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379); //连接Redis
        $this->redis->select(2);//选择数据库2
    }

    //秒杀入口
    public function index()
    {

        //echo $this->redis->get("shop_d");
        $this->shop();
        //$this->user();
    }

    //商品秒杀 1个订单对应一个库存 订单数小于库存 则可以抢购
    public function shop()
    {
        if($this->redis->get('shop_d')<1){
            sleep(10);
            //下单成功过 订单数+1
            $this->redis->incr('shop_d');
            echo '抢购成功';
            //$this->del();
        }else{
            echo '抢购失败';
        }
    }

    //加锁限制访问
    public function user()
    {
        if($this->lock()){
            $this->shop();
        }else{
            echo '服务器异常';
        }
    }

    //加锁逻辑
    public function lock($key='suo',$time=30,$num=3)
    {
        //如果redis存在key，设置会失败  存的值是当时时间戳，过期时间
        $is_lock=$this->redis->setnx($key,time()+$time);
        //setnx添加redis数据的时候如果redis存在该字段，返回空值
        if(!$is_lock){
            for ($i = 0;$i<$num;$i++){
                $is_lock=$this->redis->setnx($key,time()+$time);
                if($is_lock){
                    break;
                }else{
                    sleep(1);
                }
            }
        }
        if(!$is_lock){
            //判断已经存在的锁是否过期
            $lock_time=$this->redis->get($key);//拿到锁的过期时间
            if(time()>$lock_time){
                $this->redis->del($key); //删除老锁
                $is_lock=$this->redis->setnx($key,time()+$time);
            }
        }
        return $is_lock?true:false;
    }

    //解锁逻辑
    public function del($key='suo')
    {
        $this->redis->del($key);
    }










}
