<?php

namespace App\Helpers;

class ResponseHelper {
    /**
     * Create a response template
     *
     * @param  string $message
     * @param  array()|object $data
     * @param  int $status
     * @return Illuminate\Http\Response;
     */
    public static function response(string $message, int $status, $data = null) {
        $resp = [
            'status' => $status,
            'message' => $message,
        ];

        if ($data != null) {
            $resp['data'] = $data;
        }
        return response()->json($resp, $status);
    }
}
