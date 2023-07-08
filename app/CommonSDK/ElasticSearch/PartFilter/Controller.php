<?php

namespace App\CommonSDK\ElasticSearch\PartFilter;

use App\CommonSDK\API\Controllers\ApiController;
use App\CommonSDK\API\Responses\ApiResponse;
use App\CommonSDK\ElasticSearch\PartFilter\Request as PartFilterRequest;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class Controller extends ApiController
{
    protected string $indexName = 'parts_v2';

    protected PartFilterRequest $partFilterRequest;

    /**
     * Construct
     *
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(Request $request)
    {
        $this->validate($request, [
            'category_id'       =>  'nullable|integer',
            'manufacturer_id'   =>  'nullable|integer',
            'code'              =>  'nullable|string',
            'attributes'        =>  'nullable|array',
            'attributes.*'      =>  'array',
        ]);

        $this->partFilterRequest = new PartFilterRequest(
            $this->indexName,
            $request->input('category_id'),
            $request->input('manufacturer_id'),
            $request->input('code'),
            $request->input('attributes')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/part-filter/parts",
     *     description="查询 Parts",
     *     tags={"PartFilter"},
     *     @OA\Parameter(name="category_id", example="48", in="query", description="分类 ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="manufacturer_id", example="488", in="query", description="分类 ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="code", example="A", in="query", description="Part Code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="attributes[Mfr][0]", example="onsemi", in="query", description="Attributes", @OA\Schema(type="string")),
     *     @OA\Parameter(name="attributes[Mfr][1]", example="Texas Instruments", in="query", description="Attributes", @OA\Schema(type="string")),
     *     @OA\Parameter(name="attributes[Package][0]", example="Bulk", in="query", description="Attributes", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", example="1", in="query", description="页码", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", example="20", in="query", description="每页数量", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="success"),
     * )
     */
    public function getParts(Request $request)
    {
        $this->validate($request, [
            'page'      =>  'nullable|integer',
            'per_page'  =>  'nullable|integer',
        ]);

        $requestPageParam = (int) $request->input('page', 1);
        $requestPerPageParam = (int) $request->input('per_page', 20);

        try {
            $data = $this->partFilterRequest->getParts($requestPageParam, $requestPerPageParam);

            // 分页
            $pagination = [
                'current_page'  =>  $requestPageParam,
                'per_page'      =>  $requestPerPageParam,
                'total_page'    =>  ceil($data['parts_num'] / $requestPerPageParam),
            ];

            return ApiResponse::successResponse($data, pagination: $pagination);
        } catch (Exception $exception) {
            return ApiResponse::internalServerErrorResponse($exception->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/part-filter/part-attributes",
     *     description="查询 PartAttributes",
     *     tags={"PartFilter"},
     *     @OA\Parameter(name="category_id", example="48", in="query", description="分类 ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="manufacturer_id", example="488", in="query", description="分类 ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="code", example="A", in="query", description="Part Code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="attributes[Mfr][0]", example="onsemi", in="query", description="Attributes", @OA\Schema(type="string")),
     *     @OA\Parameter(name="attributes[Mfr][1]", example="Texas Instruments", in="query", description="Attributes", @OA\Schema(type="string")),
     *     @OA\Parameter(name="attributes[Package][0]", example="Bulk", in="query", description="Attributes", @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="success"),
     * )
     */
    public function getPartAttributes()
    {
        try {
            $data = $this->partFilterRequest->getAttributes();

            return ApiResponse::successResponse($data);
        } catch (Exception $exception) {
            return ApiResponse::internalServerErrorResponse($exception->getMessage());
        }
    }
}
