<?php
require_once __DIR__ . '/vendor/autoload.php';

use Parser\Csv\Implementer;

 $implementer = new Implementer(__DIR__ . '/data/products.csv',__DIR__ . '/data/brands.csv');

 if($implementer->run()){
     echo "Operation completely successful";
 }else{
     echo "Operation completely with Errors";
     var_dump($implementer->getErrors());
 }
