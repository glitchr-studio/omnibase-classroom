<?php

namespace Base\Classroom\DependencyInjection;

use Base\Bundle\AbstractBaseConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ClassroomConfiguration extends AbstractBaseConfiguration
{
    private bool $childrenDeclared = false;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = $this->getTreeBuilder();
        if ($this->childrenDeclared) {
            return $treeBuilder;
        }
        $this->childrenDeclared = true;

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('storage_dir')->defaultValue('%kernel.project_dir%/var/storage/classroom')->info('Where the resources are (the local.classroom flysystem storage points there).')->end()
                ->scalarNode('accel_prefix')->defaultValue('')->info('nginx internal location streaming the files (X-Accel-Redirect); empty: PHP streams them.')->end()
                ->integerNode('download_ttl')->min(30)->defaultValue(600)->info('Seconds a signed download link stays valid.')->end()
                ->integerNode('preview_rows')->min(1)->defaultValue(30)->info('Rows of a spreadsheet shown in a resource\'s preview.')->end()
                ->scalarNode('members_role')->defaultValue('ROLE_USER')->info('Who the MEMBERS resources are for.')->end()
                ->integerNode('per_page')->min(1)->defaultValue(24)->end()
                ->scalarNode('from_email')->defaultNull()->info('Sender of the mail a paid order gets (the link to its files); null: the mailer\'s default sender.')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
