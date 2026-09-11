<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\SmartyTiki\Filter\Pre;

use PHPUnit\Framework\TestCase;
use Smarty\Smarty;
use SmartyTiki\Filter\Pre\Tr;

class TrTest extends TestCase
{
    /**
     * @dataProvider smartyStringProvider
     */
    public function testTranslationIsEscapedForSmartyString(
        string $source,
        array $translations,
        string $expectedSource,
        string $expectedOutput
    ): void {
        $filtered = $this->filter($source, $translations);

        $this->assertSame($expectedSource, $filtered);
        $this->assertSame($expectedOutput, $this->render($filtered));
    }

    public static function smartyStringProvider(): array
    {
        return [
            'single-quoted argument' => [
                "{assign var='value' value='{tr}Single quote{/tr}'}{\$value}",
                ['Single quote' => "L'apostrophe"],
                "{assign var='value' value='L\\'apostrophe'}{\$value}",
                "L'apostrophe",
            ],
            'double-quoted argument' => [
                '{assign var="value" value="{tr}Double quote{/tr}"}{$value}',
                ['Double quote' => 'Un "guillemet"'],
                '{assign var="value" value="Un \\"guillemet\\""}{$value}',
                'Un "guillemet"',
            ],
            'double quote is unchanged in a single-quoted argument' => [
                "{assign var='value' value='{tr}Double quote{/tr}'}{\$value}",
                ['Double quote' => 'Un "guillemet"'],
                "{assign var='value' value='Un \"guillemet\"'}{\$value}",
                'Un "guillemet"',
            ],
            'apostrophe is unchanged in a double-quoted argument' => [
                '{assign var="value" value="{tr}Single quote{/tr}"}{$value}',
                ['Single quote' => "L'apostrophe"],
                '{assign var="value" value="L\'apostrophe"}{$value}',
                "L'apostrophe",
            ],
            'backslashes in a single-quoted argument' => [
                "{assign var='value' value='{tr}Path{/tr}'}{\$value}",
                ['Path' => "C:\\Temp\\l'utilisateur"],
                "{assign var='value' value='C:\\\\Temp\\\\l\\'utilisateur'}{\$value}",
                "C:\\Temp\\l'utilisateur",
            ],
            'backslashes in a double-quoted argument' => [
                '{assign var="value" value="{tr}Path{/tr}"}{$value}',
                ['Path' => 'C:\\Temp\\"quoted"'],
                '{assign var="value" value="C:\\\\Temp\\\\\\"quoted\\""}{$value}',
                'C:\\Temp\\"quoted"',
            ],
            'escaped delimiter before translation' => [
                "{assign var='value' value='It\\'s {tr}Single quote{/tr}'}{\$value}",
                ['Single quote' => "L'apostrophe"],
                "{assign var='value' value='It\\'s L\\'apostrophe'}{\$value}",
                "It's L'apostrophe",
            ],
            'escaped double quote before translation' => [
                '{assign var="value" value="A \\"quote\\": {tr}Double quote{/tr}"}{$value}',
                ['Double quote' => 'Un "guillemet"'],
                '{assign var="value" value="A \\"quote\\": Un \\"guillemet\\""}{$value}',
                'A "quote": Un "guillemet"',
            ],
            'backtick expression before translation' => [
                '{assign var="suffix" value="x"}{assign var="value" value="Prefix `$suffix` {tr}Double quote{/tr}"}{$value}',
                ['Double quote' => 'Un "guillemet"'],
                '{assign var="suffix" value="x"}{assign var="value" value="Prefix `$suffix` Un \\"guillemet\\""}{$value}',
                'Prefix x Un "guillemet"',
            ],
            'apostrophe in source and translation' => [
                "{assign var='value' value='{tr}The site's URL{/tr}'}{\$value}",
                ["The site's URL" => "L'URL du site"],
                "{assign var='value' value='L\\'URL du site'}{\$value}",
                "L'URL du site",
            ],
            'adjacent translations in one argument' => [
                "{assign var='value' value='{tr}Single quote{/tr} / {tr}Other{/tr}'}{\$value}",
                ['Single quote' => "L'apostrophe", 'Other' => "d'aujourd'hui"],
                "{assign var='value' value='L\\'apostrophe / d\\'aujourd\\'hui'}{\$value}",
                "L'apostrophe / d'aujourd'hui",
            ],
            'ordinary translation before quote-sensitive translation' => [
                "{assign var='value' value='{tr}Safe{/tr} / {tr}Single quote{/tr}'}{\$value}",
                ['Safe' => 'Correct', 'Single quote' => "L'apostrophe"],
                "{assign var='value' value='Correct / L\\'apostrophe'}{\$value}",
                "Correct / L'apostrophe",
            ],
            'literal block does not affect later quote context' => [
                "{literal}{assign value=\"unterminated}{/literal}{assign var='value' value='{tr}Single quote{/tr}'}{\$value}",
                ['Single quote' => "L'apostrophe"],
                "{literal}{assign value=\"unterminated}{/literal}{assign var='value' value='L\\'apostrophe'}{\$value}",
                "{assign value=\"unterminated}L'apostrophe",
            ],
            'function expression argument' => [
                '{isset("{tr}Double quote{/tr}")}',
                ['Double quote' => 'Un "guillemet"'],
                '{isset("Un \\"guillemet\\"")}',
                '1',
            ],
            'translation passed to modifier' => [
                '{{"{tr}Quoted text{/tr}"|json_encode}}',
                ['Quoted text' => 'L\'utilisateur dit "bonjour"'],
                '{{"L\'utilisateur dit \\"bonjour\\""|json_encode}}',
                '"L\'utilisateur dit \\"bonjour\\""',
            ],
            'single-quoted translation passed to modifier' => [
                "{{'{tr}Single quote{/tr}'|json_encode}}",
                ['Single quote' => "L'apostrophe"],
                "{{'L\\'apostrophe'|json_encode}}",
                '"L\'apostrophe"',
            ],
        ];
    }

