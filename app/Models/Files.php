<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class Files extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'path',
        'extension',
        'size',
        'ip',
        'mime_type',
        'disk',
        'is_protected',
        'password',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
    ];

    protected $appends = [
        'slug',
        // 'url',
    ];

    protected $hidden = [
        'password',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            Storage::disk($model->disk)->delete($model->path);
        });
    }

    public function getSlugAttribute()
    {
        return Hashids::encode($this->id);
    }

    public static function findBySlug(string $slug)
    {
        $id = Hashids::decode($slug);

        if (count($id) === 0) {
            return null;
        }

        return static::find($id[0]);
    }

    public function getUrlAttribute()
    {
        $extensionAllowed = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg', 'ico', 'mp4', 'ts'];
        if (!in_array($this->extension, $extensionAllowed)) {
            return null;
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
            'Key' => $this->path,
        ]);

        $req = $aws->createPresignedRequest($cmd, '+60 minutes');

        $url = (string) $req->getUri();

        $originalUrl = $url;
        $url = str_replace(env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET'), 'https://zippy.mitefiles.my.id', $url);
        
        // random gacha return originalUrl or Url
        $random = rand(0, 1);
        if ($random === 0) {
            return $originalUrl;
        }

        return $url;
    }
}
