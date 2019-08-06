<?php

namespace Qs\api;

use think\facade\Request;
use Qs\log\Log;

class Api{

    // 允许的method
    protected $allowMethod = ' GET, POST, PUT, DELETE, OPTIONS';
    // 请求信息实例
    protected $request;
    // 请求类型: GET/POST/PUT/DELETE/OPTIONS
    protected $method;
    // 验证TOKEN
    protected $authToken;
    // 根据TOKEN获取user_id
    protected $userId;
    // 验证器
    protected $validate;
    // token插件
    protected $token;
    // 日记
    protected $log;

    // 响应信息
    protected $returnData;
    // 请求参数数组
    protected $paramData;
    

    // 初始化
    public function __construct() {
        // 默认返回信息
        $this->returnData = ['status'=>0, 'msg'=>lang('not authorized')];
        // 允许所有跨域
        header("Access-Control-Allow-Origin: *");
        // 允许跨域传COOKICE
        header('Access-Control-Allow-Credentials: true');
        // 允许接收的头
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Auth-Token");
        // 允许的请求类型
        header('Access-Control-Allow-Methods:' . $this->allowMethod);
        // 类型、编码
        header('Content-Type:application/json; charset=utf-8');
        // 获取请求信息
        $this->request = Request::instance();
        // 获取验证TOKEN
        $this->authToken = $this->request->header('Auth-Token');
        if ( $this->authToken && $this->authToken != 'undefined' ) {
            $this->userId = base64_decode($this->authToken);
            $this->userId = explode('_', $this->userId);
            $this->userId = isset($this->userId[1]) && is_numeric($this->userId[1]) ? $this->userId[1] : null;
        }

        $this->method = strtolower($this->request->method());
        if ( !$this->auth_method() ) {
            http_response_code(406);
            exit(json_encode($this->returnData));
        }
        $this->token = new Token;
        $this->log = Log::instance();
        $this->paramData = $this->request->param();
    }

    /**
     * @Author: Qs
     * @Name: 验证请求头
     * @Note: 
     * @Time: 2018/6/25 16:16 
     * @param   string  $method   请求头类型：GET、POST、PUT、DELETE、OPTIONS
     * @return  boolean           验证结果：true or false
     **/
    protected function auth_method( $method = '' ) {
        $method = $method ? : $this->method;
        if ( stripos( $this->allowMethod, $method ) ) return true;
        return false;
    }
    
    /**
     * @Author: Qs
     * @Name: 验证qs_token
     * @Note: 
     * @Time: 2018/6/25 16:36 
     * @return  array   返回数组包含：status、msg、username、qs_token
     **/
    protected function auth_token() {
        return $this->token->auth_token($this->authToken);
    }

}