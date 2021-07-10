<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/15 10:51
 */

namespace app\seller\validate;

use think\Validate;

class Goods extends Validate
{
    protected $rule = [
        'goods_name'   => 'require',
        'cat_id'       => 'require|gt:0',
        'original_img' => 'require|url',
    ];

    protected $message = [
        'goods_name'   => '商品名称不能为空',
        'cat_id'       => '商品分类不能为空',
        'original_img' => '请上传商品图片',
    ];

    protected $scene = [
        'edit' => ['goods_name', 'cat_id', 'original_img']
    ];
}