<?php
/**
 *
 *
 *
 *
 */

namespace Cotya\SignatureChainer;

use GuzzleHttp;
use Symfony\Component\Process\Process;

class Client
{
    use Traits\Guzzle;
    /**
     * @var GuzzleHttp\Client
     */
    protected $httpClient;
    
    protected $userAgent;
    
    protected $githubApiToken;
    
    public function __construct($userAgent, $githubApiToken)
    {
        $this->userAgent = $userAgent;
        $this->githubApiToken = $githubApiToken;

        $this->instantiateDefaultGuzzleHttp($this->userAgent);
    }
    
    protected function fetchUrl($url){
        sleep(1);
        try {
            $headers = [];
            if (strpos($url, 'https://api.github.com/repos') === 0) {
                $headers = [
                    'User-Agent' => $this->userAgent,
                    'Authorization' => 'token '.$this->githubApiToken,
                ];
            }
            $result = $this->httpClient->get(
                $url,
                [
                    'headers' => $headers
                ]
            );
            return $result;
        } catch (GuzzleHttp\Exception\ClientException $exception) {
            /*
            echo PHP_EOL;
            echo $exception->getMessage();
            echo $exception->getResponse()->getBody();
            echo PHP_EOL;
            */
            var_dump(
                $exception->getRequest()->getHeaders(),
                $exception->getResponse()->getHeader('X-RateLimit-Limit'),
                $exception->getResponse()->getHeader('X-RateLimit-Remaining'),
                $exception->getResponse()->getHeader('X-RateLimit-Reset')
            );
            throw $exception;
        }
    }

    public function processUrl($url)
    {
        $result = $this->fetchUrl($url);
        $fileContent = $result->getBody()->getContents();
        $process = new Process('gpg --batch -sab');
        $process->setInput($fileContent);
        $process->run();
        $error = $process->getErrorOutput();
        $signatureStruct = new SignatureStruct();
        $signatureStruct->setDownloadUrl($url);
        $signatureStruct->setSignature($process->getOutput());
        $signatureStruct->setSignatureType('gpg');
        $signatureStruct->setSha256(hash('sha256', $fileContent));
        
        //file_put_contents('test_archive.zip', $fileContent);
        //file_put_contents('test_archive.zip.asc', $process->getOutput());
        return $signatureStruct;
    }
    
    public function downloadPackageWithValidation(SignatureStruct $signatureStruct)
    {
        $result = $this->fetchUrl($signatureStruct->getDownloadUrl());
        $fileContent = $result->getBody()->getContents();
        $sha256 = hash('sha256', $fileContent);
        if ($sha256 !== $signatureStruct->getSha256()) {
            throw new \Exception(
                "sha256 hash does not match. download has '$sha256', storage has '{$signatureStruct->getSha256()}'"
            );
        }
        $gpg = new \gnupg();
        $result = $gpg->verify($fileContent, $signatureStruct->getSignature());
        var_dump($result);
        if ($result !== false) {
            echo "\nResult is not false, so signature seems to be valid\n";

            $keyinfo = $gpg->keyinfo($result[0]['fingerprint'])[0];
            
            
            var_dump($keyinfo['uids'][0]);
            
            if ($keyinfo['disabled'] || $keyinfo['expired'] || $keyinfo['revoked']) {
                echo PHP_EOL.'WARNING';
                echo PHP_EOL.'$keyinfo[\'disabled\'] || $keyinfo[\'expired\'] || $keyinfo[\'revoked\']'.PHP_EOL.PHP_EOL;
            }
            
        } else {
            echo "\n################## ERROR ################\nomething went wrong\n";
        }
        /*
        $process = new Process('gpg --verify --batch -a');
        $process->setInput(
            "-----BEGIN PGP SIGNED MESSAGE-----
Hash: SHA256

".
            $fileContent.
            PHP_EOL.
            $signatureStruct->getSignature()
        );
        $process->run();
        $error = $process->getErrorOutput();
        $output = $process->getOutput();
        echo $error;
        echo $output;
        */
    }
}
