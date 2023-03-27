<?php

namespace app\admin\model;

use think\Model;


class Daili extends Model
{

    

    

    // 表名
    protected $name = 'daili';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'sqtime_text'
    ];
    

    
    public function getStatusList()
    {
        return ['ty' => __('Status ty'), 'jj' => __('Status jj'), 'sh' => __('Status sh')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSqtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sqtime']) ? $data['sqtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setSqtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
