<?php

namespace App\Libraries;

class ResponseFormatter {
    public static function success($data, $message = null, $pagination = null) {
        return [
            'success' => true,
            'data' => $data,
            'message' => $message ?? 'Data has been retrieved successfully',
            'pagination' => $pagination,
        ];
    }

    public static function error($data = null, $message = null, $code = 400) {
        return response()->json([
            'success' => false,
            'data' => $data,
            'message' => $message ?? 'Something went wrong',
        ], $code);
    }
}