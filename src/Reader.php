<?php
namespace Parser\Csv;

use Iterator;
use Parser\Csv\Exceptions\ParserException;
use InvalidArgumentException;

class Reader extends AbstractCsvFile implements Iterator
{
    const DEFAULT_ESCAPED_BY = "";

    /**
     * @var string
     */
    private $escapedBy;

    /**
     * @var int
     */
    private $skipLines;

    /**
     * @var int
     */
    private $rowCounter = 0;

    /**
     * @var array|null|false
     */
    private $currentRow;

    /**
     * @var array
     */
    private $header;

    /**
     * @var string
     */
    private $lineBreak;

    /**
     * @param string|resource $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escapedBy
     * @param int $skipLines
     * @throws ParserException
     */
    public function __construct(
        $file,
        $delimiter = self::DEFAULT_DELIMITER,
        $enclosure = self::DEFAULT_ENCLOSURE,
        $escapedBy = self::DEFAULT_ESCAPED_BY,
        $skipLines = 0
    ) {
        $this->escapedBy = $escapedBy;
        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
        $this->setSkipLines($skipLines);
        $this->setFile($file, "r");
        $this->lineBreak = $this->detectLineBreak();
        rewind($this->fileResource);
        $this->header = $this->readLine();
        $this->rewind();
    }

    /**
     * @param integer $skipLines
     * @throws InvalidArgumentException
     *
     * @return Reader
     */
    protected function setSkipLines($skipLines)
    {
        $this->validateSkipLines($skipLines);
        $this->skipLines = $skipLines;
        return $this;
    }

    /**
     * @param integer $skipLines
     * @throws InvalidArgumentException
     */
    protected function validateSkipLines($skipLines)
    {
        if (!is_int($skipLines) || $skipLines < 0) {
            throw new InvalidArgumentException(
                "Number of lines to skip must be a positive integer. \"$skipLines\" received.",
                ParserException::INVALID_PARAM
            );
        }
    }

    /**
     * @return string
     */
    protected function detectLineBreak()
    {
        rewind($this->getFileResource());
        $sample = fread($this->getFileResource(), 10000);
        $possibleLineBreaks = [
            "\r\n", // win
            "\r", // mac
            "\n", // unix
        ];
        $lineBreaksPositions = [];
        foreach ($possibleLineBreaks as $lineBreak) {
            $position = strpos($sample, $lineBreak);
            if ($position === false) {
                continue;
            }
            $lineBreaksPositions[$lineBreak] = $position;
        }
        asort($lineBreaksPositions);
        reset($lineBreaksPositions);
        return empty($lineBreaksPositions) ? "\n" : key($lineBreaksPositions);
    }

    /**
     * @throws ParserException
     * @throws InvalidArgumentException
     *
     * @return array|null
     */
    protected function readLine()
    {
        $this->validateLineBreak($this->lineBreak);

        $enclosure = !$this->getEnclosure() ? chr(0) : $this->getEnclosure();
        $escapedBy = !$this->escapedBy ? chr(0) : $this->escapedBy;

        return fgetcsv($this->getFileResource(), null, $this->getDelimiter(), $enclosure, $escapedBy);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected function validateLineBreak($lineBreak)
    {
       if (in_array($lineBreak, ["\r\n", "\n"])) {
            return $lineBreak;
        }
        throw new InvalidArgumentException(
            "Invalid line break. Please use unix \\n or win \\r\\n line breaks.",
            ParserException::INVALID_PARAM
        );
    }

    /**
     * @return string|null
     */
    public function getLineBreak():?string
    {
        return $this->lineBreak;
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        rewind($this->getFileResource());
        for ($i = 0; $i < $this->skipLines; $i++) {
            $this->readLine();
        }
        $this->currentRow = $this->readLine();
        $this->rowCounter = 0;
    }

    /**
     * @return string|null
     */
    public function getEscapedBy():?string
    {
        return $this->escapedBy;
    }

    /**
     * @return int
     */
    public function getColumnsCount()
    {
        return count($this->getHeader());
    }

    /**
     * @return array|null
     */
    public function getHeader():?array
    {
        if ($this->header) {
            return $this->header;
        }
        return [];
    }

    /**
     * @return string|null
     */
    public function getLineBreakAsText():?string
    {
        return trim(json_encode($this->getLineBreak()), '"');
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return $this->currentRow;
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->currentRow = $this->readLine();
        $this->rowCounter++;
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->rowCounter;
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return $this->currentRow !== false;
    }
}