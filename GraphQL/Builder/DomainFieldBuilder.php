<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace BD\EzPlatformGraphQLBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class DomainFieldBuilder implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $return = ['description' => 'Repository domain objects'];

        $domainSchemaFile = __DIR__. '/../../../../../src/AppBundle/Resources/config/graphql/platform_domain.types.yml';
        if (file_exists($domainSchemaFile)) {
            $return['type'] = 'DomainContentTypesList';
            $return['resolve'] = '[]';
        } else {
            $return['type'] = 'String';
            $return['resolve'] = 'This resource is only available once the domain types have been generated.';
        }

        return $return;
    }
}
