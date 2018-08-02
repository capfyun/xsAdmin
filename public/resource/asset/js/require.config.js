requirejs.config({
    //加载超时时间
    waitSeconds: 7,
    // 文件编码
    charset: 'utf-8',
    //根路径
    baseUrl: '/resource',
    //模块依赖
    deps: [
        'jquery'
    ],
    //映射 { 'some/newmodule':{ 'foo': 'foo1.2' }, 'some/oldmodule':{ 'foo':'foo1.0' } }
    map: {
        '*': {
            'css': 'lib/require-css/css.min'
        }
    },
    //模块位置
    paths: {
        'app': 'asset/js/app',
        'adminlte': 'asset/adminlte/js/adminlte.min',
        'jquery': 'lib/jquery/dist/jquery.min',
        'jquery-ui': 'lib/jquery-ui/jquery-ui.min',
        //时间插件
        'jquery-datetimepicker': 'lib/jquery-datetimepicker/build/jquery.datetimepicker.full.min',
        'jquery-mousewheel': 'lib/jquery-mousewheel/jquery.mousewheel.min',
        'jquery-slimscroll': 'lib/jquery-slimscroll/jquery.slimscroll.min',
        //bootstrap
        'bootstrap': 'lib/bootstrap/dist/js/bootstrap.min',
        //文件上传
        'bootstrap-fileinput': 'lib/bootstrap-fileinput/js/locales/zh',
        'bootstrap-slider': '',
        //表单验证
        'bootstrap-validator': 'lib/bootstrap-validator/dist/js/language/zh_CN',
        //h5编辑器
        'bootstrap-wysihtml5': '',
        'fastclick': '',
        'icheck': 'lib/icheck/icheck.min',
        'inputmask': '',
        'jvectormap': '',
        'layer': 'lib/layer/dist/layer',
        'pace': '',
        'select2': 'lib/select2/dist/js/select2.full.min',
        'supersized': '',
        'ueditor': 'asset/ueditor/lang/zh-cn/zh-cn',
        'vue': 'lib/vue/dist/vue.min'
    },
    //楔子 { xxx: {deps:[],exportsFn: func, exports：'jQuery',init:func},...}
    shim: {
        'jquery-datetimepicker': {
            deps: ['jquery-mousewheel', 'css!lib/jquery-datetimepicker/build/jquery.datetimepicker.min.css'],
            remark: function(){
                $.datetimepicker.setLocale('ch');
            }
        },
        'bootstrap': {
            deps: ['css!lib/ionicons/css/ionicons.min.css', 'css!lib/font-awesome/css/font-awesome.min.css', 'css!lib/bootstrap/dist/css/bootstrap.min.css']
        },
        'bootstrap-fileinput': {
            deps: ['lib/bootstrap-fileinput/js/fileinput.min', 'css!lib/bootstrap-fileinput/css/fileinput.min.css', 'bootstrap']
        },
        'bootstrap-validator': {
            deps: ['lib/bootstrap-validator/dist/js/bootstrapValidator.min', 'css!lib/bootstrap-validator/dist/css/bootstrapValidator.min.css', 'bootstrap']
        },
        'adminlte': {
            deps: ['css!asset/adminlte/css/skins/_all-skins.min.css', 'css!asset/adminlte/css/adminlte.min.css', 'bootstrap']
        },
        'icheck': {
            deps: ['css!lib/icheck/skins/all.css']
        },
        'layer' : {},
        'select2': {
            deps: ['css!lib/select2/dist/css/select2.min.css']
        },
        'ueditor': {
            deps: ['asset/ueditor/third-party/zeroclipboard/ZeroClipboard.min', 'asset/ueditor/ueditor.all.min', 'asset/ueditor/ueditor.config'],
            init: function(zeroclipboard){
                window.ZeroClipboard = zeroclipboard;
                return UE;
            }
        }
    },
    //模块束 { jsUtil:['MathUtil', 'DateUtil'] }
    bundles: {},
    //js包 [{name:'jqueryui',location: 'jqueryui/',main: 'core'}]
    pkgs: {}
});
