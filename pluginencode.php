<?php
/**
 * encode
 * 生成发行版代码包
 */
class encode
{
    /**
     * 树形文件列表
     */
    public $files = array();

    /**
     * 等待混淆的文件
     * @var array
     */
    public $confound = array();

    /**
     * 包含文件夹
     * @var array
     */
    public $includeFolder = array();

    /**
     * 插件入口文件
     */
    protected $entryFile;

    /**
     * 插件名称
     */
    protected $pluginName;


    public function __construct()
    {
    }

    public function run()
    {
        $this->read(__DIR__);
        //print_r($this->files);
        $this->create();
        $this->saveEntryFile();
    }

    /**
     * 设置需要加密的文件夹
     * @param
     */
    public function setIncludeFolder($folder)
    {
        $this->includeFolder = $folder;
    }

    /**
     * 设置插件入口文件
     */
    public function setEntryFile($file)
    {
        $this->entryFile = $file;
        $this->pluginName = $this->getPluginName();
    }

    /**
     * 读取不包含自身的所有文件
     */
    public function read($dir)
    {
        $handle = opendir($dir);
        while($file = readdir($handle)) {
            if ($file == 'pluginencode.php' || ($dir == __DIR__ && $file == $this->entryFile) || $file == '.git') {
                continue;
            }
            if ($file != '.' && $file != '..'){
                $file = $dir . '/'. $file;
                if (is_dir($file)) {
                    $this->read($file);
                }else {
                    $this->files[] = $file;
                }
            }
        }
    }

    public function create()
    {
        $i=0;
        $floderName = basename(__DIR__);
        $this->distDir = dirname(__DIR__)  . '/'. $floderName . '-dist';
        //创建发行文件夹
        if (!file_exists($this->distDir)) {
            mkdir($this->distDir);
        }
        foreach($this->files as $file) {
            $newfile = substr_replace($file, $floderName . '-dist', strpos($file, $floderName), strlen($floderName));
            if (strpos($newfile, '.php') !== false) {
                $content = $this->stripCommet($file);
                // for test
                //$content = file_get_contents($file);
                $content = $this->replaceHook($content);
                $this->save($newfile, $content);
                foreach($this->includeFolder as $include){
                    if (strpos($newfile, $include) !== false) {
                        $this->confound[] = $newfile;
                    }
                }
            }else{
                $content = file_get_contents($file);
                $this->save($newfile, $content);
            }
            $i += 1;
            //if ($i>1){
            //  break;
            //}
        }

        $this->confound();
    }

    /**
     * 去除注释
     */
    public function stripCommet($filename)
    {
        return php_strip_whitespace($filename);
    }

    /**
     * 替换插件hook
     */
    public function replaceHook($content)
    {
        return str_replace(
            array(
                'activate_'.$this->pluginName.'/',
                'deactivate_'.$this->pluginName.'/'
                ),
            array(
                'activate_'.$this->pluginName . '-dist/',
                'deactivate_'.$this->pluginName . '-dist/'
                ),
        $content);

        

    }

