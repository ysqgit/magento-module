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

        self::logByFileInputContents($logDir, $file, $message);

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

    protected static function logByFileInputContents($logDir, $file, $message)
    {
        try {
            $logDir  = $logDir ? $logDir : Mage::getBaseDir('var') . DS . 'log';
            $logFile = $logDir . DS . $file;

            if (!is_dir($logDir)) {
                $dirCreated = mkdir($logDir);
                chmod($logDir, 0750);

                if (!$dirCreated) {
                    $msg = "Can't create Mundipagg log directory" . $logDir;
                    Mage::throwException($msg);
                }
            }

            if (!file_exists($logFile)) {
                $fileCreated = file_put_contents($logFile, '');
                chmod($logFile, 0640);

                if (!$fileCreated) {
                    $msg = "Can't create Mundipagg log file: " . $logFile;
                    Mage::throwException($msg);
                }
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
}