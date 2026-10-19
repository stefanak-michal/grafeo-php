<?php

namespace Grafeo;

class Node
{
    public readonly int $id;
    /** @var string[] */
    public readonly array $labels;
    public readonly array $properties;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->labels = $data['labels'];
        $this->properties = $data['properties'];
    }
}
