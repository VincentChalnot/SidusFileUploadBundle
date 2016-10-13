<?php

namespace Sidus\FileUploadBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Uploaded resource: minimum code required to handle file-upload properly
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class BaseResource implements ResourceInterface, JsonSerializable
{
    /**
     * Generated real file name
     *
     * @var string
     * @ORM\Column(name="file_name", type="string", length=255, unique=true)
     */
    protected $fileName;

    /**
     * Original fileName from upload or import script
     *
     * @var string
     * @ORM\Column(name="original_file_name", type="string", length=255)
     */
    protected $originalFileName;

    /**
     * Checksum of the file
     *
     * @var string
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    protected $hash;

    /**
     * @return string
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }

    /**
     * @param string $originalFileName
     *
     * @return ResourceInterface
     */
    public function setOriginalFileName($originalFileName)
    {
        $this->originalFileName = $originalFileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     *
     * @return ResourceInterface
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     *
     * @return ResourceInterface
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getFileName();
    }

    /**
     * Serialize automatically the entity when passed to json_encode
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'fileName' => $this->getFileName(),
            'originalFileName' => $this->getOriginalFileName(),
        ];
    }
}
