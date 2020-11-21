<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/8 10:32
 */

namespace app\seller\controller;

use think\Db;

class Store extends Base
{
    /**
     * 店铺设置
     * @return mixed|\think\response\Json
     */
    public function setting()
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $result = $this->validate($data, [
                'store_id'      => 'require|eq:' . $this->store_id,
                'banner_pc'     => 'require|url',
                'banner_mobile' => 'require|url',
                'logo'          => 'require|url',
                'mobile'        => 'mobile',
            ], [
                'store'         => '非法请求',
                'banner_pc'     => '请上传PC版店铺banner',
                'banner_mobile' => '请上传手机版店铺banner',
                'logo'          => '请上传店铺LOGO',
                'mobile'        => '手机号码格式错误',
            ]);
            if (true !== $result) return json($result, 403);

            Db::name('store')->field('store_id,seller_id,store_name,seller_money,frozen_money,credit_money,add_time,is_delete', true)->update($data);
            return json('保存完成');
        }

        $store  = Db::name('store')->where('store_id', $this->store_id)->find();
        $region = getRegionList($store);
        return $this->fetch('', ['store' => $store, 'region' => $region]);
    }

    public function navigator()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            Db::name('store_nav')->where(['id' => $data['id'], 'store_id' => $this->store_id])->update([$data['field'] => $data['value']]);
            return json(['content' => $data['value']]);
        }
        $list = Db::name('store_nav')->where(['store_id' => $this->store_id])->order('sort_order DESC')->select();
        return $this->fetch('', ['list' => $list]);
    }

    public function navigator_add()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            if ($this->request->param('is_delete')) {
                Db::name('store_nav')->where(['id' => $data['id'], 'store_id' => $this->store_id])->delete();
                return json();
            }
            $result = $this->validate($data, [
                'name' => 'require',
                'url'  => 'require',
            ], [
                'name' => '导航名称必填',
                'url'  => '导航url地址必填',
            ]);

            if (true !== $result) {
                $this->error($result);
            }

            $data['store_id'] = $this->store_id;
            if ($data['id'] > 0) {
                Db::name('store_nav')->update($data);
            } else {
                Db::name('store_nav')->insert($data);
            }
            $this->redirect('store/navigator');
        }

        $id  = input('id/d', 0);
        $nav = Db::name('store_nav')->where('id', $id)->find();

        return $this->fetch('', ['nav' => $nav]);
    }

    public function advertising()
    {
        if ($this->request->isPost()) {
            $position = input('post.type');
            $img      = input('post.img/a', []);
            $url      = input('post.url/a', []);
            $effect   = input('post.effect', 'fade');
            if (count($img) > 6) {
                return json('广告图片最多6张', 403);
            }
            $awllo = ['slide', 'fade', 'cube', 'coverflow', 'flip'];
            if (count($img) != count($url)) {
                return json('参数错误,请刷新页面后重试', 403);
            }
            $position       = $position == 'top' ? 0 : 1;
            $data['img']    = implode(',', $img);
            $data['url']    = implode(',', $url);
            $data['effect'] = $effect;
            $map            = ['store_id' => $this->store_id, 'position' => $position];
            $id             = Db::name('store_adv')->where($map)->value('id');
            if ($id) {
                $res = Db::name('store_adv')->where('id', $id)->update($data);
            } else {
                $data['store_id'] = $this->store_id;
                $data['position'] = $position;
                $data['effect']   = $effect;
                $data['is_show']  = 1;
                $data['add_time'] = time();
                $res              = Db::name('store_adv')->where($map)->insert($data);
            }
            if (!$res) return json('广告信息未变更~', 403);
            return json();
        }

        if ($this->request->isAjax()) {
            $position = input('type');
            $position = $position == 'top' ? 0 : 1;
            $is_show  = input('is_show/d');
            $is_show  = $is_show == 1 ? 0 : 1;
            $res      = Db::name('store_adv')->where(['store_id' => $this->store_id, 'position' => $position])->update(['is_show' => $is_show]);
            if (!$res) return json('上一步操作未保存', 403);
            return json($is_show);
        }

        $adv = Db::name('store_adv')->where(['store_id' => $this->store_id])->select();
        if ($adv) {
            $top = $bottom = [];
            foreach ($adv as $vo) {
                if ($vo['position'] == 0) {
                    $top['img']     = explode(',', $vo['img']);
                    $top['url']     = explode(',', $vo['url']);
                    $top['id']      = $vo['id'];
                    $top['is_show'] = $vo['is_show'];
                } elseif ($vo['position'] == 1) {
                    $bottom['img']     = explode(',', $vo['img']);
                    $bottom['url']     = explode(',', $vo['url']);
                    $bottom['id']      = $vo['id'];
                    $bottom['effect']  = $vo['effect'];
                    $bottom['is_show'] = $vo['is_show'];
                }
            }
            unset($adv);
            $adv['top']    = $top;
            $adv['bottom'] = $bottom;
        }
        //dump($adv);die;
        $this->assign('adv', $adv);
        return $this->fetch();
    }

    public function visual_edit()
    {
        if ($this->request->isPost()) {
            $template = input('template/d');
            if ($template) {
                //选择模板
                $data = ['template' => $template];
            } else {
                //编辑首页轮播
                $slide      = input('post.slide');
                $slide_url  = input('post.slide_url');
                $slide_type = input('post.slide_type');
                if (count($slide) > 6) {
                    return json('广告图片最多6张', 403);
                }
                $awllo = ['slide', 'fade', 'cube', 'coverflow', 'flip'];
                if (count($slide) != count($slide_url) || !in_array($slide_type, $awllo)) {
                    return json('参数错误,请刷新页面后重试', 403);
                }
                $data = [
                    'slide'      => implode(',', $slide),
                    'slide_url'  => implode(',', $slide_url),
                    'slide_type' => $slide_type
                ];
            }
            $res = Db::name('store')->where(['store_id' => $this->store_id])->update($data);
            if (!$res) {
                return json('上一步操作未保存~', 403);
            }
            return json();
        }

        $store = Db::name('store')
            ->where('store_id', $this->store_id)
            ->field('store_id,template,slide,slide_url,slide_type,slide,slide_url,slide_type')
            ->find();
        if (strpos($store['slide'], ',')) {
            $store['slide']     = explode(',', $store['slide']);
            $store['slide_url'] = explode(',', $store['slide_url']);
        }
        $template = Db::name('store_template')->order('sort DESC')->column('temp_id,name,desc,author,amount,img', 'temp_id');

        $param = [
            'store'    => $store,
            'template' => $template
        ];
        return $this->fetch('', $param);
    }
}
