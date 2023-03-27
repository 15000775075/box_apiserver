<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 盲盒购买订单管理
 *
 * @icon fa fa-circle-o
 */
class Shoporder extends Backend
{

    /**
     * Shoporder模型对象
     * @var \app\admin\model\Shoporder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Shoporder;
        $this->view->assign("payMethodList", $this->model->getPayMethodList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("kdgsList", $this->model->getKdgsList());
    }

    
    public function set_dc(){
        if(empty($_POST['id'])){
            $this->model->where('id','>',0)->update(['is_dc'=>1]);
            return;
        }
        $id = $_POST['id'];
        $ids = implode(',',$id);
        $this->model->where('id','in',$ids)->update(['is_dc'=>1]);
    }
    
    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        
        if(input('status')==''){
            $this->model = $this->model->where('status','neq','unpay');
        }
        
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
            // var_dump($list);die;
        // if($limit==999999 || empty($limit)){
        //     // echo 1;die;
        //     foreach($list->items() as $k => $v){
        //         if(!empty($v['is_dc']) || $v['is_dc']==0){
        //             // $v->save(['is_dc'=>1]);
        //             // echo 1;die;
        //             $this->model->where('id',$v['id'])->update(['is_dc'=>1]);
        //         }
        //     }
        // }
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


}
