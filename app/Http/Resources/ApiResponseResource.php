<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Pastikan aman ketika resource berupa array, object, atau tipe lain
        $payload = is_array($this->resource) ? $this->resource : (array) ($this->resource ?? []);

        $status  = $payload['status']  ?? 'success';
        $message = $payload['message'] ?? 'Success';
        $data    = array_key_exists('data', $payload) ? $payload['data'] : $this->resource;
        $meta    = $payload['meta']   ?? null;
        $errors  = $payload['errors'] ?? null;

        return [
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
            'meta'    => $this->when($meta, $meta),
            'errors'  => $this->when($errors, $errors),
        ];
    }

    /**
     * Create a success response
     */
    public static function success($data = null, $message = 'Success', $meta = null)
    {
        return new static([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
            'meta'    => $meta,
        ]);
    }

    /**
     * Create an error response
     */
    public static function error($message = 'Error', $errors = null, $status = 400)
    {
        return new static([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ]);
    }
}
