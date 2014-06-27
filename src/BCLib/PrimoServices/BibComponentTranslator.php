<?php

namespace BCLib\PrimoServices;

class BibComponentTranslator
{
    private $is_multi = false;
    private $keys = array();

    /**
     * @var BibComponent[]
     */
    private $components = array();

    /**
     * @param \stdClass $sear_doc a single "sear:DOC" object
     *
     * @return BibComponent[]
     */
    public function translate(\stdClass $sear_doc)
    {
        $record = $sear_doc->PrimoNMBib->record;

        $this->is_multi = is_array($record->control->originalsourceid);

        if ($this->is_multi) {
            $this->keys = array_map(
                function ($value) {
                    return $this->splitMultiField($value)->key;
                },
                $sear_doc->PrimoNMBib->record->control->originalsourceid
            );
        } else {
            $this->keys = array($record->control->recordid);
        }

        $this->components = array();
        foreach ($this->keys as $key) {
            $this->components[$key] = new BibComponent();
        }

        $this->assign($record->control, 'sourcerecordid', 'source_record_id');
        $this->assign($record->delivery, 'delcategory', 'delivery_category');
        $this->assign($record->control, 'sourceid', 'source');
        $this->assign($record->control, 'almaid', 'alma_id');


        return $this->components;
    }

    private function splitMultiField($value)
    {
        $parts = explode('$$O', $value);
        $pair = new \stdClass();
        $pair->val = substr($parts[0], 3);
        $pair->key = $parts[1];
        return $pair;
    }

    private function assign($group, $field, $property)
    {
        $values = $this->extractField($group, $field);
        foreach ($values as $value) {
            $pair = $this->splitMultiField($value);
            $this->components[$pair->key]->$property = $pair->val;
        }
    }

    private function extractField(\stdClass $group, $field)
    {
        if (!isset($group->$field)) {
            return array();
        }
        if ($this->is_multi) {
            return $group->$field;
        }
        return array('$$V' . $group->$field . '$$O' . $this->keys[0]);
    }
}