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
          index_url: "prize/record/index" + location.search,
          // add_url: 'prize/record/add',
          // edit_url: 'prize/record/edit',
          // del_url: 'prize/record/del',
          multi_url: "prize/record/multi",
          import_url: "prize/record/import",
          table: "prize_record",
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
            { field: "id", title: __("Id") },
            { field: "boxfl_id", title: __("Boxfl_id") },
            { field: "order_id", title: __("Order_id"), operate: "LIKE" },
            {
              field: "out_trade_no",
              title: __("Out_trade_no"),
              operate: "LIKE",
            },
            { field: "user_id", title: __("User_id") },
            { field: "goods_id", title: __("Goods_id") },
            { field: "goods_name", title: __("Goods_name"), operate: "LIKE" },
            {
              field: "goods_image",
              title: __("Goods_image"),
              operate: false,
              events: Table.api.events.image,
              formatter: Table.api.formatter.image,
            },
            { field: "goods_coin_price", title: __("Goods_coin_price") },
            {
              field: "goods_rmb_price",
              title: __("Goods_rmb_price"),
              operate: "BETWEEN",
            },
            {
              field: "status",
              title: __("Status"),
              searchList: {
                bag: __("Status bag"),
                exchange: __("Status exchange"),
                delivery: __("Status delivery"),
                received: __("Status received"),
              },
              formatter: Table.api.formatter.status,
            },
            {
              field: "exchange_time",
              title: __("Exchange_time"),
              operate: "RANGE",
              addclass: "datetimerange",
              autocomplete: false,
              formatter: Table.api.formatter.datetime,
            },
            {
              field: "delivery_time",
              title: __("Delivery_time"),
              operate: "RANGE",
              addclass: "datetimerange",
              autocomplete: false,
              formatter: Table.api.formatter.datetime,
            },
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
              field: "update_time",
              title: __("Update_time"),
              operate: "RANGE",
              addclass: "datetimerange",
              autocomplete: false,
              formatter: Table.api.formatter.datetime,
            },
            {
              field: "delete_time",
              title: __("Delete_time"),
              operate: "RANGE",
              addclass: "datetimerange",
              autocomplete: false,
              formatter: Table.api.formatter.datetime,
            },
            {
              field: "hstime",
              title: __("Hstime"),
              operate: "RANGE",
              addclass: "datetimerange",
              autocomplete: false,
              formatter: Table.api.formatter.datetime,
            },
            // {
            //   field: "operate",
            //   title: __("Operate"),
            //   table: table,
            //   events: Table.api.events.operate,
            //   formatter: Table.api.formatter.operate,
            // },
          ],
        ],
      });

      // 为表格绑定事件
      Table.api.bindevent(table);
    },
    // add: function () {
    //   Controller.api.bindevent();
    // },
    // edit: function () {
    //   Controller.api.bindevent();
    // },
    api: {
      bindevent: function () {
        Form.api.bindevent($("form[role=form]"));
      },
    },
  };
  return Controller;
});
