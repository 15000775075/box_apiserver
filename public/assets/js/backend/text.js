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
          index_url: "text/index" + location.search,
          add_url: "text/add",
          edit_url: "text/edit",
          //   del_url: "text/del",
          multi_url: "text/multi",
          import_url: "text/import",
          table: "text",
        },
      });

      var table = $("#table");

      // 初始化表格
      table.bootstrapTable({
        url: $.fn.bootstrapTable.defaults.extend.index_url,
        pk: "id",
        sortName: "weigh",
        columns: [
          [
            { checkbox: true },
            { field: "id", title: __("Id") },
            // { field: "title", title: __("Title"), operate: "LIKE" },
            { field: "desc", title: __("Desc"), operate: "LIKE" },
            // {field: 'text', title: __('Text')},
            // {
            //   field: "type",
            //   title: __("Type"),
            //   searchList: { rich_text: __("Rich_text"), text: __("Text") },
            //   formatter: Table.api.formatter.normal,
            // },
            // { field: "public", title: __("Public") },
            // { field: "weigh", title: __("Weigh"), operate: false },
            {
              field: "update_time",
              title: __("Update_time"),
              operate: "RANGE",
              addclass: "datetimerange",
              autocomplete: false,
              formatter: Table.api.formatter.datetime,
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
