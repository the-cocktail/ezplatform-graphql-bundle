<?php

namespace spec\BD\EzPlatformGraphQLBundle\DependencyInjection\Compiler;

use BD\EzPlatformGraphQLBundle\DependencyInjection\Compiler\RepositoryFormMutationPass;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Repository;
use PhpSpec\ObjectBehavior;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RepositoryFormMutationPassSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(RepositoryFormMutationPass::class);
        $this->shouldHaveType(CompilerPassInterface::class);
    }

    function it_reads_the_list_of_content_types_from_the_repository(
        ContainerBuilder $containerBuilder,
        ContentTypeService $contentTypeService
    ) {
        $containerBuilder->get('ezpublish.api.service.content_type')->willReturn($contentTypeService);

        $this->process($containerBuilder);
    }
}
