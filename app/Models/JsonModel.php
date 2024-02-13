<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\EnumeratesValues;

class JsonModel
{
    protected string $table;

    protected array $schema = [];

    protected Collection $collection;


    public function __construct()
    {

        // get the file from storage folder 'database/' . $this->table . '.json'
        $table = (Storage::disk('database')->exists($this->table . '.json')) ? Storage::disk('database')->get($this->table . '.json') : null;

        if(!$table) {
            throw new \Exception('Table database/' . $this->table . '.json not found');
        }

        $table = json_decode($table, true);
        if(empty($this->schema)) {
            $this->schema = $table['tableStructure'];
        }
        $tableData = $table['tableData'];

        $this->collection = collect($tableData);
    }

    public function add($entry)
    {
        foreach ($this->schema as $column) {
            $entry[$column] = $entry[$column] ?? null;
        }
        // get total of existing entries
        $total = count($this->all());
        $entry['id'] = $total + 1;
        $entry['created_at'] = date('Y-m-d H:i:s');
        $entry['updated_at'] = date('Y-m-d H:i:s');

        // remove non-structure columns
        $entry = array_intersect_key($entry, array_flip($this->schema));
        $this->collection->push($entry);
    }

    public function save()
    {
        $table = [
            'tableStructure' => $this->schema,
            'tableData' => $this->all()
        ];

        //make sure the tableData has all the columns
        foreach($table['tableData'] as $index => $entry) {
            foreach ($this->schema as $column) {
                $table['tableData'][$index][$column] = $entry[$column] ?? null;
            }
        }
        Storage::disk('database')->put($this->table . '.json', json_encode($table));
    }

    public function find($id)
    {
        return $this->collection->where('id', $id)->first();
    }

    public function update($id, $entry)
    {
        $this->collection = $this->collection->map(function ($item) use ($id, $entry) {
            if ($item['id'] == $id) {
                $entry['updated_at'] = date('Y-m-d H:i:s');
                return array_merge($item, $entry);
            }
            return $item;
        });
        $this->save();
    }

    public function delete($id, string $field = 'id')
    {
        $this->collection = $this->collection->reject(function ($item) use ($id,$field) {
            return $item[$field] === $id;
        });

        $this->save();
    }

    public function create(array $attributes)
    {
        $this->add($attributes);
        $this->save();
    }

    public function exists()
    {
        return $this->collection->isNotEmpty();
    }

    public function __call($method, $parameters)
    {
        return $this->collection->{$method}(...$parameters);
    }

}
