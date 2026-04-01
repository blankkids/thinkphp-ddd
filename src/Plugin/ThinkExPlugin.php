<?php

namespace Blankkids\ThinkphpDdd\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;

/**
 * ThinkEx Composer Plugin
 *
 * This plugin provides DDD code generation tools for ThinkPHP 5.0
 *
 * Features:
 * - Register bin commands (thinkex, tool)
 * - Display welcome message on plugin activation
 *
 * @author blankkids
 * @version 1.0.0
 */
class ThinkExPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var Composer */
    protected $composer;

    /** @var IOInterface */
    protected $io;

    /**
     * Plugin activation callback
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;

        // Display welcome message
        if ($io->isVerbose()) {
            $io->write('<info>ThinkEx Plugin activated</info>');
            $io->write('<info>DDD Code Generation Tools for ThinkPHP 5.0</info>');
        }
    }

    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump' => 'postAutoloadDump',
            'pre-autoload-dump' => 'preAutoloadDump',
        ];
    }

    /**
     * Called after autoload dump
     *
     * @param $event
     */
    public function postAutoloadDump($event)
    {
        // 通过 getcwd() 获取项目根目录（最可靠的方式）
        $projectRoot = getcwd();
        if (!$projectRoot || !is_dir($projectRoot)) {
            return;
        }
        $localComposerFile = $projectRoot . '/composer.json';

        if (!file_exists($localComposerFile)) {
            return;
        }

        $composerContent = file_get_contents($localComposerFile);
        $composerData = json_decode($composerContent, true);

        if (!is_array($composerData)) {
            return;
        }

        // 确保 autoload 节点存在
        if (!isset($composerData['autoload'])) {
            $composerData['autoload'] = [];
        }
        if (!isset($composerData['autoload']['psr-4'])) {
            $composerData['autoload']['psr-4'] = [];
        }

        // 需要自动注入的 PSR-4 映射
        $requiredMappings = [
            'domain\\' => 'domain/',
        ];

        $modified = false;
        foreach ($requiredMappings as $namespace => $path) {
            if (!isset($composerData['autoload']['psr-4'][$namespace])) {
                $composerData['autoload']['psr-4'][$namespace] = $path;
                $modified = true;
                if ($this->io->isVerbose()) {
                    $this->io->write('<info>ThinkEx: Added "' . $namespace . '" => "' . $path . '" to autoload</info>');
                }
            }
        }

        if ($modified) {
            // 写回 composer.json
            $newContent = json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $newContent .= "\n";
            file_put_contents($localComposerFile, $newContent);

            $this->io->write('<info>ThinkEx: composer.json updated. Run "composer dump-autoload" to apply changes.</info>');
        }
    }

    /**
     * Called before autoload dump
     *
     * @param $event
     */
    public function preAutoloadDump($event)
    {
        // Reserved for future use
        // Could be used to prepare resources before autoload regeneration
    }

    /**
     * Plugin deactivation callback
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        if ($io->isVerbose()) {
            $io->write('<info>ThinkEx Plugin deactivated</info>');
        }
    }

    /**
     * Plugin uninstall callback
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        if ($io->isVerbose()) {
            $io->write('<info>ThinkEx Plugin uninstalled</info>');
        }
    }
}
