define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'shoporder/index' + location.search,
                    add_url: 'shoporder/add',
                    edit_url: 'shoporder/edit',
                    del_url: 'shoporder/del',
                    multi_url: 'shoporder/multi',
                    import_url: 'shoporder/import',
                    table: 'shoporder',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'shop_id', title: __('Shop_id')},
                        {field: 'shop_name', title: __('Shop_name'), operate: 'LIKE',width:'100'},
                        {field: 'is_dc_text', title: '是否导出过', operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"used":__('Status used'),"refund":__('Status refund'),"ywc":__('Status ywc'),"undei":__('Status undei')}, formatter: Table.api.formatter.status},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'num', title: __('Num')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'address', title: __('Address'), operate: 'LIKE'},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'pay_method', title: __('Pay_method'), searchList: {"wechat":__('Pay_method wechat'),"alipay":__('Pay_method alipay'),"xiguazi":__('Pay_method xiguazi'),"lucyk":__('Pay_method lucyk'),"sqfh":__('Pay_method sqfh')}, formatter: Table.api.formatter.normal},
                        {field: 'pay_coin', title: __('Pay_coin'), operate:'BETWEEN'},
                        {field: 'pay_rmb', title: __('Pay_rmb'), operate:'BETWEEN'},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'out_trade_no', title: __('Out_trade_no'), operate: 'LIKE'},
                        {field: 'transaction_id', title: __('Transaction_id'), operate: 'LIKE'},
                        {field: 'alipay_trade_no', title: __('Alipay_trade_no'), operate: 'LIKE'},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'desc', title: __('Desc'), operate: 'LIKE'},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'delete_time', title: __('Delete_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'terminal', title: __('Terminal')},
                        {field: 'delivery_fee', title: __('Delivery_fee'), operate:'BETWEEN'},
                        {field: 'kddh', title: __('Kddh'), operate: 'LIKE'},
                        {field: 'kdgs', title: __('Kdgs'), searchList: {"yuantong":__('Kdgs yuantong'),"yunda":__('Kdgs yunda'),"shentong":__('Kdgs shentong'),"zhongtong":__('Kdgs zhongtong'),"jtexpress":__('Kdgs jtexpress'),"shunfeng":__('Kdgs shunfeng'),"youzhengguonei":__('Kdgs youzhengguonei'),"ems":__('Kdgs ems'),"jd":__('Kdgs jd'),"debangkuaidi":__('Kdgs debangkuaidi')}, formatter: Table.api.formatter.normal},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
            $('.dropdown-menu > li').click(function() {
    // 			this.style.background = 'red';
                // alert(1);
                var selectedIds = Table.api.selectedids(table, true);
                // alert(selectedIds);
                // return;
                // if(selectedIds!=''){
                    $.ajax({
                        url: 'shoporder/set_dc',
                        type: 'POST',
                        data: {id: selectedIds},
                        dataType: 'json'
                    });
                // }
    		});
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
