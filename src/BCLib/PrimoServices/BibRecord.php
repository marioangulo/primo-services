<?php

namespace BCLib\PrimoServices;

use DOMDocument;
use DOMXPath;

/**
 * Class BibRecord
 * @package BCLib\PrimoServices
 *
 * @property string               $id
 * @property string               $title
 * @property Person               $creator
 * @property string[]             $contributors
 * @property string               $date
 * @property string               $publisher
 * @property string               $abstract
 * @property string               $frbr_group_id
 * @property string               $type
 * @property string               $url
 * @property string               $availability
 * @property object               $cover_images
 * @property string               $isbn
 * @property string               $issn
 * @property string               $oclcid
 * @property string[]             $subjects
 * @property string               $display_subject
 * @property string[]             $genres
 * @property string[]             $creator_facet
 * @property string[]             $collection_facet
 * @property string[]             $languages
 * @property string               $format
 * @property string               $description
 * @property BibRecordComponent[] $components
 */
class BibRecord implements \JsonSerializable
{
    use EncodeJson;

    /**
     * @var \DOMDocument
     */
    private $_xml;

    /**
     * @var \DOMXPath
     */
    private $_xpath;

    private $_xml_literal;

    private $_creator;
    private $_components;
    private $_frbr_group_id;

    private $_person_template;
    private $_component_template;

    private $_single_elements = array(
        'id'              => 'control/recordid',
        'title'           => 'display/title',
        'date'            => 'display/date',
        'publisher'       => 'display/publisher',
        'abstract'        => 'addata/abstract',
        'availability'    => 'display/availpnx',
        'issn'            => 'search/issn',
        'isbn'            => 'search/isbn',
        'oclcid'          => 'addata/oclcid',
        'type'            => 'display/type',
        'display_subject' => 'display/subject',
        'format'          => 'display/format',
        'description'     => 'display/description'
    );

    private $_array_elements = array(
        'subjects'      => 'facets/topic',
        'genres'        => 'facets/genre',
        'languages'     => 'facets/language',
        'creator_facet' => 'facets/creatorcontrib',
        'contributors'  => 'display/contributor'
    );

    public function __construct(Person $person_template, BibRecordComponent $component_template)
    {
        $this->_person_template = $person_template;
        $this->_component_template = $component_template;
    }

    public function load(\DOMDocument $xml)
    {
        $this->_xml = $xml;
        $this->_xpath = new \DOMXPath($xml);
    }

    public function field($path)
    {
        $return_array = array();
        foreach ($this->_xpath->query("/record/$path") as $result) {
            $return_array[] = $result->textContent;
        }
        return $return_array;
    }

    private function _load_components()
    {
        /** @var $components BibRecordComponent[] */
        $is_deduped = strpos($this->id, 'dedup') !== false;

        if (!$is_deduped) {
            $component = clone $this->_component_template;
            $component->alma_id = $this->_getSingle('control/almaid');
            $component->delivery_category = $this->_getSingle('delivery/delcategory');
            $component->source = $this->_getSingle('control/sourceid');
            $component->source_record_id = $this->_getSingle('control/sourcerecordid');
            $this->_components[] = $component;
        } else {
            $source_record_ids = $this->field('control/sourcerecordid');
            foreach ($source_record_ids as $source_record_id) {

                $component = clone $this->_component_template;
                $parts = $element_parts = preg_split('/\$\$\w/', $source_record_id);
                $component->source_record_id = $parts[1];
                $component->alma_id = $this->_loadComponentPart($parts[2], 'control/almaid');
                $component->delivery_category = $this->_loadComponentPart($parts[2], 'delivery/delcategory');
                $component->source = $this->_loadComponentPart($parts[2], 'control/sourceid');
                $this->_components[] = $component;
            }
        }
    }

    protected function _loadComponentPart($identifier, $path)
    {
        $xpath = $path . "[contains(.,'$identifier')]";
        $part = $this->_getSingle($xpath);
        $parts = preg_split('/\$\$\w/', $part);
        return $parts[1];
    }

    private function _getSingle($path)
    {
        $result_array = $this->field($path);
        return isset($result_array[0]) ? $result_array[0] : '';
    }

    public function __get($property)
    {
        if (isset($this->_single_elements[$property])) {
            return $this->_getSingle($this->_single_elements[$property]);
        }

        if (isset($this->_array_elements[$property])) {
            return $this->field($this->_array_elements[$property]);
        }

        $load_method = '_load_' . $property;
        if (method_exists($this, $load_method)) {

            $property_name = '_' . $property;
            if (!isset($this->$property_name)) {
                $this->$load_method();
            }

            return $this->$property_name;
        }

        throw new \Exception("$property is not a valid property name");
    }

    protected function _load_creator()
    {
        $this->_creator = clone $this->_person_template;
        $this->_creator->first_name = $this->_getSingle('addata/aufirst');
        $this->_creator->last_name = $this->_getSingle('addata/aulast');
        $this->_creator->display_name = $this->_getSingle('display/creator');
    }

    /**
     * Prepare for serlialization
     *
     * @return array
     */
    public function __sleep()
    {
        $this->_xml_literal = $this->_xml->saveXML();
        return array('_xml_literal', '_creator', '_component', '_person_template', '_component_template');
    }

    /**
     * Wake up from serialization
     */
    public function __wakeup()
    {
        $this->_xml = new \DOMDocument();
        $this->_xml->loadXML($this->_xml_literal);
        $this->_xpath = new \DOMXPath($this->_xml);
    }
}