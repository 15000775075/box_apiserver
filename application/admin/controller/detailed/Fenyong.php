<?php

namespace app\admin\controller\detailed;

use app\common\controller\Backend;

/**
 * 分佣明细管理
 *
 * @icon fa fa-circle-o
 */
class Fenyong extends Backend
{

    /**
     * Record模型对象
     * @var \app\admin\model\prize\Record
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User')->where('isdl',1);
    }



    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $DetailedModel = new \app\admin\model\Detailed;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as &$v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
                $v['fyzs'] = $DetailedModel->where('user_id',$v['id'])->where('lxtag','coin')->sum('coinnum');
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
