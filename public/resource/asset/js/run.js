require([
    'jquery',
    'app',
    'vue',
    'jquery-ui',
    'select2',
    'layer',
    'bootstrap',
    'adminlte',
    'bootstrap-validator',
    // 'jquery-slimscroll',
    'jquery-datetimepicker',
    'ueditor'
], function($, app, Vue){

    //主菜单组件
    Vue.component("menu-list",{
        template:"#menu-template",
        props:["param","is_bar","is_child"],
        data:function(){
            return {app:app};
        },
        methods : {
            isChild : function(item){
                return item.child && item.child.length>0 ? true : false;
            },
            request : function (){
                var action = $(this);
                if(action.attr('data-request-type') == 1){
                    var button = {
                        '确定': function(){
                            if(app.loading == true){
                                app.alert('当前有请求还未完成，请稍后再试...', {code: 1002});
                                return false;
                            }
                            app.shade('show');
                            app.loading = true;
                            $.post(action.attr('href'), {}, function(data, status){
                                if(status == 'success' && data.code != undefined){
                                    if(data.code == 0){
                                        app.alert(data.msg, { code: 0, onHidden: function(){ window.location.reload(); } });
                                    }else{
                                        app.alert(data.msg, {code: 1000});
                                    }
                                }else{
                                    app.alert('请求失败', {code: 1002});
                                }
                                app.loading = false;
                                app.shade('hide');
                            });
                        }
                    };
                    app.alert('确定“' + action.attr('data-title') + '”？', {button: button, code: 1001});
                    return false;
                }
            }
        }
    });

    //主菜单
    var menu = new Vue({
        el: '#main-menu',
        data: {
            data : []
        },
        computed : {},
        methods: {}
    });

    //内容头
    var header = new Vue({
        el: '#content-header',
        data: {
            checked : [],
            current : []
        },
        computed : {},
        methods: {}
    });

    //表格
    var table = new Vue({
        el: '#table',
        data: {
            app:app,
            option : []
        },
        computed : {
            search : function(){ return $('div#search'); },
            table : function(){ return $('div#table'); }
        },
        mounted : function(){
            this.orderInit();
            this.checkInit();
            this.selectInit();
        },
        methods: {
            //排序
            orderInit : function (){
                var object = this;
                var field = (this.table.attr('data-order') || '').split(' ', 2)[0];
                var sort = (this.table.attr('data-order') || '').split(' ', 2)[1];

                //初始化
                var order = this.table.find('[order-field]');
                order.find('.order-icon').remove();
                order.removeClass('order-asc').removeClass('order-desc');
                order.each(function(k, v){
                    var order = $(v);
                    //响应初始化
                    var icon = 'fa-sort';
                    var color = 'D6D6D6';
                    if(order.attr('order-field') == field){
                        order.addClass('order-' + sort);
                        icon = 'fa-sort-'+sort;
                        color = '999999';
                    }
                    //绑定事件
                    $(
                        '<div class="order-icon" style="float:right;cursor:pointer;"><i class="fa '+icon+'" style="color:#'+color+';"></i></div>'
                    ).on('click', object.orderEvent).appendTo(order);
                });
            },
            orderEvent : function(event){
                var th = $(event.currentTarget).closest('th');
                var field = th.attr('order-field') + (th.hasClass('order-asc') ? ' desc' : ' asc');
                //表单提交
                table.search.find('input[name=order]').val(field);
                table.search.find('form').submit();
            },
            //全选事件
            checkInit : function(){
                //全选事件
                this.table.find('span.table-checkall').on('click', function(event){
                    if($(event.currentTarget).hasClass('selected')){
                        table.table.find('tbody').find('tr').removeClass('selected');
                        $(event.currentTarget).removeClass('selected');
                    }else{
                        table.table.find('tbody').find('tr').addClass('selected');
                        $(event.currentTarget).addClass('selected');
                    }
                });
                //反选事件
                this.table.find('span.table-checkinvert').on('click', function(){
                    table.table.find('tbody').find('tr').each(function(k, v){
                        $(v).hasClass('selected') ? $(v).removeClass('selected') : $(v).addClass('selected');
                    });
                });
            },
            //选中事件
            selectInit: function(){
                this.table.find('tbody').find('tr').on('click', function(event){
                    $(event.currentTarget).hasClass('selected')
                        ? $(event.currentTarget).removeClass('selected')
                        : $(event.currentTarget).addClass('selected');
                });
            },
            //选项事件
            optionEvent : function(event){
                //参数
                var action = $(event.currentTarget);
                var url = action.attr('href');
                var request = action.attr('request');
                var name = (action.attr('param') || '').split(':')[0]  || 'id';
                var type = (action.attr('param') || '').split(':')[1] || '0';
                var length = this.table.find('tbody').find('tr.selected').length;
                var extra = action.attr('extra') ? $.parseJSON(action.attr('extra')) : [];
                var param = app.helper.jsonMerge(extra, app.helper.input());

                var value = '';
                this.table.find('tbody').find('tr.selected').each(function(k, v){
                    value += (k == 0 ? '' : ',') + $(v).attr('data-' + name);
                });
                param[name] = value;
                param = app.helper.filterObject(param);
                //参数限制
                switch(type){
                    //必须一个
                    case '1':
                        if(length != 1){ app.alert('选择1条数据'); return false; }
                        break;
                    //至少一个
                    case '2':
                        if(length <= 0){ app.alert('至少选择1条数据'); return false; }
                        break;
                    //没有或一个
                    case '3':
                        if(length != 0 && length != 1){ app.alert('不选择或选择1条数据'); return false; }
                        break;
                    //无限制
                    case '0':
                    default:
                }
                //普通页面和ajax提交
                switch(request){
                    //ajax提交
                    case '1':
                        var $button = {
                            '确定': function(){
                                if(app.loading == true){
                                    app.alert('当前有请求还未完成，请稍后再试...', {code: 1002});
                                    return false;
                                }
                                app.shade('show');
                                app.loading = true;
                                $.post(url, param, function(data, status){
                                    if(status == 'success' && data.code != undefined){
                                        if(data.code == 0){
                                            app.alert(data.msg, {
                                                code: 0, onHidden: function(){
                                                    window.location.reload();
                                                }
                                            });
                                        }else{
                                            app.alert(data.msg, {code: 1000});
                                        }
                                    }else{
                                        app.alert('请求失败', {code: 1002});
                                    }
                                    app.loading = false;
                                    app.shade('hide');
                                });
                            }
                        };
                        app.alert('确定“' + action.attr('title') + '”？', {button: $button, code: 1001});
                        break;
                    //普通页面
                    case '0':
                    default:
                        app.shade('show');
                        var params = Object.keys(param).map(function(key){
                            return encodeURIComponent(key) + "=" + encodeURIComponent(param[key]);
                        }).join("&");
                        window.location.href = url + (params ? '?' + params : '');
                        break;
                }
                return false;
            }
        }
    });

    //插件初始化
    $(".select2").select2();
    //datetimepicker
    $.datetimepicker.setLocale('ch');
    //bootstrap validator
    $.extend($.fn.bootstrapValidator.DEFAULT_OPTIONS, {
        //live : 'disabled',
    });
    $(".form-validator").bootstrapValidator();

    //工具栏
    app.toolbar.init();
    //请求菜单栏
    var url = module=='admin' ? "/auth/get_menu" : "/admin/auth/get_menu";
    $.post(app.href("/auth/get_menu"),{url:window.location.pathname},function(data,status){
        if(status=='success' && data.code != undefined){
            menu.data = data.data.menu;
            table.option = data.data.option;
            header.checked = data.data.checked;
            header.current = data.data.current;
        }
    });

});