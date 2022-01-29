<?php


namespace Beyond\WxWorkRobot;


use Beyond\Supports\Collection;
use Beyond\Supports\Str;
use Beyond\Supports\Traits\HasHttpRequest;
use GuzzleHttp\Exception\GuzzleException;

class Robot
{
    use HasHttpRequest;

    /**
     * @var array
     */
    private static $instance = [];

    /**
     * @var null | self
     */
    private static $currentInstance = null;

    /**
     * @var string
     */
    private static $key = '';

    /**
     * 😀😁😂😃😄😅😆😉😊😋😎😍😘😗😙😚😇😐
     * 😑😶😏😣😥😮😯😪😫😴😌😛😜😝😒😓😔😕
     * 😲😷😖😞😟😤😢😭😦😧😨😬😰😱😳😵😡😠
     *
     * @var string
     */
    private static $desc = '😱😱😱😱😱😱😱😱😱😱😱😱😱😱😱😱😱😱' . PHP_EOL;

    /**
     * @var string
     */
    private static $hook = 'https://qyapi.weixin.qq.com';

    /**
     * @var string
     */
    private $baseUri = '';


    /**
     * @param $key
     * @return self
     * @throws \Exception
     */
    public static function instance($key)
    {
        if (empty($key)) {
            throw new \Exception('key非法');
        }

        self::$key = $key;

        if (!isset(self::$instance[$key]) || self::$instance[$key] instanceof self) {
            self::$instance[$key] = new self();
        }

        return self::$currentInstance = self::$instance[$key];
    }

    /**
     * @param string $desc 自定义描述
     * @param \ArrayAccess|array $data
     * @param \Throwable|null $error
     * @param string $msgType
     * @throws \Exception
     */
    public function notice($desc, $data, \Throwable $error = null, $msgType = 'text')
    {
        self::$desc .= $desc;
        $method = Str::camel($msgType);

        if (is_null(self::$currentInstance)) {
            throw new \Exception('请先实例化对象');
        }

        if (!method_exists(self::$currentInstance, $method)) {
            throw new \Exception('不支持的消息类型');
        }

        $this->$method($data, $error);
    }

    /**
     * @param $data
     * @param $debug
     * @return string
     */
    private function message($data, $debug)
    {
        $desc = self::$desc;
        $content = vsprintf('%s%s相关参数: %s%s', [$desc, PHP_EOL, PHP_EOL, var_export($data, true)]);
        if (!empty($debug)) {
            $content .= sprintf('%s调试信息:%s%s', PHP_EOL, PHP_EOL, var_export($debug, true));
        }

        return $content;
    }

    /**
     * @param $data
     * @param \Throwable $error
     * @return Collection
     */
    private function builderMsg($data, $error)
    {
        $debug = [];
        if ($data instanceof \ArrayAccess) {
            $data = $data->toArray();
        }

        if ($error instanceof \Throwable) {
            $debug['code']    = $error->getCode();
            $debug['message'] = $error->getMessage();
            $debug['line']    = $error->getLine();
            $debug['file']    = $error->getFile();
        }

        return new Collection(
            [
                'msgtype' => 'text',
                'text'    => [
                    'content' => $this->message($data, $debug),
                ]
            ]
        );
    }


    /**
     * 文本消息
     *
     * @param $data
     * @param $error
     * @throws GuzzleException
     */
    private function text($data, $error)
    {
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'query' => ['key' => self::$key],
            'body' => $this->builderMsg($data, $error)->toJson(),
        ];

        $this->setBaseUri(self::$hook)->getHttpClient()->post('/cgi-bin/webhook/send', $options);
    }

}