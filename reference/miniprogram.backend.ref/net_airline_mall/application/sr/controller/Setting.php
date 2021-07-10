<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/3/13 11:45
 */

namespace app\sr\controller;

use app\common\model\Supplier;
use think\Controller;
use think\Db;

class Setting extends Controller
{
    public function index()
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $result = $this->validate($data, [
                'id'     => 'require|eq:' . session('sr.id'),
                'logo'   => 'require|url',
                'mobile' => 'mobile'
            ], [
                'id'     => '非法请求',
                'logo'   => '请上传LOGO',
                'mobile' => '手机号码格式错误',
            ]);
            if (true !== $result) return json($result, 403);
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
            (new Supplier)->allowField('name,logo,mobile,password')->save($data, ['id' => $data['id']]);
            $sr         = session('sr');
            $sr['logo'] = $data['logo'];
            session('sr', $sr);
            return json('保存完成');
        }

        $store = Supplier::field('id,username,name,logo,mobile')->get(session('sr.id'));
        return $this->fetch('', ['store' => $store]);
    }

    public function uploadtoken()
    {
        //return json(uploadtoken());//七牛上传
        $path = input('path', 'temp');
        return json(ossUploadToken($path));//阿里上传
    }
}