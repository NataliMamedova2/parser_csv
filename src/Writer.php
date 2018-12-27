<?php
namespace Parser\Csv;

use Parser\Csv\Exceptions\ParserException;
use InvalidArgumentException;

class Writer extends AbstractCsvFile
{
   /**
     * @var string
     */
    private $lineBreak;

    /**
     * @param string|resource $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $lineBreak
     * @throws ParserException
     * @throws InvalidArgumentException
     */
    public function __construct(
        $file,
        $delimiter = self::DEFAULT_DELIMITER,
        $enclosure = self::DEFAULT_ENCLOSURE,
        $lineBreak = "\n"
    ) {
        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
        $this->setLineBreak($lineBreak);
        $this->setFile($file, "w");
    }

    /**
     * @param string $lineBreak
     */
    private function setLineBreak($lineBreak)
    {
        $this->validateLineBreak($lineBreak);
        $this->lineBreak = $lineBreak;
    }

    /**
     * @param string $lineBreak
     */
    protected function validateLineBreak($lineBreak)
    {
        $allowedLineBreaks = [
            "\r\n", // win
            "\r", // mac
            "\n", // unix
        ];
        if (!in_array($lineBreak, $allowedLineBreaks)) {
            throw new ParserException(
                "Invalid line break: " . json_encode($lineBreak) .
                " allowed line breaks: " . json_encode($allowedLineBreaks),
                ParserException::INVALID_PARAM
            );
        }
    }

    /**
     * @param array $row
     * @throws ParserException
     */
    public function writeRow(array $row)
    {
        $str = $this->rowToStr($row);
        $ret = @fwrite($this->getFileResource(), $str);

        if (($ret === false) || (($ret === 0) && (strlen($str) > 0))) {
            throw new ParserException(
                "Cannot write to CSV file " . $this->fileName .
                ($ret === false && error_get_last() ? 'Error: ' . error_get_last()['message'] : '') .
                ' Return: ' . json_encode($ret) .
                ' To write: ' . strlen($str) . ' Written: ' . $ret,
                ParserException::WRITE_ERROR
            );
        }
    }

    /**
     * @param array $row
     * @throws ParserException
     *
     * @return string
     */
    public function rowToStr(array $row)
    {
        $return = [];
        foreach ($row as $column) {
            if (!(
                is_scalar($column)
                || is_null($column)
                || (
                    is_object($column)
                    && method_exists($column, '__toString')
                )
            )) {
                throw new ParserException(
                    "Cannot write data into column: " . var_export($column, true),
                    ParserException::WRITE_ERROR
                );
            }
            $return[] = $this->getEnclosure() .
                str_replace($this->getEnclosure(), str_repeat($this->getEnclosure(), 2), $column) .
                $this->getEnclosure();
        }
        return implode($this->getDelimiter(), $return) . $this->lineBreak;
    }
}