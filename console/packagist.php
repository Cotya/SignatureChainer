<?php
/**
 *
 *
 *
 *
 */

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** @var Application $console */

$console
    ->register('packagist:process-package')
    ->setDefinition(array(
        new InputArgument('name', InputArgument::REQUIRED, 'name of the package'),
        new InputArgument('dir', InputArgument::REQUIRED, 'Directory to put the signature files into'),
    ))
    ->setDescription('find all packages with this exact name and create signatures')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($config) {
        $name = $input->getArgument('name');
        $dir = $input->getArgument('dir');
        $client = new Packagist\Api\Client();

        $SignatureClient = new \Cotya\SignatureChainer\Client($config['userAgent'], $config['githubApiToken']);
        $storage = new \Cotya\SignatureChainer\Storage($dir.'/signatures');
        
        
        $packages = $client->get($name);
        $numberOfVersions = count($packages->getVersions());
        $output->writeln("found {$numberOfVersions} versions for {$packages->getName()}");
        foreach ($packages->getVersions() as $version) {
            /** @var \Packagist\Api\Result\Package\Version $version */
            if (!is_numeric(mb_substr($version->getVersionNormalized(), 0, 1, 'utf-8'))
                || mb_stripos($version->getVersionNormalized(), '-dev') !== false
            ) {
                $output->writeln('jump over version: '.$version->getVersionNormalized());
                continue;
            }
            $dist = $version->getDist();
            if (!$dist || !$dist->getUrl()) {
                $output->writeln('no Dist url found ');
                var_dump($dist);
                continue;
            }
            if ($storage->doesSignatureExist(
                $packages->getName(),
                $version->getVersionNormalized(),
                $config['storageKey']
            )) {
                $output->writeln('Signature already exists for version: '.$version->getVersionNormalized());
                continue;
            }
            try {
                $signatureStruct = $SignatureClient->processUrl($dist->getUrl());
                $signatureStruct->setPackageName($packages->getName());
                $signatureStruct->setPackageVersion($version->getVersionNormalized());
                $storage->addEntry($signatureStruct, $config['storageKey']);
            } catch (GuzzleHttp\Exception\ClientException $exception) {
                echo PHP_EOL;
                echo $exception->getMessage();
                echo PHP_EOL;
                if ($exception->getResponse()->getStatusCode() == 404) {
                    continue;
                }
                echo $exception->getResponse()->getBody();
                echo PHP_EOL;
                throw $exception;
            }

            $output->write('.');
        }

        $output->writeln('command finished');
    });
