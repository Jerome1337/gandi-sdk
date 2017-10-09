<?php

declare(strict_types=1);

/*
 * This file is part of the Nexylan packages.
 *
 * (c) Nexylan SAS <contact@nexylan.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nexy\Gandi;

use fXmlRpc\Client as FxmlrpcClient;
use fXmlRpc\Proxy;
use fXmlRpc\Transport\HttpAdapterTransport;
use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Nexy\Gandi\Api\AbstractApi;

/**
 * @author Jérôme Pogeant <p-jerome@hotmail.fr>
 */
final class Gandi
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @param HttpClient $httpClient
     * @param string $apiUrl
     * @param string $apiKey
     */
    public function __construct(HttpClient $httpClient, string $apiUrl, string $apiKey)
    {
        $this->httpClient = $httpClient ?: HttpClientDiscovery::find();
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return AbstractApi
     */
    public function __call(string $name, array $arguments): AbstractApi
    {
        try {
            return $this->api(ucfirst(str_replace('api', '', $name)));
        } catch (\InvalidArgumentException $e) {
            throw new \BadMethodCallException(sprintf('Undefined method %s', $name));
        }
    }

    /**
     * Return Gandi proxy.
     *
     * @return Proxy
     */
    public function setup(): Proxy
    {
        $client = new FxmlrpcClient(
            $this->apiUrl,
            new HttpAdapterTransport(
                new GuzzleMessageFactory(),
                new GuzzleClient($this->httpClient)
            )
        );

        $client->prependParams([$this->apiKey]);

        $proxy = new Proxy($client);

        return $proxy;
    }

    /**
     * @param string $apiClass
     *
     * @return AbstractApi
     */
    private function api(string $apiClass): AbstractApi
    {
        if (!isset($this->apis[$apiClass])) {
            $apiFQNClass = '\\Nexy\\Gandi\\Api\\'.$apiClass;
            if (false === class_exists($apiFQNClass)) {
                throw new \InvalidArgumentException(sprintf('Undefined api class %s', $apiClass));
            }
            $this->apis[$apiClass] = new $apiFQNClass($this);
        }

        return $this->apis[$apiClass];
    }
}