    /**
     * @dataProvider nonSmartyStringProvider
     */
    public function testTranslationIsNotEscapedOutsideSmartyString(string $source, string $expected): void
    {
        $this->assertSame($expected, $this->filter($source, ['Text' => "L'apostrophe"]));
    }

    public static function nonSmartyStringProvider(): array
    {
        return [
            'plain output' => ['{tr}Text{/tr}', "L'apostrophe"],
            'HTML attribute' => [
                "<span title='{tr}Text{/tr}'>Text</span>",
                "<span title='L'apostrophe'>Text</span>",
            ],
            'JavaScript string' => [
                "<script>const text = '{tr}Text{/tr}';</script>",
                "<script>const text = 'L'apostrophe';</script>",
            ],
            'JavaScript object property' => [
                "<script>const options = {title: '{tr}Text{/tr}'};</script>",
                "<script>const options = {title: 'L'apostrophe'};</script>",
            ],
            'unquoted Smarty argument' => [
                '{assign var=value value={tr}Text{/tr}}',
                "{assign var=value value=L'apostrophe}",
            ],
        ];
    }

    public function testInitialNewsletterFailureIsEscaped(): void
    {
        $source = "{icon name='copy' title='|{tr}Copy the preview html{/tr}' class='tips copy-html'}";

        $this->assertSame(
            "{icon name='copy' title='|Copier l\\'HTML de l\\'aperçu' class='tips copy-html'}",
            $this->filter($source, ['Copy the preview html' => "Copier l'HTML de l'aperçu"])
        );
    }

    public function testSpecialCharactersAreUnchangedInPlainOutput(): void
    {
        $translation = "C:\\Temp\\l'utilisateur dit \"bonjour\"";

        $this->assertSame($translation, $this->filter('{tr}Text{/tr}', ['Text' => $translation]));
    }

    public function testQuoteContextIsTrackedAcrossTranslations(): void
    {
        $source = "{tr}Outside{/tr}{assign var='value' value='{tr}Inside{/tr}'}{\$value}{tr}Outside again{/tr}";
        $filtered = $this->filter($source, [
            'Outside' => "L'apostrophe",
            'Inside' => "d'aujourd'hui",
            'Outside again' => "l'été",
        ]);

        $this->assertSame(
            "L'apostrophe{assign var='value' value='d\\'aujourd\\'hui'}{\$value}l'été",
            $filtered
        );
        $this->assertSame("L'apostrophed'aujourd'huil'été", $this->render($filtered));
    }

    public function testTranslatedSmartyTagIsStillEvaluated(): void
    {
        $filtered = $this->filter('{tr}Conditional{/tr}', [
            'Conditional' => '{if true}translated{/if}',
        ]);

        $this->assertSame('{if true}translated{/if}', $filtered);
        $this->assertSame('translated', $this->render($filtered));
    }

    public function testTranslatedSmartyTagInsideArgumentIsStillEvaluated(): void
    {
        $source = '{assign var="value" value="{tr}Conditional{/tr}"}{$value}';
        $filtered = $this->filter($source, [
            'Conditional' => '{if true}translated{/if}',
        ]);

        $this->assertSame('{assign var="value" value="{if true}translated{/if}"}{$value}', $filtered);
        $this->assertSame('translated', $this->render($filtered));
    }

    public function testRuntimeTranslationFallbackIsLeftUnchanged(): void
    {
        $source = '{assign var="value" value="{tr}Hello {$name}{/tr}"}';

        $this->assertSame($source, $this->filter($source, []));
    }

    private function filter(string $source, array $translations): string
    {
        $filter = new class ($translations) extends Tr {
            public function __construct(private readonly array $translations)
            {
            }

            protected function translate(string $source): string
            {
                return $this->translations[$source] ?? $source;
            }
        };

        $smarty = new Smarty();
        return $filter->filter($source, $smarty->createTemplate('eval:'));
    }

    private function render(string $source): string
    {
        return (new Smarty())->fetch('eval:' . $source);
    }
}
