<?php

namespace App\Helpers;

class ResponseHelper {
    public static function response(string $message, $data, int $status) {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $status);
    }
}
