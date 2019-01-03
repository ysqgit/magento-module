<?php

class Uecommerce_Mundipagg_Helper_Logger extends Mage_Core_Helper_Abstract
{
    /**
     * @param string $message
     * @param integer $level
     * @param string $file
     * @param string $logDir
     * @param bool $forceLog
     */
    public static function log($message, $level = null, $file = '', $logDir = null ,$forceLog = false)
    {
        if (!Mage::getConfig()) {
            return;
        }

        try {
            $logActive = Mage::getStoreConfig('dev/log/active');
            if (empty($file)) {
                $file = Mage::getStoreConfig('dev/log/file');
            }
        }
        catch (Exception $e) {
            $logActive = true;
        }

        if (!Mage::getIsDeveloperMode() && !$logActive && !$forceLog) {
            return;
        }

        static $loggers = array();

        $level  = is_null($level) ? Zend_Log::DEBUG : $level;
        $file = empty($file) ? 'system.log' : basename($file);

        // Validate file extension before save. Allowed file extensions: log, txt, html, csv
        if (!self::isLogFileExtensionValid($file)) {
            return;
        }

        $config = "payment/mundipagg_standard/log_by_file_put_contents";
        if (Mage::getStoreConfig($config)) {
            self::logByFilePutContents($logDir, $file, $message);
            return;
        }

        try {
            if (!isset($loggers[$file])) {
                $logDir  = $logDir ? $logDir : Mage::getBaseDir('var') . DS . 'log';
                $logFile = $logDir . DS . $file;

                if (!is_dir($logDir)) {
                    self::createDirectory($logDir);
                }

                if (!file_exists($logFile)) {
                    self::createFile($logFile);
                }

                $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
                $formatter = new Zend_Log_Formatter_Simple($format);
                $writerModel = (string)Mage::getConfig()->getNode('global/log/core/writer_model');

                if (!Mage::app() || !$writerModel) {
                    $writer = new Zend_Log_Writer_Stream($logFile);
                } else {
                    $writer = new $writerModel($logFile);
                }
                $writer->setFormatter($formatter);
                $loggers[$file] = new Zend_Log($writer);
            }

            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            $loggers[$file]->log($message, $level);

        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }
    }

    public static function isLogFileExtensionValid($file)
    {
        $validatedFileExtension = pathinfo($file, PATHINFO_EXTENSION);
        if ($validatedFileExtension && in_array($validatedFileExtension, self::getAllowedFileExtensions())) {
            return true;
        }

        return false;
    }

    protected static function getAllowedFileExtensions()
    {
        return ['log', 'txt', 'html', 'csv'];
    }

    protected function logByFilePutContents($logDir, $file, $message)
    {
        try {
            $logDir  = $logDir ? $logDir : Mage::getBaseDir('var') . DS . 'log';
            $logFile = $logDir . DS . $file;

            if (!is_dir($logDir)) {
                self::createDirectory($logDir);
            }

            if (!file_exists($logFile)) {
                self::createFile($logFile);
            }

            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            $putContents =
                file_put_contents(
                    $logFile,
                    $message . PHP_EOL,
                    FILE_APPEND
                );

            if (!$putContents) {
                Mage::throwException("Can't put log content into: " . $logFile);
            }

        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }
    }

    protected static function createDirectory($logDir)
    {
        $dirCreated = mkdir($logDir);
        chmod($logDir, 0750);

        if (!$dirCreated) {
            $msg = "Can't create Mundipagg log directory" . $logDir;
            Mage::throwException($msg);
        }
    }

    protected static function createFile($logFile)
    {
        $fileCreated = file_put_contents($logFile, '');
        chmod($logFile, 0640);

        if ($fileCreated === false) {
            $msg = "Can't create Mundipagg log file: " . $logFile;
            Mage::throwException($msg);
        }
    }
}