<?php

namespace app\admin\model;

use think\Model;


class Shoporder extends Model
{

    

    

    // 表名
    protected $name = 'shoporder';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'pay_method_text',
        'pay_time_text',
        'status_text',
        'create_time_text',
        'update_time_text',
        'delete_time_text',
        'kdgs_text',
        'is_dc_text',
    ];
    

    
    public function getPayMethodList()
    {
        return ['wechat' => __('Pay_method wechat'), 'alipay' => __('Pay_method alipay'), 'lucyk' => __('Pay_method lucyk'), 'sqfh' => __('Pay_method sqfh')];
    }

    public function getStatusList()
    {
        return ['unpay' => __('Status unpay'), 'used' => __('Status used'), 'refund' => __('Status refund'), 'ywc' => __('Status ywc'), 'undei' => __('Status undei')];
    }

    public function getKdgsList()
    {
        return ['yuantong' => __('Kdgs yuantong'), 'yunda' => __('Kdgs yunda'), 'shentong' => __('Kdgs shentong'), 'zhongtong' => __('Kdgs zhongtong'), 'jtexpress' => __('Kdgs jtexpress'), 'shunfeng' => __('Kdgs shunfeng'), 'youzhengguonei' => __('Kdgs youzhengguonei'), 'ems' => __('Kdgs ems'), 'jd' => __('Kdgs jd'), 'debangkuaidi' => __('Kdgs debangkuaidi')];
    }


    public function getPayMethodTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_method']) ? $data['pay_method'] : '');
        $list = $this->getPayMethodList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_time']) ? $data['pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    
    public function getIsDcTextAttr($value, $data)
    {
        $list = [0=>'未导出',1=>'已导出'];
        return $list[$data['is_dc']];
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getDeleteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['delete_time']) ? $data['delete_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getKdgsTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['kdgs']) ? $data['kdgs'] : '');
        $list = $this->getKdgsList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setDeleteTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
