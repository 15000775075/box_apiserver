<?php

namespace app\admin\model;

use think\Model;


class Banner extends Model
{

    

    

    // 表名
    protected $name = 'banner';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'tag_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }


    public function getTagList()
    {
        return ['scsy' => __('Tag scsy'), 'sczj' => __('Tag sczj'),'mhsy' => __('Tag mhsy')];
    }

    public function getTagTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['tag']) ? $data['tag'] : '');
        $list = $this->getTagList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
