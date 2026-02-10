<?php

/**
 * @copyright
 */

namespace App\Controller\V1;

use App\Controller\V1\Schemas\Body;
use App\Controller\V1\Schemas\Section;
use App\Controller\V1\Schemas\Signatures;
use App\Controller\V1\Schemas\Tags;
use App\Controller\V1\Schemas\Titles;
use App\Orchestrator\OrchestratorChain;
use Ec\MicroserviceBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class EditorialController extends AbstractController
{
    public function __construct(
        private readonly OrchestratorChain $orchestratorChain,
        private readonly int $sMaxAge = 0,
    ) {
        parent::__construct($this->sMaxAge, 'v1.0.0');
    }

    // @codeCoverageIgnoreStart
    #[OA\Get(
        path: '/editorials/{id}',
        operationId: 'getEditorialById',
        description: 'Get editorial by id',
        summary: 'Get editorial by id',
        tags: ['Editorials'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Editorial id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '4433')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Editorial',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: '4433'),
                        new OA\Property(property: 'url', type: 'string', example: 'https://www.elconfidencial.dev/espana/madrid/2024-10-10/titular-con-mas-cosas_4433'),
                        new OA\Property(property: 'titles', ref: new Model(type: Titles::class)),
                        new OA\Property(property: 'lead', type: 'string', example: 'Entradilla con mas cosas'),
                        new OA\Property(property: 'publicationDate', type: 'string', format: 'date-time', example: '2024-10-10 13:09:00'),
                        new OA\Property(property: 'updatedOn', type: 'string', format: 'date-time', example: '2024-10-10 13:09:00'),
                        new OA\Property(property: 'endOn', type: 'string', format: 'date-time', example: '2024-10-10 13:09:00'),
                        new OA\Property(
                            property: 'type',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: '1'),
                                new OA\Property(property: 'name', type: 'string', example: 'news'),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'indexable', type: 'boolean', example: true),
                        new OA\Property(property: 'deleted', type: 'boolean', example: false),
                        new OA\Property(property: 'published', type: 'boolean', example: true),
                        new OA\Property(property: 'closingModeId', type: 'string', example: ''),
                        new OA\Property(property: 'commentable', type: 'boolean', example: true),
                        new OA\Property(property: 'isBrand', type: 'boolean', example: false),
                        new OA\Property(property: 'isAmazonOnsite', type: 'boolean', example: false),
                        new OA\Property(property: 'contentType', type: 'string', example: ''),
                        new OA\Property(property: 'canonicalEditorialId', type: 'string', example: ''),
                        new OA\Property(property: 'urlDate', type: 'string', format: 'date-time', example: '2024-10-10 13:09:00'),
                        new OA\Property(property: 'countWords', type: 'integer', example: 34),
                        new OA\Property(property: 'body', ref: new Model(type: Body::class)),
                        new OA\Property(property: 'signatures', ref: new Model(type: Signatures::class)),
                        new OA\Property(property: 'section', ref: new Model(type: Section::class)),
                        new OA\Property(property: 'tags', ref: new Model(type: Tags::class)),
                        new OA\Property(property: 'countComments', type: 'integer', example: 0),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Artículo no encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Article not found'),
                    ]
                )
            ),
        ]
    )]
    // @codeCoverageIgnoreEnd
    public function getEditorialById(Request $request, string $id): JsonResponse
    {
        $request->attributes->set('id', $id);

        return new JsonResponse($this->orchestratorChain->handler('editorial', $request));
    }
}
