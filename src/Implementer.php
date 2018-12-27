<?php

namespace Parser\Csv;

use Throwable;

class Implementer
{
    /**
     * @var \Parser\Csv\Writer
     */
    private $csvWriter;

    /**
     * @var \Parser\Csv\Reader
     */
    private $productsFileReader;

    /**
     * @var \Parser\Csv\Reader
     */
    private $brandsFileReader;

    /**
     * @var array
     */
    private $numberProductsInBrand;

    /**
     * @var string
     */
    private $tempFile;

    /**
     * @var array
     */
    private $header;

    /**
     * @var string
     */
    private $brandsFileName;

    /**
     * @var array
     */
    private $errors;

    /**
     * Implementer constructor.
     * @param string $productsFile
     * @param string $brandsFile
     * @param array $header
     */
    public function __construct($productsFile, $brandsFile, $header = [])
    {
        try {
            $this->productsFileReader = new Reader($productsFile);
            $this->brandsFileReader = new Reader($brandsFile);

            $this->tempFile = dirname($brandsFile) . DIRECTORY_SEPARATOR . "temp.csv";
            $this->csvWriter = new Writer($this->tempFile);

            $this->header = $header;
            if (empty($this->header)) {
                $this->header = ['Brand', 'Number'];
            }

            $this->brandsFileName = $brandsFile;
        } catch (Throwable $exception) {
            $this->errors[] = $exception->getMessage();
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function run()
    {
        try {

            $this->countNumberProductsInBrand();

            $this->saveBrandsToTempFile();
            unlink($this->brandsFileName);

            if (rename($this->tempFile, $this->brandsFileName)) {
                return true;
            }
        } catch (Throwable $exception) {
            $this->errors[] = $exception->getMessage();
        }

        return false;
    }

    /**
     * @throws Exceptions\ParserException
     */
    private function saveBrandsToTempFile()
    {
        $this->csvWriter->writeRow($this->header);

        foreach ($this->brandsFileReader as $row) {
            $brand = current($row);
            $number = $this->getNumberProductsInBrand($brand);
            $this->csvWriter->writeRow([$brand, $number]);
        }
    }

    /**
     * @param string $brandName
     *
     * @return mixed|string
     */
    private function getNumberProductsInBrand($brandName)
    {
        $splitBrandName = $this->splitName($brandName);
        if (array_key_exists($splitBrandName, $this->numberProductsInBrand)) {
            return $this->numberProductsInBrand[$splitBrandName];
        }
        return '0';
    }


    private function countNumberProductsInBrand()
    {
        foreach ($this->productsFileReader as $row) {
            $this->saveNumberProductsInBrand(current($row));
        }
    }

    /**
     * @param string $productName
     */
    private function saveNumberProductsInBrand($productName)
    {
        $brandName = $this->splitName($productName);
        ++$this->numberProductsInBrand[$brandName];
    }

    /**
     * @param string $name
     * @return string
     */
    private function splitName($name)
    {
        $keywords = preg_split('/[\s]+/', $name);
        $countKeywords = count($keywords);
        if ($countKeywords == 1) {
            $brandName = $name;
        } elseif ($countKeywords > 1) {
            if (strlen($keywords[0]) <= 3) {
                $brandName = $keywords[0] . ' ' . $keywords[1];
            } else {
                $brandName = $keywords[0];
            }
        }
        return $brandName;
    }
}