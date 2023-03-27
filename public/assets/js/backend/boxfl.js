define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'boxfl/index' + location.search,
                    add_url: 'boxfl/add',
                    edit_url: 'boxfl/edit',
                    del_url: 'boxfl/del',
                    multi_url: 'boxfl/multi',
                    import_url: 'boxfl/import',
                    table: 'boxfl',
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
                        {field: 'box_name', title: __('Box_name'), operate: 'LIKE'},
                        {field: 'box_label', title: __('Box_label'), operate: 'LIKE'},
                        {field: 'probability_gj', title: __('Probability_gj'), operate:'BETWEEN'},
                        {field: 'probability_xy', title: __('Probability_xy'), operate:'BETWEEN'},
                        {field: 'probability_ss', title: __('Probability_ss'), operate:'BETWEEN'},
                        {field: 'probability_cs', title: __('Probability_cs'), operate:'BETWEEN'},
                        {field: 'boxswitch', title: __('Boxswitch'), table: table, formatter: Table.api.formatter.toggle},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
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
