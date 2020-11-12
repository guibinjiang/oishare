<?php
/**************************Start Function****************************/

if (!function_exists('debugBacktrace')) {
    /**
     * 打印进程执行步骤
     * @param null $thread 线程号
     * @param bool $exit 是否退出
     * @return void
     */
    function debugBacktrace($thread = null, $exit = false)
    {
        static $staticThread = null;
        if ($thread && !isset($staticThread)) {
            $staticThread = $thread;
        } elseif ($thread === null || ($thread && $thread == $staticThread)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
            $data = array();
            foreach ($backtrace as $key => $arr) {
                $data[] = "<b>File:</b> {$arr['file']}; <b>Line:</b> {$arr['line']}; <b>Func:</b> {$arr['class']}{$arr['type']}{$arr['function']}; <b>args:</b> " . json_encode($arr['args']);
            }
            echo '<pre>';
            echo implode("\n", array_reverse($data));
            $exit && exit;
        }
    }
}

if (!function_exists('dump')) {
    /**
     * 打印格式化的数组
     * @access public
     * @author jgb
     * @param string | array  $var 打印的变量
     * @param string $flag 打印标志
     * @param boolean $exit 是否退出
     * @return string
     */
    function dump($var = null, $flag = null, $exit = false)
    {
        static $staticFlag = null;
        if ($var === null && $flag !== null) {
            $staticFlag = $flag;
        } elseif ($staticFlag === $flag) {
            ob_start();
            var_dump($var);
            $vars = ob_get_clean();
            $vars = preg_replace("/\\]\\=\\>\\n(\\s+)/m", "] => ", $vars);
            echo '<pre>' . $vars . '<pre>';
            $exit && exit();
        }
    }
}

if (!function_exists('dump')) {
    /**
     * 收集性能分析数据
     * @access public
     * @author jgb
     * @param bool $open
     * @return void
     */
    function saveXhprof($open = false) {
        $xhprofData = xhprof_disable();// $xhprofData是数组形式的分析结果

        $xhprofPath = '/home/websites/xhprof/';
        require $xhprofPath . 'xhprof_lib/utils/xhprof_lib.php';
        require $xhprofPath . 'xhprof_lib/utils/xhprof_runs.php';

        $xhprofRuns = new XHProfRuns_Default();
        $runId = $xhprofRuns->save_run($xhprofData, 'xhprof');
        if ($open === true) {
            echo '<script type="text/javascript">window.open("http://xhprof.gb/index.php?run=' . $runId . '&source=xhprof");</script>';
        }
    }
}

/********************************End Function*********************************/


/********************************Start Execute*********************************/

/**
 * 开启debug调试
 */
if (isset($_GET['__debug']) && $_GET['__debug'] == '1') {
    ini_set('display_errors', true);
    error_reporting(E_ALL ^ E_NOTICE);
}

/**
 * 开启xhprof性能调试
 */
if (isset($_GET['__xhprof']) && $_GET['__xhprof'] == 1) {
    xhprof_enable(
        XHPROF_FLAGS_MEMORY|XHPROF_FLAGS_CPU,
        [
            'ignored_functions' => [
                'call_user_func',
                'call_user_func_array'
            ]
        ]
    );
    // 启用程序结束运行函数
    $open = isset($_GET['__open']) ? true : false;
    register_shutdown_function("saveXhprof", $open);
}

/********************************End Execute*********************************/
