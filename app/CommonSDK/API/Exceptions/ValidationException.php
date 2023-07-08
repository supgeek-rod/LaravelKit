<?php

namespace App\CommonSDK\API\Exceptions;

use App\CommonSDK\API\Responses\ApiResponse;

class ValidationException extends \Illuminate\Validation\ValidationException
{
    public function render()
    {
        $errors = $this->validator->errors()->toArray();

        return ApiResponse::requestValidationFailedResponse(errors: $errors);
    }
}
