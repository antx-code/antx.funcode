<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/8 10:32
 */

namespace app\seller\controller;

use think\Db;

class System extends Base
{
    public function shipping()
    {
        $list = Db::name('shipping')->where('enabled', 1)->select();
        return $this->fetch('', ['list' => $list]);
    }

    public function template()
    {
        if ($this->request->isPost()) {
            $id = input('id');
            Db::name('transport')->where(['store_id' => $this->store_id, 'transport_id' => $id])->update(['is_delete' => time()]);
            return json();
        }
        $data['list'] = Db::name('transport')->where(['store_id' => $this->store_id, 'is_delete' => 0])->order('shipping_id desc')->select();
        $data['type'] = [0 => '不计重量和件数', 1 => '按商品件数', 2 => '按重量'];
        return $this->fetch('', $data);
    }

    public function template_add()
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $result = $this->validate($data, [
                'transport_name' => 'require',
                'shipping_id'    => 'require|gt:0',
                'price'          => 'require|gt:0'
            ], [
                'transport_name' => '运费模板名称必填',
                'shipping_id'    => '请选择物流公司',
                'price'          => '物流费用'
            ]);
            if (true !== $result) return json($result, 403);

            $data['transport_price'] = $data['unit_price'] = $data['kg_price'] = 0;
            switch ($data['type']) {
                case 0:
                    $data['transport_price'] = $data['price'];
                    break;
                case 1:
                    $data['unit_price'] = $data['price'];
                    break;
                case 2:
                    $data['kg_price'] = $data['price'];
                    break;
                default:
                    return json('运费计算错误', 403);
            }
            unset($data['price']);
            $data['update_time'] = time();
            if ($data['transport_id'] > 0) {
                $flag = Db::name('transport')->update($data);
            } else {
                $data['store_id']    = $this->store_id;
                $data['create_time'] = $data['update_time'];
                $flag                = Db::name('transport')->insert($data);
            }
            if (!$flag) return json('保存失败', 405);
            return json();
        }

        $id       = input('id');
        $template = Db::name('transport')->where(['store_id' => $this->store_id, 'transport_id' => $id])->find();
        $shipping = Db::name('shipping')->where('enabled', 1)->select();
        return $this->fetch('', ['template' => $template, 'shipping' => $shipping]);
    }
}