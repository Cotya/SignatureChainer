<?php
/**
 *
 *
 *
 *
 */

namespace Cotya\SignatureChainer\Traits;

use GuzzleHttp;

trait Guzzle
{
    /**
     * @var GuzzleHttp\Client
     */
    protected $httpClient;


    protected function instantiateDefaultGuzzleHttp($userAgent)
    {
        $this->httpClient = new GuzzleHttp\Client([
            'defaults' => ['debug' => true],

            'headers' => [
                'User-Agent' => $userAgent,
            ],
        ]);
    }

}
