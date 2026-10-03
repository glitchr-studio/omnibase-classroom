<?php

namespace Base\Classroom;

use Base\Bundle\AbstractBaseBundle;
use Base\Traits\SingletonTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** The open classroom: levels, subjects, sequences, cards and resources on omnibase's Thread and Taxon. */
class ClassroomBundle extends AbstractBaseBundle
{
    use SingletonTrait;

    public function __construct()
    {
        parent::__construct();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->setMapping($this->getPath().'/src/Entity', 'Base\Classroom\Entity', 'App\Entity\Classroom');
        $this->setMapping($this->getPath().'/src/Repository', 'Base\Classroom\Repository', 'App\Repository\Classroom');
    }
}
