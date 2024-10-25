<?php

namespace App\Http\Controllers;

use App\Libraries\ResponseFormatter;
use App\Models\Files;
use Illuminate\Http\Request;

class FilesController extends Controller
{
    public function index(Request $request)
    {
        $files = Files::orderBy('id', 'desc')->paginate($request->limit);

        return ResponseFormatter::success($files->items(), 'Files retrieved successfully', $this->autoPagination($files));
    }

    public function show(string $slug)
    {
        $file = Files::findBySlug($slug);
        if (!$file) {
            return ResponseFormatter::error(null, 'File not found', 404);
        }

        $file->url = $file->url;

        return ResponseFormatter::success($file, 'File found successfully');
    }
}
