<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace BD\EzPlatformGraphQLBundle\Command;

use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use EzSystems\RepositoryForms\Data\Mapper\ContentCreateMapper;
use EzSystems\RepositoryForms\Form\Type\Content\ContentEditType;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class GraphQLRepositoryMutationsCommand extends ContainerAwareCommand
{
    const LANGUAGE = 'eng-GB';

    protected function configure()
    {
        $this->setName('graphql:repository-mutations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $contentTypeService = $this->getContentTypeService();

        $config = [
            'RepositoryFormsMutations' => [
                'type' => 'object',
                'config' => [
                    'fields' => []
                ]
            ]
        ];

        $mutationsConfig = &$config['RepositoryFormsMutations']['config']['fields'];
        foreach ($contentTypeService->loadContentTypeGroups() as $contentTypeGroup) {
            foreach ($contentTypeService->loadContentTypes($contentTypeGroup) as $contentType) {
                $mutationName = 'create' . ucfirst($contentType->getName(self::LANGUAGE));
                $mutationsConfig[$mutationName] = [
                    'builder' => 'Mutation',
                    'builderConfig' => [
                        'inputType' => $mutationName . 'Input',
                        'payloadType' => $mutationName . 'Payload',
                        'mutateAndGetPayload' => "@=mutation(\"$mutationName\", [value])"
                    ]
                ];

                $form = $this->buildContentTypeForm($contentType);
                /** @var \EzSystems\RepositoryForms\Data\Content\FieldData $fieldData */
                foreach ($form->get('fieldsData')->all() as $fieldData) {

                }
                // $transformedForm = $this->getContainer()->get('liform')->transform($form);
            }
        }

        echo Yaml::dump($mutationsConfig, 5);
    }

    private function getContentTypeService()
    {
        return $this->getContainer()->get('ezpublish.api.service.content_type');
    }

    private function getLocationService()
    {
        return $this->getContainer()->get('ezpublish.api.service.location');
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildContentTypeForm(ContentType $contentType)
    {
        $data = (new ContentCreateMapper())->mapToFormData($contentType, [
            'mainLanguageCode' => 'eng-GB',
            'parentLocation' => $this->getLocationService()->newLocationCreateStruct(2),
        ]);
        return $this->createForm(
            ContentEditType::class,
            $data,
            [
                'languageCode' => self::LANGUAGE,
                'controls_enabled' => false,
            ]
        );
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createForm($type, $data = null, array $options = array())
    {
        return $this->getContainer()->get('form.factory')->create($type, $data, $options);
    }
}
