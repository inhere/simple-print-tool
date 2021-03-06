<?php

/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 15-1-20
 * Time: 10:35
 */
class PrintHelper
{
    /**
     * @param $data
     * @param bool $hasType
     * @return mixed
     */
    public static function getSystemPrintData($data, $hasType = true)
    {
        $fun = $hasType ? 'var_dump' : 'print_r';

        ob_start();
        $fun($data);
        $string = ob_get_clean();

        if (self::isWebRequest() && preg_match('/^<pre[\s]*/i', $string) !== 1) {
            $string = "<pre>$string</pre>";
        }

        return self::simpleFormat($string);
    }

    /**
     * 清除标签并格式化数据
     * @param string $data
     * @return mixed|string
     */
    public static function clearTagAndFormat($data)
    {
        if (!$data || !is_string($data)) {
            return $data;
        }

        $data = strip_tags($data);
        $data = str_replace(
            ['&rArr;', '&gt;'],
            ['=>', '>'],
            $data
        );
        $data = preg_replace(
            [
                "/[\n\r]+/i",
                "/Array[\s]*\(/",
                "/=>[\s]+/i"
            ],
            ["\n", 'Array (', '=> '],
            $data
        );

        return $data;
    }

    public static function simpleFormat($data)
    {
        if (!$data || !is_string($data)) {
            return $data;
        }

        $data = preg_replace(
            ["/[\n\r]+/i", "/Array[\s]*\(/", "/=>[\s]+/i"],
            ["\n", 'Array (', '=> '],
            $data
        );

        return $data;
    }

    public static function versionCheck()
    {
        $re = version_compare(PHP_VERSION, '5.6.0') >= 0;

        if (!$re) {
            exit('你的PHP版本是：' . PHP_VERSION . '；要求PHP>=5.6!');
        }
    }

    // 计算字符长度
    public static function strLength($str)
    {
        if ($str === '0' || $str === 0) {
            return '1';
        }

        if (empty($str)) {
            return '0';
        }

        if (\function_exists('mb_strlen')) {
            return mb_strlen($str, 'utf-8');
        }

        preg_match_all('/./u', $str, $arr);

        return count($arr[0]);
    }

    /**
     * getLines 获取文件一定范围内的内容]
     * @param  string  $fileName  含完整路径的文件]
     * @param  integer $startLine 开始行数 默认第1行]
     * @param  integer $endLine 结束行数 默认第50行]
     * @param  string $method 打开文件方式]
     * @throws Exception
     * @return array             返回内容
     */
    public static function getLines($fileName, $startLine = 1, $endLine = 50, $method = 'rb'): array
    {
        $content = array();

        // 判断php版本（因为要用到SplFileObject，PHP>=5.1.0）
        if (PHP_VERSION_ID >= 50100) {
            $count = $endLine - $startLine;

            try {
                $obj_file = new \SplFileObject($fileName, $method);
                $obj_file->seek($startLine - 1); // 转到第N行, seek方法参数从0开始计数

                for ($i = 0; $i <= $count; ++$i) {
                    $content[] = $obj_file->current(); // current()获取当前行内容
                    $obj_file->next(); // 下一行
                }
            } catch (Exception $e) {
                throw new \RuntimeException("读取文件--{$fileName} 发生错误！");
            }

        } else { //PHP<5.1
            $openFile = fopen($fileName, $method);

            if (!$openFile) {
                exit('error:can not read file--' . $fileName);
            }

            // 跳过前$startLine行
            for ($i = 1; $i < $startLine; ++$i) {
                fgets($openFile);
            }

            // 读取文件行内容
            for ($i; $i <= $endLine; ++$i) {
                $content[] = fgets($openFile);
            }

            fclose($openFile);
        }

        return array_filter($content); // array_filter过滤：false,null,''
    }

    /**
     * isWeb
     * @return  boolean
     */
    public static function isWeb(): bool
    {
        return in_array(
            PHP_SAPI,
            array(
                'apache',
                'cgi',
                'fast-cgi',
                'cgi-fcgi',
                'fpm-fcgi',
                'srv',
                'cli-server'
            )
        );
    }

    /**
     * isCli
     * @return  boolean
     */
    public static function isCli(): bool
    {
        return 'cli' === PHP_SAPI;
    }

    // ajax 请求
    public static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    // flash 请求
    public static function isFlash(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && stripos($_SERVER['HTTP_X_REQUESTED_WITH'], 'Shockwave') !== false;
    }

    public static function getIsFlash(): bool
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        return $userAgent && (stripos($userAgent, 'Shockwave') !== false || stripos($userAgent, 'Flash') !== false);
    }

    // 是正常的网络请求 get post
    public static function isWebRequest(): bool
    {
        return !self::isCli() && !self::isAjax() && !self::isFlash();
    }

    /**
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public static function hasColorSupport(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI');
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     * @param  int|resource $fileDescriptor
     * @return boolean
     */
    public static function isInteractive($fileDescriptor): bool
    {
        return \function_exists('posix_isatty') && @\posix_isatty($fileDescriptor);
    }

    /**
     * @param $var
     * @param bool $return
     * @param int $length
     * @return string
     */
    public static function varExport($var, $return = false, $length = 200): string
    {
        $string = \var_export($var, true);

        if (\is_object($var)) {
            $string = \str_replace(array('::__set_state(', "=> \n"), array('(Object) ', '=>'), $string);
        }

        $string = \trim($string);

        if ($return) {
            return \strlen($string) > $length ? \substr($string, 0, $length) . '...' : $string;
        }

        echo $string;

        return '';
    }

    /**
     * @param mixed $v
     * @return string
     */
    public static function getTypeString($v): string
    {
        if ($v === false) {
            $v = 'bool(false)';
        } elseif ($v === true) {
            $v = 'bool(true)';
        } elseif ($v === null) {
            $v = 'null(null)';
        } elseif ($v === '') {
            $v = '""';
        }

        return $v;
    }
}
