<?php

namespace Grafeo;

enum Status: int
{
    case OK = 0;
    case ERROR_DATABASE = 1;
    case ERROR_QUERY = 2;
    case ERROR_TRANSACTION = 3;
    case ERROR_STORAGE = 4;
    case ERROR_IO = 5;
    case ERROR_SERIALIZATION = 6;
    case ERROR_INTERNAL = 7;
    case ERROR_NULL_POINTER = 8;
    case ERROR_INVALID_UTF8 = 9;
}
