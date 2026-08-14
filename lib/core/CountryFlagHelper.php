<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki;

/**
 * Helper for country flag emoji rendering.
 *
 * Maps Tiki country names (used as PNG filenames) to ISO 3166-1 alpha-2 codes,
 * then converts those codes to Unicode Regional Indicator Symbol pairs (emoji flags).
 * Entries with no ISO code (e.g. fictional regions, dissolved states) map to null
 * and produce no output.
 */
class CountryFlagHelper
{
    // Tiki country name => ISO 3166-1 alpha-2 code (null = no standard emoji available)
    private const COUNTRIES = [
        'Afghanistan'                                  => 'AF',
        'Aland_Islands'                                => 'AX',
        'Albania'                                      => 'AL',
        'Algeria'                                      => 'DZ',
        'American_Samoa'                               => 'AS',
        'Andorra'                                      => 'AD',
        'Angola'                                       => 'AO',
        'Anguilla'                                     => 'AI',
        'Antigua'                                      => 'AG',
        'Argentina'                                    => 'AR',
        'Armenia'                                      => 'AM',
        'Aruba'                                        => 'AW',
        'Australia'                                    => 'AU',
        'Austria'                                      => 'AT',
        'Azerbaijan'                                   => 'AZ',
        'Bahamas'                                      => 'BS',
        'Bahrain'                                      => 'BH',
        'Bangladesh'                                   => 'BD',
        'Barbados'                                     => 'BB',
        'Belarus'                                      => 'BY',
        'Belgium'                                      => 'BE',
        'Belize'                                       => 'BZ',
        'Benin'                                        => 'BJ',
        'Bermuda'                                      => 'BM',
        'Bhutan'                                       => 'BT',
        'Bolivia'                                      => 'BO',
        'Bosnia_and_Herzegovina'                       => 'BA',
        'Botswana'                                     => 'BW',
        'Bouvet_Island'                                => 'BV',
        'Brazil'                                       => 'BR',
        'British_Indian_Ocean_Territory'               => 'IO',
        'British_Virgin_Islands'                       => 'VG',
        'Brunei'                                       => 'BN',
        'Bulgaria'                                     => 'BG',
        'Burkina_Faso'                                 => 'BF',
        'Burundi'                                      => 'BI',
        'Cambodia'                                     => 'KH',
        'Cameroon'                                     => 'CM',
        'Canada'                                       => 'CA',
        'Cape_Verde'                                   => 'CV',
        'Catalan_Countries'                            => null,
        'Cayman_Islands'                               => 'KY',
        'Central_African_Republic'                     => 'CF',
        'Chad'                                         => 'TD',
        'Chile'                                        => 'CL',
        'China'                                        => 'CN',
        'Christmas_Island'                             => 'CX',
        'Cocos_Islands'                                => 'CC',
        'Colombia'                                     => 'CO',
        'Comoros'                                      => 'KM',
        'Congo'                                        => 'CG',
        'Cook_Islands'                                 => 'CK',
        'Costa_Rica'                                   => 'CR',
        'Croatia'                                      => 'HR',
        'Cuba'                                         => 'CU',
        'Cyprus'                                       => 'CY',
        'Czech_Republic'                               => 'CZ',
        'Democratic_Republic_of_the_Congo'             => 'CD',
        'Denmark'                                      => 'DK',
        'Djibouti'                                     => 'DJ',
        'Dominica'                                     => 'DM',
        'Dominican_Republic'                           => 'DO',
        'Ecuador'                                      => 'EC',
        'Egypt'                                        => 'EG',
        'El_Salvador'                                  => 'SV',
        'England'                                      => null,
        'Equatorial_Guinea'                            => 'GQ',
        'Eritrea'                                      => 'ER',
        'Estonia'                                      => 'EE',
        'Ethiopia'                                     => 'ET',
        'Europe'                                       => null,
        'Falkland_Islands'                             => 'FK',
        'Faroe_Islands'                                => 'FO',
        'Federated_States_of_Micronesia'               => 'FM',
        'Fiji'                                         => 'FJ',
        'Finland'                                      => 'FI',
        'France'                                       => 'FR',
        'French_Guiana'                                => 'GF',
        'French_Polynesia'                             => 'PF',
        'French_Southern_Territories'                  => 'TF',
        'Gabon'                                        => 'GA',
        'Gambia'                                       => 'GM',
        'Georgia'                                      => 'GE',
        'Germany'                                      => 'DE',
        'Ghana'                                        => 'GH',
        'Gibraltar'                                    => 'GI',
        'Greece'                                       => 'GR',
        'Greenland'                                    => 'GL',
        'Grenada'                                      => 'GD',
        'Guadeloupe'                                   => 'GP',
        'Guam'                                         => 'GU',
        'Guatemala'                                    => 'GT',
        'Guinea'                                       => 'GN',
        'Guinea_Bissau'                                => 'GW',
        'Guyana'                                       => 'GY',
        'Haiti'                                        => 'HT',
        'Heard_Island_and_McDonald_Islands'            => 'HM',
        'Honduras'                                     => 'HN',
        'Hong_Kong'                                    => 'HK',
        'Hungary'                                      => 'HU',
        'Iceland'                                      => 'IS',
        'India'                                        => 'IN',
        'Indonesia'                                    => 'ID',
        'Iran'                                         => 'IR',
        'Iraq'                                         => 'IQ',
        'Ireland'                                      => 'IE',
        'Israel'                                       => 'IL',
        'Italy'                                        => 'IT',
        'Ivory_Coast'                                  => 'CI',
        'Jamaica'                                      => 'JM',
        'Japan'                                        => 'JP',
        'Jordan'                                       => 'JO',
        'Kazakstan'                                    => 'KZ',
        'Kenya'                                        => 'KE',
        'Kiribati'                                     => 'KI',
        'Kuwait'                                       => 'KW',
        'Kyrgyzstan'                                   => 'KG',
        'Laos'                                         => 'LA',
        'Latvia'                                       => 'LV',
        'Lebanon'                                      => 'LB',
        'Lesotho'                                      => 'LS',
        'Liberia'                                      => 'LR',
        'Libya'                                        => 'LY',
        'Liechtenstein'                                => 'LI',
        'Lithuania'                                    => 'LT',
        'Luxemburg'                                    => 'LU',
        'Macao'                                        => 'MO',
        'Madagascar'                                   => 'MG',
        'Malawi'                                       => 'MW',
        'Malaysia'                                     => 'MY',
        'Maldives'                                     => 'MV',
        'Mali'                                         => 'ML',
        'Malta'                                        => 'MT',
        'Marshall_Islands'                             => 'MH',
        'Martinique'                                   => 'MQ',
        'Mauritania'                                   => 'MR',
        'Mauritius'                                    => 'MU',
        'Mayotte'                                      => 'YT',
        'Mexico'                                       => 'MX',
        'Moldova'                                      => 'MD',
        'Monaco'                                       => 'MC',
        'Mongolia'                                     => 'MN',
        'Montenegro'                                   => 'ME',
        'Montserrat'                                   => 'MS',
        'Morocco'                                      => 'MA',
        'Mozambique'                                   => 'MZ',
        'Myanmar'                                      => 'MM',
        'Namibia'                                      => 'NA',
        'Nauru'                                        => 'NR',
        'Nepal'                                        => 'NP',
        'Netherlands'                                  => 'NL',
        'Netherlands_Antilles'                         => 'AN',
        'New_Caledonia'                                => 'NC',
        'New_Zealand'                                  => 'NZ',
        'Nicaragua'                                    => 'NI',
        'Niger'                                        => 'NE',
        'Nigeria'                                      => 'NG',
        'Niue'                                         => 'NU',
        'None'                                         => null,
        'Norfolk_Island'                               => 'NF',
        'Northern_Mariana_Islands'                     => 'MP',
        'North_Korea'                                  => 'KP',
        'Norway'                                       => 'NO',
        'Oman'                                         => 'OM',
        'Other'                                        => null,
        'Pakistan'                                     => 'PK',
        'Palau'                                        => 'PW',
        'Palestine'                                    => 'PS',
        'Panama'                                       => 'PA',
        'Papua_New_Guinea'                             => 'PG',
        'Paraguay'                                     => 'PY',
        'Peru'                                         => 'PE',
        'Philippines'                                  => 'PH',
        'Pitcairn'                                     => 'PN',
        'Poland'                                       => 'PL',
        'Portugal'                                     => 'PT',
        'Puerto_Rico'                                  => 'PR',
        'Quatar'                                       => 'QA',
        'Republic_of_Macedonia'                        => 'MK',
        'Reunion'                                      => 'RE',
        'Romania'                                      => 'RO',
        'Russian_Federation'                           => 'RU',
        'Rwanda'                                       => 'RW',
        'Saint_Helena'                                 => 'SH',
        'Saint_Kitts_and_Nevis'                        => 'KN',
        'Saint_Lucia'                                  => 'LC',
        'Saint_Pierre_and_Miquelon'                    => 'PM',
        'Samoa'                                        => 'WS',
        'San_Marino'                                   => 'SM',
        'Sao_Tome_and_Principe'                        => 'ST',
        'Saudi_Arabia'                                 => 'SA',
        'Senegal'                                      => 'SN',
        'Serbia'                                       => 'RS',
        'Seychelles'                                   => 'SC',
        'Sierra_Leone'                                 => 'SL',
        'Singapore'                                    => 'SG',
        'Slovakia'                                     => 'SK',
        'Slovenia'                                     => 'SI',
        'Solomon_Islands'                              => 'SB',
        'Somalia'                                      => 'SO',
        'South_Africa'                                 => 'ZA',
        'South_Georgia_and_South_Sandwich_Islands'     => 'GS',
        'South_Korea'                                  => 'KR',
        'Spain'                                        => 'ES',
        'Sri_Lanka'                                    => 'LK',
        'St_Vincent_Grenadines'                        => 'VC',
        'Sudan'                                        => 'SD',
        'Surinam'                                      => 'SR',
        'Svalbard_and_Jan_Mayen'                       => 'SJ',
        'Swaziland'                                    => 'SZ',
        'Sweden'                                       => 'SE',
        'Switzerland'                                  => 'CH',
        'Syria'                                        => 'SY',
        'Taiwan'                                       => 'TW',
        'Tajikistan'                                   => 'TJ',
        'Tanzania'                                     => 'TZ',
        'Thailand'                                     => 'TH',
        'the_former_Yugoslav_Republic_of_Macedonia'    => 'MK',
        'Timor-Leste'                                  => 'TL',
        'Togo'                                         => 'TG',
        'Tokelau'                                      => 'TK',
        'Tonga'                                        => 'TO',
        'Trinidad_Tobago'                              => 'TT',
        'Tunisia'                                      => 'TN',
        'Turkey'                                       => 'TR',
        'Turkmenistan'                                 => 'TM',
        'Turks_and_Caicos_Islands'                     => 'TC',
        'Tuvalu'                                       => 'TV',
        'Uganda'                                       => 'UG',
        'Ukraine'                                      => 'UA',
        'United_Arab_Emirates'                         => 'AE',
        'United_Kingdom'                               => 'GB',
        'United_Nations_Organization'                  => null,
        'United_States'                                => 'US',
        'United_States_Minor_Outlying_Islands'         => 'UM',
        'Uruguay'                                      => 'UY',
        'US_Virgin_Islands'                            => 'VI',
        'Uzbekistan'                                   => 'UZ',
        'Vanuatu'                                      => 'VU',
        'Vatican'                                      => 'VA',
        'Venezuela'                                    => 'VE',
        'Viet_Nam'                                     => 'VN',
        'Wales'                                        => null,
        'Wallis_and_Futuna'                            => 'WF',
        'Western_Sahara'                               => 'EH',
        'World'                                        => null,
        'Yemen'                                        => 'YE',
        'Zambia'                                       => 'ZM',
        'Zimbabwe'                                     => 'ZW',
    ];

