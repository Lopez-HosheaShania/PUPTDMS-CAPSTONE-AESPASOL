<?php

namespace App\Models\Concerns;

trait StoresOptionalDetails
{
    private array $pendingOptionalDetails = [];

    public function initializeStoresOptionalDetails(): void
    {
        $relations = array_keys($this->detailFields());
        $this->with = array_merge($this->with, $relations);
        $this->hidden = array_merge($this->hidden, $relations);
    }

    private function optionalDetailRelation($key): ?string
    {
        foreach ($this->detailFields() as $relation => $fields) {
            if (in_array($key, $fields, true)) {
                return $relation;
            }
        }

        return null;
    }

    public function getAttribute($key)
    {
        if ($relation = $this->optionalDetailRelation($key)) {
            if (array_key_exists($key, $this->pendingOptionalDetails[$relation] ?? [])) {
                return $this->{$relation}()->getRelated()->newInstance($this->pendingOptionalDetails[$relation])->getAttribute($key);
            }

            return $this->getRelationValue($relation)?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if ($relation = $this->optionalDetailRelation($key)) {
            $this->pendingOptionalDetails[$relation][$key] = $value;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function attributesToArray()
    {
        $values = [];
        foreach ($this->detailFields() as $relation => $fields) {
            $detail = $this->{$relation}()->getRelated()->newInstance();
            foreach ($fields as $field) {
                $detail->setAttribute($field, $this->getAttribute($field));
            }
            $values = array_merge($values, $detail->attributesToArray());
        }

        return array_merge(parent::attributesToArray(), $this->getArrayableItems($values));
    }

    public function save(array $options = [])
    {
        if ($this->pendingOptionalDetails === []) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(function () use ($options) {
            if (! parent::save($options)) {
                return false;
            }
            foreach ($this->pendingOptionalDetails as $relation => $values) {
                $existing = $this->{$relation}()->first();
                // Do not create empty optional detail rows.
                if (! $existing && count(array_filter($values, fn ($value) => $value !== null)) === 0) {
                    $this->setRelation($relation, null);
                    continue;
                }
                $this->setRelation($relation, $this->{$relation}()->updateOrCreate([], $values));
            }
            $this->pendingOptionalDetails = [];

            return true;
        });
    }
}
