<?php
/**
 *
 *
 *
 *
 */

namespace Cotya\SignatureChainer;


class SignatureStruct implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $packageName;

    /**
     * @var string
     */
    protected $packageVersion;

    /**
     * @var string
     */
    protected $signature;

    /**
     * @var string
     */
    protected $signatureType;

    /**
     * @var string
     */
    protected $downloadUrl;

    /**
     * @var string
     */
    protected $sha256;


    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @param string $packageName
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;
    }

    /**
     * @return string
     */
    public function getPackageVersion()
    {
        return $this->packageVersion;
    }

    /**
     * @param string $packageVersion
     */
    public function setPackageVersion($packageVersion)
    {
        $this->packageVersion = $packageVersion;
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function getSignatureType()
    {
        return $this->signatureType;
    }

    /**
     * @param string $signatureType
     */
    public function setSignatureType($signatureType)
    {
        $this->signatureType = $signatureType;
    }

    /**
     * @return string
     */
    public function getDownloadUrl()
    {
        return $this->downloadUrl;
    }

    /**
     * @param string $downloadUrl
     */
    public function setDownloadUrl($downloadUrl)
    {
        $this->downloadUrl = $downloadUrl;
    }

    /**
     * @return string
     */
    public function getSha256()
    {
        return $this->sha256;
    }

    /**
     * @param string $sha256
     */
    public function setSha256($sha256)
    {
        $this->sha256 = $sha256;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'packageName' => $this->packageName,
            'packageVersion' => $this->packageVersion,
            'signature' => $this->signature,
            'signatureType' => $this->signatureType,
            'downloadUrl' => $this->downloadUrl,
            'sha256' => $this->sha256,
        ];
    }

    /**
     * @param $json
     *
     * @return SignatureStruct
     */
    public static function jsonDesserialize($json)
    {
        $object = new self();
        $object->setPackageName($json['packageName']);
        $object->setPackageVersion($json['packageVersion']);
        $object->setSignature($json['signature']);
        $object->setSignatureType($json['signatureType']);
        $object->setDownloadUrl($json['downloadUrl']);
        $object->setSha256($json['sha256']);
        return $object;
    }
}
