<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../BaseTest.php';
require_once __DIR__ . '/../../src/model/SearchDefinitions.php';


/**
 * Test that enum values match field-definitions and translations
 */
final class SearchDefinitionsTest extends BaseTest
{
    public static function providerFieldDbNameTranslation(): array
    {
        return [
            [SortField::FIELD_RELEVANCE, 'relevance', 'Relevanz'],
            [SortField::FIELD_TITLE, 'title', 'Titel'],
            [SortField::FIELD_NICK, 'nick', 'Stammposter'],
            [SortField::FIELD_DATE, 'creation_ts', 'Datum'],
        ];
    }

    #[DataProvider('providerFieldDbNameTranslation')]
    public function testGetSortFieldTranslation(SortField $field, string $dbField, string $translation): void
    {
        static::assertEquals($translation, $field->getTranslation());
        static::assertEquals($dbField, $field->value);
    }

    public static function providerOrderSqlTranslation(): array
    {
        return [
            [SortOrder::ORDER_ASC, 'ASC', 'Aufsteigend'],
            [SortOrder::ORDER_DESC, 'DESC', 'Absteigend'],
        ];
    }

    #[DataProvider('providerOrderSqlTranslation')]
    public function testGetSortOrderTranslation(SortOrder $order, string $sql, string $translation): void
    {
        static::assertEquals($translation, $order->getTranslation());
        static::assertEquals($sql, $order->value);
    }
}
