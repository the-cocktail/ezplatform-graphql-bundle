<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace BD\EzPlatformGraphQLBundle\GraphQL\Resolver;

use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\Core\QueryType\QueryTypeRegistry;

class SearchResolver
{
    /**
     * @var SearchService
     */
    private $searchService;

    /**
     * @var QueryTypeRegistry
     */
    private $queryTypeRegistry;

    public function __construct(SearchService $searchService, QueryTypeRegistry $queryTypeRegistry)
    {
        $this->searchService = $searchService;
        $this->queryTypeRegistry = $queryTypeRegistry;
    }

    public function searchContent($args)
    {
        $queryArg = $args['query'];

        $query = new Query();
        $criteria = [];

        if (isset($queryArg['ContentTypeIdentifier'])) {
            $criteria[] = new Query\Criterion\ContentTypeIdentifier($queryArg['ContentTypeIdentifier']);
        }

        if (isset($queryArg['Text'])) {
            foreach ($queryArg['Text'] as $text) {
                $criteria[] = new Query\Criterion\FullText($text);
            }
        }

        if (count($criteria) === 0) {
            return null;
        }
        $query->filter = count($criteria) > 1 ? new Query\Criterion\LogicalAnd($criteria) : $criteria[0];
        $searchResult = $this->searchService->findContentInfo($query);

        return $this->mapSearchHitsToValueObjects($searchResult);
    }

    /**
     * Searches for content using a $queryTypeIdentifier with $args.
     *
     * Args are passed as-is to the QueryType.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     */
    public function searchContentWithQueryType($queryTypeIdentifier, $args)
    {
        $queryType = $this->queryTypeRegistry->getQueryType($queryTypeIdentifier);
        $searchResults = $this->searchService->findContentInfo($queryType->getQuery($args->getRawArguments()));

        return $this->mapSearchHitsToValueObjects($searchResults);
    }

    /**
     * @param $searchResult
     * @return \eZ\Publish\API\Repository\Values\ValueObject[]
     */
    private function mapSearchHitsToValueObjects($searchResult)
    {
        return array_map(
            function (SearchHit $hit) {
                return $hit->valueObject;
            },
            $searchResult->searchHits
        );
    }
}
