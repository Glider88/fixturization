<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database\Query;

use Glider88\Fixturization\Spider\Node;

readonly class JoinClause
{
    public function __construct(
        public string $table,
        public string $alias,
        public string $column,
        public string $parentAlias,
        public string $parentColumn,
    ) {}

    public static function make(Node $parent, Node $node): self
    {
        $parentAlias = $parent->alias;
        if ($node->link->parentTable === $node->name) {
            $parentAlias = $node->alias;
        }

        $alias = $parent->alias;
        if ($node->link->table === $node->name) {
            $alias = $node->alias;
        }

        return new JoinClause(
            table: $node->name,
            alias: $alias,
            column: $node->link->column,
            parentAlias: $parentAlias,
            parentColumn: $node->link->parentColumn,
        );
    }

    public function __toString(): string
    {
        return "  JOIN $this->table $this->alias ON $this->parentAlias.$this->parentColumn = $this->alias.$this->column";
    }
}
