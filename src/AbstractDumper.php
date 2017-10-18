<?php

namespace carono\yii2dumper;

use yii\db\Connection;
use yii\helpers\FileHelper;

abstract class AbstractDumper
{
    public $backup = '';
    public $dbName = '';
    public $compress = true;
    public $user;
    public $password;
    public $host = '127.0.0.1';
    public $port;

    abstract function export($destination);

    abstract function import($source);

    abstract function drop();

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        return true;
    }

    public static function checkAccess()
    {
        return strpos(ini_get('disable_functions'), "exec") === false;
    }

    /**
     * @param array|string $command
     *
     * @return string
     */
    protected function exec(array $command)
    {
        $command = join(' ', $command);
        echo "\nEXEC: $command\n\n";
        return exec($command);
    }

    protected static function isArchive($path)
    {
        //composer require wapmorgan/file-type-detector
    }

    public function getPrefix()
    {
        return date("Ymd");
    }

    /**
     * @param null $suffix
     * @param string $extension
     *
     * @return string
     */
    public function formFileName($suffix = null, $extension = 'sql')
    {
        $prefix = $this->getPrefix();
        $dbName = $this->dbName;
        return $prefix . "_" . $dbName . ($suffix ? "_" . $suffix : "") . ($extension ? "." . $extension : "");
    }
}