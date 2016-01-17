<?php
/**
 *
 *
 *
 *
 */

require 'vendor/autoload.php';

use Symfony\Component\Console\Application;

$console = new Application();

$config = require __DIR__ . '/config.php';

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$convert = function ($size) {
    $unit = array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size, 1024)))), 2).$unit[$i];
};

$console
    ->register('debug')
    ->setDefinition(array(
    ))
    ->setDescription('placeholder command to test code')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $client = new \Cotya\SignatureChainer\Client();
        $client->processUrl(
            'https://api.github.com/repos/Adyen/magento/zipball/b4377e4ff360eddb46f9f431214e3807fea542dd'
        );

        $output->writeln('command finished');
    });

$console
    ->register('example')
    ->setDefinition(array(
        new InputArgument('dir', InputArgument::REQUIRED, 'Directory name'),
    ))
    ->setDescription('Displays the files in the given directory')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $dir = $input->getArgument('dir');

        $output->writeln(sprintf('Dir listing for <info>%s</info>', $dir));
    });


$console
    ->register('process:csv')
    ->setDefinition(array(
        new InputArgument('dir', InputArgument::REQUIRED, 'Directory to put the signature files into'),
        new InputArgument('csvFile', InputArgument::REQUIRED, 'csvFile to import'),
        new InputArgument('start', InputArgument::OPTIONAL, 'csvFile to import'),
        new InputArgument('limit', InputArgument::OPTIONAL, 'csvFile to import'),
    ))
    ->setDescription('process')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($convert, $config) {
        $time_start = microtime(true);
        $dir = $input->getArgument('dir');
        $csvFile = $input->getArgument('csvFile');
        $start = $input->getArgument('start')?:0;
        $limit = $input->getArgument('limit')?:2000;

        $csvFileHandle = fopen($csvFile, "r");
        $client = new \Cotya\SignatureChainer\Client($config['userAgent'], $config['githubApiToken']);
        $storage = new \Cotya\SignatureChainer\Storage($dir.'/signatures');
        //$limit = 5;
        $count = 0;
        while (($data = fgetcsv($csvFileHandle)) !== false) {
            if ($count<$start) {
                $count++;
                continue;
            }
            if ($count>=$start+$limit) {
                $output->writeln("Limit($limit) reached, started at $start and reached $count");
                break;
            }
            try {
                $signatureStruct = $client->processUrl($data[2]);
                $signatureStruct->setPackageName($data[0]);
                $signatureStruct->setPackageVersion($data[1]);
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
            $count++;
            $output->write('.');
            if ($count%100 == 0) {
                $time_end = microtime(true);
                $time = $time_end - $time_start;
                $output->writeln("\n##$count## \tMemory Usage: ".
                    $convert(memory_get_usage(true)).'/'.$convert(memory_get_peak_usage(true)).
                    " Runtime: {$time}s");
            }
            //$limit--;
            //if ($limit < 0) {
            //    $output->writeln('break');
            //    break;
            //}
        };
        
        $output->writeln('command finished');
    });

$console
    ->register('composer-repo:parse2csv')
    ->setDefinition(array(
        new InputArgument('file', InputArgument::REQUIRED, 'file path name'),
        new InputArgument('output', InputArgument::OPTIONAL, 'file path name'),
    ))
    ->setDescription('parses a composer packages file to a simpler csv list')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $file = $input->getArgument('file');
        $outputFile = $input->getArgument('output');

        $packages = json_decode(file_get_contents($file), true);
        if ($outputFile) {
            $csvFileHandle = fopen($outputFile, 'w');
        } else {
            $csvFileHandle = fopen(__DIR__.'/packages.csv', 'w');
        };

        foreach ($packages['packages'] as $packageName => $versionList) {
            foreach ($versionList as $version => $package) {
                if (!isset($package['dist']['url'])) {
                    continue;
                }
                if (!is_numeric(mb_substr($package['version'], 0, 1, 'utf-8'))
                  || mb_stripos($package['version'], '-dev') !== false
                ) {
                    continue;
                }
                $url = $package['dist']['url'];
                $entry = [
                    $packageName,
                    $package['version'],
                    $url,
                ];
                fputcsv($csvFileHandle, $entry);
            }
        }

        $output->writeln('command finished');
    });


$console->run();
