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
          index_url: "detailed.fenyong/index",
          look_user: "detailed/index",
          table: "detailed",
        },
      });

      var table = $("#table");

      // 初始化表格
      table.bootstrapTable({
        url: $.fn.bootstrapTable.defaults.extend.index_url,
        pk: "id",
        sortName: "user.id",
        columns: [
          [
            { checkbox: true },
            { field: "id", title: __("Id"), sortable: true , operate: false},
            { field: "username", title: __("Username"), operate: false},
            { field: "nickname", title: __("Nickname"), operate: false},
            { field: "mobile", title: '手机', operate: false },
            {
              field: "avatar",
              title: '头像',
              events: Table.api.events.image,
              formatter: Table.api.formatter.image,
              operate: false,
            },
            { field: "fyzs", title: '返佣合计' , operate: false},
            {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
            buttons: [{
                name: 'look_user',
                text: '查看详情',
                icon: 'fa fa-list',
                classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                url: 'detailed/index?look_type=user'
              }],
            formatter: function(value, row, index){
                var that = $.extend({}, this);
                var table = $(that.table).clone(true);
                //根据状态判断是否要显示该按钮
                that.table = table;
                return Table.api.formatter.operate.call(that, value,row,index)
            }
                
            }
          ],
        ],
      });

      // 为表格绑定事件
      Table.api.bindevent(table);
    },
    look_user: function (){
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
