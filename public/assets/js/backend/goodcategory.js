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
          index_url: "goodcategory/index" + location.search,
          add_url: "goodcategory/add",
          edit_url: "goodcategory/edit",
          del_url: "goodcategory/del",
          multi_url: "goodcategory/multi",
          import_url: "goodcategory/import",
          table: "goodcategory",
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
            { field: "pid", title: __("Pid") },
            // {field: 'type', title: __('Type'), operate: 'LIKE'},
            { field: "flname", title: __("Flname"), operate: "LIKE" },
            {
              field: "image",
              title: __("Image"),
              operate: false,
              events: Table.api.events.image,
              formatter: Table.api.formatter.image,
            },
            {
              field: "creattime",
              title: __("Creattime"),
              operate: "RANGE",
              addclass: "datetimerange",
              autocomplete: false,
              formatter: Table.api.formatter.datetime,
            },
            {
              field: "categoryswitch",
              title: __("Categoryswitch"),
              searchList: { 1: __("Yes"), 0: __("No") },
              table: table,
              formatter: Table.api.formatter.toggle,
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
