<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'nama_event' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'kategori_event_id' => 'nullable|exists:cabor_kategori,id',
            'tingkat_event_id' => 'nullable|exists:mst_tingkat,id',
            'lokasi' => 'nullable|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:draft,publish,selesai,dibatalkan',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $eventId = $this->route('event') ?? $this->route('id') ?? $this->input('id');

            if ($eventId) {
                $rules['nama_event'] = 'required|string|max:255|unique:event,nama_event,'.$eventId;
            } else {
                $rules['nama_event'] = 'required|string|max:255';
            }
        } else {
            $rules['nama_event'] = 'required|string|max:255|unique:event,nama_event';
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('tanggal_mulai') && $this->tanggal_mulai) {
            $this->merge([
                'tanggal_mulai' => Carbon::parse($this->tanggal_mulai)->format('Y-m-d'),
            ]);
        }

        if ($this->has('tanggal_selesai') && $this->tanggal_selesai) {
            $this->merge([
                'tanggal_selesai' => Carbon::parse($this->tanggal_selesai)->format('Y-m-d'),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'nama_event.required' => 'Nama event wajib diisi.',
            'nama_event.string' => 'Nama event harus berupa teks.',
            'nama_event.max' => 'Nama event tidak boleh lebih dari 255 karakter.',
            'nama_event.unique' => 'Nama event sudah ada.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPEG, PNG, JPG, atau GIF.',
            'foto.max' => 'Ukuran foto tidak boleh lebih dari 5MB.',
            'kategori_event_id.exists' => 'Kategori event yang dipilih tidak valid.',
            'tingkat_event_id.exists' => 'Tingkat event yang dipilih tidak valid.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date' => 'Tanggal mulai harus berupa tanggal yang valid.',
            'tanggal_selesai.date' => 'Tanggal selesai harus berupa tanggal yang valid.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus salah satu dari: draft, publish, selesai, dibatalkan.',
        ];
    }
}