    /**
     * 代码混淆
     * @return 
     */
    public function confound()
    {
        $vars = array();
        $functions = array();
        $vascii = $fascii = 127;
        foreach($this->confound as $file) {
            $content = file_get_contents($file);
            $tokens = token_get_all($content);
            foreach ($tokens as $key=>$token) {
                if (is_array($token)) {
                    if (token_name($token[0]) == 'T_VARIABLE') {
                        if ($token[1] != '$_POST' &&
                            $token[1] != '$_GET' &&
                            $token[1] != '$_REQUEST' &&
                            $token[1] != '$_COOKIE' &&
                            $token[1] != '$_SESSION' &&
                            $token[1] != '$_SERVER' &&
                            $token[1] != '$_ENV' &&
                            $token[1] != '$_FILES'&&
                            $token[1] != '$_GLOBAL' &&
                            $token[1] != '$this')
                        {
                            if (!isset($var[$token[1]])) {
                                $var[$token[1]] = $vascii;
                                $vascii += 1;
                            }
                        }
                    }

                    //遇到class
                    if (token_name($token[0]) == 'T_CLASS') {
                        $currentClass = $tokens[$key+2][1];
                    }
                    if (token_name($token[0]) == 'T_FUNCTION') {
                        //获取函数名
                        if ($tokens[$key+2][1] != '__construct' &&
                            $tokens[$key+2][1] != '__destruct' &&
                            $tokens[$key+2][1] != '__call' &&
                            $tokens[$key+2][1] != '__callStatic' &&
                            $tokens[$key+2][1] != '__get' &&
                            $tokens[$key+2][1] != '__set' &&
                            $tokens[$key+2][1] != '__isset' &&
                            $tokens[$key+2][1] != '__unset' &&
                            $tokens[$key+2][1] != '__sleep' &&
                            $tokens[$key+2][1] != '__wakeup' &&
                            $tokens[$key+2][1] != '__toString' &&
                            $tokens[$key+2][1] != '__invoke' &&
                            $tokens[$key+2][1] != '__clone' &&
                            $tokens[$key+2][1] != '__set_state' &&
                            $tokens[$key+2][1] != '__debugInfo' &&
                            !method_exists('redis', $tokens[$key+2][1]) &&
                            !method_exists('memcache', $tokens[$key+2][1])
                        )
                        {
                            if (!isset($function[$tokens[$key+2][1]])) {
                                $function[$tokens[$key+2][1]] = $fascii;
                                $fascii += 1;
                            }
                        }
                    }
                }
            }
            if(!empty($var)){
                $vars = array_merge($vars, $var);
            }
            if(!empty($function)) {
                $functions = array_merge($functions, $function);
            }
        }

        print_r($vars);
        print_r($functions);

        //进行替换

        foreach($this->confound as $file) {
            $content = file_get_contents($file);
            $tokens = token_get_all($content);
            $newContent = '';
            foreach ($tokens as $key=>$token) {
                if (is_array($token)) {
                    if (token_name($token[0]) == 'T_VARIABLE') {
                        if ($token[1] != '$_POST' &&
                            $token[1] != '$_GET' &&
                            $token[1] != '$_REQUEST' &&
                            $token[1] != '$_COOKIE' &&
                            $token[1] != '$_SESSION' &&
                            $token[1] != '$_SERVER' &&
                            $token[1] != '$_ENV' &&
                            $token[1] != '$_FILES'&&
                            $token[1] != '$_GLOBAL' &&
                            $token[1] != '$this')
                        {
                            $newContent .= '$'.chr($vars[$token[1]]);
                            //$tokens[$key][1] = chr($vars[$token[1]]);
                        }else{
                            $newContent .= $token[1];
                        }
                    } else if (token_name($token[0]) == 'T_STRING' || token_name($token[0]) == 'T_CONSTANT_ENCAPSED_STRING') {
                        if (isset($functions[$token[1]])) {
                            $newContent .= chr($functions[$token[1]]);
                            //$tokens[$key][1] = chr($functions[$token[1]]);
                        } else {
                            $str = str_replace(array('"',"'"), "", $token[1]);
                            if (isset($functions[$str])) {
                                $newContent .= "'" . chr($functions[$str]) . "'";
                            } else {
                                $newContent .= $token[1];
                            }
                        }
                    } else {
                        $newContent .= $token[1];
                    }
                } else {
                    $newContent .= $token;
                }

            }//end tokens foreach
            file_put_contents($file, $newContent);
        }
    }


    /**
     * 保存文件
     */
    public function save($filename, $content)
    {
        if (!file_exists(dirname($filename))) {
            //检查目录是否存在
            $filename = str_replace('\\', '/', $filename);
            $pathArr = explode('/', dirname($filename));
            $path = '';
            foreach ($pathArr as $split) {
                $path .= $split . '/';
                if (!file_exists($path)) {
                    mkdir($path);
                }
            }
        }

        file_put_contents($filename, $content);
    }

    /**
     * 保存入口文件
     * @return
     */
    public function saveEntryFile()
    {
        $content = file_get_contents($this->entryFile);
        $content = preg_replace("/Plugin Name:(\s*)([\w]+)/i",'/Plugin Name: $2-dist', $content);
        file_put_contents($this->distDir . '/' . $this->entryFile, $content);
    }

    /**
     * 获取插件的名称
     * @return
     */
    protected function getPluginName()
    {
        $entry = file_get_contents($this->entryFile);
        preg_match("/Plugin Name:(\s*)([\w]+)/i", $entry, $matches);
        return $matches[2];
    }
}



$encode = new encode();
$encode->setEntryFile('imwpcache.php');
$encode->setIncludeFolder(array('bootstrap','classes', 'drivers', 'pages'));
$encode->run();
