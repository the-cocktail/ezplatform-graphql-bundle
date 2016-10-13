<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace BD\EzPlatformGraphQLBundle\Command;

use eZ\Publish\API\Repository\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Yaml\Yaml;

class GeneratePlatformDomainTypesCommand extends Command
{
    private $repository;

    /**
     * FieldType <=> GraphQL type mapping.
     * @todo Deduplicate, this comes from ContentResolver.
     *
     * @var array
     */
    private $typesMap = [
        'ezauthor' => 'AuthorFieldValue',
        'ezgmaplocation' => 'MapLocationFieldValue',
        'ezimage' => 'ImageFieldValue',
        'ezrichtext' => 'RichTextFieldValue',
        'ezstring' => 'TextLineFieldValue',
    ];

    public function __construct(Repository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected function configure()
    {
        $this->setName('bd:platform-graphql:generate-domain-schema');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $contentTypeService = $this->repository->getContentTypeService();

        $converter = new CamelCaseToSnakeCaseNameConverter(null, false);
        $schema = [
            'DomainContentTypesList' => [
                'type' => 'object',
                'config' => [
                    'fields' => []
                ]
            ],
        ];
        foreach ($contentTypeService->loadContentTypeGroups() as $contentTypeGroup)
        {
            foreach ($contentTypeService->loadContentTypes($contentTypeGroup) as $contentType) {
                $fields = [
                    '_content' => [
                        'description' => 'Underlying content item',
                        'type' => 'Content',
                        'resolve' => '@=value["_content"].contentInfo'
                    ],
                ];

                foreach ($contentType->getFieldDefinitions() as $fieldDefinition) {
                    $descriptions = $fieldDefinition->getDescriptions();

                    $fields[$fieldDefinition->identifier] = [
                        'type' => $this->mapFieldTypeIdentifierToGraphQLType($fieldDefinition->fieldTypeIdentifier),
                    ];

                    if (isset($descriptions['eng-GB'])) {
                        $fields[$fieldDefinition->identifier]['description'] = $descriptions['eng-GB'];
                    }
                }

                $graphQLTypeName = $converter->denormalize($contentType->identifier) . 'Content';
                $domainContentSchema = [
                    'type' => 'object',
                    'config' => [
                        'fields' => $fields,
                        'interfaces' => ['DomainContent'],
                    ]
                ];
                $descriptions = $contentType->getDescriptions();
                if (isset($descriptions['eng-GB'])) {
                    $domainContentSchema['description'] = $descriptions['eng-GB'];
                }

                $schema[$graphQLTypeName] = $domainContentSchema;
                $schema['DomainContentTypesList']['config']['fields'][$contentType->identifier] = [
                    'type' => "[$graphQLTypeName]",
                    'description' => '@todo',
                    'resolve' => sprintf(
                        '@=resolver("DomainContentItemsByTypeIdentifier", ["%s"])',
                        $contentType->identifier
                    ),
                ];
            }
        }


        $fs = new Filesystem();$fs->dumpFile(
            'src/AppBundle/Resources/config/graphql/platform_domain.types.yml',
            Yaml::dump($schema, 6)
        );

        $output->writeln('Platform domain types generated to src/AppBundle/Resources/config/graphql/platform_domain.types.yml');
    }

    private function mapFieldTypeIdentifierToGraphQLType($fieldTypeIdentifier)
    {
        return isset($this->typesMap[$fieldTypeIdentifier]) ? $this->typesMap[$fieldTypeIdentifier] : 'GenericFieldValue';
    }
}
