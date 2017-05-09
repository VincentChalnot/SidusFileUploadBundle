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
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * File's path inside the filesystem
     *
     * @var string
     * @ORM\Column(name="path", type="string", length=255, unique=true)
     */
    protected $path;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

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
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return ResourceInterface
     */
    public function setPath($path)
    {
        $this->path = $path;

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
        return (string) $this->getPath();
    }

    /**
     * Serialize automatically the entity when passed to json_encode
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'identifier' => $this->getIdentifier(),
            'path' => $this->getPath(),
            'originalFileName' => $this->getOriginalFileName(),
        ];
    }
}
