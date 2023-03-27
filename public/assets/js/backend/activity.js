define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity/index' + location.search,
                    add_url: 'activity/add',
                    edit_url: 'activity/edit',
                    del_url: 'activity/del',
                    multi_url: 'activity/multi',
                    import_url: 'activity/import',
                    table: 'activity',
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
                        {field: 'activityname', title: __('Activityname'), operate: 'LIKE'},
                        {field: 'activityimage', title: __('Activityimage'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'tcimage', title: __('Tcimage'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'boxfl_id', title: __('Boxfl_id')},
                        {field: 'tcswitch', title: __('Tcswitch'), table: table, formatter: Table.api.formatter.toggle},
                        {field: 'boxfl.box_name', title: __('Boxfl.box_name'), operate: 'LIKE'},
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
