<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Db;
/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        return parent::add();
    }
    
    public function edit_money(){
        $ids = input('ids');
        $row = $this->model->get($ids);
        if ($this->request->isPost()) {
            $row = $this->model->get(input('id'));
            if(!$row){
                $this->error('用户不存在');
            }
            if(input('number')<=0){
                $this->error('操作金额必须大于0');
            }
            if(input('money_type')==0){
                $edit_field = 'score';
            }else{
                $edit_field = 'money';
            }
            if(input('type')==1){ //增加
                $new_money = $row[$edit_field] + input('number');
            }else{
                $new_money = $row[$edit_field] - input('number');
            }
            $add_log = [
                'user_id'=>$row['id'],
                'before'=>$row[$edit_field],
                'after'=>$new_money,
                'coin'=>input('number'),
                'type'=>'admin_edit',
                'money_type'=>input('money_type'),
                'create_time'=>time(),
                'update_time'=>time(),
            ];
            Db::name('coin_record')->insert($add_log);
            
            $row->$edit_field = $new_money;
            $row->save();
            $this->success('操作成功');
        }
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        // return $this->view->fetch();
        return view('',['row'=>$row]);
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Db::table('box_user')->where('id','in',$ids)->delete();
        // Auth::instance()->delete($row['id']);
        $this->success();
    }

}
