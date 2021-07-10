<?php

/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2019/4/16 9:28
 */

namespace app\home\validate;

use think\Validate;

class Invoince extends Validate
{
    protected $rule = [
        'inv_payee'              => 'require',
        'company_name'           => 'require',
        'company_address'        => 'require',
        'company_telephone'      => 'require',
        'tax_id'                 => 'require',
        'bank_of_deposit'        => 'require',
        'bank_account'           => 'require',
        'consignee_name'         => 'require',
        'consignee_mobile_phone' => 'require|mobile',
        'country'                => 'require',
        'province'               => 'require',
        'city'                   => 'require',
        'district'               => 'require',
        'consignee_address'      => 'require'
    ];

    protected $message = [
        'inv_payee'              => '发票抬头必须填写',
        'company_name'           => '单位名称必填',
        'company_address'        => '注册地址必填',
        'company_telephone'      => '注册电话必填',
        'tax_id'                 => '纳税人识别号必填',
        'bank_of_deposit'        => '开户银行必填',
        'bank_account'           => '银行账号必填',
        'consignee_name'         => '收票人姓名必填',
        'consignee_mobile_phone' => '收票人手机号错误',
        'country'                => '请选择地址',
        'province'               => '请选择省',
        'city'                   => '请选择市',
        'district'               => '请选择区',
        'consignee_address'      => '收票人详细地址必填'
    ];

    public function sceneUpdate_name()
    {
        return $this->only(['inv_payee']);
    }

    public function sceneUpdate_vat()
    {
        return $this->remove('inv_payee', 'require');
    }

    public function sceneDel()
    {
        return $this->only(['tax_id']);
    }
}