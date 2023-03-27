define(["jquery", "bootstrap", "backend", "table", "form"], function (
  $,
  undefined,
  Backend,
  Table,
  Form
) {
  var Controller = {
    index: function () {
      // 初始化表格参数配置
      Table.api.init({
        extend: {
          index_url: "sign/index" + location.search,
          add_url: "sign/add",
          edit_url: "sign/edit",
          del_url: "sign/del",
          multi_url: "sign/multi",
          import_url: "sign/import",
          table: "sign",
        },
      });

      var table = $("#table");

      // 初始化表格
      table.bootstrapTable({
        url: $.fn.bootstrapTable.defaults.extend.index_url,
        pk: "id",
        sortName: "id",
        columns: [
          [
            { checkbox: true },
            { field: "id", title: __("Id") },
            { field: "sign_1", title: __("Sign_1") },
            { field: "sign_2", title: __("Sign_2") },
            { field: "sign_3", title: __("Sign_3") },
            { field: "sign_4", title: __("Sign_4") },
            { field: "sign_5", title: __("Sign_5") },
            { field: "sign_6", title: __("Sign_6") },
            // {field: 'boxfl_id', title: __('Boxfl_id')},
            {
              field: "boxfl.box_name",
              title: __("Boxfl.box_name"),
              operate: "LIKE",
            },
            {
              field: "operate",
              title: __("Operate"),
              table: table,
              events: Table.api.events.operate,
              formatter: Table.api.formatter.operate,
            },
          ],
        ],
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
      },
    },
  };
  return Controller;
});
