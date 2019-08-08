<?php 

namespace Qs\api;

use Qs\redis\QsRedis;
use Qs\log\Log;

class Token {
    // 日记实例
    protected $log;
    // redis
    protected $redis;

    public function __construct(){
        // TODO: 初始化
        $this->log = Log::instance();
        $this->redis = new QsRedis(config('redis.'));
    }


    /**
     * @Author : Qs
     * @Name   : 生成token
     * @Note   : 
     * @Time   : 2018/9/18 0:55
     * @param   integer     $userId     用户ID
     * @param   string      $oldToken   用户表上的token
     * @param   integer     $time       生成token附加值
     * @return  string                  新的token
     **/
    protected function create_token( $userId, $time = 3600 ){
        $token = md5($userId . rand(5, 32) . (time() + $time) );
        if ( $this->is_repeat($token) ) $token = $this->create_token($userId, $time + 1);
        return $token;
    }

    /**
     * @Author : Qs
     * @Name   : 生成scan_token
     * @Note   : 
     * @Time   : 2018/9/13 0:58
     * @param   integer     $userId     用户ID
     * @param   string      $token      用户表上的token
     * @param   integer     $time       生成token附加值
     * @return  string                  新的scan_token
     **/
    protected function create_scan ( $userId, $time = 604800 ) {
        $scan = md5( $userId . rand(5, 32) . (time() + $time) );
        if ( $this->is_repeat($scan, 'scan_token') ) $data = $this->create_token($userId, $time + 1);
        return $scan;
    }

    /**
     * @Author : Qs
     * @Name   : 生成token/scan_token
     * @Note   : 
     * @Time   : 2018/9/18 15:35
     * @param    integer     $userId    用户ID
     * @param    string      $type      生成类型
     * @return   string                 生成对应的token
     **/
    public function token ( $userId, $type = 'token' ) {
        $newToken = false;
        try {
            switch ( $type ) {
                case 'token':
                    $newToken = $this->create_token($userId);
                    break;
                case 'scan':
                    $newToken = $this->create_scan($userId);
                    break;
            }
            // 删除已有TOKEN
            $this->redis->rm($userId);
            // 添加新的TOKEN
            $this->redis->set($userId, $newToken, []);
            $newToken = base64_encode($newToken . '_' . $userId . '_' . $type);
        } catch ( \Exception $e ) {
            $newToken = false;
            $this->log->save($e->getMessage(), 'token_error');
        }
        return $newToken;
    }

    /**
     * @Author : Qs
     * @Name   : 判断某个字段值是否重复
     * @Note   : 
     * @Time   : 2018/9/13 1:19
     * @param   string      $string     字段值
     * @param   string      $key        字段
     * @return  boolean
     **/
    protected function is_repeat( $string, $key = 'token' ){
        $tmp = $this->redis->has($string);
        if ( $tmp ) return true;
        return false;
    }

    /**
     * @Author : Qs
     * @Name   : 验证token/scan_token
     * @Note   : 
     * @Time   : 2018/9/18 16:13
     * @param    string    $authToken    需验证的TOKEN信息
     * @return   boolean
     **/
    public function auth_token ( $authToken ) {
        $authToken = base64_decode($authToken);
        $authToken = explode('_',$authToken);
        if ( count($authToken) != 3 ) return false;
        $token = $authToken[0];
        $userId = $authToken[1];
        if ( 'scan' != $authToken[2] && 'token' != $authToken[2] ) return false;
        $type = $authToken[2] == 'scan' ? 'scan_token' : $authToken[2];
        $userTmp = $this->redis->get($userId);
        if ($userTmp == $token) return true;
        return false;
    }
   

}