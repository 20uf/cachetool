<?php

/*
 * This file is part of CacheTool.
 *
 * (c) Samuel Gordalina <samuel.gordalina@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CacheTool\Console;

use CacheTool\Adapter\FastCGI;
use CacheTool\Adapter\Cli;
use CacheTool\CacheTool;
use CacheTool\Command as CacheToolCommand;
use CacheTool\Monolog\ConsoleHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Application extends BaseApplication
{
    const VERSION = '@package_version@';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct('CacheTool', self::VERSION);

        $this->config = $config;
        $this->logger = new Logger('cachetool');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new CacheToolCommand\SelfUpdateCommand();

        $commands[] = new CacheToolCommand\ApcBinDumpCommand();
        $commands[] = new CacheToolCommand\ApcBinLoadCommand();
        $commands[] = new CacheToolCommand\ApcCacheClearCommand();
        $commands[] = new CacheToolCommand\ApcCacheInfoCommand();
        $commands[] = new CacheToolCommand\ApcCacheInfoFileCommand();
        $commands[] = new CacheToolCommand\ApcKeyDeleteCommand();
        $commands[] = new CacheToolCommand\ApcKeyExistsCommand();
        $commands[] = new CacheToolCommand\ApcKeyFetchCommand();
        $commands[] = new CacheToolCommand\ApcKeyStoreCommand();
        $commands[] = new CacheToolCommand\ApcSmaInfoCommand();

        $commands[] = new CacheToolCommand\OpcacheConfigurationCommand();
        $commands[] = new CacheToolCommand\OpcacheResetCommand();
        $commands[] = new CacheToolCommand\OpcacheStatusCommand();
        $commands[] = new CacheToolCommand\OpcacheStatusScriptsCommand();

        return $commands;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('--fcgi', null, InputOption::VALUE_OPTIONAL, 'If specified, used as a connection string to FastCGI server.'));
        $definition->addOption(new InputOption('--cli', null, InputOption::VALUE_NONE, 'If specified, forces adapter to cli'));

        return $definition;
    }

    /**
     * {@inheritDoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->logger->pushHandler(new ConsoleHandler($output));

        return parent::doRun($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    public function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        if ($command instanceof ContainerAwareInterface) {
            $container = $this->buildContainer($input);
            $command->setContainer($container);
        }

        return parent::doRunCommand($command, $input, $output);
    }

    /**
     * @param  InputInterface     $input
     * @return ContainerInterface
     */
    public function buildContainer(InputInterface $input)
    {
        if ($input->hasOption('cli')) {
            $this->config['adapter'] = 'cli';
        } else if ($input->hasOption('fcgi')) {
            $this->config['adapter'] = 'fastcgi';
            $this->config['fastcgi'] = $input->getOption('fcgi');
        }

        switch ($this->config['adapter']) {
            case 'cli':
                $adapter = new Cli();
                break;

            case 'fastcgi':
                $adapter = new FastCGI($this->config['fastcgi']);
                break;

            default:
                throw new \RuntimeException("Adapter `{$this->config['adapter']}` is not one of cli or fastcgi");
        }

        $container = new Container();
        $container->set('cachetool', CacheTool::factory($adapter, $this->logger));
        $container->set('logger', $this->logger);

        return $container;
    }
}
