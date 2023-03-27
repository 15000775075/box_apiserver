define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'detailed/index' + location.search,
                    add_url: 'detailed/add',
                    edit_url: 'detailed/edit',
                    del_url: 'detailed/del',
                    multi_url: 'detailed/multi',
                    import_url: 'detailed/import',
                    table: 'detailed',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'nickname', title: '昵称'},
                        {field: 'mobile', title: '手机号'},
                        {field: 'lytag', title: __('Lytag'), searchList: {"yaoqing":__('Lytag yaoqing'),"kaihe":__('Lytag kaihe')}, formatter: Table.api.formatter.flag},
                        {field: 'lxtag', title: __('Lxtag'), searchList: {"yhq":__('Lxtag yhq'),"box":__('Lxtag box'),"coin":__('Lxtag coin')}, formatter: Table.api.formatter.flag},
                        {field: 'boxfl_id', title: __('Boxfl_id')},
                        {field: 'coupon_id', title: __('Coupon_id')},
                        {field: 'coinnum', title: __('Coinnum')},
                        {field: 'laiyuan', title: __('Laiyuan'), operate: 'LIKE'},
                        {field: 'jltime', title: __('Jltime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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
