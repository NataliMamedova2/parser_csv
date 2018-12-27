<?php

namespace Parser\Csv\Exceptions;

use Exception as BaseException;

class ParserException extends BaseException
{
    const FILE_NOT_EXISTS = 1;
    const INVALID_PARAM = 2;
    const WRITE_ERROR = 3;
}