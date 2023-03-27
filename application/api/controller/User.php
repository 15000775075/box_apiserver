<?php

namespace app\api\controller;

use app\api\model\DeliveryTrade;
use app\api\model\Detail;
use app\api\model\Goods;
use app\api\model\CoinRecord;
use app\api\model\Delivery;
use app\api\model\MallOrder;
use app\api\model\Order;
use app\api\model\Zz;
use app\api\model\Prizerecord;
use app\api\model\SearchHistory;
use app\api\model\Setting;
use app\api\model\Star;
use app\api\model\Text;
use app\api\model\UserAddress;
use app\api\model\Version;
use app\api\model\Withdrawal;
use app\common\controller\Api;
use app\common\library\Sms;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use fast\Random;
use think\Db;
use think\Exception;
use think\Validate;
use fast\Http;
use wxpay\WeixinPay;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'getWechatInfoByAPP', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third', 'userCapital', 'lunbobox', 'getBox', 'mpWxLogin', 'getCop', 'getPhoneNumber','getAdminUserImg','googleLogin'];
    protected $noNeedRight = '*';

    protected $lockUserId = ['applyDelivery', 'exchange', 'moneyToCoin', 'withdrawal'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 手机验证码登录
     *
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = input('mobile');
        $captcha = input('captcha');
        $invite_code = input('sharecode', '');
        // $is_channel = input('is_channel', '');
        // print_r($_GET);//是否特定渠道进来
        // exit;
        $is_notice = 0; //是否弹窗
        if (!$mobile || !$captcha) {
            $this->error('参数错误');
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error('手机号格式不正确');
        }
//        if (!Sms::check($mobile, $captcha, 'login') && !Sms::check($mobile, $captcha, 'register')) {
//            $this->error('验证码不正确');
//        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error('账号被锁定');
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, ['invite_code' => $invite_code]);
        }
        if ($ret) {
            Sms::flush($mobile, 'login');
            $data = $this->auth->getUserinfo();
            $data['score'] = $data['score'] ? floatval($data['score']) : 0;
            $data['avatar'] = $data['avatar'] ? cdnurl($data['avatar'], true) : '';
            $data['is_notice'] = $is_notice;
            unset($data['id']);
            unset($data['user_id']);
            unset($data['createtime']);
            unset($data['expiretime']);
            unset($data['expires_in']);
            $this->success('登录成功', $data);
        } else {
            $this->error($this->auth->getError());
        }
    }
    /**
     * 退出登录
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success('退出成功');
    }
    /**
     * 绑定手机号
     *
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public function bindMobile()
    {
        $user = $this->auth->getUser();
        $mobile = input('mobile');
        $captcha = input('captcha');
        if (!$mobile || !$captcha) {
            $this->error('参数错误');
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error('手机号格式不正确');
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error('手机号已存在');
        }
        $result = Sms::check($mobile, $captcha, 'bindmobile');
        if (!$result) {
            $this->error('验证码不正确');
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'bindmobile');
        $this->success('绑定成功');
    }
    /**
     * 获取微信用户信息
     */
    public function getWechatInfoByAPP()
    {
        // if (!$code) (501);
        $s = Db::table('box_setting')->where('id', 1)->find();
        $app_id = $s['wxappid']; // 开放平台APP的id
        $app_secret = $s['wxappkey']; // 开放平台APP的secret
        $code = input('code');
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$app_id}&secret={$app_secret}&code={$code}&grant_type=authorization_code";
        $data = $this->curl_get($url);

        if ($data['code'] != 200 || !isset($data['data'])) {
            return ['code' => "500", 'msg' => "登录错误" . $data['errmsg']];
        }
        $data = $data['data'];
        if (isset($data['errcode']) && $data['errcode']) {
            return ['code' => "502", 'msg' => "code错误," . $data['errmsg']];
        }
        // 请求用户信息
        $info_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$data['access_token']}&openid={$data['openid']}";
        $user_info = $this->curl_get($info_url);
        if ($user_info['code'] != 200 || !isset($user_info['data'])) {
            return ['code' => "500", 'msg' => "登录错误" . $user_info['errmsg']];
        }

        $data = $user_info['data'];
        if (!isset($data['openid']) || !isset($data['nickname']) || !isset($data['headimgurl'])) {
            return ['code' => "500", 'msg' => "APP登录失败,网络繁忙"];
        }
        $this->threeLogin('appWx', $data['openid'], $data['nickname'], $data['headimgurl'], $data['unionid'] ?? null, $inviteUserId ?? null);
        // return ['code' => 200, 'data' => $data];
    }

    // APP登录API
    // function appLogin()
    // {
    //     $code = input('code');
    //     $user_wechat_info = $this->getWechatInfoByAPP($code);
    //     //获取到的用户信息
    //     print_r($user_wechat_info);
    //     // 
    // }
    // curl get请求
    public function curl_get($url)
    {
        $header = [
            'Accept: application/json',
        ];
        $curl = curl_init();
        // 设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        // 设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, false);
        // 超时设置,以秒为单位
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);

        // 超时设置，以毫秒为单位
        // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);

        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        // 设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // 执行命令
        $data = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        // 显示错误信息
        if ($error) {
            return ['code' => 500, 'msg' => $error];
        } else {
            return ['code' => 200, 'msg' => 'success', 'data' => json_decode($data, true)];
        }
    }
    /**
     * 微信小程序登录
     *
     * @ApiMethod (POST)
     * @param string $nickName 昵称
     * @param string $avatarUrl 头像
     * @param string $code 微信code
     */
    public function mpWxLogin()
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $param = $this->request->param();
        $nickName = $param['nickName'];

        $avatarUrl = $param['avatarUrl'];
        $code = $param['code'];
        // 邀请用户id
        $inviteUserId = $param['invitecode'] ?? null;
        if (!$code) {
            $this->error("参数不正确");
        }
        $params = [
            'js_code' => $code,
            'grant_type' => 'authorization_code',
            'appid' => $s['mpappid'],
            'secret' => $s['mpappkey']
        ];
        $result = Http::sendRequest("https://api.weixin.qq.com/sns/jscode2session", $params, 'GET');
        // var_dump($result);die;

        if ($result['ret']) {
            $json = (array)json_decode($result['msg'], true);
            if (isset($json['openid']))
                $this->threeLogin('mpWx', $json['openid'], $nickName, $avatarUrl, $json['unionid'] ?? null, $inviteUserId ?? null);
        }
        $this->error("授权失败," . $result['msg']);
    }
    /**
     * 三方登陆
     */
    public function threeLogin($type, $openid, $nickName, $avatarUrl, $unionid = null, $inviteUserId = null)
    {
        $openidType = '';
        $user = null;
        // 微信小程序
        if ($type == 'mpWx') {
            $openidType = 'wx_mini_openid';
        }
        if ($type == 'appWx') {
            $openidType = 'wx_app_openid';
        }
        // print_r($unionid);
        // exit;
        if ($openidType == 'wx_mini_openid') {
            if ($unionid != null) {
                $user = Db::name('user')->where('unionid', $unionid)->field('id,wx_mini_openid')->find();
                // 微信小程序 是否注册
                if ($type == 'mpWx') {
                    if (!empty($user)) {
                        if ($user['wx_mini_openid'] == null) {
                            Db::name('user')->where('unionid', $unionid)->update([
                                'wx_mini_openid' => $openid
                            ]);
                        }
                    }
                }
            } else {
                $user = Db::name('user')->where([$openidType => $openid])->find();
            }
        } else if ($openidType == 'wx_app_openid') {
            if ($unionid != null) {
                $user = Db::name('user')->where('wxunionid', $unionid)->field('id,wx_app_openid')->find();
                // 微信小程序 是否注册
                if ($type == 'appWx') {
                    if (!empty($user)) {
                        if ($user['wx_app_openid'] == null) {
                            Db::name('user')->where('wxunionid', $unionid)->update([
                                'wx_app_openid' => $openid
                            ]);
                        }
                    }
                }
            } else {
                $user = Db::name('user')->where([$openidType => $openid])->find();
            }
        }
        // 查找到用户
        if ($user) {
            $this->auth->direct($user['id']);
            $user = $this->auth->getUserinfo();
            $this->success('登录成功！', $user);
        }
        if(empty($user)){
        $c = Db::table('box_user')->where('mobile', $nickName)->find();
            if(!empty($c)){
                Db::table('box_user')->where('mobile',$nickName)->update([$openidType => $openid, 'unionid' => $unionid]);
            $this->auth->direct($c['id']);
            $user = $this->auth->getUserinfo();
            $this->success('登录成功！', $user);
            }
        }
        if (!empty($inviteUserId)) {
            $u = Db::table('box_user')->where('invitation', $inviteUserId)->find();
        }
        // 注册
        if ($type == 'mpWx') {
            $jmnickName = self::encryptTel($nickName);
            $params = [
                'nickname' => $jmnickName,
                'username' => $jmnickName,
                'avatar' => $avatarUrl,
                'status' => 'normal',
                'mobile' => $nickName,
                $openidType => $openid,
                'createtime' => time(),
                'invitation' => self::mycode(),
                'jointime' => time(),
                'unionid' => $unionid
            ];
        } else if ($type == 'appWx') {
            // $jmnickName = self::encryptTel($nickName);
            $params = [
                'nickname' => $nickName,
                'username' => $nickName,
                'avatar' => $avatarUrl,
                'status' => 'normal',
                // 'mobile' => $nickName,
                $openidType => $openid,
                'createtime' => time(),
                'invitation' => self::mycode(),
                'jointime' => time(),
                'wxunionid' => $unionid
            ];
        }


        if (!empty($u)) {
            $params['pid'] = $u['id'];
        }
        //         print_r($params);
        // exit;
        Db::startTrans();
        try {

            $register = Db::table('box_user')->insert($params);

            if ($register) {
                // 直接登陆
                $this->auth->direct(Db::getLastInsID());
                $user = $this->auth->getUserinfo();
                // 开启邀请新用户奖励
                if (!empty($u)) {
                                        $s = Db::table('box_setting')->where('id', 1)->find();
                    if($u['isdl'] != 1){
                    $fw = explode('-', $s['yqzscoin']);
                    $start = $fw[0];
                    $end = $fw[1];
                    $coin = rand($start, $end);
                    $coinjl = Db::table('box_user')->where('id', $u['id'])->update(['score' => $u['score'] + $coin]);
                    if (!empty($coinjl)) {
                        $coinjldata = [
                            'user_id' => $u['id'],
                            'before' => $u['score'],
                            'after' => $u['score'] + $coin,
                            'type' => 'yqhy',
                            'create_time' => time()
                        ];
                        Db::table('box_coin_record')->insert($coinjldata);
                    }
                    }


                }
                Db::commit();
                $this->success('注册成功!', $user);
            } else {
                Db::rollback();
                $this->error('注册失败！');
            }
        } catch (Exception $exception) {
            print_r($exception);
            exit;
            Db::rollback();
            $this->error('系统错误');
        }
    }
    /**
     * 生成邀请码
     * @param 
     */
    public static function mycode()
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0, 25)]
            . strtoupper(dechex(date('m')))
            . date('d') . substr(time(), -5)
            . substr(microtime(), 2, 6)
            . sprintf('%02d', rand(0, 99));
        for (
            $a = md5($rand, true),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < 6;
            $g = ord($a[$f]),
            $d .= $s[($g ^ ord($a[$f + 5])) - $g & 0x1F],
            $f++
        );
        return $d;
    }
    /**
     * 中间加密 替换字符串的子串
     */
    public static function encryptTel($tel)
    {
        $new_tel = substr_replace($tel, '****', 3, 4);
        return $new_tel;
    }
    /**
     * 用户信息
     * @date 2021/07/08 15:54
     */
    public function userinfo()
    {
        $ret =  Db::table('box_user')->where('id', $this->auth->id)->find();
        // $ret['avatar'] = $ret['avatar'] ? 
        $ret['avatar'] = cdnurl($ret['avatar'], true);
        $this->success('用户信息', $ret);
    }
    /**
     * 商城订单确认收货
     */
    public function mallOrderConfirmReceipt()
    {
        $post = input('post.');
        //拿到对应的订单号
        if (empty($post['ooid'])) {
            $this->error('未选择订单');
        }
        $order = Db::table('box_shoporder')->where('out_trade_no', $post['ooid'])->find();
        if (empty($order)) {
            $this->error('订单出错了');
        }
        $ret = Db::table('box_shoporder')->where('out_trade_no', $post['ooid'])->update(['status' => 'ywc']);
        if (empty($ret)) {
            $this->error('收获失败请重试');
        }
        $this->success('确认收货成功');
    }

    /**
     * 我的金币
     * @throws \think\exception\DbException
     */
    public function myCoin()
    {
        // $pagesize = input('pagesize/d', 10);
        $page = input('page/d', 1);
        if(input('money_type')==1){
            $money_type = 1;
            $balance = $this->auth->money;
        }else{
            $money_type = 0;
            $balance = $this->auth->score;
        }
        $list = Db::table('box_coin_record')->page($page, 10)->where('user_id', $this->auth->id)->where('money_type',$money_type)->order('create_time DESC')->select();
        foreach ($list as &$list_v) {
            $list_v['create_time'] = date('Y-m-d H:i:s', $list_v['create_time']);
            if ($list_v['type'] == 'pay_shop') {
                $list_v['status'] = '购买商品减少';
            } else if ($list_v['type'] == 'recharge') {
                $list_v['status'] = '盲盒回收增加';
            } else if ($list_v['type'] == 'duihuan') {
                $list_v['status'] = '兑换码兑换';
            } else if ($list_v['type'] == 'fxfy') {
                $list_v['status'] = '好友开盒';
            } else if ($list_v['type'] == 'xfzs') {
                $list_v['status'] = '消费赠送';
            } else if ($list_v['type'] == 'sing_jl') {
                $list_v['status'] = '签到获取';
            }else if($list_v['type'] == 'yqhy'){
                $list_v['status'] = '邀请玩家';
            }else if($list_v['type'] == 'admin_edit'){
                $list_v['status'] = '管理员操作';
            }
            $list_v['coin'] = round($list_v['coin'],2);
        }
        $ret = [
            'balance' => $balance,
            'record' => $list
        ];

        $this->success('查询成功', $ret);
    }

    /**
     * 我的星石
     * @throws \think\exception\DbException
     */
    public function myBalance()
    {
        $page = input('page/d', 1);
        $list = Db::table('box_moneylog')->where('user_id', $this->auth->id)->select();
        foreach ($list as &$list_v) {
            $list_v['addtime'] = date('Y-m-d H:i:s', $list_v['addtime']);
            if ($list_v['bgexplain'] == 'sing_jl') {
                $list_v['status'] = '签到奖励下发';
            } else if ($list_v['bgexplain'] == 'dikou') {
                $list_v['status'] = '开盒抵扣使用';
            }
        }
        $ret = [
            'balance' => $this->auth->money,
            'record' => $list
        ];

        $this->success('查询成功', $ret);
    }
    /**
     * 我的收货地址
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myAddress()
    {
        //查询所有收货地址
        $address = Db::table('box_user_address')->where('user_id', $this->auth->id)->order('is_default DESC')->select();
        $this->success('收货地址', $address);
    }
    /**
     * 获取单个收获地址回显
     */
    public function getAddress()
    {
        $add = Db::table('box_user_address')->where('id', input('addresid'))->find();
        $this->success('查询成功', $add);
    }
    /**
     * 添加收货地址
     */
    public function addAddress()
    {
        $params = input();
        $data = [
            'user_id' => $this->auth->id,
            'name' => $params['name'],
            'mobile' => $params['mobile'],
            'province' => $params['province'],
            'city' => $params['city'],
            'area' => $params['area'],
            'detail' => $params['detail'],
            'is_default' => $params['is_default'],
        ];
        $ret = Db::table('box_user_address')->insert($data);
        if (empty($ret)) {
            $this->error('添加失败');
        }
        $this->success('添加成功');
    }

    /**
     * 编辑收货地址
     */
    public function editAddress()
    {
        $params = input();
        $data = [
            'user_id' => $this->auth->id,
            'name' => $params['name'],
            'mobile' => $params['mobile'],
            'province' => $params['province'],
            'city' => $params['city'],
            'area' => $params['area'],
            'detail' => $params['detail'],
            'is_default' => $params['is_default'],
        ];
        $ret = Db::table('box_user_address')->where('id', $params['addressid'])->update($data);
        if (empty($ret)) {
            $this->error('编辑失败');
        }
        $this->success('编辑成功');
    }

    /**
     * 删除收货地址
     */
    public function deleteAddress()
    {
        $id = input('address_id/d');
        if (empty($id)) {
            $this->error('未选择地址');
        }
        $ret = Db::table('box_user_address')->where('id', $id)->delete();

        if (empty($ret)) {
            $this->error('删除失败');
        }
        $this->success('删除成功');
    }

    /**
     * 获取设置信息
     */
    public function getSettingInfo()
    {
        $ret = [
            'avatar' => $this->auth->avatar ? cdnurl($this->auth->avatar, true) : letter_avatar($this->auth->nickname),
            'avatar_url' => $this->auth->avatar,
            'nickname' => $this->auth->nickname
        ];

        $this->success('查询成功', $ret);
    }

    /**
     * 检查新版本
     */
    public function checkVersion()
    {
    }

    /**
     * 修改个人信息
     * @date 2021/07/13 14:52
     */
    public function changeInfo()
    {
        $post = input('post.');
        $ret = Db::table('box_user')->where('id', $this->auth->id)->update($post);
        if (empty($ret)) {
            $this->error('没有任何改变哦');
        }
        $this->success('保存成功');
    }
    /**
     * 盒机回收
     */
    public function exchange()
    {
        $post = input();
        $ids = $post['id'];
        // 获取商品价值
        if(is_array($ids)){
            $shops = Db::table('box_prize_record')->where('id', 'in',$ids)->select();
            $coins = array_column($shops,'goods_coin_price');
            $coina = array_sum($coins);
            $shop = Db::table('box_prize_record')->where('id', 'in',$ids)->update(['status' => 'exchange', 'hstime' => time()]);
            $s = Db::table('box_setting')->where('id', 1)->find();
            if (!empty($shop)) {
                $coin = $this->auth->score + $coina;
                $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => $coin]);
                if (!empty($ret)) {
                    //记录用户金币表
                    $data = [
                        'user_id' => $this->auth->id,
                        'before' => $this->auth->score,
                        'after' => $coin,
                        'coin' => $coina,
                        'type' => 'recharge',
                        'order_id' => 1,
                        'create_time' => time()
                    ];
                    $res = Db::table('box_coin_record')->insert($data);
                    $cardjl = Db::table('box_card_list')->where('user_id', $this->auth->id)->find();
                    if (empty($cardjl)) {
                        Db::table('box_card_list')->insert([
                            'user_id' => $this->auth->id,
                            'card_id' => $s['card_id'],
                            'lqsm' => '新人首次兑换',
                            'status' => 0,
                            'lqtime' => time()
                        ]);
                        $this->success('回收成功', ['coin' => $this->auth->score, 'cck' => '1']);
                    }
                    $this->success('回收成功', ['coin' => $this->auth->score, 'cck' => '0']);
                } else {
                    $this->error('回收失败');
                }
            }
        }else{
            $shops = Db::table('box_prize_record')->where('id', input('id'))->find();
            if ($shops['goods_coin_price'] == '0') {
                $this->error('幸运币价值为0不可兑换');
            }
            $shop = Db::table('box_prize_record')->where('id', input('id'))->update(['status' => 'exchange', 'hstime' => time()]);
            $s = Db::table('box_setting')->where('id', 1)->find();
            if (!empty($shop)) {
                $coin = $this->auth->score + $shops['goods_coin_price'];
                $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => $coin]);
                if (!empty($ret)) {
                    //记录用户金币表
                    $data = [
                        'user_id' => $this->auth->id,
                        'before' => $this->auth->score,
                        'after' => $coin,
                        'coin' => $shops['goods_coin_price'],
                        'type' => 'recharge',
                        'order_id' => $shops['id'],
                        'create_time' => time()
                    ];
                    $res = Db::table('box_coin_record')->insert($data);
                    $cardjl = Db::table('box_card_list')->where('user_id', $this->auth->id)->find();
                    if (empty($cardjl)) {
                        Db::table('box_card_list')->insert([
                            'user_id' => $this->auth->id,
                            'card_id' => $s['card_id'],
                            'lqsm' => '新人首次兑换',
                            'status' => 0,
                            'lqtime' => time()
                        ]);
                        $this->success('回收成功', ['coin' => $this->auth->score, 'cck' => '1']);
                    }
                    $this->success('回收成功', ['coin' => $this->auth->score, 'cck' => '0']);
                } else {
                    $this->error('回收失败');
                }
            }
        }


    }
    
    /**
     * 用户签到
     */
    public function signList()
    {
        /**先查到是否有这个用户的签到记录*/
        $sign = Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->whereTime('signtime', 'yesterday')->find(); //查询昨天是否签到过
        $day_sign = Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->whereTime('signtime', 'today')->find(); //查询今天是否签到过
        $singjl = Db::table('box_sign')->where('id', 1)->find();
        $jl = [
            'user_id' => $this->auth->id,
            'before' => $this->auth->score,
            'type' => 'sing_jl',
            'create_time' => time()
        ];
        if($day_sign){
            $this->error('您今天已经签到过了');
        }
        /**如果有就进行判断时间差，然后处理签到次数*/
        if (!empty($sign)) {
            
            $da['signtime'] = time();
            $da['count'] = $sign['count'] + 1;
            /**这里还可以加一些判断连续签到几天然后加积分等等的操作*/
            //判断连续签到后为第几天
            // var_dump($da['count']);die;
            switch ($da['count']) {
                case 2:
                    $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_2'])]);
                    $jl['after'] = $this->auth->score + $singjl['sign_2'];
                    $jl['coin'] =  $singjl['sign_2'];
                    break;
                case 3:
                    $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_3'])]);
                    $jl['after'] = $this->auth->score + $singjl['sign_3'];
                    $jl['coin'] =  $singjl['sign_3'];
                    break;

                case 4:
                    $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_4'])]);
                    $jl['after'] = $this->auth->score + $singjl['sign_4'];
                    $jl['coin'] =  $singjl['sign_4'];
                    break;

                case 5:
                    $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_5'])]);
                    $jl['after'] = $this->auth->score + $singjl['sign_5'];
                    $jl['coin'] =  $singjl['sign_5'];
                    break;

                case 6:
                    $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_6'])]);
                    $jl['after'] = $this->auth->score + $singjl['sign_6'];
                    $jl['coin'] =  $singjl['sign_6'];
                    break;
                case 7:
                    $data = [
                        'boxfl_id' => $singjl['boxfl_id'],
                        'user_id' => $this->auth->id,
                        'status' => 1,
                        'add_time' => time()
                    ];
                    $ret = Db::table('box_yhbox')->insert($data);
                    break;
                default:
                    $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_1'])]);
                    $jl['after'] = $this->auth->score + $singjl['sign_1'];
                    $jl['coin'] =  $singjl['sign_1'];
                    break;
            }
            if (!empty($ret)) {
                $res = Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->update($da);
                if (empty($res)) {
                    $this->error('签到失败');
                } else {
                    Db::table('box_coin_record')->insert($jl);
                    $this->success('签到成功');
                }
            }
        } else {
            $data['user_id'] = $this->auth->id;
            $data['signtime'] = time();
            $data['count'] = 1;
            $res = Db::table('box_sign_jilu')->insert($data);
            if (!empty($res)) {
                /**成功就返回，或者处理一些程序，比如加积分*/
                $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_1'])]);
                $jl['after'] = $this->auth->score + $singjl['sign_1'];
                $jl['coin'] =  $singjl['sign_1'];
                Db::table('box_coin_record')->insert($jl);
                $this->success('签到成功');
            } else {
                $this->error('签到失败');
            }
        }
    }
    
    // /**
    //  * 用户签到
    //  */
    // public function signList()
    // {
    //     /**先查到是否有这个用户的签到记录*/
    //     $sign = Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->whereTime('signtime', 'yesterday')->find();
    //     $singjl = Db::table('box_sign')->where('id', 1)->find();
    //     $jl = [
    //         'user_id' => $this->auth->id,
    //         'before' => $this->auth->score,
    //         'type' => 'sing_jl',
    //         'create_time' => time()
    //     ];
    //     /**如果有就进行判断时间差，然后处理签到次数*/
    //     if (!empty($sign)) {
    //         /**昨天的时间戳时间范围*/
    //         $t = time();
    //         $last_start_time = mktime(0, 0, 0, date("m", $t), date("d", $t) - 1, date("Y", $t));
    //         $last_end_time = mktime(23, 59, 59, date("m", $t), date("d", $t) - 1, date("Y", $t));

    //         if ($sign['signtime'] > $last_start_time && $sign['signtime'] < $last_end_time) {
    //             $da['signtime'] = time();
    //             $da['count'] = $sign['count'] + 1;
    //             /**这里还可以加一些判断连续签到几天然后加积分等等的操作*/
    //             //判断连续签到后为第几天
    //             switch ($da['count']) {
    //                 case 2:
    //                     $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_2'])]);
    //                     $jl['after'] = $this->auth->score + $singjl['sign_2'];
    //                     $jl['coin'] =  $singjl['sign_2'];
    //                     break;
    //                 case 3:
    //                     $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_3'])]);
    //                     $jl['after'] = $this->auth->score + $singjl['sign_3'];
    //                     $jl['coin'] =  $singjl['sign_3'];
    //                     break;

    //                 case 4:
    //                     $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_4'])]);
    //                     $jl['after'] = $this->auth->score + $singjl['sign_4'];
    //                     $jl['coin'] =  $singjl['sign_4'];
    //                     break;

    //                 case 5:
    //                     $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_5'])]);
    //                     $jl['after'] = $this->auth->score + $singjl['sign_5'];
    //                     $jl['coin'] =  $singjl['sign_5'];
    //                     break;

    //                 case 6:
    //                     $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_6'])]);
    //                     $jl['after'] = $this->auth->score + $singjl['sign_6'];
    //                     $jl['coin'] =  $singjl['sign_6'];
    //                     break;
    //                 case 7:
    //                     $data = [
    //                         'boxfl_id' => $singjl['boxfl_id'],
    //                         'user_id' => $this->auth->id,
    //                         'status' => 1,
    //                         'add_time' => time()
    //                     ];
    //                     $ret = Db::table('box_yhbox')->insert($data);
    //                     break;
    //                 default:
    //                     break;
    //             }

    //             if (!empty($ret)) {
    //                 $res = Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->update($da);
    //                 if (empty($res)) {
    //                     $this->error('签到失败');
    //                 } else {
    //                     Db::table('box_coin_record')->insert($jl);
    //                     $this->success('签到成功');
    //                 }
    //             }
    //         } else {
    //             $this->error('今天已经签到过了哦');
    //         }
    //     } else {
    //         $data['user_id'] = $this->auth->id;
    //         $data['signtime'] = time();
    //         $data['count'] = 1;
    //         $res = Db::table('box_sign_jilu')->insert($data);
    //         if (!empty($res)) {
    //             /**成功就返回，或者处理一些程序，比如加积分*/
    //             $ret = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score + $singjl['sign_1'])]);
    //             $jl['after'] = $this->auth->score + $singjl['sign_1'];
    //             $jl['coin'] =  $singjl['sign_1'];
    //             Db::table('box_coin_record')->insert($jl);
    //             $this->success('签到成功');
    //         } else {
    //             $this->error('签到失败');
    //         }
    //     }
    // }
    /**
     * 用户签到记录获取
     */
    public function getSign()
    {
        $signjl = Db::table('box_sign')->where('id', 1)->find();
        $sign = Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->find();
        $zt_sign = Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->whereTime('signtime', 'yesterday')->find(); //查询昨天是否签到过
        $day_sign = Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->whereTime('signtime', 'today')->find(); //查询今天是否签到过
        $arr = [
            'sign' => $sign,
            'signjl' => $signjl
        ];
        if (empty($sign)) {
            $this->success('还未进行签到', $arr);
        } else {
            if(!empty($day_sign)){
                $arr['sign'] = $day_sign;
                echo json_encode(['code' => 1, 'msg' => '签到天数1', 'data' => $arr]);
                exit;
            }else if(!empty($zt_sign)){
                $arr['sign'] = $zt_sign;
                echo json_encode(['code' => 1, 'msg' => '签到天数1', 'data' => $arr]);
                exit;
            }else{
                Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->update(['count' => 0]);
                $this->success('已经断签了', $arr);
            }
            // $t = time();
            // $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            // $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
            // $last_start_time = mktime(0, 0, 0, date("m", $t), date("d", $t) - 1, date("Y", $t));
            // $last_end_time = mktime(23, 59, 59, date("m", $t), date("d", $t) - 1, date("Y", $t));
            // //签到时间大于昨天的开始时间 和 签到时间小于昨天的结束时间
            // if ($sign['signtime'] > $last_start_time && $sign['signtime'] < $last_end_time) {
            //     //这是时间在昨天内
            //     echo json_encode(['code' => 1, 'msg' => '签到天数1', 'data' => $arr]);
            //     exit;
            //     //签到时间大于今天的开始时间 和 签到时间小于今天的结束时间
            // } else if ($sign['signtime'] > $beginToday  && $sign['signtime'] < $endToday) {
            //     echo json_encode(['code' => 1, 'msg' => '签到天数1', 'data' => $arr]);
            //     exit;
            // } else {
            //     Db::table('box_sign_jilu')->where('user_id', $this->auth->id)->update(['count' => 0]);
            //     $this->success('已经断签了', $arr);
            // }
        }
    }
    /**
     * 获取未开启盲盒
     */
    public function getBox()
    {
        $boxlist = Db::table('box_yhbox')->where('user_id', $this->auth->id)->where('status', 1)->select();
        if (empty($boxlist)) {
            $this->error('未找到数据', $boxlist);
        }
        $id = [];
        foreach ($boxlist as $b) {
            $id[] = $b['boxfl_id'];
        }
        $box = Db::table('box_boxfl')->where('id', 'in', $id)->select();
        // print_r($box);
        foreach ($box as &$box_v) {
            $box_v['box_banner_images'] = cdnurl($box_v['box_banner_images'], true);
        }

        foreach ($boxlist as &$boxlist_v) {
            foreach ($box as $box_a) {
                if ($boxlist_v['boxfl_id'] == $box_a['id']) {
                    $boxlist_v['box_banner_images'] = $box_a['box_banner_images'];
                    $boxlist_v['box_name'] = $box_a['box_name'];
                }
            }
        }
        $this->success('查询成功', $boxlist);
    }
    /**
     * 获取用户优惠券
     */
    // public function getCop()
    // {
    //     $coulist = Db::table('box_coupon_list')->where('user_id', $this->auth->id)->where('status', 0)->select();
    //     $id = [];
    //     foreach ($coulist as $cou) {
    //         $id[] = $cou['coupon_id'];
    //     }
    //     $coupons = Db::table('box_coupon')->where('id', 'in', $id)->where('end_time', '>', time())->select();
    //     // $tmp = [];
    //     foreach ($coupons as &$cou_v) {
    //         foreach ($coulist as &$cou_s) {
    //             if ($cou_s['coupon_id'] == $cou_v['id']) {
    //                 $cou_s['amount'] = $cou_v['amount'];
    //                 $cou_s['mzamount'] = $cou_v['mzamount'];
    //                 $cou_s['typetag'] = $cou_v['typetag'];
    //                 $cou_s['couname'] = $cou_v['couponname'];
    //                 $cou_s['end_time'] = $cou_v['end_time'];
    //             }
    //         }
    //     }
    //     $this->success('我的优惠券', $coulist);
    // }
    /**
     * 获取用户优惠券
     */
    public function getCop()
    {
        $coulist = Db::table('box_coupon_list')->where('user_id', $this->auth->id)->where('status', 0)->select();
        $id = [];
        foreach ($coulist as $cou) {
            $id[] = $cou['coupon_id'];
        }
        $coupons = Db::table('box_coupon')->where('id', 'in', $id)->select();
        // $tmp = [];
        $my_coulist = [];
        foreach ($coupons as &$cou_v) {
            foreach ($coulist as $k=> &$cou_s) {
                if($cou_v['end_time']<time()){
                    // unset($coulist[$k]);
                    continue;
                }
                if ($cou_s['coupon_id'] == $cou_v['id']) {
                    $my_coulist[$k] = $cou_s;
                    $my_coulist[$k]['amount'] = $cou_v['amount'];
                    $my_coulist[$k]['mzamount'] = $cou_v['mzamount'];
                    $my_coulist[$k]['typetag'] = $cou_v['typetag'];
                    $my_coulist[$k]['couname'] = $cou_v['couponname'];
                    $my_coulist[$k]['end_time'] = $cou_v['end_time'];
                }
            }
        }
        $this->success('我的优惠券', $my_coulist);
    }
    /**
     * 兑换码使用
     */
    public function getKami()
    {
        $km = Db::table('box_kami')->where('kahao', input('kahao'))->find();
        if (empty($km)) {
            $this->error('兑换码不存在');
        }
        $kmjl = Db::table('box_kamilist')->where('user_id', $this->auth->id)->where('kami_id', $km['id'])->find();
        if (!empty($kmjl)) {
            $this->error('已经使用过了哦~');
        }
        //没有使用过进行记录
        $ret =  Db::table('box_user')->where('id', $this->auth->id)->update(['score' => $this->auth->score + $km['amount']]);
        if (empty($ret)) {
            $this->error('兑换失败');
        }
        Db::table('box_kamilist')->insert([
            'user_id' => $this->auth->id,
            'kami_id' => $km['id'],
            'sytime' => time()
        ]);
        Db::table('box_coin_record')->insert([
            'user_id' => $this->auth->id,
            'before' => $this->auth->score,
            'after' => $this->auth->score + $km['amount'],
            'coin' => $km['amount'],
            'type' => 'duihuan',
            'create_time' => time()
        ]);
        $this->success('兑换成功');
    }
    /**
     * 获取系统通知
     */
    public function getNotices()
    {
        $not = Db::table('box_notice')->select();
        $this->success('系统通知', $not);
    }
    /**
     * 查看对应系统通知
     * 
     */
    public function getNotice()
    {
        $n = Db::table('box_notice')->where('id', input('nid'))->find();
        $this->success('查询成功', $n);
    }
    //获取下级用户
    public function getTuand()
    {
        $tuandui = Db::table('box_user')->where('pid', $this->auth->id)->select();
        // foreach ($tuandui)
        $this->success('查询成功', $tuandui);
    }
    /*获取access_token,不能用于获取用户信息的token*/
    public  function getAccessToken()
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $appid = $s['mpappid'];
        $secret = $s['mpappkey'];

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret . "";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
        exit();
    }
    //图片合法性验证
    public function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);

        return $output;
        exit();
    }
    //  获取手机号
    public function getPhoneNumber()
    {
        $tmp = $this->getAccessToken();
        $tmptoken = json_decode($tmp);
        $token = $tmptoken->access_token;
        $data['code'] = input('code'); //前端获取code
        $url = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=$token";
        $info = $this->http_request($url, json_encode($data), 'json');
        // 一定要注意转json，否则汇报47001错误
        $tmpinfo = json_decode($info);

        $code = $tmpinfo->errcode;
        $phone_info = $tmpinfo->phone_info;
        //手机号
        $phoneNumber = $phone_info->phoneNumber;
        if ($code == '0') {
            echo json_encode(['code' => 1, 'msg' => '请求成功', 'phoneNumber' => $phoneNumber]);
            die();
        } else {
            echo json_encode(['code' => 2, 'msg' => '请求失败']);
            die();
        }
    }
    /**
     * 对接快递100
     */
    public function getWl()
    {
        //参数设置
        $s = Db::table('box_setting')->where('id', 1)->find();
        $key = $s['kdkey'];                        //客户授权key
        $customer = $s['kdcustomer'];                   //查询公司编号
        $param = array(
            'com' => input('com'),          //快递公司编码
            'num' => input('kddh'),   //快递单号
            'resultv2' => '1'             //开启行政区域解析
        );

        //请求参数
        $post_data = array();
        $post_data["customer"] = $customer;
        $post_data["param"] = json_encode($param);
        $sign = md5($post_data["param"] . $key . $post_data["customer"]);
        $post_data["sign"] = strtoupper($sign);

        $url = 'http://poll.kuaidi100.com/poll/query.do';    //实时查询请求地址

        $params = "";
        foreach ($post_data as $k => $v) {
            $params .= "$k=" . urlencode($v) . "&";              //默认UTF-8编码格式
        }
        $post_data = substr($params, 0, -1);
        //发送post请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $data = json_decode($result);
        $this->success('快递信息', $data);
    }
    
    /**
    * 商品多选转增
    **/
    public function echargez_arr(){
        
    }
    
    /**
     * 商品转赠
     */
    public function echargez()
    {
        $post = input();
        $prizeId = $post['prizeid'];
        // print_r($ids);
        // exit;
        // $prizeId = input('prizeid');
        $phone = input('mobile');
        $users =  Db::table('box_user')->where('mobile', $phone)->find();

        if(is_array($prizeId)){
            if (!empty($users)) {
                $mobile = $this->auth->mobile;
                // 更新盒柜
                if ($phone == $mobile) {
                    $this->error('不能转赠给自己!');
                }
                $prize = Db::table('box_prize_record')->where('id','in', $prizeId)->update(['user_id' => $users['id']]);
                if (empty($prize)) {
                    $this->error('转赠失败');
                }
                $shop = Db::table('box_prize_record')->where('id', 'in',$prizeId)->select();
                // $times = date("Y-m-d H:i:s", time());
                foreach ($shop as $shop_v){
                    $data = [
                        'lyuserid' => $this->auth->id,
                        'jsuserid' => $users['id'],
                        'lyname' => $mobile,
                        'jsname' => $phone,
                        'good_name' => $shop_v['goods_name'],
                        'good_image' => $shop_v['goods_image'],
                        'zztime' => time()
                    ];
                    $ret = Db::table('box_give')->insert($data);
                }
                // print_r($ret);
                // exit;
                if (!empty($ret)) {
                    $this->success('转赠成功');
                } else {
                    $this->error('转赠失效');
                }
            } else {
                $this->error('手机号未注册！');
            }
        }else{
            if (!empty($users)) {
                $mobile = $this->auth->mobile;
                // 更新盒柜
                if ($phone == $mobile) {
                    $this->error('不能转赠给自己!');
                }
                $prize = Db::table('box_prize_record')->where('id', $prizeId)->update(['user_id' => $users['id']]);
                if (empty($prize)) {
                    $this->error('转赠失败');
                }
                $shop = Db::table('box_prize_record')->where('id', $prizeId)->find();
                $times = date("Y-m-d H:i:s", time());
                $data = [
                    'lyuserid' => $this->auth->id,
                    'jsuserid' => $users['id'],
                    'lyname' => $mobile,
                    'jsname' => $phone,
                    'good_name' => $shop['goods_name'],
                    'good_image' => $shop['goods_image'],
                    'zztime' => time()
                ];
                $ret = Db::table('box_give')->insert($data);
                if (!empty($ret)) {
                    $this->success('转赠成功');
                } else {
                    $this->error('转赠失效');
                }
            } else {
                $this->error('手机号未注册！');
            }
        }

    }
    /**
     * 分佣明细
     */
    public function getFyjl()
    {
        $jl = Db::table('box_detailed')->where('user_id', $this->auth->id)->order('jltime DESC')->select();
        foreach ($jl as &$jl_v) {
            if ($jl_v['lytag'] == 'yaoqing') {
                $jl_v['ly'] = '邀请好友';
            } else if ($jl_v['lytag'] == 'kaihe') {
                $jl_v['ly'] = '好友开盒';
            }
            if ($jl_v['lxtag'] == 'yhq') {
                $jl_v['lx'] = '优惠券';
            } else if ($jl_v['lxtag'] == 'box') {
                $jl_v['lx'] = '盲盒';
            } else if ($jl_v['lxtag'] == 'coin') {
                $jl_v['lx'] = '钻石';
            }
            $jl_v['jltime'] = date('Y-m-d H:i:s', $jl_v['jltime']);
        }
        $this->success('分佣明细', $jl);
    }
    /**
     * 转赠记录
     */
    public function getZz()
    {
        $zzjl = Db::table('box_give')->where('lyuserid', $this->auth->id)->whereOr('jsuserid', $this->auth->id)->order('zztime DESC')->select();
        foreach ($zzjl as &$zzjl_v) {
            if ($zzjl_v['lyuserid'] == $this->auth->id) {
                $zzjl_v['status'] = '赠送好友';
            } else if ($zzjl_v['jsuserid'] == $this->auth->id) {
                $zzjl_v['status'] = '好友赠送';
            }
            $zzjl_v['zztime'] = date('Y-m-d H:i:s', $zzjl_v['zztime']);
        }
        $this->success('转赠记录', $zzjl);
    }
    /**
     * 申请代理
     */
    public function sqdaili()
    {
        $user = Db::table('box_user')->where('id', $this->auth->id)->find();
        if ($user['isdl'] == 1) {
            $this->error('您已经是代理了哦');
        }
        $sqjl = Db::table('box_daili')->where('user_id', $this->auth->id)->where('status', 'sh')->select();
        $jjjl = Db::table('box_daili')->where('user_id', $this->auth->id)->where('status', 'jj')->select();
        if (!empty($sqjl)) {
            $this->error('您已经申请过了哦');
        }
        if (!empty($jjjl)) {
            Db::table('box_daili')->where('user_id', $this->auth->id)->where('status', 'jj')->update(['status' => sh]);
        }
        $ret = Db::table('box_daili')->insert([
            'user_id' => $this->auth->id,
            'status' => 'sh',
            'sqtime' => time()
        ]);
        if (!empty($ret)) {
            $this->success('申请成功');
        } else {
            $this->error('申请失败');
        }
    }
    /*
    * 查询代理申请状态
    */
    public function getDaili()
    {
        $sh = Db::table('box_daili')->where('user_id', $this->auth->id)->where('status', 'sh')->select();
        $jj = Db::table('box_daili')->where('user_id', $this->auth->id)->where('status', 'jj')->select();
        if (!empty($sh)) {
            $status = 1;
        } else if (!empty($jj)) {
            $status = 2;
        } else {
            $status = 0;
        }
        $this->success('状态', $status);
    }
    public function getAdminUserImg()
    {
        $this->success('获取成功',['img'=>Db::table('box_admin')->where('id',1)->value('avatar')]);
        
    }


    /*
     * google授权登录
     * @param string nickName 昵称
     * @param string unionid unionid
     * @param string openid openid
     * @param string email 邮箱（用户）
    */
    public  function  googleLogin()
    {
        $param = $this->request->param();
        $nickName = $param['nickName'];
        $unionId = $param['unionid'];
        $openid=$param['openid'];
        $email=$param['email'];
        if (!$email) {
            $this->error('google登录授权失败m,请稍后再试');
        }
        $user = \app\common\model\User::getByEmail($email);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error('账号被锁定');
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($nickName, Random::alnum(), $email, '', ['google_unionid' => $unionId,'google_openid'=>$openid]);
        }
        if ($ret) {
            $data = $this->auth->getUserinfo();
            $data['score'] = $data['score'] ? floatval($data['score']) : 0;
            $data['avatar'] = $data['avatar'] ? cdnurl($data['avatar'], true) : '';
            unset($data['id']);
            unset($data['user_id']);
            unset($data['createtime']);
            unset($data['expiretime']);
            unset($data['expires_in']);
            $this->success('登录成功', $data);
        } else {
            $this->error($this->auth->getError());
        }
    }


}
