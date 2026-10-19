<?php

namespace Grafeo;

enum Isolation: int
{
    case GRAFEO_ISOLATION_READ_COMMITTED = 0;
    case GRAFEO_ISOLATION_SNAPSHOT = 1;
    case GRAFEO_ISOLATION_SERIALIZABLE = 2;
}
