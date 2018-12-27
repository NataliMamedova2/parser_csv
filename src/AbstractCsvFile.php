<?php

namespace Parser\Csv;

use InvalidArgumentException;
use Parser\Csv\Exceptions\ParserException;

abstract class AbstractCsvFile
{
    const DEFAULT_DELIMITER = ',';
    const DEFAULT_ENCLOSURE = '"';

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var resource
     */

    protected $fileResource;

    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var string
     */
    private $enclosure;

    /**
     * @param string $lineBreak
     * @return mixed
     */
    abstract protected function validateLineBreak($lineBreak);

    /**
     * @param string $fileName
     * @param string $mode
     * @throws ParserException
     */
    protected function openCsvFile($fileName, $mode)
    {
        $this->fileResource = @fopen($fileName, $mode);
        if (!$this->fileResource) {
            throw new ParserException(
                "Cannot open file {$fileName} " . error_get_last()['message'],
                ParserException::FILE_NOT_EXISTS
            );
        }
    }

    public function __destruct()
    {
        $this->closeFile();
    }

    /**
     * @return string|null
     */
    public function getDelimiter(): ?string
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     * @throws InvalidArgumentException
     */
    protected function setDelimiter($delimiter)
    {
        $this->validateDelimiter($delimiter);
        $this->delimiter = $delimiter;
    }

    /**
     * @param string $delimiter
     * @throws InvalidArgumentException
     */
    protected function validateDelimiter($delimiter)
    {
        $delimiterLen = strlen($delimiter);

        if ($delimiterLen > 1) {
            throw new InvalidArgumentException(
                "Delimiter must be a single character. " . json_encode($delimiter) . " received",
                ParserException::INVALID_PARAM
            );
        }
        if ($delimiterLen == 0) {
            throw new InvalidArgumentException(
                "Delimiter cannot be empty.",
                ParserException::INVALID_PARAM
            );
        }
    }

    /**
     * @return string|null
     */
    public function getEnclosure(): ?string
    {
        return $this->enclosure;
    }

    /**
     * @param $enclosure
     * @throws InvalidArgumentException
     */
    protected function setEnclosure($enclosure)
    {
        $this->validateEnclosure($enclosure);
        $this->enclosure = $enclosure;
    }

    /**
     * @param string $enclosure
     * @throws InvalidArgumentException
     */
    protected function validateEnclosure($enclosure)
    {
        if (strlen($enclosure) > 1) {
            throw new InvalidArgumentException(
                "Enclosure must be a single character. " . json_encode($enclosure) . " received",
                ParserException::INVALID_PARAM
            );
        }
    }

    /**
     * @param string $file
     * @param string $mode
     * @throws ParserException
     */
    protected function setFile($file, $mode)
    {
        if (is_string($file)) {
            $this->openCsvFile($file, $mode);
            $this->fileName = $file;
        } elseif (is_resource($file)) {
            $this->fileResource = $file;
        } else {
            throw new InvalidArgumentException("Invalid file: " . var_export($file, true));
        }
    }

    /**
     * @return resource
     */
    protected function getFileResource()
    {
        return $this->fileResource;
    }

    protected function closeFile()
    {
        if ($this->fileName && is_resource($this->fileResource)) {
            fclose($this->fileResource);
        }
    }
}