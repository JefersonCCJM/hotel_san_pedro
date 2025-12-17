<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class FactusApiException extends Exception
{
    protected $statusCode;
    protected $responseBody;

    public function __construct(string $message, int $statusCode = 500, $responseBody = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'error_code' => $this->statusCode,
                'details' => $this->responseBody
            ], $this->statusCode);
        }

        return back()->with('error', $this->getMessage())->withInput();
    }
}
