<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReportSignatory extends Model
{
    protected $fillable = ['user_id', 'name', 'license_number', 'signature_path'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function signaturePath(): ?string
    {
        return $this->signature_path && Storage::disk('local')->exists($this->signature_path)
            ? Storage::disk('local')->path($this->signature_path) : null;
    }

    public function signatureDataUri(): string
    {
        $path = $this->signaturePath();

        return $path ? 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path)) : '';
    }
}
