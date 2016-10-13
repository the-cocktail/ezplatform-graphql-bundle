<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace BD\EzPlatformGraphQLBundle\GraphQL\Resolver;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class DomainContentResolver
{
    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    private $contentService;

    /**
     * @var \eZ\Publish\API\Repository\SearchService
     */
    private $searchService;

    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    private $contentTypeService;

    /**
     * @var \Overblog\GraphQLBundle\Resolver\TypeResolver
     */
    private $typeResolver;

    public function __construct(ContentService $contentService, SearchService $searchService, ContentTypeService $contentTypeService, TypeResolver $typeResolver)
    {
        $this->contentService = $contentService;
        $this->searchService = $searchService;
        $this->contentTypeService = $contentTypeService;
        $this->typeResolver = $typeResolver;
    }

    public function resolveDomainArticles()
    {
        return $this->resolveDomainContentItems('article');
    }

    public function resolveDomainBlogPosts()
    {
        return $this->resolveDomainContentItems('blog_post');
    }

    private function resolveDomainContentItems($contentTypeIdentifier)
    {
        return array_map(
            function (Content $content) {
                $contentItem['_content'] = $content;
                foreach ($content->getFieldsByLanguage() as $field) {
                    $contentItem[$field->fieldDefIdentifier] = $field->value;
                };

                return $contentItem;
            },
            $this->findContentItemsByTypeIdentifier($contentTypeIdentifier)
        );
    }

    /**
     * @param $contentTypeIdentifier
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     */
    private function findContentItemsByTypeIdentifier($contentTypeIdentifier): array
    {
        $searchResults = $this->searchService->findContent(
            new Query([
                'filter' => new Query\Criterion\ContentTypeIdentifier($contentTypeIdentifier)
            ])
        );

        return array_map(
            function (SearchHit $searchHit) {
                return $searchHit->valueObject;
            },
            $searchResults->searchHits
        );
    }
}
