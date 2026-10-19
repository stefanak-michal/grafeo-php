<?php

namespace Grafeo;

class Edge
{
    public readonly int $id;
    public readonly string $type;
    public readonly array $properties;
    public readonly int $sourceId;
    public readonly int $targetId;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->properties = $data['properties'];
        $this->sourceId = $data['source_id'];
        $this->targetId = $data['target_id'];
    }
}
