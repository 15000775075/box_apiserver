<?php

namespace app\admin\model;

use think\Model;


class Detailed extends Model
{

    

    

    // 表名
    protected $name = 'detailed';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'lytag_text',
        'lxtag_text',
        'jltime_text'
    ];
    

    
    public function getLytagList()
    {
        return ['yaoqing' => __('Lytag yaoqing'), 'kaihe' => __('Lytag kaihe')];
    }

    public function getLxtagList()
    {
        return ['yhq' => __('Lxtag yhq'), 'box' => __('Lxtag box'), 'coin' => __('Lxtag coin')];
    }


    public function getLytagTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['lytag']) ? $data['lytag'] : '');
        $list = $this->getLytagList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getLxtagTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['lxtag']) ? $data['lxtag'] : '');
        $list = $this->getLxtagList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getJltimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['jltime']) ? $data['jltime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setJltimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
