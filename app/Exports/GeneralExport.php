<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GeneralExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $model;

    protected $request;

    protected $columns;

    protected $headings;

    protected $sheetName;

    public function __construct(Model $model, $request = [], $columns = [], $headings = [], $sheetName = null)
    {
        $this->model = $model;

        $fillableColumns = $model->getFillable();
        // Tambahkan 'id' ke kolom jika tidak ada di fillable
        if (! in_array('id', $fillableColumns)) {
            array_unshift($fillableColumns, 'id');
        }

        $this->request  = $request;
        $this->columns  = $columns ?: $fillableColumns;
        $this->headings = $headings ?: $this->columns;
        // Set sheetName, jika tidak diberikan gunakan nama tabel sebagai default
        $this->sheetName = $sheetName ?: $this->model->getTable();
    }

    public function collection()
    {
        return $this->model->select($this->columns)->filter($this->request)->get();
    }

    public function map($row): array
    {
        return array_map(function ($column) use ($row) {
            return $row->{$column};
        }, $this->columns);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    // Set title dengan nama sheet
    public function title(): string
    {
        return $this->sheetName;
    }
}
