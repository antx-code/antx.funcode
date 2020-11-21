<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/22 9:14
 */

namespace app\home\controller;

use think\Db;

class Merchant extends Common
{
    public function index()
    {
        $case     = Db::name('store')->where('is_delete=0 AND `status`=1')->order('store_average_score DESC,store_service_score DESC')->limit(4)->field('store_id,store_name,logo,store_subtitle')->select();
        $question = Db::name('article')->where('cat_id=14 AND is_open=1')->limit(6)->field('title,description,article_id')->select();

        $this->assign('question', $question);
        $this->assign('case', $case);
        return view();
    }

    public function apply()
    {
        if ($this->request->isGet() && !$this->request->isAjax()) {
            $seller = Db::name('seller')->where('user_id', session('user.user_id'))->count();
            if ($seller) {
                $this->redirect('seller/login/index');//'您已成为商户,正在跳转至商户中心'
            }
        }

        $apply         = Db::name('merchant_apply')->where('user_id', session('user.user_id'))->find();
        $process       = Db::name('store_process')->where('is_show=1')->order('process_order')->column('id,process_title,process_article,fields_next', 'process_order');
        $process_count = count($process);
        if ($this->request->isPost()) {
            $step = input('post.step/d');
            $data = input('post.');
            unset($data['step']);
            if ($step == 1) {
                //1. 同意协议 提交下一步，预防step==2刷新页面导致重复提交
                $count = Db::name('merchant_apply')->where('user_id', session('user.user_id'))->count();
                if (!$count) {
                    $apply             = [
                        'user_id'  => session('user.user_id'),
                        'step'     => $step,
                        'status'   => 0,
                        'add_time' => time()
                    ];
                    $apply['apply_id'] = Db::name('merchant_apply')->insertGetId($apply);
                }
            } else if ($step >= $process_count || $apply['step'] == $step) {
                //2. 申请填写完毕

            } else {
                //3. 申请中
                //$apply  = Db::name('merchant_apply')->where('user_id', session('user.user_id'))->find();
                $i      = 0;
                $record = [];
                $old    = [];
                foreach ($data as $key => $value) {
                    if ($value == '') continue;
                    $old[$i]                   = $key;
                    $record[$i]['apply_id']    = $apply['apply_id'];
                    $record[$i]['fields_name'] = $key;
                    $record[$i]['value']       = is_array($value) ? implode('_', $value) : $value;
                    $i++;
                }

                if ($record) {
                    Db::name('store_process_steps_content')->where('apply_id', $apply['apply_id'])->whereIn('fields_name', $old)->delete();
                    Db::name('store_process_steps_content')->insertAll($record);
                }
                $update = ['step' => $step];
                if (($step == $process_count - 1) && end($process)['process_article'] > 0) {
                    $update['step']   = $step + 1;
                    $update['status'] = 1;
                }
                Db::name('merchant_apply')->where('apply_id', $apply['apply_id'])->update($update);
            }
            $step += 1;
        }

        if ($this->request->isGet()) {
            $step = input('step/d');
            if (!$step) {
                $step = $apply ? $apply['step'] + 1 : 1;
            }
        }

        if ($step > $process_count) {
            $this->redirect('merchant/inquiry');
        } else {
            $map  = [
                'a.process_id' => $process[$step]['id'],
                'a.is_show'    => 1
            ];
            $join = 'a.fieldsName=b.fields_name';
            if ($apply) {
                $join .= ' AND b.apply_id=' . $apply['apply_id'];
            }

            $field = Db::name('store_process_steps a')
                ->join('store_process_steps_content b', $join, 'LEFT')
                ->where($map)
                ->field('a.*,b.value')
                ->order('sort_order DESC')
                ->select();

            //还原取出的字段格式;
            array_walk($field, function (&$item) {
                if ($item['fieldsFormType'] == 'select') {
                    $field    = 'region_id,parent_id,region_name,region_type,region_first_char';
                    $country  = Db::name('region')->where('parent_id=0')->field($field)->cache(true, 86400)->select();
                    $province = Db::name('region')->where('parent_id=1')->field($field)->cache(true, 86400)->select();
                    $this->assign('countryList', $country);
                    $this->assign('provinceList', $province);
                    if ($item['value']) {
                        $item['value'] = explode('_', $item['value']);
                        if (isset($item['value'][2])) {
                            //已选择省
                            $city = Db::name('region')->where('parent_id', $item['value'][1])->field($field)->cache(true, 86400)->select();
                            $this->assign('cityList', $city);
                        }
                        if (isset($item['value'][2])) {
                            //已选择市
                            $district = Db::name('region')->where('parent_id', $item['value'][2])->field($field)->cache(true, 86400)->select();
                            $this->assign('districtList', $district);
                        }
                        //todo 编辑模式默认显示已选择的省市区信息
                    }
                } elseif ($item['fieldsFormType'] == 'select_normal') {
                    if ($item['fieldsName'] == 'shop_main_category') {
                        //申请主营分类,需单独处理
                        $item['options'] = Db::name('category')->where('parent_id=0 AND is_delete=0 AND is_show=1')->column('cat_name', 'cat_id');
                    } else {
                        $item['options'] = json_decode($item['fieldsValue'], true);
                    }
                }
            });
        }

        if (!$field) {
            $field['_article'] = Db::name('article')->where('article_id', $process[$step]['process_article'])->value('content');
        }

        $this->assign('token', ossUploadToken());
        $this->assign('step', $step);
        $this->assign('sn', 0);
        $this->assign('field', $field);
        $this->assign('process', $process);
        return view();
    }

    public function inquiry()
    {
        $apply = Db::name('merchant_apply')->where('user_id', session('user.user_id'))->find();
        if ($apply['status'] == 0) {
            $this->redirect('merchant/apply');
        }
        $status                  = ['申请流程中', '正在审核中...', '审核已通过', '未审核通过...'];
        $progress['status_code'] = $apply['status'];
        $progress['status']      = $status[$apply['status']];

        $info                       = Db::name('store_process_steps_content')->where(['apply_id' => $apply['apply_id']])->whereIn('fields_name', 'store_name,store_subtitle')->column('value', 'fields_name');
        $progress['store_name']     = $info['store_name'] ?? '';
        $progress['store_subtitle'] = $info['store_subtitle'] ?? '';

        $this->assign('progress', $progress);
        return view('inquiry');
    }

    public function ajaxGetArticle()
    {
        $title   = input('title', '入驻流程');
        $article = Db::name('article')->where('cat_id', 14)->whereLike('title', '%' . $title . '%')->value('content');

        $this->assign('content', $article);
        return view('ajax/merchant_article');
    }

    public function getRegionList()
    {
        return json(getRegionByParentId());
    }

    /**
     * 申请入驻店铺重名检查
     * @return \think\response\Json
     */
    public function uniqueName()
    {
        $store_name = input('store_name');
        $count      = Db::name('store')->where('store_name', $store_name)->count();
        if ($count) return json('您期望的店铺名称已被使用,换一个试试吧~', 403);
        return json();
    }
}
