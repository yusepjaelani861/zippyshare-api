<?php

namespace App\Http\Controllers;

use App\Libraries\ResponseFormatter;
use App\Models\Files;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'is_protected' => 'nullable|string|in:true,false',
            'password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error($validator->errors(), $validator->errors()->first(), 422);
        }

        $file = $request->file('file');

        $ip = $this->getUserIP();
        $path = 'zippyshare/' . date('Y-m-d') . '/' . $ip . '/';
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();

        $filePath = $path . $name . '.' . $extension;

        Storage::disk('s3')->put($filePath, file_get_contents($file));

        $password = null;
        if ($request->is_protected === 'true') {
            $password = Hash::make($request->password);
        }

        $files = Files::create([
            'user_id' => null,
            'name' => $file->getClientOriginalName(),
            'path' => $filePath,
            'extension' => $extension,
            'size' => $file->getSize(),
            'ip' => $ip,
            'mime_type' => $file->getMimeType(),
            'disk' => 's3',
            'is_protected' => $request->is_protected === 'true',
            'password' => $password,
        ]);

        return ResponseFormatter::success($files, 'File uploaded successfully');
    }

    public function download(string $slug, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'nullable|string',
        ]);

        $file = Files::findBySlug($slug);
        if (!$file) {
            return ResponseFormatter::error(null, 'File not found', 404);
        }

        if ($file->is_protected) {
            if (!$request->has('password')) {
                return ResponseFormatter::error(null, 'Password is required', 422);
            }

            if (!Hash::check($request->input('password'), $file->password)) {
                return ResponseFormatter::error(null, 'Password is incorrect', 422);
            }
        }

        $aws = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'endpoint' => env('AWS_ENDPOINT'),
        ]);

        $cmd = $aws->getCommand('GetObject', [
            'Bucket' => env('AWS_BUCKET'),
            'Key' => $file->path,
        ]);

        $req = $aws->createPresignedRequest($cmd, '+60 minutes');

        $url = (string) $req->getUri();

        // $url = str_replace(env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET'), 'https://zippy.mitefiles.my.id', $url);

        return ResponseFormatter::success(['url' => $url], 'File download link generated successfully');
    }
}
