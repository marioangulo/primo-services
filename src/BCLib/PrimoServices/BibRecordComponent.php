<?php

namespace BCLib\PrimoServices;

/**
 * Class BibRecordComponent
 * @package BCLib\PrimoServices
 *
 * @property string $alma_id
 * @property string $source
 * @property string $source_record_id
 * @property string $link_to_worldcat
 * @property string $delivery_category
 */
class BibRecordComponent implements \JsonSerializable
{
    use Accessor, EncodeJson;

    private $_alma_id;
    private $_source;
    private $_source_record_id;
    private $_link_to_worldcat;
    private $_delivery_category;
}