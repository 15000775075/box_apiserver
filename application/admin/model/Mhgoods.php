<?php

namespace app\admin\model;

use think\Model;


class Mhgoods extends Model
{

    

    

    // 表名
    protected $name = 'mhgoods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text',
        'tag_text'
    ];
    

    
    public function getTagList()
    {
        return ['normal' => __('Tag normal'), 'rare' => __('Tag rare'), 'supreme' => __('Tag supreme'), 'legend' => __('Tag legend')];
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getTagTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['tag']) ? $data['tag'] : '');
        $list = $this->getTagList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function boxfl()
    {
        return $this->belongsTo('Boxfl', 'boxfl_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
