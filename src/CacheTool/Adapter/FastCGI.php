<?php

/*
 * This file is part of CacheTool.
 *
 * (c) Samuel Gordalina <samuel.gordalina@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CacheTool\Adapter;

use CacheTool\Code;
use Adoy\FastCGI\Client;
use Adoy\FastCGI\ForbiddenException as CommunicationException;

class FastCGI extends AbstractAdapter
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @param string $host 127.0.0.1:9000 or /var/run/php5-fpm.sock
     * @param string $tempDir
     */
    public function __construct($host = null, $tempDir = null)
    {
        // try to guess where it is
        if ($host === null) {
            if (file_exists('/var/run/php5-fpm.sock')) {
                $host = '/var/run/php5-fpm.sock';
            } else {
                $host = '127.0.0.1:9000';
            }
        }

        if (false !== strpos($host, ':')) {
            list($host, $port) = explode(':', $host);
            $this->client = new Client($host, $port);
        } else {
            // socket
            $this->client = new Client('unix://' . $host, -1);
        }
        $this->client->setReadWriteTimeout(60 * 1000);
        $this->client->setPersistentSocket(false);
        $this->client->setKeepAlive(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRun(Code $code)
    {
        $response = $this->request($code);

        if ($response['statusCode'] === 200) {
            return $response['body'];
        } else {
            throw new \RuntimeException(sprintf("%s: %s", $response['stderr'], $response['body']));
        }
    }

    protected function request(Code $code)
    {
        $file = $this->createTemporaryFile();

        $this->logger->info(sprintf('FastCGI: Dumped code to file: %s', $file));

        try {
            $code->writeTo($file);

            $environment = array(
                'REQUEST_METHOD'  => 'POST',
                'REQUEST_URI'     => '/',
                'SCRIPT_FILENAME' => $file,
            );

            try {
                $response = $this->client->request($environment, '');
            } catch (CommunicationException $e) {
                $this->client->close();
                $response = $this->client->request($environment, '');
            }
            $this->logger->debug(sprintf('FastCGI: Response: %s', json_encode($response)));

            @unlink($file);
            return $response;
        } catch (CommunicationException $e) {
            @unlink($file);

            throw new \RuntimeException(
                sprintf('Could not connect to FastCGI server: %s', $e->getMessage()),
                $e->getCode()
            );
        }
    }
}