    /**
     * Returns the list of all Tiki country names, used as a drop-in replacement
     * for scanning img/flags/*.png from the filesystem.
     *
     * @return string[]
     */
    public static function getCountries(): array
    {
        return array_keys(self::COUNTRIES);
    }

    /**
     * Converts a Tiki country name to a Unicode emoji flag string.
     * Returns empty string for entries without a standard ISO 3166-1 alpha-2 code.
     */
    public static function toEmoji(string $countryName): string
    {
        $code = self::COUNTRIES[$countryName] ?? null;
        if ($code === null) {
            return '';
        }
        $code = \strtoupper($code);
        return \mb_chr(0x1F1E6 + \ord($code[0]) - \ord('A'))
             . \mb_chr(0x1F1E6 + \ord($code[1]) - \ord('A'));
    }

    /**
     * Returns an HTML span containing the emoji flag, with accessible title and aria-label.
     * Returns empty string when no emoji is available for the given country name.
     *
     * @param string      $countryName  Tiki country name (e.g. 'France', 'United_Kingdom')
     * @param string|null $label        Human-readable label for title/aria-label (falls back to country name)
     */
    public static function toHtml(string $countryName, ?string $label = null): string
    {
        $emoji = self::toEmoji($countryName);
        if ($emoji === '') {
            return '';
        }
        if ($label === null || $label === '') {
            $label = strtr($countryName, '_', ' ');
        }
        $escaped = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<span class="country-flag" title="' . $escaped . '" aria-label="' . $escaped . '" role="img">' . $emoji . '</span>';
    }
}
