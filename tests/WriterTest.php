<?php

namespace Parser\Csv\Tests;

use Parser\Csv\Exceptions\ParserException;
use Parser\Csv\Writer;
use Parser\Csv\Exception;
use PHPUnit\Framework\TestCase;

class CsvWriteTest extends TestCase
{
    public function testNewFileShouldBeCreated()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        self::assertInstanceOf(Writer::class, new Writer($fileName));
    }

    public function testAccessors()
    {
        $csvFile = new Writer(sys_get_temp_dir() . '/test-write.csv');
        self::assertEquals('"', $csvFile->getEnclosure());
        self::assertEquals(',', $csvFile->getDelimiter());
    }

    public function testWrite()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new Writer($fileName);
        $rows = [
            [
                'col1', 'col2',
            ],
            [
                'line without enclosure', 'second column',
            ],
            [
                'enclosure " in column', 'hello \\',
            ],
            [
                'line with enclosure', 'second column',
            ],
            [
                'column with enclosure ", and comma inside text', 'second column enclosure in text "',
            ],
            [
                "columns with\nnew line", "columns with\ttab",
            ],
            [
                'column with \n \t \\\\', 'second col',
            ]
        ];
        foreach ($rows as $row) {
            $csvFile->writeRow($row);
        }
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\n",
                [
                    '"col1","col2"',
                    '"line without enclosure","second column"',
                    '"enclosure "" in column","hello \\"',
                    '"line with enclosure","second column"',
                    '"column with enclosure "", and comma inside text","second column enclosure in text """',
                    "\"columns with\nnew line\",\"columns with\ttab\"",
                    '"column with \\n \\t \\\\","second col"',
                    '',
                ]
            ),
            $data
        );
    }

    public function testWriteInvalidObject()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new Writer($fileName);
        $rows = [
            [
                'col1', 'col2',
            ],
            [
                '1', new \stdClass(),
            ],
        ];
        $csvFile->writeRow($rows[0]);
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Cannot write data into column: stdClass::");
        $csvFile->writeRow($rows[1]);
    }

    public function testWriteValidObject()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new Writer($fileName);
        $rows = [
            [
                'col1', 'col2',
            ],
            [
                '1', 'test',
            ],
        ];
        $csvFile->writeRow($rows[0]);
        $csvFile->writeRow($rows[1]);
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\n",
                [
                    '"col1","col2"' ,
                    '"1","me string"',
                    '',
                ]
            ),
            $data
        );
    }

    /**
     * @dataProvider invalidFilenameProvider
     * @param string $filename
     * @param string $message
     */
    public function testInvalidFileName($filename, $message)
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage($message);
        new Writer($filename);
    }
    public function invalidFileNameProvider()
    {
        return [
            ["", 'Filename cannot be empty'],
            ["\0", 'fopen() expects parameter 1 to be a valid path, string given'],
        ];
    }

    public function testNonStringWrite()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new Writer($fileName);
        $row = [['nested']];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Cannot write data into column: array");
        $csvFile->writeRow($row);
    }

    public function testWriteResource()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $file = fopen($fileName, 'w');
        $csvFile = new Writer($file);
        $rows = [['col1', 'col2']];
        $csvFile->writeRow($rows[0]);
        // check that the file pointer remains valid
        unset($csvFile);
        fwrite($file, 'foo,bar');
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\n",
                [
                    '"col1","col2"' ,
                    'foo,bar',
                ]
            ),
            $data
        );
    }

    public function testInvalidResource()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        touch($fileName);
        $pointer = fopen($fileName, 'r');
        $csvFile = new Writer($pointer);
        $rows = [['col1', 'col2']];
        self::expectException(ParserException::class);
        self::expectExceptionMessage('Cannot write to CSV file  Return: 0 To write: 14 Written: 0');
        $csvFile->writeRow($rows[0]);
    }

    public function testInvalidPointer2()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        touch($fileName);
        $pointer = fopen($fileName, 'r');
        $csvFile = new Writer($pointer);
        fclose($pointer);
        $rows = [['col1', 'col2']];
        self::expectException(ParserException::class);
        self::expectExceptionMessage(
            'a valid stream resource Return: false To write: 14 Written: '
        );
        $csvFile->writeRow($rows[0]);
    }


    public function testWriteLineBreak()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new Writer(
            $fileName,
            Writer::DEFAULT_DELIMITER,
            Writer::DEFAULT_ENCLOSURE,
            "\r\n"
        );
        $rows = [
            [
                'col1', 'col2',
            ],
            [
                'val1', 'val2',
            ],
        ];
        foreach ($rows as $row) {
            $csvFile->writeRow($row);
        }
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\r\n",
                [
                    '"col1","col2"',
                    '"val1","val2"',
                    '',
                ]
            ),
            $data
        );
    }
}