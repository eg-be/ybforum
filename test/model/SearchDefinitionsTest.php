<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/model/SearchDefinitions.php';


/**
 * Test that enum values match field-definitions and translations
 */
final class SearchDefinitionsTest extends BaseTest
{
    public static function providerFieldDbNameTranslation() : array 
    {
        return array(
            [SortField::FIELD_RELEVANCE, 'relevance', 'Relevanz'],
	        [SortField::FIELD_TITLE, 'title', 'Titel'],
            [SortField::FIELD_NICK, 'nick', 'Stammposter'],
	        [SortField::FIELD_DATE, 'creation_ts', 'Datum']
        );
    }

    #[DataProvider('providerFieldDbNameTranslation')]
    function testGetSortFieldTranslation(SortField $field, string $dbField, string $translation) {
        $this->assertEquals($translation, $field->getTranslation());
        $this->assertEquals($dbField, $field->value);
    }

    public static function providerOrderSqlTranslation() : array 
    {
        return array(
            [SortOrder::ORDER_ASC , 'ASC', 'Aufsteigend'],
	        [SortOrder::ORDER_DESC , 'DESC', 'Absteigend']
        );
    }

    #[DataProvider('providerOrderSqlTranslation')]
    function testGetSortOrderTranslation(SortOrder $order, string $sql, string $translation) {
        $this->assertEquals($translation, $order->getTranslation());
        $this->assertEquals($sql, $order->value);
    }
}