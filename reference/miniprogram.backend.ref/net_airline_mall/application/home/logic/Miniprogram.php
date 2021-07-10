<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2020/2/19 12:14
 */

namespace app\home\logic;

use EasyWeChat\Factory;
use think\Db;
use app\home\model\Goods;
use think\facade\Env;

class Miniprogram
{
    /**
     * 生成小程序商品二维码
     * @param $goods_id
     * @param int $shareMan
     * @return array|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function makeQrcodeWithGoods($goods_id, $shareMan = 0)
    {
        $config   = [
            'app_id'        => config('miniwxpayConfig')['appid'],
            'secret'        => config('miniwxpayConfig')['secret'],
            'response_type' => 'array',
        ];
        $app      = Factory::miniProgram($config);
        $savePath = Env::get('root_path') . 'uploads';

        $map = [['goods_id', '=', $goods_id]];
        if (is_array($goods_id)) {
            $map = [['goods_id', 'IN', $goods_id]];
        }
        $goodsModel = new Goods;
        $ids        = $goodsModel->where($map)->column('goods_name', 'goods_id');
        set_time_limit(0);
        $name    = '';
        $nameArr = [];

        $optional = ['width' => 400];
        foreach ($ids as $goods_id => $goods_name) {
            $path = 'pages/goods/index?goods_id=' . $goods_id;
            if ($shareMan) {
                $path .= '&uid=' . $shareMan;
            }
            $response = $app->app_code->get($path, $optional);
            if ($shareMan) {
                $type = $response->getHeader('Content-Type')[0];
                $name = 'data:' . $type . ';base64,' . base64_encode($response->getBody()->getContents());
                break;
            }
            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                $goods_name = $goods_id . '_' . str_replace(['/', '*', ':', '?', '='], ['', '-', 'x', '-', '|', '-'], $goods_name);
                $name       = $response->saveAs($savePath, $goods_name);
                array_push($nameArr, '/uploads/' . $name);
            }
        }
        if ($shareMan) {
            return $name;
        }
        if (is_array($goods_id)) {
            return $nameArr;
        } else {
            return '/uploads/' . $name;
        }
    }
}