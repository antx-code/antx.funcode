<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/3/7 14:25
 */

namespace app\seller\controller;

use think\Db;
use app\seller\validate\SellerUser as User;

class Privilege extends Base
{
    /**
     * 下级管理员列表
     * @return mixed
     */
    public function admin_list()
    {
        $p        = input('page/d', 1);
        $pageSize = input('pageSize/d', config('pageSize'));

        $map       = [
            'store_id'  => $this->store_id,
            'is_admin'  => 0,
            'is_delete' => 0
        ];
        $shop_name = Db::name('store')->where('store_id', $this->store_id)->cache(true, null, $this->tag)->value('store_name');
        $list      = Db::name('seller')->where($map)->field('id,username,email,add_time,last_login_time')->page($p, $pageSize)->select();
        $this->assign('store_name', $shop_name);
        $this->assign('list', $list);

        //$temp = json_encode(auth_list(config('sellerMenu')));
        //dump($temp);die;
        //Db::name('seller')->where('id', 7)->update(['action' => $temp]);
        return $this->fetch();
    }

    /**
     * 权限分派
     */
    public function allot()
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $result = $this->validate($data, [
                'token' => 'require|max:32|token:token',
                'id'    => 'require',
            ], [
                'token' => 'token失效,请刷新页面',
                'id'    => '参数错误,请刷新后重试'
            ]);
            if (true !== $result) {
                $this->error($result);
            }
            $id = $data['id'];
            unset($data['id'], $data['token']);

            $controller = '';
            $action     = [];
            foreach ($data as $key => $item) {
                $controller .= ',' . $key;
                $temp       = '';
                foreach ($item as $index => $val) {
                    $temp .= ',' . $val;
                }
                if ($temp) {
                    $action[$key] = array_unique(explode(',', trim($temp, ',')));
                }
            }
            $controller = trim($controller, ',');
            $action     = json_encode($action);
            $flag       = Db::name('seller')->where(['id' => $id, 'store_id' => $this->store_id])->update(['controller' => $controller, 'action' => $action]);
            if (!$flag) $this->error('操作失败,分派权限未变化');
            $this->success('编辑完成', 'privilege/admin_list');
        }

        //GET获取数据
        $id   = input('id');
        $user = Db::name('seller')->where('id', $id)->field('id,username,controller,action')->find();
        if (!$user) $this->error('管理员不存在');

        $user['controller'] = explode(',', $user['controller']);
        $user['action']     = json_decode($user['action'], true);
        $this->assign('auth_list', config('sellerMenu'));
        $this->assign('user', $user);

        return $this->fetch();
    }

    /**
     * 添加/编辑
     */
    public function add()
    {
        $id = input('id/d');
        if ($this->request->isPost()) {
            $data     = input('post.');
            $validate = new User();
            if ($data['id']) {
                $result = $validate->scene('edit')->check($data);
            } else {
                $result = $validate->check($data);
            }
            if (!$result) $this->error($validate->getError());

            unset($data['token'], $data['act'], $data['repassword']);
            if ($data['password']) {
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            } else {
                unset($data['password']);
            }

            //编辑
            if ($data['id']) {
                Db::name('seller')->update($data);
                $this->redirect('privilege/admin_list');
            } else {
                //新增
                $unique = Db::name('seller')->where('username', $data['username'])->count();
                if ($unique) $this->error('用户名:[ ' . $data['username'] . ' ]已存在');
                unset($data['id']);
                $data['add_time']   = time();
                $data['store_id']   = $this->store_id;
                $data['is_admin']   = 0;
                $menu               = config('sellerMenu');
                $data['controller'] = implode(',', array_keys($menu));
                $data['action']     = json_encode(auth_list($menu));

                $id = Db::name('seller')->insertGetId($data);
            }
            $this->redirect('privilege/allot', ['id' => $id]);
        }

        //GET获取数据
        if ($id) {
            $user = Db::name('seller')->where('id', $id)->field('id,username,email')->find();
            if (!$user) $this->error('下级管理员不存在');
            $this->assign('user', $user);
        }
        return $this->fetch();
    }

    /**
     * 管理员删除
     */
    public function del()
    {
        $id   = input('post.id/d');
        $flag = Db::name('seller')->where(['id' => $id, 'store_id' => $this->store_id])->update(['is_delete' => time()]);
        if (!$flag) {
            json('操作失败', 405)->send();
            exit;
        }
        return json();
    }

    public function modify_pwd()
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $result = $this->validate($data, [
                'email'      => 'email',
                'password'   => 'require|min:6',
                'repassword' => 'require|confirm:password'
            ], [
                'email'      => '邮箱格式错误',
                'password'   => '密码长度至少6位',
                'repassword' => '两次输入密码不一致'
            ]);
            if (true !== $result) {
                return json($result, 403);
            }

            $update['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            if (isset($data['email'])) {
                $update['email'] = $data['email'];
            }

            Db::name('seller')->where('id', $this->seller['id'])->update($update);
            return json();
        }
        return $this->fetch('', ['username' => $this->seller['username'], 'email' => $this->seller['email']]);
    }
}