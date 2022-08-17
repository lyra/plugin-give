<?php
/**
 * Copyright © Lyra Network.
 * This file is part of Lyra Collect plugin for GiveWP. See COPYING.md for license details.
 *
 * @author    Lyra Network <https://www.lyra.com>
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL v3)
 */

// No direct access is allowed.
if (! defined('ABSPATH')) {
    exit;
}

class LyraGiveLogger
{
    const DEBUG = 1;
    const INFO = 2;
    const WARN = 3;
    const ERROR = 4;

    private $levels = array(
        self::DEBUG => 'DEBUG',
        self::INFO  => 'INFO',
        self::WARN  => 'WARN',
        self::ERROR => 'ERROR'
    );

    const LOG_LEVEL = self::INFO;
    const LOG_PATH = 'lyra.log';

    private $name;
    private $path;

    // Logger single instance.
    private static $logger = null;

    // Logger private constructor.
    private function __construct()
    {
        $this->path = LYRA_GIVE_DIR . 'logs/' . self::LOG_PATH;
        if (! is_dir(LYRA_GIVE_DIR . 'logs')) {
            mkdir(LYRA_GIVE_DIR . 'logs');
        }
    }

    // Create a single instance of logger if it doesn't exist yet.
    public static function getLogger($name)
    {
        if (is_null(self::$logger)) {
            self::$logger = new LyraGiveLogger();
        }

        self::$logger->name = $name;

        return self::$logger;
    }

    public function log($msg, $msgLevel = self::INFO)
    {
        if ($msgLevel < 1 || $msgLevel > 4) {
            $msgLevel = self::INFO;
        }

        if ($msgLevel < self::LOG_LEVEL) {
            // No logs.
            return;
        }

        $date = date('Y-m-d H:i:s', time());

        $fLog = @fopen($this->path, 'a');
        if ($fLog) {
            fwrite($fLog, "[$date] " . $this->name . ". {$this->levels[$msgLevel]}: $msg\n");
            fclose($fLog);
        }
    }

    public function debug($msg)
    {
        $this->log($msg, self::DEBUG);
    }

    public function info($msg)
    {
        $this->log($msg, self::INFO);
    }

    public function warn($msg)
    {
        $this->log($msg, self::WARN);
    }

    public function error($msg)
    {
        $this->log($msg, self::ERROR);
    }
}
