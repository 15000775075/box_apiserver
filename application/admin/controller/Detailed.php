<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Detailed extends Backend
{

    /**
     * Detailed模型对象
     * @var \app\admin\model\Detailed
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Detailed;
        $this->view->assign("lytagList", $this->model->getLytagList());
        $this->view->assign("lxtagList", $this->model->getLxtagList());
        if(input('get.ids')!=0 && input('get.look_type')=='user'){
            $this->model = $this->model->where('user_id',input('get.ids'));
            // var_dump(1);die;
        }
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
     
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
            $UserModel = model('User');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as &$v) {
                $user = $UserModel->find($v['user_id']);
                
                if(empty($user['nickname'])){
                    // var_dump($v['user_id']);die;
                    $user['nickname'] = '';
                    $user['mobile'] = '';
                }
                $v['nickname'] = $user['nickname'];
                $v['mobile'] = $user['mobile'];
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    


}
