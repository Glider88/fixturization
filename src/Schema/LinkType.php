<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

enum LinkType {
    case OneToMany;
    case ManyToOne;
}
