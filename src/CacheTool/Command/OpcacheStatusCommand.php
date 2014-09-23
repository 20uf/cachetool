<?php

/*
 * This file is part of CacheTool.
 *
 * (c) Samuel Gordalina <samuel.gordalina@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CacheTool\Command;

use CacheTool\Util\Formatter;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OpcacheStatusCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('opcache:status')
            ->setDescription('Show summary information about the opcode cache')
            ->setHelp('');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ensureExtensionLoaded('Zend OPcache');

        $info = $this->getCacheTool()->opcache_get_status(true);
        $stats = $info['opcache_statistics'];

        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Name', 'Value'))
            ->setRows(array(
                array('Enabled', $info['opcache_enabled'] ? 'Yes' : 'No'),
                array('Cache full', $info['cache_full'] ? 'Yes' : 'No'),
                array('Restart pending', $info['restart_pending'] ? 'Yes' : 'No'),
                array('Restart in progress', $info['restart_in_progress'] ? 'Yes' : 'No'),
                array('Memory used', Formatter::bytes($info['memory_usage']['used_memory'])),
                array('Memory free', Formatter::bytes($info['memory_usage']['free_memory'])),
                array('Memory wasted (%)', sprintf("%s (%s%%)", Formatter::bytes($info['memory_usage']['wasted_memory']), $info['memory_usage']['current_wasted_percentage'])),
                array('Strings buffer size', Formatter::bytes($info['interned_strings_usage']['buffer_size'])),
                array('Strings memory used', Formatter::bytes($info['interned_strings_usage']['used_memory'])),
                array('Strings memory free', Formatter::bytes($info['interned_strings_usage']['free_memory'])),
                array('Number of strings', $info['interned_strings_usage']['number_of_strings']),
                new TableSeparator(),
                array('Cached scripts', $stats['num_cached_scripts']),
                array('Cached keys', $stats['num_cached_keys']),
                array('Max cached keys', $stats['max_cached_keys']),
                array('Start time', Formatter::date($stats['start_time'], 'U')),
                array('Last restart time', $stats['last_restart_time'] ? Formatter::date($stats['last_restart_time'], 'U') : 'Never'),
                array('Oom restarts', $stats['oom_restarts']),
                array('Hash restarts', $stats['hash_restarts']),
                array('Manual restarts', $stats['manual_restarts']),
                array('Hits', $stats['hits']),
                array('Misses', $stats['misses']),
                array('Blacklist misses (%)', sprintf('%s (%s%%)', $stats['blacklist_misses'], $stats['blacklist_miss_ratio'])),
                array('Opcache hit rate', $stats['opcache_hit_rate']),
            ))
        ;

        $table->render($output);
    }
}
