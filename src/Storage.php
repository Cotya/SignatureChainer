<?php
/**
 *
 *
 *
 *
 */

namespace Cotya\SignatureChainer;


class Storage
{
    /**
     * @var string
     */
    protected $chainDirectory;

    /**
     * Storage constructor.
     *
     * @param $chainDirectory string
     */
    public function __construct($chainDirectory)
    {
        $this->chainDirectory = $chainDirectory;
    }
    
    protected function getDirectoryByPackageNameAndVersion($packageName, $version)
    {
        $directory = $this->chainDirectory.'/'.$packageName.'/'.$version;
        
        return $directory;
    }

    /**
     * @param SignatureStruct $signature
     * @param          string $storageKey
     */
    public function addEntry(SignatureStruct $signature, $storageKey)
    {
        $directory = $this->getDirectoryByPackageNameAndVersion(
            $signature->getPackageName(),
            $signature->getPackageVersion()
        );
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $fileName = $directory.'/'.$storageKey.'.json';
        file_put_contents(
            $fileName,
            json_encode($signature, JSON_PRETTY_PRINT)
        );
    }

    /**
     *
     * @param string $packageName
     * @param string $version
     *
     * @return SignatureStruct[]
     */
    public function getSignaturesForPackageByNameAndVersion($packageName, $version)
    {
        $directory = $this->getDirectoryByPackageNameAndVersion(
            $packageName,
            $version
        );
        $result = [];
        foreach (glob($directory.'/*.json') as $file) {
            $json = json_decode(file_get_contents($file), true);
            $result[] = SignatureStruct::jsonDesserialize($json);
        }
        return $result;
    }
    
    public function doesSignatureExist($packageName, $version, $storageKey)
    {
        $directory = $this->getDirectoryByPackageNameAndVersion(
            $packageName,
            $version
        );
        return file_exists($directory.'/'.$storageKey.'.json');
    }
}
