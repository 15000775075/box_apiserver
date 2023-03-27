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
          index_url: "mhgoods/index" + location.search,
          add_url: "mhgoods/add",
          edit_url: "mhgoods/edit",
          del_url: "mhgoods/del",
          multi_url: "mhgoods/multi",
          import_url: "mhgoods/import",
          table: "mhgoods",
        },
      });

      var table = $("#table");

      // 初始化表格
      table.bootstrapTable({
        url: $.fn.bootstrapTable.defaults.extend.index_url,
        pk: "id",
        sortName: "id",
        fixedColumns: true,
        fixedRightNumber: 1,
        columns: [
          [
            { checkbox: true },
            // { field: "id", title: __("Id") },
            {
              field: "boxfl.box_name",
              title: __("Boxfl.box_name"),
              operate: "LIKE",
            },
            { field: "goods_name", title: __("Goods_name"), operate: "LIKE" },
            // {field: 'boxfl_id', title: __('Boxfl_id')},

            // { field: "goods_stock", title: __("Goods_stock") },
            { field: "goods_pirce", title: __("Goods_pirce") },
            {
              field: "delivery_fee",
              title: __("Delivery_fee"),
              operate: "BETWEEN",
            },
            {
              field: "create_time",
              title: __("Create_time"),
              operate: "RANGE",
              addclass: "datetimerange",
              autocomplete: false,
              formatter: Table.api.formatter.datetime,
            },
            {
              field: "tag",
              title: __("Tag"),
              searchList: {
                normal: __("Tag normal"),
                rare: __("Tag rare"),
                supreme: __("Tag supreme"),
                legend: __("Tag legend"),
              },
              formatter: Table.api.formatter.flag,
            },
            { field: "luckycoin", title: __("Luckycoin"), operate: "BETWEEN" },

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
