define(function(){
    return {
        //请求中标识
        loading: false,
        /**
         * 模态框
         * @param msg
         * @param option
         */
        alert: function(msg, option){
            //默认配置
            var $option = {
                code: 1000, //状态码
                msg: '', //消息内容
                title: null, //标题
                prompt: 0, //是否有输入框
                button: {}, //操作按钮
                size: 'sm', //尺寸
                time: 0, //停留秒数，0为不限时
                modal: $('#modal-alert'), //模态框jquery对象
                input: function(){ //获取输入框内容
                    return this.modal.find('.modal-body').find('textarea').val();
                },
                onShow: null,
                onShown: null,
                onHide: null,
                onHidden: null,
                onLoaded: null,
            };
            if(typeof msg == "object"){
                $.extend($option, msg);
            }else{
                $option.msg = msg ? msg : '';
                option && $.extend($option, option);
            }
            //模态框
            var $code = {
                0: '<i class="fa fa-check text-green"></i>',
                1000: '<i class="fa fa-close text-red"></i>',
                1001: '<i class="fa fa-bell-o"></i>',
                1002: '<i class="fa fa-exclamation-triangle text-yellow"></i>',
            };
            //标题
            var $title = ($code[$option.code] || $code[1000]) + ($option.title === null ? '' : ' ' + $option.title);
            $option.modal.find('.modal-title').html($title);
            //内容
            $body = $option.msg;
            if($option.prompt){
                $body += '<textarea class="form-control" rows="4" placeholder="Enter ..." ></textarea>';
            }
            $option.modal.find('.modal-body').html($body);
            //按钮
            $option.modal.find('.modal-footer').html('<button type="button" class="btn btn-default " data-dismiss="modal">关闭</button>');
            $.each($option.button, function(k, v){
                var $button = $('<button type="button" class="btn btn-primary " data-dismiss="modal">' + k + '</button>');
                v && $button.on('click', function(e){ setTimeout(function(){ v($option, e); }, 500); });
                $option.modal.find('.modal-footer').append($button);
            });
            //大小
            $option.modal.find('.modal-dialog').removeClass('modal-sm', 'modal-lg').addClass($option.size && 'modal-' + $option.size);
            //事件
            $option.modal.off();
            $option.onShow && $option.modal.on('show.bs.modal', function(e){ $option.onShow($option, e); });
            $option.onShown && $option.modal.on('shown.bs.modal', function(e){ $option.onShown($option, e); });
            $option.onHide && $option.modal.on('hide.bs.modal', function(e){ $option.onHide($option, e); });
            $option.onHidden && $option.modal.on('hidden.bs.modal', function(e){ $option.onHidden($option, e); });
            $option.onLoaded && $option.modal.on('loaded.bs.modal', function(e){ $option.onLoaded($option, e); });
            //定时消失
            $option.time && setTimeout(function(){ $option.modal.modal('hide'); }, $option.time * 1000);
            //显示模态框
            $option.modal.modal();
        },
        /**
         * 遮罩层
         */
        shade: function(option){
            $('#modal-shade').modal(option || 'toggle');
        },
        /**
         * 工具栏
         */
        toolbar: {
            init: function(){
                this.fullscreen();
                this.setSkin();
                this.fixedLayout();
                this.boxedLayout();
                this.toggleMenubar();
                this.keepToolbar();
                this.darkToolbar();
            },
            //全屏
            fullscreen: function(){
                $('#fullscreen').on('click', function(){
                    if(document.fullscreen || document.webkitIsFullScreen || document.mozFullScreen){
                        var exit_fullscreen = document.exitFullscreen || document.webkitCancelFullScreen || document.mozCancelFullScreen;
                        exit_fullscreen && exit_fullscreen.call(document);
                    }else{
                        var element = document.body;
                        element.style.width = '100%';
                        var fullscreen = element.requestFullScreen || element.webkitRequestFullScreen || element.mozRequestFullScreen || element.msRequestFullScreen;
                        fullscreen && fullscreen.call(element);
                    }
                });
            },
            //选择皮肤
            setSkin: function(){
                var tag_a = $('#control-sidebar-skin').find('a');
                var tag_body = $('body');
                var skin = window.localStorage.getItem('toolbar_skin') || 'skin-blue';
                tag_body.addClass(skin);
                tag_a.on('click', function(){
                    var data_skin = $(this).attr('data-skin');
                    if(data_skin){
                        $.each(tag_a, function(){
                            var this_skin = $(this).attr('data-skin');
                            this_skin && tag_body.removeClass(this_skin);
                        });
                        tag_body.addClass(data_skin);
                        window.localStorage.setItem('toolbar_skin', data_skin);
                    }
                });
            },
            //固定布局
            fixedLayout: function(){
                var input = $('#control-sidebar-setting').find('input[name=fixed_layout]');
                var item = window.localStorage.getItem('toolbar_fixed_layout');
                var body = $('body');
                if(item){
                    input.prop('checked', true);
                    body.addClass('fixed');
                }
                input.on('change', function(){
                    if(input.prop('checked')){
                        body.addClass('fixed');
                        window.localStorage.setItem('toolbar_fixed_layout', 1);
                    }else{
                        body.removeClass('fixed');
                        window.localStorage.removeItem('toolbar_fixed_layout');
                    }
                });
            },
            //盒子布局
            boxedLayout: function(){
                var input = $('#control-sidebar-setting').find('input[name=boxed_layout]');
                var item = window.localStorage.getItem('toolbar_boxed_layout');
                var body = $('body');
                if(item){
                    input.prop('checked', true);
                    body.addClass('layout-boxed');
                }
                input.on('change', function(){
                    if(input.prop('checked')){
                        body.addClass('layout-boxed');
                        window.localStorage.setItem('toolbar_boxed_layout', 1);
                    }else{
                        body.removeClass('layout-boxed');
                        window.localStorage.removeItem('toolbar_boxed_layout');
                    }
                });
            },
            //切换菜单
            toggleMenubar: function(){
                var input = $('#control-sidebar-setting').find('input[name=toggle_menubar]');
                var item = window.localStorage.getItem('toolbar_toggle_menubar');
                var body = $('body');
                if(item){
                    input.prop('checked', true);
                    body.addClass('sidebar-collapse');
                }
                input.on('change', function(){
                    if(input.prop('checked')){
                        body.addClass('sidebar-collapse');
                        window.localStorage.setItem('toolbar_toggle_menubar', 1);
                    }else{
                        body.removeClass('sidebar-collapse');
                        window.localStorage.removeItem('toolbar_toggle_menubar');
                    }
                });
            },
            //固定工具栏
            keepToolbar: function(){
                var input = $('#control-sidebar-setting').find('input[name=keep_toolbar]');
                var item = window.localStorage.getItem('toolbar_keep_toolbar');
                var body = $('body');
                if(item){
                    input.prop('checked', true);
                    body.addClass('control-sidebar-open');
                }
                input.on('change', function(){
                    if(input.prop('checked')){
                        body.addClass('control-sidebar-open');
                        window.localStorage.setItem('toolbar_keep_toolbar', 1);
                    }else{
                        body.removeClass('control-sidebar-open');
                        window.localStorage.removeItem('toolbar_keep_toolbar');
                    }
                });
            },
            //亮色工具栏
            darkToolbar: function(){
                var input = $('#control-sidebar-setting').find('input[name=dark_toolbar]');
                var item = window.localStorage.getItem('toolbar_dark_toolbar');
                var toolbar = $('aside.control-sidebar');
                if(item){
                    input.prop('checked', true);
                    toolbar.removeClass('control-sidebar-light').addClass('control-sidebar-dark');
                }
                input.on('change', function(){
                    if(input.prop('checked')){
                        toolbar.removeClass('control-sidebar-light').addClass('control-sidebar-dark');
                        window.localStorage.setItem('toolbar_dark_toolbar', 1);
                    }else{
                        toolbar.removeClass('control-sidebar-dark').addClass('control-sidebar-light');
                        window.localStorage.removeItem('toolbar_dark_toolbar');
                    }
                });
            },
        },
        /**
         * 助手方法
         */
        helper: {
            //接收url参数
            input: function(name){
                var query = window.location.search.substring(1);
                if(undefined == name){
                    var vars = query ? query.split("&") : [];
                    var param = [];
                    for(var i = 0; i < vars.length; i++){
                        var pair = vars[i].split("=");
                        param[pair[0]] = decodeURI(pair[1]);
                    }
                    return param;
                }
                var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
                var r = query.match(reg);
                if(r != null){
                    return decodeURI(r[2]);
                }
                return null;
            },
            //处理对象参数值，排除对象参数值为”“、null、undefined，并返回一个新对象
            filterObject : function (obj){
                var param = {};
                if ( obj === null || obj === undefined || obj === "" ) return param;
                for ( var key in obj ){
                    if ( obj[key] !== null && obj[key] !== undefined && obj[key] !== "" ){
                        param[key] = obj[key];
                    }
                }
                return param;
            },
            //合并json
            jsonMerge : function(){
                var result = {};
                for(var i=0; i<arguments.length; i++){
                    for(var attr in arguments[i]){
                        result[attr]=arguments[i][attr];
                    }
                }
                return result;
            }
        }
    };
});