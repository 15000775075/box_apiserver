<?php

namespace app\admin\model\coin;

use think\Model;


class Record extends Model
{

    

    

    // 表名
    protected $name = 'coin_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'money_type_text',
        'create_time_text',
        'update_time_text',
        'delete_time_text'
    ];
    

    
    public function getTypeList()
    {
        return ['pay_shop' => '购买商品', 'recharge' => '盲盒回收', 'huihuan' => '兑换码兑换', 'duihuan'=>'兑换码兑换','fxfy'=>'好友开盒','xfzs'=>'消费赠送','sing_jl'=>'签到赠送','yqhy'=>'邀请好友','admin_edit'=>'管理员操作'];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    public function getMoneyTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['money_type']) ? $data['money_type'] : '');
        $list = [0=>'幸运币',1=>'钻石'];
        return isset($list[$value]) ? $list[$value] : '';
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
