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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApcBinDumpCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('apc:bin:dump')
            ->setDescription('Get a binary dump of files and user variables')
            ->addOption('file', InputArgument::REQUIRED)
            ->setHelp('');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ensureExtensionLoaded('apc');

        $file = $input->getArgument('file');

        if (!is_file($file) || !is_writeable($file)) {
            throw new \InvalidArgumentException(sprintf("Could not write to file: %s", $file));
        }

        $this->getCacheTool()->apc_bin_dumpfile(null, null, $file);
    }
}
