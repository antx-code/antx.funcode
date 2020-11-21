<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/8 12:10
 */

namespace app\seller\validate;

use think\Validate;

class SellerUser extends Validate
{
    protected $rule = [
        'token'      => 'require|max:32|token:token',
        'username'   => 'require',
        'email'      => 'require|email',
        'password'   => 'require|min:6',
        'repassword' => 'require|confirm:password',
    ];

    protected $message = [
        'token'    => 'token失效,请刷新页面',
        'username' => '请输入用户名',
        'email'    => '邮箱格式错误',
        'password' => '密码至少6位',
        'confirm'  => '两次输入的密码不一致'
    ];

    protected $scene = [
        'edit' => ['token', 'username', 'email']
    ];
}