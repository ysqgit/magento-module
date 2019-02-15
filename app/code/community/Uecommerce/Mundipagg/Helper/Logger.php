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

    protected static function logByFilePutContents($logDir, $file, $message)
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

            $handle = fopen($logFile, 'a');
            if (!$handle) {
                $errorFilePut = print_r(error_get_last(), true);
                $msg =
                    "MP - Can't open file: " .
                    $logFile . ' ' .
                    'Last PHP error: ' . $errorFilePut
                ;

                Mage::throwException($msg);
            }

            if (fwrite($handle, $message . PHP_EOL) === false) {
                $errorFilePut = print_r(error_get_last(), true);
                $permissions = substr(sprintf('%o', fileperms($logFile)), -4);

                $msg =
                    "MP - Can't write on file: " .
                    $logFile . ' ' .
                    'Last PHP error: ' . $errorFilePut .
                    ' File permissions: ' . $permissions
                ;

                Mage::throwException($msg);
            }

            fclose($handle);

        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }
    }

    protected static function createDirectory($logDir)
    {
        Mage::log("MP - Creating log directory: " . $logDir);
        $dirCreated = mkdir($logDir);

        $errorDirCreation = print_r(error_get_last(), true);

        if (!$dirCreated) {
            $msg =
                "MP - Can't create Mundipagg log directory" .
                $logDir . ' ' .
                'Last PHP error: ' . $errorDirCreation
            ;

            Mage::throwException($msg);
        }

        $chmodResult = chmod($logDir, 0750);

        $errorChmod = print_r(error_get_last(), true);
        $permissions = substr(sprintf('%o', fileperms($logDir)), -4);

        if (!$chmodResult) {
            $msg =
                'MP - Failed to set file permissions chmod($logDir, 0750); on: ' .
                $logDir .
                ' Dir permissions: ' . $permissions .
                ' Last PHP error: ' . $errorChmod
            ;

            Mage::throwException($msg);
        }
    }

    protected static function createFile($logFile)
    {
        Mage::log("MP - Creating log file: " . $logFile);
        $fileCreated = file_put_contents($logFile, '');
        $errorFileCreation = print_r(error_get_last(), true);

        if ($fileCreated === false) {
            $msg =
                "MP - Can't create Mundipagg log file: " .
                $logFile . ' ' .
                'Last PHP error: ' . $errorFileCreation
            ;
            Mage::throwException($msg);
        }

        $chmodResult = chmod($logFile, 0640);
        $errorChmod = print_r(error_get_last(), true);
        $permissions = substr(sprintf('%o', fileperms($logFile)), -4);

        if (!$chmodResult) {
            $msg =
                'MP - Failed to set file permissions chmod($logFile, 0640); on: ' .
                $logFile .
                ' File permissions: ' . $permissions .
                ' Last PHP error: ' . $errorChmod
            ;

            Mage::throwException($msg);
        }
    }
}