<?php

namespace Grafeo;

enum Language: string
{
    case GQL = 'gql';
    case Cypher = 'cypher';
    case Gremlin = 'gremlin';
    case GraphQL = 'graphql';
    case SparQL = 'sparql';
    case SQL = 'sql';
}
