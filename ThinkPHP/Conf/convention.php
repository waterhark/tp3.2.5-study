<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 *
 */
defined('THINK_PATH') or exit();
return array(
    /* 应用设定 */
    'APP_USE_NAMESPACE'      => true,    //
    'APP_SUB_DOMAIN_DEPLOY'  => false,   //
    'APP_SUB_DOMAIN_RULES'   => array(), //
    'APP_DOMAIN_SUFFIX'      => '', //
    'ACTION_SUFFIX'          => '', //
    'MULTI_MODULE'           => true, //
    'MODULE_DENY_LIST'       => array('Common', 'Runtime'),
    'CONTROLLER_LEVEL'       => 1,
    'APP_AUTOLOAD_LAYER'     => 'Controller,Model', //
    'APP_AUTOLOAD_PATH'      => '', //

    /*  */
    'COOKIE_EXPIRE'          => 0,       //
    'COOKIE_DOMAIN'          => '',      //
    'COOKIE_PATH'            => '/',     //
    'COOKIE_PREFIX'          => '',      //
    'COOKIE_SECURE'          => false,   //
    'COOKIE_HTTPONLY'        => '',      //

    /*  */
    'DEFAULT_M_LAYER'        => 'Model', //
    'DEFAULT_C_LAYER'        => 'Controller', //
    'DEFAULT_V_LAYER'        => 'View', //
    'DEFAULT_LANG'           => 'zh-cn', //
    'DEFAULT_THEME'          => '',    //
    'DEFAULT_MODULE'         => 'Home',  //
    'DEFAULT_CONTROLLER'     => 'Index', //
    'DEFAULT_ACTION'         => 'index', //
    'DEFAULT_CHARSET'        => 'utf-8', //
    'DEFAULT_TIMEZONE'       => 'PRC',    //
    'DEFAULT_AJAX_RETURN'    => 'JSON',  //
    'DEFAULT_JSONP_HANDLER'  => 'jsonpReturn', //
    'DEFAULT_FILTER'         => 'htmlspecialchars', //

    /* 数据库设置 */
    'DB_TYPE'                => '',     //
    'DB_HOST'                => '', //
    'DB_NAME'                => '',          //
    'DB_USER'                => '',      //
    'DB_PWD'                 => '',          //
    'DB_PORT'                => '',        //
    'DB_PREFIX'              => '',    //
    'DB_PARAMS'              => array(), //
    'DB_DEBUG'               => TRUE, //
    'DB_FIELDS_CACHE'        => true,        //
    'DB_CHARSET'             => 'utf8',      //
    'DB_DEPLOY_TYPE'         => 0, //
    'DB_RW_SEPARATE'         => false,       //
    'DB_MASTER_NUM'          => 1, //
    'DB_SLAVE_NO'            => '', //

    /* 数据缓存设置 */
    'DATA_CACHE_TIME'        => 0,      //
    'DATA_CACHE_COMPRESS'    => false,   //
    'DATA_CACHE_CHECK'       => false,   //
    'DATA_CACHE_PREFIX'      => '',     //
    'DATA_CACHE_TYPE'        => 'File',  //
    'DATA_CACHE_PATH'        => TEMP_PATH,//
    'DATA_CACHE_KEY'         => '',    //
    'DATA_CACHE_SUBDIR'      => false,    //
    'DATA_PATH_LEVEL'        => 1,        //

    /* 错误设置 */
    'ERROR_MESSAGE'          => '页面错误！请稍后再试～',//
    'ERROR_PAGE'             => '',    //
    'SHOW_ERROR_MSG'         => false,    //
    'TRACE_MAX_RECORD'       => 100,    //

    /* 日志设置 */
    'LOG_RECORD'             => false,   //
    'LOG_TYPE'               => 'File', //
    'LOG_LEVEL'              => 'EMERG,ALERT,CRIT,ERR',//
    'LOG_FILE_SIZE'          => 2097152,    //
    'LOG_EXCEPTION_RECORD'   => false,    //

    /*  */
    'SESSION_AUTO_START'     => true,    //
    'SESSION_OPTIONS'        => array(), //
    'SESSION_TYPE'           => '', //
    'SESSION_PREFIX'         => '', //
    //'VAR_SESSION_ID'      =>  'session_id',     //sessionID的提交变量

    /*  */
    'TMPL_CONTENT_TYPE'      => 'text/html', //
    'TMPL_ACTION_ERROR'      => THINK_PATH . 'Tpl/dispatch_jump.tpl', //
    'TMPL_ACTION_SUCCESS'    => THINK_PATH . 'Tpl/dispatch_jump.tpl', //
    'TMPL_EXCEPTION_FILE'    => THINK_PATH . 'Tpl/think_exception.tpl',//
    'TMPL_DETECT_THEME'      => false,       //
    'TMPL_TEMPLATE_SUFFIX'   => '.html',     //
    'TMPL_FILE_DEPR'         => '/', //
    //
    'TMPL_ENGINE_TYPE'       => 'Think',     //
    'TMPL_CACHFILE_SUFFIX'   => '.php',      //
    'TMPL_DENY_FUNC_LIST'    => 'echo,exit',    //
    'TMPL_DENY_PHP'          => false, //
    'TMPL_L_DELIM'           => '{',            //
    'TMPL_R_DELIM'           => '}',            //
    'TMPL_VAR_IDENTIFY'      => 'array',     //
    'TMPL_STRIP_SPACE'       => true,       //
    'TMPL_CACHE_ON'          => true,        //
    'TMPL_CACHE_PREFIX'      => '',         //
    'TMPL_CACHE_TIME'        => 0,         //
    'TMPL_LAYOUT_ITEM'       => '{__CONTENT__}', //
    'LAYOUT_ON'              => false, //
    'LAYOUT_NAME'            => 'layout', //

    //
    'TAGLIB_BEGIN'           => '<',  //
    'TAGLIB_END'             => '>',  //
    'TAGLIB_LOAD'            => true, //
    'TAGLIB_BUILD_IN'        => 'cx', //
    'TAGLIB_PRE_LOAD'        => '',   //

    /*  */
    'URL_CASE_INSENSITIVE'   => true,   //
    'URL_MODEL'              => 1,       //
    //
    'URL_PATHINFO_DEPR'      => '/',    //
    'URL_PATHINFO_FETCH'     => 'ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL', //
    'URL_REQUEST_URI'        => 'REQUEST_URI', //
    'URL_HTML_SUFFIX'        => '',  //
    'URL_DENY_SUFFIX'        => 'ico|png|gif|jpg', //
    'URL_PARAMS_BIND'        => true, //
    'URL_PARAMS_BIND_TYPE'   => 0, //
    'URL_PARAMS_FILTER'      => false, //
    'URL_PARAMS_FILTER_TYPE' => '', //
    'URL_ROUTER_ON'          => true,   //
    'URL_ROUTE_RULES'        => array(
        'test/hello' => 'Test/hello'
    ), //
    'URL_MAP_RULES'          => array(), //

    /*  */
    'VAR_MODULE'             => 'm',     //
    'VAR_ADDON'              => 'addon',     //
    'VAR_CONTROLLER'         => 'c',    //
    'VAR_ACTION'             => 'a',    //
    'VAR_AJAX_SUBMIT'        => 'ajax',  //
    'VAR_JSONP_HANDLER'      => 'callback',
    'VAR_PATHINFO'           => 's',    //
    'VAR_TEMPLATE'           => 't',    //
    'VAR_AUTO_STRING'        => false,    //

    'HTTP_CACHE_CONTROL' => 'private',  //
    'CHECK_APP_DIR'      => true,       //
    'FILE_UPLOAD_TYPE'   => 'Local',    //
    'DATA_CRYPT_TYPE'    => 'Think',    //

);
