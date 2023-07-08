<?php

namespace App\CommonSDK\API\Responses;

use Illuminate\Http\Response;

class ApiResponse extends Response
{
    /**
     * Make ApiResponse
     */
    public static function make(): ApiResponse
    {
        $selfInstance = new self();
        $selfInstance->header('Content-Type', 'application/json');

        return $selfInstance;
    }

    /**
     * Success response
     */
    public static function successResponse($data, $message = null, $statusCode = self::HTTP_OK, $pagination = null): ApiResponse
    {
        $content = [
            'code'      =>  0,
            'msg'       =>  $message,
            'data'      =>  $data,
        ];

        if ($pagination) $content['pagination'] = $pagination;

        return self::make()
            ->setContent($content)
            ->setStatusCode($statusCode);
    }

    /**
     * Failed response
     */
    public static function failedResponse($message, $statusCode = self::HTTP_INTERNAL_SERVER_ERROR, $data = null)
    {
    }

    /**
     * Not found response
     */
    public static function notFoundResponse(): ApiResponse
    {
        return self::make()
            ->setContent([
                'code'      =>  self::HTTP_NOT_FOUND,
                'msg'       =>  self::$statusTexts[self::HTTP_NOT_FOUND],
            ])->setStatusCode(404);
    }

    /**
     * Internal server error response
     */
    public static function internalServerErrorResponse($message = null, $statusCode = self::HTTP_INTERNAL_SERVER_ERROR, $data = null): ApiResponse
    {
        $message || $message = self::$statusTexts[$statusCode] ?? self::$statusTexts[self::HTTP_INTERNAL_SERVER_ERROR];

        $content = [
            'code'      =>  $statusCode,
            'msg'       =>  $message,
        ];
        $data && $content['data'] = $data;

        return self::make()->setContent($content)->setStatusCode($statusCode);
    }

    /**
     * 请求数据校验失败响应
     *
     * @param string $message
     * @param null $data
     *
     * @return ApiResponse
     */
    public static function requestValidationFailedResponse($message = 'Request data validation failed', $errors = null)
    {
        $statusCode = self::HTTP_UNPROCESSABLE_ENTITY;

        $content = [
            'code'      =>  $statusCode,
            'msg'       =>  $message ?? self::$statusTexts[$statusCode],
        ];
        $errors && $content['errors'] = $errors;

        return self::make()->setContent($content)->setStatusCode($statusCode);
    }
}
