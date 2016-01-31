<?php
    /**
     * Reference countries of the world and their regions as defined in the ISO 3166 standard.
     * Based on the {@link http://alioth.debian.org/projects/pkg-isocodes/ Debian ISO codes} interpretation.
     *
     * @link      http://www.iso.org/iso/country_codes.htm ISO 3166 maintenance agency
     *
     * @author    Niall Kennedy <niall@niallkennedy.com>
     * @version   1.0
     * @copyright Public Domain
     */

    namespace Railpage\ISO;


    class ISO_3166 {

        /**
         * Get the region name from a given country code and region code
         * @since Version 3.10.0
         *
         * @param string $country
         * @param string $region
         *
         * @return string
         */

        public static function getRegionName($country, $region) {

            $bad = array(
                "region",
                "county",
                "state"
            );

            $replace = array_pad(array(), count($bad), " ");


            $country = strtoupper($country);
            $region = str_ireplace($bad, $replace, $region);
            $region = trim(str_replace("-", " ", $region));
            $region = strtoupper($region);

            $regions = self::regions_by_country($country);

            if (isset( $regions['regions'][$region] )) {
                return $regions['regions'][$region]['name'];
            }

            foreach ($regions['regions'] as $r) {
                if (isset( $r['subregions'][$region] )) {
                    return $r['subregions'][$region]['name'];
                }

                foreach ($r['subregions'][$region] as $row) {
                    if (strtoupper($row['name']) == $region) {
                        return $row['name'];
                    }
                }
            }

            // Look up again, this time accepting partial matches
            if ($name = self::partialMatch(array_values($regions['regions']), $region)) {
                return $name;
            }

            foreach ($regions['regions'] as $r) {
                if ($name = self::partialMatch($r, $region)) {
                    return $name;
                }
            }

            return ucwords(strtolower(str_replace("-", " ", $region)));

        }

        /**
         * Partial match lookup
         * @since Version 3.10.0
         *
         * @param string $lookup
         * @param array  $match
         *
         * @return string|boolean
         */

        private function partialMatch($lookup, $match) {

            $name = array_filter($lookup, function ($el) use ($match) {
                return ( stripos($el['name'], $match) !== false );
            });

            if (count($name) > 0) {
                return $name[0]['name'];
            }

            return false;

        }

        private static function _($str, $domain = '') {
            return $str;
        }

        /**
         * A list of ISO 3166-1 countries.
         * Countries and territories are provided as defined by ISO. You may wish to edit your use of these values to
         * avoid controversy, such as "Taiwan, Province of China" or "Palestinian Territory, Occupied."
         *
         * @link http://www.iso.org/iso/country_codes.htm ISO 3166 maintenance agency
         * @return array ISO 3166-1 alpha-2 country codes with name and official name English values
         */
        public static function get_countries() {
            return array(
                'AF' => array(
                    'name'          => self::_('Afghanistan'),
                    'official_name' => self::_('Islamic Republic of Afghanistan')
                ),
                'AX' => array(
                    'name' => self::_('Åland Islands')
                ),
                'AL' => array(
                    'name'          => self::_('Albania'),
                    'official_name' => self::_('Republic of Albania')
                ),
                'DZ' => array(
                    'name'          => self::_('Algeria'),
                    'official_name' => self::_('People\'s Democratic Republic of Algeria')
                ),
                'AS' => array(
                    'name' => self::_('American Samoa')
                ),
                'AD' => array(
                    'name'          => self::_('Andorra'),
                    'official_name' => self::_('Principality of Andorra')
                ),
                'AO' => array(
                    'name'          => self::_('Angola'),
                    'official_name' => self::_('Republic of Angola')
                ),
                'AI' => array(
                    'name' => self::_('Anguilla')
                ),
                'AQ' => array(
                    'name' => self::_('Antarctica')
                ),
                'AG' => array(
                    'name' => self::_('Antigua and Barbuda')
                ),
                'AR' => array(
                    'name'          => self::_('Argentina'),
                    'official_name' => self::_('Argentine Republic')
                ),
                'AM' => array(
                    'name'          => self::_('Armenia'),
                    'official_name' => self::_('Republic of Armenia')
                ),
                'AW' => array(
                    'name' => self::_('Aruba')
                ),
                'AU' => array(
                    'name' => self::_('Australia')
                ),
                'AT' => array(
                    'name'          => self::_('Austria'),
                    'official_name' => self::_('Republic of Austria')
                ),
                'AZ' => array(
                    'name'          => self::_('Azerbaijan'),
                    'official_name' => self::_('Republic of Azerbaijan')
                ),
                'BS' => array(
                    'name'          => self::_('Bahamas'),
                    'official_name' => self::_('Commonwealth of the Bahamas')
                ),
                'BH' => array(
                    'name'          => self::_('Bahrain'),
                    'official_name' => self::_('Kingdom of Bahrain')
                ),
                'BD' => array(
                    'name'          => self::_('Bangladesh'),
                    'official_name' => self::_('People\'s Republic of Bangladesh')
                ),
                'BB' => array(
                    'name' => self::_('Barbados')
                ),
                'BY' => array(
                    'name'          => self::_('Belarus'),
                    'official_name' => self::_('Republic of Belarus')
                ),
                'BE' => array(
                    'name'          => self::_('Belgium'),
                    'official_name' => self::_('Kingdom of Belgium')
                ),
                'BZ' => array(
                    'name' => self::_('Belize')
                ),
                'BJ' => array(
                    'name'          => self::_('Benin'),
                    'official_name' => self::_('Republic of Benin')
                ),
                'BM' => array(
                    'name' => self::_('Bermuda')
                ),
                'BT' => array(
                    'name'          => self::_('Bhutan'),
                    'official_name' => self::_('Kingdom of Bhutan')
                ),
                'BO' => array(
                    'name'          => self::_('Bolivia, Plurinational State of'),
                    'official_name' => self::_('Plurinational State of Bolivia')
                ),
                'BA' => array(
                    'name'          => self::_('Bosnia and Herzegovina'),
                    'official_name' => self::_('Republic of Bosnia and Herzegovina')
                ),
                'BW' => array(
                    'name'          => self::_('Botswana'),
                    'official_name' => self::_('Republic of Botswana')
                ),
                'BV' => array(
                    'name' => self::_('Bouvet Island')
                ),
                'BR' => array(
                    'name'          => self::_('Brazil'),
                    'official_name' => self::_('Federative Republic of Brazil')
                ),
                'IO' => array(
                    'name' => self::_('British Indian Ocean Territory')
                ),
                'BN' => array(
                    'name' => self::_('Brunei Darussalam')
                ),
                'BG' => array(
                    'name'          => self::_('Bulgaria'),
                    'official_name' => self::_('Republic of Bulgaria')
                ),
                'BF' => array(
                    'name' => self::_('Burkina Faso')
                ),
                'BI' => array(
                    'name'          => self::_('Burundi'),
                    'official_name' => self::_('Republic of Burundi')
                ),
                'KH' => array(
                    'name'          => self::_('Cambodia'),
                    'official_name' => self::_('Kingdom of Cambodia')
                ),
                'CM' => array(
                    'name'          => self::_('Cameroon'),
                    'official_name' => self::_('Republic of Cameroon')
                ),
                'CA' => array(
                    'name' => self::_('Canada')
                ),
                'CV' => array(
                    'name'          => self::_('Cape Verde'),
                    'official_name' => self::_('Republic of Cape Verde')
                ),
                'KY' => array(
                    'name' => self::_('Cayman Islands')
                ),
                'CF' => array(
                    'name' => self::_('Central African Republic')
                ),
                'TD' => array(
                    'name'          => self::_('Chad'),
                    'official_name' => self::_('Republic of Chad')
                ),
                'CL' => array(
                    'name'          => self::_('Chile'),
                    'official_name' => self::_('Republic of Chile')
                ),
                'CN' => array(
                    'name'          => self::_('China'),
                    'official_name' => self::_('People\'s Republic of China')
                ),
                'CX' => array(
                    'name' => self::_('Christmas Island')
                ),
                'CC' => array(
                    'name' => self::_('Cocos (Keeling) Islands')
                ),
                'CO' => array(
                    'name'          => self::_('Colombia'),
                    'official_name' => self::_('Republic of Colombia')
                ),
                'KM' => array(
                    'name'          => self::_('Comoros'),
                    'official_name' => self::_('Union of the Comoros')
                ),
                'CG' => array(
                    'name'          => self::_('Congo'),
                    'official_name' => self::_('Republic of the Congo')
                ),
                'CD' => array(
                    'name' => self::_('Congo, The Democratic Republic of the')
                ),
                'CK' => array(
                    'name' => self::_('Cook Islands')
                ),
                'CR' => array(
                    'name'          => self::_('Costa Rica'),
                    'official_name' => self::_('Republic of Costa Rica')
                ),
                'CI' => array(
                    'name'          => self::_('Côte d\'Ivoire'),
                    'official_name' => self::_('Republic of Côte d\'Ivoire')
                ),
                'HR' => array(
                    'name'          => self::_('Croatia'),
                    'official_name' => self::_('Republic of Croatia')
                ),
                'CU' => array(
                    'name'          => self::_('Cuba'),
                    'official_name' => self::_('Republic of Cuba')
                ),
                'CY' => array(
                    'name'          => self::_('Cyprus'),
                    'official_name' => self::_('Republic of Cyprus')
                ),
                'CZ' => array(
                    'name' => self::_('Czech Republic')
                ),
                'DK' => array(
                    'name'          => self::_('Denmark'),
                    'official_name' => self::_('Kingdom of Denmark')
                ),
                'DJ' => array(
                    'name'          => self::_('Djibouti'),
                    'official_name' => self::_('Republic of Djibouti')
                ),
                'DM' => array(
                    'name'          => self::_('Dominica'),
                    'official_name' => self::_('Commonwealth of Dominica')
                ),
                'DO' => array(
                    'name' => self::_('Dominican Republic')
                ),
                'EC' => array(
                    'name'          => self::_('Ecuador'),
                    'official_name' => self::_('Republic of Ecuador')
                ),
                'EG' => array(
                    'name'          => self::_('Egypt'),
                    'official_name' => self::_('Arab Republic of Egypt')
                ),
                'SV' => array(
                    'name'          => self::_('El Salvador'),
                    'official_name' => self::_('Republic of El Salvador')
                ),
                'GQ' => array(
                    'name'          => self::_('Equatorial Guinea'),
                    'official_name' => self::_('Republic of Equatorial Guinea')
                ),
                'ER' => array(
                    'name' => self::_('Eritrea')
                ),
                'EE' => array(
                    'name'          => self::_('Estonia'),
                    'official_name' => self::_('Republic of Estonia')
                ),
                'ET' => array(
                    'name'          => self::_('Ethiopia'),
                    'official_name' => self::_('Federal Democratic Republic of Ethiopia')
                ),
                'FK' => array(
                    'name' => self::_('Falkland Islands (Malvinas)')
                ),
                'FO' => array(
                    'name' => self::_('Faroe Islands')
                ),
                'FJ' => array(
                    'name'          => self::_('Fiji'),
                    'official_name' => self::_('Republic of the Fiji Islands')
                ),
                'FI' => array(
                    'name'          => self::_('Finland'),
                    'official_name' => self::_('Republic of Finland')
                ),
                'FR' => array(
                    'name'          => self::_('France'),
                    'official_name' => self::_('French Republic')
                ),
                'GF' => array(
                    'name' => self::_('French Guiana')
                ),
                'PF' => array(
                    'name' => self::_('French Polynesia')
                ),
                'TF' => array(
                    'name' => self::_('French Southern Territories')
                ),
                'GA' => array(
                    'name'          => self::_('Gabon'),
                    'official_name' => self::_('Gabonese Republic')
                ),
                'GM' => array(
                    'name'          => self::_('Gambia'),
                    'official_name' => self::_('Republic of the Gambia')
                ),
                'GE' => array(
                    'name' => self::_('Georgia')
                ),
                'DE' => array(
                    'name'          => self::_('Germany'),
                    'official_name' => self::_('Federal Republic of Germany')
                ),
                'GH' => array(
                    'name'          => self::_('Ghana'),
                    'official_name' => self::_('Republic of Ghana')
                ),
                'GI' => array(
                    'name' => self::_('Gibraltar')
                ),
                'GR' => array(
                    'name'          => self::_('Greece'),
                    'official_name' => self::_('Hellenic Republic')
                ),
                'GL' => array(
                    'name' => self::_('Greenland')
                ),
                'GD' => array(
                    'name' => self::_('Grenada')
                ),
                'GP' => array(
                    'name' => self::_('Guadeloupe')
                ),
                'GU' => array(
                    'name' => self::_('Guam')
                ),
                'GT' => array(
                    'name'          => self::_('Guatemala'),
                    'official_name' => self::_('Republic of Guatemala')
                ),
                'GG' => array(
                    'name' => self::_('Guernsey')
                ),
                'GN' => array(
                    'name'          => self::_('Guinea'),
                    'official_name' => self::_('Republic of Guinea')
                ),
                'GW' => array(
                    'name'          => self::_('Guinea-Bissau'),
                    'official_name' => self::_('Republic of Guinea-Bissau')
                ),
                'GY' => array(
                    'name'          => self::_('Guyana'),
                    'official_name' => self::_('Republic of Guyana')
                ),
                'HT' => array(
                    'name'          => self::_('Haiti'),
                    'official_name' => self::_('Republic of Haiti')
                ),
                'HM' => array(
                    'name' => self::_('Heard Island and McDonald Islands')
                ),
                'VA' => array(
                    'name' => self::_('Holy See (Vatican City State)')
                ),
                'HN' => array(
                    'name'          => self::_('Honduras'),
                    'official_name' => self::_('Republic of Honduras')
                ),
                'HK' => array(
                    'name'          => self::_('Hong Kong'),
                    'official_name' => self::_('Hong Kong Special Administrative Region of China')
                ),
                'HU' => array(
                    'name'          => self::_('Hungary'),
                    'official_name' => self::_('Republic of Hungary')
                ),
                'IS' => array(
                    'name'          => self::_('Iceland'),
                    'official_name' => self::_('Republic of Iceland')
                ),
                'IN' => array(
                    'name'          => self::_('India'),
                    'official_name' => self::_('Republic of India')
                ),
                'ID' => array(
                    'name'          => self::_('Indonesia'),
                    'official_name' => self::_('Republic of Indonesia')
                ),
                'IR' => array(
                    'name'          => self::_('Iran, Islamic Republic of'),
                    'official_name' => self::_('Islamic Republic of Iran')
                ),
                'IQ' => array(
                    'name'          => self::_('Iraq'),
                    'official_name' => self::_('Republic of Iraq')
                ),
                'IE' => array(
                    'name' => self::_('Ireland')
                ),
                'IM' => array(
                    'name' => self::_('Isle of Man')
                ),
                'IL' => array(
                    'name'          => self::_('Israel'),
                    'official_name' => self::_('State of Israel')
                ),
                'IT' => array(
                    'name'          => self::_('Italy'),
                    'official_name' => self::_('Italian Republic')
                ),
                'JM' => array(
                    'name' => self::_('Jamaica')
                ),
                'JP' => array(
                    'name' => self::_('Japan')
                ),
                'JE' => array(
                    'name' => self::_('Jersey')
                ),
                'JO' => array(
                    'name'          => self::_('Jordan'),
                    'official_name' => self::_('Hashemite Kingdom of Jordan')
                ),
                'KZ' => array(
                    'name'          => self::_('Kazakhstan'),
                    'official_name' => self::_('Republic of Kazakhstan')
                ),
                'KE' => array(
                    'name'          => self::_('Kenya'),
                    'official_name' => self::_('Republic of Kenya')
                ),
                'KI' => array(
                    'name'          => self::_('Kiribati'),
                    'official_name' => self::_('Republic of Kiribati')
                ),
                'KP' => array(
                    'name'          => self::_('Korea, Democratic People\'s Republic of'),
                    'official_name' => self::_('Democratic People\'s Republic of Korea')
                ),
                'KR' => array(
                    'name' => self::_('Korea, Republic of')
                ),
                'KW' => array(
                    'name'          => self::_('Kuwait'),
                    'official_name' => self::_('State of Kuwait')
                ),
                'KG' => array(
                    'name'          => self::_('Kyrgyzstan'),
                    'official_name' => self::_('Kyrgyz Republic')
                ),
                'LA' => array(
                    'name' => self::_('Lao People\'s Democratic Republic')
                ),
                'LV' => array(
                    'name'          => self::_('Latvia'),
                    'official_name' => self::_('Republic of Latvia')
                ),
                'LB' => array(
                    'name'          => self::_('Lebanon'),
                    'official_name' => self::_('Lebanese Republic')
                ),
                'LS' => array(
                    'name'          => self::_('Lesotho'),
                    'official_name' => self::_('Kingdom of Lesotho')
                ),
                'LR' => array(
                    'name'          => self::_('Liberia'),
                    'official_name' => self::_('Republic of Liberia')
                ),
                'LY' => array(
                    'name'          => self::_('Libyan Arab Jamahiriya'),
                    'official_name' => self::_('Socialist People\'s Libyan Arab Jamahiriya')
                ),
                'LI' => array(
                    'name'          => self::_('Liechtenstein'),
                    'official_name' => self::_('Principality of Liechtenstein')
                ),
                'LT' => array(
                    'name'          => self::_('Lithuania'),
                    'official_name' => self::_('Republic of Lithuania')
                ),
                'LU' => array(
                    'name'          => self::_('Luxembourg'),
                    'official_name' => self::_('Grand Duchy of Luxembourg')
                ),
                'MO' => array(
                    'name'          => self::_('Macao'),
                    'official_name' => self::_('Macao Special Administrative Region of China')
                ),
                'MK' => array(
                    'name'          => self::_('Macedonia, Republic of'),
                    'official_name' => self::_('The Former Yugoslav Republic of Macedonia')
                ),
                'MG' => array(
                    'name'          => self::_('Madagascar'),
                    'official_name' => self::_('Republic of Madagascar')
                ),
                'MW' => array(
                    'name'          => self::_('Malawi'),
                    'official_name' => self::_('Republic of Malawi')
                ),
                'MY' => array(
                    'name' => self::_('Malaysia')
                ),
                'MV' => array(
                    'name'          => self::_('Maldives'),
                    'official_name' => self::_('Republic of Maldives')
                ),
                'ML' => array(
                    'name'          => self::_('Mali'),
                    'official_name' => self::_('Republic of Mali')
                ),
                'MT' => array(
                    'name'          => self::_('Malta'),
                    'official_name' => self::_('Republic of Malta')
                ),
                'MH' => array(
                    'name'          => self::_('Marshall Islands'),
                    'official_name' => self::_('Republic of the Marshall Islands')
                ),
                'MQ' => array(
                    'name' => self::_('Martinique')
                ),
                'MR' => array(
                    'name'          => self::_('Mauritania'),
                    'official_name' => self::_('Islamic Republic of Mauritania')
                ),
                'MU' => array(
                    'name'          => self::_('Mauritius'),
                    'official_name' => self::_('Republic of Mauritius')
                ),
                'YT' => array(
                    'name' => self::_('Mayotte')
                ),
                'MX' => array(
                    'name'          => self::_('Mexico'),
                    'official_name' => self::_('United Mexican States')
                ),
                'FM' => array(
                    'name'          => self::_('Micronesia, Federated States of'),
                    'official_name' => self::_('Federated States of Micronesia')
                ),
                'MD' => array(
                    'name'          => self::_('Moldova, Republic of'),
                    'official_name' => self::_('Republic of Moldova')
                ),
                'MC' => array(
                    'name'          => self::_('Monaco'),
                    'official_name' => self::_('Principality of Monaco')
                ),
                'MN' => array(
                    'name' => self::_('Mongolia')
                ),
                'ME' => array(
                    'name'          => self::_('Montenegro'),
                    'official_name' => self::_('Montenegro')
                ),
                'MS' => array(
                    'name' => self::_('Montserrat')
                ),
                'MA' => array(
                    'name'          => self::_('Morocco'),
                    'official_name' => self::_('Kingdom of Morocco')
                ),
                'MZ' => array(
                    'name'          => self::_('Mozambique'),
                    'official_name' => self::_('Republic of Mozambique')
                ),
                'MM' => array(
                    'name'          => self::_('Myanmar'),
                    'official_name' => self::_('Union of Myanmar')
                ),
                'NA' => array(
                    'name'          => self::_('Namibia'),
                    'official_name' => self::_('Republic of Namibia')
                ),
                'NR' => array(
                    'name'          => self::_('Nauru'),
                    'official_name' => self::_('Republic of Nauru')
                ),
                'NP' => array(
                    'name'          => self::_('Nepal'),
                    'official_name' => self::_('Federal Democratic Republic of Nepal')
                ),
                'NL' => array(
                    'name'          => self::_('Netherlands'),
                    'official_name' => self::_('Kingdom of the Netherlands')
                ),
                'AN' => array(
                    'name' => self::_('Netherlands Antilles')
                ),
                'NC' => array(
                    'name' => self::_('New Caledonia')
                ),
                'NZ' => array(
                    'name' => self::_('New Zealand')
                ),
                'NI' => array(
                    'name'          => self::_('Nicaragua'),
                    'official_name' => self::_('Republic of Nicaragua')
                ),
                'NE' => array(
                    'name'          => self::_('Niger'),
                    'official_name' => self::_('Republic of the Niger')
                ),
                'NG' => array(
                    'name'          => self::_('Nigeria'),
                    'official_name' => self::_('Federal Republic of Nigeria')
                ),
                'NU' => array(
                    'name'          => self::_('Niue'),
                    'official_name' => self::_('Republic of Niue')
                ),
                'NF' => array(
                    'name' => self::_('Norfolk Island')
                ),
                'MP' => array(
                    'name'          => self::_('Northern Mariana Islands'),
                    'official_name' => self::_('Commonwealth of the Northern Mariana Islands')
                ),
                'NO' => array(
                    'name'          => self::_('Norway'),
                    'official_name' => self::_('Kingdom of Norway')
                ),
                'OM' => array(
                    'name'          => self::_('Oman'),
                    'official_name' => self::_('Sultanate of Oman')
                ),
                'PK' => array(
                    'name'          => self::_('Pakistan'),
                    'official_name' => self::_('Islamic Republic of Pakistan')
                ),
                'PW' => array(
                    'name'          => self::_('Palau'),
                    'official_name' => self::_('Republic of Palau')
                ),
                'PS' => array(
                    'name'          => self::_('Palestinian Territory, Occupied'),
                    'official_name' => self::_('Occupied Palestinian Territory')
                ),
                'PA' => array(
                    'name'          => self::_('Panama'),
                    'official_name' => self::_('Republic of Panama')
                ),
                'PG' => array(
                    'name' => self::_('Papua New Guinea')
                ),
                'PY' => array(
                    'name'          => self::_('Paraguay'),
                    'official_name' => self::_('Republic of Paraguay')
                ),
                'PE' => array(
                    'name'          => self::_('Peru'),
                    'official_name' => self::_('Republic of Peru')
                ),
                'PH' => array(
                    'name'          => self::_('Philippines'),
                    'official_name' => self::_('Republic of the Philippines')
                ),
                'PN' => array(
                    'name' => self::_('Pitcairn')
                ),
                'PL' => array(
                    'name'          => self::_('Poland'),
                    'official_name' => self::_('Republic of Poland')
                ),
                'PT' => array(
                    'name'          => self::_('Portugal'),
                    'official_name' => self::_('Portuguese Republic')
                ),
                'PR' => array(
                    'name' => self::_('Puerto Rico')
                ),
                'QA' => array(
                    'name'          => self::_('Qatar'),
                    'official_name' => self::_('State of Qatar')
                ),
                'RE' => array(
                    'name' => self::_('Reunion')
                ),
                'RO' => array(
                    'name' => self::_('Romania')
                ),
                'RU' => array(
                    'name' => self::_('Russian Federation')
                ),
                'RW' => array(
                    'name'          => self::_('Rwanda'),
                    'official_name' => self::_('Rwandese Republic')
                ),
                'BL' => array(
                    'name' => self::_('Saint Barthélemy')
                ),
                'SH' => array(
                    'name' => self::_('Saint Helena, Ascension and Tristan da Cunha')
                ),
                'KN' => array(
                    'name' => self::_('Saint Kitts and Nevis')
                ),
                'LC' => array(
                    'name' => self::_('Saint Lucia')
                ),
                'MF' => array(
                    'name' => self::_('Saint Martin (French part)')
                ),
                'PM' => array(
                    'name' => self::_('Saint Pierre and Miquelon')
                ),
                'VC' => array(
                    'name' => self::_('Saint Vincent and the Grenadines')
                ),
                'WS' => array(
                    'name'          => self::_('Samoa'),
                    'official_name' => self::_('Independent State of Samoa')
                ),
                'SM' => array(
                    'name'          => self::_('San Marino'),
                    'official_name' => self::_('Republic of San Marino')
                ),
                'ST' => array(
                    'name'          => self::_('Sao Tome and Principe'),
                    'official_name' => self::_('Democratic Republic of Sao Tome and Principe')
                ),
                'SA' => array(
                    'name'          => self::_('Saudi Arabia'),
                    'official_name' => self::_('Kingdom of Saudi Arabia')
                ),
                'SN' => array(
                    'name'          => self::_('Senegal'),
                    'official_name' => self::_('Republic of Senegal')
                ),
                'RS' => array(
                    'name'          => self::_('Serbia'),
                    'official_name' => self::_('Republic of Serbia')
                ),
                'SC' => array(
                    'name'          => self::_('Seychelles'),
                    'official_name' => self::_('Republic of Seychelles')
                ),
                'SL' => array(
                    'name'          => self::_('Sierra Leone'),
                    'official_name' => self::_('Republic of Sierra Leone')
                ),
                'SG' => array(
                    'name'          => self::_('Singapore'),
                    'official_name' => self::_('Republic of Singapore')
                ),
                'SK' => array(
                    'name'          => self::_('Slovakia'),
                    'official_name' => self::_('Slovak Republic')
                ),
                'SI' => array(
                    'name'          => self::_('Slovenia'),
                    'official_name' => self::_('Republic of Slovenia')
                ),
                'SB' => array(
                    'name' => self::_('Solomon Islands')
                ),
                'SO' => array(
                    'name'          => self::_('Somalia'),
                    'official_name' => self::_('Somali Republic')
                ),
                'ZA' => array(
                    'name'          => self::_('South Africa'),
                    'official_name' => self::_('Republic of South Africa')
                ),
                'GS' => array(
                    'name' => self::_('South Georgia and the South Sandwich Islands')
                ),
                'ES' => array(
                    'name'          => self::_('Spain'),
                    'official_name' => self::_('Kingdom of Spain')
                ),
                'LK' => array(
                    'name'          => self::_('Sri Lanka'),
                    'official_name' => self::_('Democratic Socialist Republic of Sri Lanka')
                ),
                'SD' => array(
                    'name'          => self::_('Sudan'),
                    'official_name' => self::_('Republic of the Sudan')
                ),
                'SR' => array(
                    'name'          => self::_('Suriname'),
                    'official_name' => self::_('Republic of Suriname')
                ),
                'SJ' => array(
                    'name' => self::_('Svalbard and Jan Mayen')
                ),
                'SZ' => array(
                    'name'          => self::_('Swaziland'),
                    'official_name' => self::_('Kingdom of Swaziland')
                ),
                'SE' => array(
                    'name'          => self::_('Sweden'),
                    'official_name' => self::_('Kingdom of Sweden')
                ),
                'CH' => array(
                    'name'          => self::_('Switzerland'),
                    'official_name' => self::_('Swiss Confederation')
                ),
                'SY' => array(
                    'name' => self::_('Syrian Arab Republic')
                ),
                'TW' => array(
                    'name'          => self::_('Taiwan, Province of China'),
                    'official_name' => self::_('Taiwan, Province of China')
                ),
                'TJ' => array(
                    'name'          => self::_('Tajikistan'),
                    'official_name' => self::_('Republic of Tajikistan')
                ),
                'TZ' => array(
                    'name'          => self::_('Tanzania, United Republic of'),
                    'official_name' => self::_('United Republic of Tanzania')
                ),
                'TH' => array(
                    'name'          => self::_('Thailand'),
                    'official_name' => self::_('Kingdom of Thailand')
                ),
                'TL' => array(
                    'name'          => self::_('Timor-Leste'),
                    'official_name' => self::_('Democratic Republic of Timor-Leste')
                ),
                'TG' => array(
                    'name'          => self::_('Togo'),
                    'official_name' => self::_('Togolese Republic')
                ),
                'TK' => array(
                    'name' => self::_('Tokelau')
                ),
                'TO' => array(
                    'name'          => self::_('Tonga'),
                    'official_name' => self::_('Kingdom of Tonga')
                ),
                'TT' => array(
                    'name'          => self::_('Trinidad and Tobago'),
                    'official_name' => self::_('Republic of Trinidad and Tobago')
                ),
                'TN' => array(
                    'name'          => self::_('Tunisia'),
                    'official_name' => self::_('Republic of Tunisia')
                ),
                'TR' => array(
                    'name'          => self::_('Turkey'),
                    'official_name' => self::_('Republic of Turkey')
                ),
                'TM' => array(
                    'name' => self::_('Turkmenistan')
                ),
                'TC' => array(
                    'name' => self::_('Turks and Caicos Islands')
                ),
                'TV' => array(
                    'name' => self::_('Tuvalu')
                ),
                'UG' => array(
                    'name'          => self::_('Uganda'),
                    'official_name' => self::_('Republic of Uganda')
                ),
                'UA' => array(
                    'name' => self::_('Ukraine')
                ),
                'AE' => array(
                    'name' => self::_('United Arab Emirates')
                ),
                'GB' => array(
                    'name'          => self::_('United Kingdom'),
                    'official_name' => self::_('United Kingdom of Great Britain and Northern Ireland')
                ),
                'US' => array(
                    'name'          => self::_('United States'),
                    'official_name' => self::_('United States of America')
                ),
                'UM' => array(
                    'name' => self::_('United States Minor Outlying Islands')
                ),
                'UY' => array(
                    'name'          => self::_('Uruguay'),
                    'official_name' => self::_('Eastern Republic of Uruguay')
                ),
                'UZ' => array(
                    'name'          => self::_('Uzbekistan'),
                    'official_name' => self::_('Republic of Uzbekistan')
                ),
                'VU' => array(
                    'name'          => self::_('Vanuatu'),
                    'official_name' => self::_('Republic of Vanuatu')
                ),
                'VE' => array(
                    'name'          => self::_('Venezuela, Bolivarian republic of'),
                    'official_name' => self::_('Bolivarian Republic of Venezuela')
                ),
                'VN' => array(
                    'name'          => self::_('Viet Nam'),
                    'official_name' => self::_('Socialist Republic of Viet Nam')
                ),
                'VG' => array(
                    'name'          => self::_('Virgin Islands, British'),
                    'official_name' => self::_('British Virgin Islands')
                ),
                'VI' => array(
                    'name'          => self::_('Virgin Islands, U.S.'),
                    'official_name' => self::_('Virgin Islands of the United States')
                ),
                'WF' => array(
                    'name' => self::_('Wallis and Futuna')
                ),
                'EH' => array(
                    'name' => self::_('Western Sahara')
                ),
                'YE' => array(
                    'name'          => self::_('Yemen'),
                    'official_name' => self::_('Republic of Yemen')
                ),
                'ZM' => array(
                    'name'          => self::_('Zambia'),
                    'official_name' => self::_('Republic of Zambia')
                ),
                'ZW' => array(
                    'name'          => self::_('Zimbabwe'),
                    'official_name' => self::_('Republic of Zimbabwe')
                )
            );
        }

        /**
         * Retrieve an array of region and subregion data by country.
         * Region and subregion labels are normalized to the most common type of region or generic reference. A
         * "federal district," typically a small region within a country responsible for general administration, is
         * labeled as the standard region type for the country for example (Washington D.C. is a "state," as are
         * territories). You may wish to edit responses for political sensitivities, such as listing Taiwan as a
         * province of China.
         *
         * @param String $country_code ISO 3166-1 alpha-2 country code
         *
         * @return array region label and array of regions for the given country context. Subregion label and subregion
         *               region children if applicable.
         */
        public static function regions_by_country($country_code) {

            $country_code = strtoupper($country_code);

            switch ($country_code) {
                case 'AD':
                    return array(
                        'regions_label' => self::_('Parish'),
                        'regions'       => array(
                            '07' => array( 'name' => self::_('Andorra la Vella') ),
                            '02' => array( 'name' => self::_('Canillo') ),
                            '03' => array( 'name' => self::_('Encamp') ),
                            '08' => array( 'name' => self::_('Escaldes-Engordany') ),
                            '04' => array( 'name' => self::_('La Massana') ),
                            '05' => array( 'name' => self::_('Ordino') ),
                            '06' => array( 'name' => self::_('Sant Julià de Lòria') )
                        ) );
                    break;
                case 'AE':
                    return array(
                        'regions_label' => self::_('Emirate'),
                        'regions'       => array(
                            'AZ' => array( 'name' => self::_('Abū Ȥaby [Abu Dhabi]') ),
                            'AJ' => array( 'name' => self::_('\'Ajmān') ),
                            'FU' => array( 'name' => self::_('Al Fujayrah') ),
                            'SH' => array( 'name' => self::_('Ash Shāriqah') ),
                            'DU' => array( 'name' => self::_('Dubayy') ),
                            'RK' => array( 'name' => self::_('Ra\'s al Khaymah') ),
                            'UQ' => array( 'name' => self::_('Umm al Qaywayn') )
                        ) );
                    break;
                case 'AF':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'BDS' => array( 'name' => self::_('Badakhshān') ),
                            'BDG' => array( 'name' => self::_('Bādghīs') ),
                            'BGL' => array( 'name' => self::_('Baghlān') ),
                            'BAL' => array( 'name' => self::_('Balkh') ),
                            'BAM' => array( 'name' => self::_('Bāmīān') ),
                            'DAY' => array( 'name' => self::_('Dāykondī') ),
                            'FRA' => array( 'name' => self::_('Farāh') ),
                            'FYB' => array( 'name' => self::_('Fāryāb') ),
                            'GHA' => array( 'name' => self::_('Ghaznī') ),
                            'GHO' => array( 'name' => self::_('Ghowr') ),
                            'HEL' => array( 'name' => self::_('Helmand') ),
                            'HER' => array( 'name' => self::_('Herāt') ),
                            'JOW' => array( 'name' => self::_('Jowzjān') ),
                            'KAB' => array( 'name' => self::_('Kābul [Kābol]') ),
                            'KAN' => array( 'name' => self::_('Kandahār') ),
                            'KAP' => array( 'name' => self::_('Kāpīsā') ),
                            'KHO' => array( 'name' => self::_('Khowst') ),
                            'KNR' => array( 'name' => self::_('Konar [Kunar]') ),
                            'KDZ' => array( 'name' => self::_('Kondoz [Kunduz]') ),
                            'LAG' => array( 'name' => self::_('Laghmān') ),
                            'LOW' => array( 'name' => self::_('Lowgar') ),
                            'NAN' => array( 'name' => self::_('Nangrahār [Nangarhār]') ),
                            'NIM' => array( 'name' => self::_('Nīmrūz') ),
                            'NUR' => array( 'name' => self::_('Nūrestān') ),
                            'ORU' => array( 'name' => self::_('Orūzgān [Urūzgān]') ),
                            'PAN' => array( 'name' => self::_('Panjshīr') ),
                            'PIA' => array( 'name' => self::_('Paktīā') ),
                            'PKA' => array( 'name' => self::_('Paktīkā') ),
                            'PAR' => array( 'name' => self::_('Parwān') ),
                            'SAM' => array( 'name' => self::_('Samangān') ),
                            'SAR' => array( 'name' => self::_('Sar-e Pol') ),
                            'TAK' => array( 'name' => self::_('Takhār') ),
                            'WAR' => array( 'name' => self::_('Wardak [Wardag]') ),
                            'ZAB' => array( 'name' => self::_('Zābol [Zābul]') )
                        ) );
                    break;
                case 'AG':
                    return array(
                        'regions_label' => self::_('Parish'), // label dependency as parish
                        'regions'       => array(
                            '03' => array( 'name' => self::_('Saint George') ),
                            '04' => array( 'name' => self::_('Saint John') ),
                            '05' => array( 'name' => self::_('Saint Mary') ),
                            '06' => array( 'name' => self::_('Saint Paul') ),
                            '07' => array( 'name' => self::_('Saint Peter') ),
                            '08' => array( 'name' => self::_('Saint Philip') ),
                            '10' => array( 'name' => self::_('Barbuda') ),
                            '11' => array( 'name' => self::_('Redonda') )
                        ) );
                    break;
                case 'AL':
                    return array(
                        'regions_label'    => self::_('County'),
                        'subregions_label' => self::_('District'),
                        'regions'          => array(
                            '01' => array(
                                'name'       => self::_('Berat'),
                                'subregions' => array(
                                    'BR' => array( 'name' => self::_('Berat') ),
                                    'KC' => array( 'name' => self::_('Kuçovë') ),
                                    'SK' => array( 'name' => self::_('Skrapar') )
                                )
                            ),
                            '09' => array(
                                'name'       => self::_('Dibër'),
                                'subregions' => array(
                                    'BU' => array( 'name' => self::_('Bulqizë') ),
                                    'DI' => array( 'name' => self::_('Dibër') ),
                                    'MT' => array( 'name' => self::_('Mat') )
                                )
                            ),
                            '02' => array(
                                'name'       => self::_('Durrës'),
                                'subregions' => array(
                                    'DR' => array( 'name' => self::_('Durrës') ),
                                    'KR' => array( 'name' => self::_('Krujë') )
                                )
                            ),
                            '03' => array(
                                'name'       => self::_('Elbasan'),
                                'subregions' => array(
                                    'EL' => array( 'name' => self::_('Elbasan') ),
                                    'GR' => array( 'name' => self::_('Gramsh') ),
                                    'LB' => array( 'name' => self::_('Librazhd') ),
                                    'PQ' => array( 'name' => self::_('Peqin') )
                                )
                            ),
                            '04' => array(
                                'name'       => self::_('Fier'),
                                'subregions' => array(
                                    'FR' => array( 'name' => self::_('Fier') ),
                                    'LU' => array( 'name' => self::_('Lushnjë') ),
                                    'MK' => array( 'name' => self::_('Mallakastër') )
                                )
                            ),
                            '05' => array(
                                'name'       => self::_('Gjirokastër'),
                                'subregions' => array(
                                    'GJ' => array( 'name' => self::_('Gjirokastër') ),
                                    'PR' => array( 'name' => self::_('Përmet') ),
                                    'TE' => array( 'name' => self::_('Tepelenë') )
                                )
                            ),
                            '06' => array(
                                'name'       => self::_('Korçë'),
                                'subregions' => array(
                                    'DV' => array( 'name' => self::_('Devoll') ),
                                    'ER' => array( 'name' => self::_('Kolonjë') ),
                                    'KO' => array( 'name' => self::_('Korçë') ),
                                    'PG' => array( 'name' => self::_('Pogradec') )
                                )
                            ),
                            '07' => array(
                                'name'       => self::_('Kukës'),
                                'subregions' => array(
                                    'HA' => array( 'name' => self::_('Has') ),
                                    'KU' => array( 'name' => self::_('Kukës') ),
                                    'TP' => array( 'name' => self::_('Tropojë') )
                                )
                            ),
                            '08' => array(
                                'name'       => self::_('Lezhë'),
                                'subregions' => array(
                                    'KB' => array( 'name' => self::_('Kurbin') ),
                                    'LE' => array( 'name' => self::_('Lezhë') ),
                                    'MR' => array( 'name' => self::_('Mirditë') )
                                )
                            ),
                            '10' => array(
                                'name'       => self::_('Shkodër'),
                                'subregions' => array(
                                    'MM' => array( 'name' => self::_('Malësi e Madhe') ),
                                    'PU' => array( 'name' => self::_('Pukë') ),
                                    'SH' => array( 'name' => self::_('Shkodër') )
                                )
                            ),
                            '11' => array(
                                'name'       => self::_('Tiranë'),
                                'subregions' => array(
                                    'KA' => array( 'name' => self::_('Kavajë') ),
                                    'TR' => array( 'name' => self::_('Tiranë') )
                                )
                            ),
                            '12' => array(
                                'name'       => self::_('Vlorë'),
                                'subregions' => array(
                                    'DL' => array( 'name' => self::_('Delvinë') ),
                                    'SR' => array( 'name' => self::_('Sarandë') ),
                                    'VL' => array( 'name' => self::_('Vlorë') )
                                )
                            ) )
                    );
                    break;
                case 'AM':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'ER' => array( 'name' => self::_('Erevan') ),
                            'AG' => array( 'name' => self::_('Aragacotn') ),
                            'AR' => array( 'name' => self::_('Ararat') ),
                            'AV' => array( 'name' => self::_('Armavir') ),
                            'GR' => array( 'name' => self::_('Gegarkunik\'') ),
                            'KT' => array( 'name' => self::_('Kotayk\'') ),
                            'LO' => array( 'name' => self::_('Lory') ),
                            'SH' => array( 'name' => self::_('Sirak') ),
                            'SU' => array( 'name' => self::_('Syunik\'') ),
                            'TV' => array( 'name' => self::_('Tavus') ),
                            'VD' => array( 'name' => self::_('Vayoc Jor') )
                        ) );
                    break;
                case 'AO':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'BGO' => array( 'name' => self::_('Bengo') ),
                            'BGU' => array( 'name' => self::_('Benguela') ),
                            'BIE' => array( 'name' => self::_('Bié') ),
                            'CAB' => array( 'name' => self::_('Cabinda') ),
                            'CCU' => array( 'name' => self::_('Cuando-Cubango') ),
                            'CNO' => array( 'name' => self::_('Cuanza Norte') ),
                            'CUS' => array( 'name' => self::_('Cuanza Sul') ),
                            'CNN' => array( 'name' => self::_('Cunene') ),
                            'HUA' => array( 'name' => self::_('Huambo') ),
                            'HUI' => array( 'name' => self::_('Huíla') ),
                            'LUA' => array( 'name' => self::_('Luanda') ),
                            'LNO' => array( 'name' => self::_('Lunda Norte') ),
                            'LSU' => array( 'name' => self::_('Lunda Sul') ),
                            'MAL' => array( 'name' => self::_('Malange') ),
                            'MOX' => array( 'name' => self::_('Moxico') ),
                            'NAM' => array( 'name' => self::_('Namibe') ),
                            'UIG' => array( 'name' => self::_('Uíge') ),
                            'ZAI' => array( 'name' => self::_('Zaire') )
                        ) );
                    break;
                case 'AR':
                    return array(
                        'regions_label' => self::_('Province'), // label city of Buenos Aires as province
                        'regions'       => array(
                            'C' => array( 'name' => self::_('Ciudad Autónoma de Buenos Aires') ),
                            'B' => array( 'name' => self::_('Buenos Aires') ),
                            'K' => array( 'name' => self::_('Catamarca') ),
                            'X' => array( 'name' => self::_('Cordoba') ),
                            'W' => array( 'name' => self::_('Corrientes') ),
                            'H' => array( 'name' => self::_('Chaco') ),
                            'U' => array( 'name' => self::_('Chubut') ),
                            'E' => array( 'name' => self::_('Entre Rios') ),
                            'P' => array( 'name' => self::_('Formosa') ),
                            'Y' => array( 'name' => self::_('Jujuy') ),
                            'L' => array( 'name' => self::_('La Pampa') ),
                            'M' => array( 'name' => self::_('Mendoza') ),
                            'N' => array( 'name' => self::_('Misiones') ),
                            'Q' => array( 'name' => self::_('Neuquen') ),
                            'R' => array( 'name' => self::_('Rio Negro') ),
                            'A' => array( 'name' => self::_('Salta') ),
                            'J' => array( 'name' => self::_('San Juan') ),
                            'D' => array( 'name' => self::_('San Luis') ),
                            'Z' => array( 'name' => self::_('Santa Cruz') ),
                            'S' => array( 'name' => self::_('Santa Fe') ),
                            'G' => array( 'name' => self::_('Santiago del Estero') ),
                            'V' => array( 'name' => self::_('Tierra del Fuego') ),
                            'T' => array( 'name' => self::_('Tucuman') )
                        ) );
                    break;
                case 'AT':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            1 => array( 'name' => self::_('Burgenland') ),
                            2 => array( 'name' => self::_('Kärnten') ),
                            3 => array( 'name' => self::_('Niederösterreich') ),
                            4 => array( 'name' => self::_('Oberösterreich') ),
                            5 => array( 'name' => self::_('Salzburg') ),
                            6 => array( 'name' => self::_('Steiermark') ),
                            7 => array( 'name' => self::_('Tirol') ),
                            8 => array( 'name' => self::_('Vorarlberg') ),
                            9 => array( 'name' => self::_('Wien') )
                        ) );
                    break;
                case 'AU':
                    return array(
                        'regions_label' => self::_('State'), // label territoru as state
                        'regions'       => array(
                            'NSW' => array( 'name' => self::_('New South Wales') ),
                            'QLD' => array( 'name' => self::_('Queensland') ),
                            'SA'  => array( 'name' => self::_('South Australia') ),
                            'TAS' => array( 'name' => self::_('Tasmania') ),
                            'VIC' => array( 'name' => self::_('Victoria') ),
                            'WA'  => array( 'name' => self::_('Western Australia') ),
                            'ACT' => array( 'name' => self::_('Australian Capital Territory') ),
                            'NT'  => array( 'name' => self::_('Northern Territory') )
                        ) );
                    break;
                case 'AZ':
                    return array(
                        'regions_label'    => self::_('Rayon'), // label city districts and autonomous republic as rayon
                        'subregions_label' => self::_('City'),
                        'regions'          => array(
                            'NX'  => array(
                                'name'       => self::_('Naxçıvan'),
                                'subregions' => array(
                                    'BAB' => array( 'name' => self::_('Babək') ),
                                    'CUL' => array( 'name' => self::_('Culfa') ),
                                    'ORD' => array( 'name' => self::_('Ordubad') ),
                                    'SAD' => array( 'name' => self::_('Sədərək') ),
                                    'SAH' => array( 'name' => self::_('Şahbuz') ),
                                    'SAR' => array( 'name' => self::_('Şərur') )
                                ),
                            ),
                            'AB'  => array( 'name' => self::_('Əli Bayramlı') ),
                            'BA'  => array( 'name' => self::_('Bakı') ),
                            'GA'  => array( 'name' => self::_('Gəncə') ),
                            'LA'  => array( 'name' => self::_('Lənkəran') ),
                            'MI'  => array( 'name' => self::_('Mingəçevir') ),
                            'NA'  => array( 'name' => self::_('Naftalan') ),
                            'SA'  => array( 'name' => self::_('Şəki') ),
                            'SM'  => array( 'name' => self::_('Sumqayıt') ),
                            'SS'  => array( 'name' => self::_('Şuşa') ),
                            'XA'  => array( 'name' => self::_('Xankəndi') ),
                            'YE'  => array( 'name' => self::_('Yevlax') ),
                            'ABS' => array( 'name' => self::_('Abşeron') ),
                            'AGC' => array( 'name' => self::_('Ağcabədi') ),
                            'AGM' => array( 'name' => self::_('Ağdam') ),
                            'AGS' => array( 'name' => self::_('Ağdaş') ),
                            'AGA' => array( 'name' => self::_('Ağstafa') ),
                            'AGU' => array( 'name' => self::_('Ağsu') ),
                            'AST' => array( 'name' => self::_('Astara') ),
                            'BAL' => array( 'name' => self::_('Balakən') ),
                            'BAR' => array( 'name' => self::_('Bərdə') ),
                            'BEY' => array( 'name' => self::_('Beyləqan') ),
                            'BIL' => array( 'name' => self::_('Biləsuvar') ),
                            'CAB' => array( 'name' => self::_('Cəbrayıl') ),
                            'CAL' => array( 'name' => self::_('Cəlilabab') ),
                            'DAS' => array( 'name' => self::_('Daşkəsən') ),
                            'DAV' => array( 'name' => self::_('Dəvəçi') ),
                            'FUZ' => array( 'name' => self::_('Füzuli') ),
                            'GAD' => array( 'name' => self::_('Gədəbəy') ),
                            'GOR' => array( 'name' => self::_('Goranboy') ),
                            'GOY' => array( 'name' => self::_('Göyçay') ),
                            'HAC' => array( 'name' => self::_('Hacıqabul') ),
                            'IMI' => array( 'name' => self::_('İmişli') ),
                            'ISM' => array( 'name' => self::_('İsmayıllı') ),
                            'KAL' => array( 'name' => self::_('Kəlbəcər') ),
                            'KUR' => array( 'name' => self::_('Kürdəmir') ),
                            'LAC' => array( 'name' => self::_('Laçın') ),
                            'LAN' => array( 'name' => self::_('Lənkəran') ),
                            'LER' => array( 'name' => self::_('Lerik') ),
                            'MAS' => array( 'name' => self::_('Masallı') ),
                            'NEF' => array( 'name' => self::_('Neftçala') ),
                            'OGU' => array( 'name' => self::_('Oğuz') ),
                            'QAB' => array( 'name' => self::_('Qəbələ') ),
                            'QAX' => array( 'name' => self::_('Qax') ),
                            'QAZ' => array( 'name' => self::_('Qazax') ),
                            'QOB' => array( 'name' => self::_('Qobustan') ),
                            'QBA' => array( 'name' => self::_('Quba') ),
                            'QBI' => array( 'name' => self::_('Qubadlı') ),
                            'QUS' => array( 'name' => self::_('Qusar') ),
                            'SAT' => array( 'name' => self::_('Saatlı') ),
                            'SAB' => array( 'name' => self::_('Sabirabad') ),
                            'SAK' => array( 'name' => self::_('Şəki') ),
                            'SAL' => array( 'name' => self::_('Salyan') ),
                            'SMI' => array( 'name' => self::_('Şamaxı') ),
                            'SKR' => array( 'name' => self::_('Şəmkir') ),
                            'SMX' => array( 'name' => self::_('Samux') ),
                            'SIY' => array( 'name' => self::_('Siyəzən') ),
                            'SUS' => array( 'name' => self::_('Şuşa') ),
                            'TAR' => array( 'name' => self::_('Tərtər') ),
                            'TOV' => array( 'name' => self::_('Tovuz') ),
                            'UCA' => array( 'name' => self::_('Ucar') ),
                            'XAC' => array( 'name' => self::_('Xaçmaz') ),
                            'XAN' => array( 'name' => self::_('Xanlar') ),
                            'XIZ' => array( 'name' => self::_('Xızı') ),
                            'XCI' => array( 'name' => self::_('Xocalı') ),
                            'XVD' => array( 'name' => self::_('Xocavənd') ),
                            'YAR' => array( 'name' => self::_('Yardımlı') ),
                            'YEV' => array( 'name' => self::_('Yevlax') ),
                            'ZAN' => array( 'name' => self::_('Zəngilan') ),
                            'ZAQ' => array( 'name' => self::_('Zaqatala') ),
                            'ZAR' => array( 'name' => self::_('Zərdab') )
                        ) );
                    break;
                case 'BA':
                    return array(
                        'regions_label'    => self::_('Entity'),
                        'subregions_label' => self::_('Canton'),
                        'regions'          => array(
                            'BIH' => array(
                                'name'       => self::_('Federacija Bosne i Hercegovine'),
                                'subregions' => array(
                                    '05' => array( 'name' => self::_('Bosansko-podrinjski') ),
                                    '07' => array( 'name' => self::_('Hercegovačko-neretvanski') ),
                                    '10' => array( 'name' => self::_('br. 10 (Livanjski)') ),
                                    '09' => array( 'name' => self::_('Sarajevo') ),
                                    '02' => array( 'name' => self::_('Posavski') ),
                                    '06' => array( 'name' => self::_('Srednjobosanski') ),
                                    '03' => array( 'name' => self::_('Tuzlanski') ),
                                    '01' => array( 'name' => self::_('Unsko-sanski') ),
                                    '08' => array( 'name' => self::_('Zapadnohercegovački') ),
                                    '04' => array( 'name' => self::_('Zeničko-dobojski kanton') )
                                )
                            ),
                            'SRP' => array( 'name' => self::_('Republika Srpska') ),
                            'BRC' => array( 'name' => self::_('Brčko distrikt') )
                        ) );
                    break;
                case 'BB':
                    return array(
                        'regions_label' => self::_('Parish'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Christ Church') ),
                            '02' => array( 'name' => self::_('Saint Andrew') ),
                            '03' => array( 'name' => self::_('Saint George') ),
                            '04' => array( 'name' => self::_('Saint James') ),
                            '05' => array( 'name' => self::_('Saint John') ),
                            '06' => array( 'name' => self::_('Saint Joseph') ),
                            '07' => array( 'name' => self::_('Saint Lucy') ),
                            '08' => array( 'name' => self::_('Saint Michael') ),
                            '09' => array( 'name' => self::_('Saint Peter') ),
                            10   => array( 'name' => self::_('Saint Philip') ),
                            11   => array( 'name' => self::_('Saint Thomas') )
                        ) );
                    break;
                case 'BD':
                    return array(
                        'regions_label'    => self::_('Division'),
                        'subregions_label' => self::_('District'),
                        'regions'          => array(
                            '1' => array(
                                'name'       => self::_('Barisal bibhag'),
                                'subregions' => array(
                                    '02' => array( 'name' => self::_('Barguna zila') ),
                                    '06' => array( 'name' => self::_('Barisal zila') ),
                                    '07' => array( 'name' => self::_('Bhola zila') ),
                                    '25' => array( 'name' => self::_('Jhalakati zila') ),
                                    '51' => array( 'name' => self::_('Patuakhali zila') ),
                                    '50' => array( 'name' => self::_('Pirojpur zila') )
                                )
                            ),
                            '2' => array(
                                'name'       => self::_('Chittagong bibhag'),
                                'subregions' => array(
                                    '01' => array( 'name' => self::_('Bandarban zila') ),
                                    '04' => array( 'name' => self::_('Brahmanbaria zila') ),
                                    '09' => array( 'name' => self::_('Chandpur zila') ),
                                    '10' => array( 'name' => self::_('Chittagong zila') ),
                                    '08' => array( 'name' => self::_('Comilla zila') ),
                                    '11' => array( 'name' => self::_('Cox\'s Bazar zila') ),
                                    '16' => array( 'name' => self::_('Feni zila') ),
                                    '29' => array( 'name' => self::_('Khagrachari zila') ),
                                    '31' => array( 'name' => self::_('Lakshmipur zila') ),
                                    '47' => array( 'name' => self::_('Noakhali zila') ),
                                    '56' => array( 'name' => self::_('Rangamati zila') )
                                )
                            ),
                            '3' => array(
                                'name'       => self::_('Dhaka bibhag'),
                                'subregions' => array(
                                    '13' => array( 'name' => self::_('Dhaka zila') ),
                                    '15' => array( 'name' => self::_('Faridpur zila') ),
                                    '18' => array( 'name' => self::_('Gazipur zila') ),
                                    '17' => array( 'name' => self::_('Gopalganj zila') ),
                                    '21' => array( 'name' => self::_('Jamalpur zila') ),
                                    '26' => array( 'name' => self::_('Kishorganj zila') ),
                                    '36' => array( 'name' => self::_('Madaripur zila') ),
                                    '33' => array( 'name' => self::_('Manikganj zila') ),
                                    '35' => array( 'name' => self::_('Munshiganj zila') ),
                                    '34' => array( 'name' => self::_('Mymensingh zila') ),
                                    '40' => array( 'name' => self::_('Narayanganj zila') ),
                                    '42' => array( 'name' => self::_('Narsingdi zila') ),
                                    '41' => array( 'name' => self::_('Netrakona zila') ),
                                    '53' => array( 'name' => self::_('Rajbari zila') ),
                                    '62' => array( 'name' => self::_('Shariatpur zila') ),
                                    '57' => array( 'name' => self::_('Sherpur zila') ),
                                    '63' => array( 'name' => self::_('Tangail zila') )
                                )
                            ),
                            '4' => array(
                                'name'       => self::_('Khulna bibhag'),
                                'subregions' => array(
                                    '05' => array( 'name' => self::_('Bagerhat zila') ),
                                    '12' => array( 'name' => self::_('Chuadanga zila') ),
                                    '22' => array( 'name' => self::_('Jessore zila') ),
                                    '23' => array( 'name' => self::_('Jhenaidah zila') ),
                                    '27' => array( 'name' => self::_('Khulna zila') ),
                                    '30' => array( 'name' => self::_('Kushtia zila') ),
                                    '37' => array( 'name' => self::_('Magura zila') ),
                                    '39' => array( 'name' => self::_('Meherpur zila') ),
                                    '43' => array( 'name' => self::_('Narail zila') ),
                                    '58' => array( 'name' => self::_('Satkhira zila') )
                                )
                            ),
                            '5' => array(
                                'name'       => self::_('Rajshahi bibhag'),
                                'subregions' => array(
                                    '03' => array( 'name' => self::_('Bogra zila') ),
                                    '14' => array( 'name' => self::_('Dinajpur zila') ),
                                    '19' => array( 'name' => self::_('Gaibandha zila') ),
                                    '24' => array( 'name' => self::_('Jaipurhat zila') ),
                                    '28' => array( 'name' => self::_('Kurigram zila') ),
                                    '32' => array( 'name' => self::_('Lalmonirhat zila') ),
                                    '48' => array( 'name' => self::_('Naogaon zila') ),
                                    '44' => array( 'name' => self::_('Natore zila') ),
                                    '45' => array( 'name' => self::_('Nawabganj zila') ),
                                    '46' => array( 'name' => self::_('Nilphamari zila') ),
                                    '49' => array( 'name' => self::_('Pabna zila') ),
                                    '52' => array( 'name' => self::_('Panchagarh zila') ),
                                    '54' => array( 'name' => self::_('Rajshahi zila') ),
                                    '55' => array( 'name' => self::_('Rangpur zila') ),
                                    '59' => array( 'name' => self::_('Sirajganj zila') ),
                                    '64' => array( 'name' => self::_('Thakurgaon zila') )
                                )
                            ),
                            '6' => array(
                                'name'       => self::_('Sylhet bibhag'),
                                'subregions' => array(
                                    '20' => array( 'name' => self::_('Habiganj zila') ),
                                    '38' => array( 'name' => self::_('Moulvibazar zila') ),
                                    '61' => array( 'name' => self::_('Sunamganj zila') ),
                                    '60' => array( 'name' => self::_('Sylhet zila') )
                                )
                            )
                        ) );
                    break;
                case 'BE':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'VAN' => array( 'name' => self::_('Antwerpen') ),
                            'WBR' => array( 'name' => self::_('Brabant Wallon') ),
                            'BRU' => array( 'name' => self::_('Brussels-Capital Region') ),
                            'WHT' => array( 'name' => self::_('Hainaut') ),
                            'WLG' => array( 'name' => self::_('Liege') ),
                            'VLI' => array( 'name' => self::_('Limburg') ),
                            'WLX' => array( 'name' => self::_('Luxembourg') ),
                            'WNA' => array( 'name' => self::_('Namur') ),
                            'VOV' => array( 'name' => self::_('Oost-Vlaanderen') ),
                            'VBR' => array( 'name' => self::_('Vlaams-Brabant') ),
                            'VWV' => array( 'name' => self::_('West-Vlaanderen') )
                        ) );
                    break;
                case 'BF':
                    return array(
                        'regions_label'    => self::_('Region'),
                        'subregions_label' => self::_('Province'),
                        'regions'          => array(
                            '01' => array(
                                'name'       => self::_('Boucle du Mouhoun'),
                                'subregions' => array(
                                    'BAL' => array( 'name' => self::_('Balé') ),
                                    'BAN' => array( 'name' => self::_('Banwa') ),
                                    'KOS' => array( 'name' => self::_('Kossi') ),
                                    'MOU' => array( 'name' => self::_('Mouhoun') ),
                                    'NAY' => array( 'name' => self::_('Nayala') ),
                                    'SOR' => array( 'name' => self::_('Sourou') )
                                )
                            ),
                            '02' => array(
                                'name'       => self::_('Cascades'),
                                'subregions' => array(
                                    'COM' => array( 'name' => self::_('Comoé') ),
                                    'LER' => array( 'name' => self::_('Léraba') )
                                )
                            ),
                            '03' => array(
                                'name'       => self::_('Centre'),
                                'subregions' => array(
                                    'KAD' => array( 'name' => self::_('Kadiogo') )
                                )
                            ),
                            '04' => array(
                                'name'       => self::_('Centre-Est'),
                                'subregions' => array(
                                    'BLG' => array( 'name' => self::_('Boulgou') ),
                                    'KOP' => array( 'name' => self::_('Koulpélogo') ),
                                    'KOT' => array( 'name' => self::_('Kouritenga') )
                                )
                            ),
                            '05' => array(
                                'name'       => self::_('Centre-Nord'),
                                'subregions' => array(
                                    'BAM' => array( 'name' => self::_('Bam') ),
                                    'NAM' => array( 'name' => self::_('Namentenga') ),
                                    'SMT' => array( 'name' => self::_('Sanmatenga') )
                                )
                            ),
                            '06' => array(
                                'name'       => self::_('Centre-Ouest'),
                                'subregions' => array(
                                    'BLK' => array( 'name' => self::_('Boulkiemdé') ),
                                    'SNG' => array( 'name' => self::_('Sanguié') ),
                                    'SIS' => array( 'name' => self::_('Sissili') ),
                                    'ZIR' => array( 'name' => self::_('Ziro') )
                                )
                            ),
                            '07' => array(
                                'name'       => self::_('Centre-Sud'),
                                'subregions' => array(
                                    'BAZ' => array( 'name' => self::_('Bazèga') ),
                                    'NAO' => array( 'name' => self::_('Naouri') ),
                                    'ZOU' => array( 'name' => self::_('Zoundwéogo') )
                                )
                            ),
                            '08' => array(
                                'name'       => self::_('Est'),
                                'subregions' => array(
                                    'GNA' => array( 'name' => self::_('Gnagna') ),
                                    'GOU' => array( 'name' => self::_('Gourma') ),
                                    'KMD' => array( 'name' => self::_('Komondjari') ),
                                    'KMP' => array( 'name' => self::_('Kompienga') ),
                                    'TAP' => array( 'name' => self::_('Tapoa') )
                                )
                            ),
                            '09' => array(
                                'name'       => self::_('Hauts-Bassins'),
                                'subregions' => array(
                                    'HOU' => array( 'name' => self::_('Houet') ),
                                    'KEN' => array( 'name' => self::_('Kénédougou') ),
                                    'TUI' => array( 'name' => self::_('Tui') )
                                )
                            ),
                            '10' => array(
                                'name'       => self::_('Nord'),
                                'subregions' => array(
                                    'LOR' => array( 'name' => self::_('Loroum') ),
                                    'PAS' => array( 'name' => self::_('Passoré') ),
                                    'YAT' => array( 'name' => self::_('Yatenga') ),
                                    'ZON' => array( 'name' => self::_('Zondoma') )
                                )
                            ),
                            '11' => array(
                                'name'       => self::_('Plateau-Central'),
                                'subregions' => array(
                                    'GAN' => array( 'name' => self::_('Ganzourgou') ),
                                    'KOW' => array( 'name' => self::_('Kourwéogo') ),
                                    'OUB' => array( 'name' => self::_('Oubritenga') )
                                )
                            ),
                            '12' => array(
                                'name'       => self::_('Sahel'),
                                'subregions' => array(
                                    'OUD' => array( 'name' => self::_('Oudalan') ),
                                    'SEN' => array( 'name' => self::_('Séno') ),
                                    'SOM' => array( 'name' => self::_('Soum') ),
                                    'YAG' => array( 'name' => self::_('Yagha') )
                                )
                            ),
                            '13' => array(
                                'name'       => self::_('Sud-Ouest'),
                                'subregions' => array(
                                    'BGR' => array( 'name' => self::_('Bougouriba') ),
                                    'IOB' => array( 'name' => self::_('Ioba') ),
                                    'NOU' => array( 'name' => self::_('Noumbiel') ),
                                    'PON' => array( 'name' => self::_('Poni') )
                                )
                            )
                        ) );
                    break;
                case 'BG':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Blagoevgrad') ),
                            '02' => array( 'name' => self::_('Burgas') ),
                            '08' => array( 'name' => self::_('Dobrich') ),
                            '07' => array( 'name' => self::_('Gabrovo') ),
                            '26' => array( 'name' => self::_('Haskovo') ),
                            '09' => array( 'name' => self::_('Kardzhali') ),
                            '10' => array( 'name' => self::_('Kyustendil') ),
                            '11' => array( 'name' => self::_('Lovech') ),
                            '12' => array( 'name' => self::_('Montana') ),
                            '13' => array( 'name' => self::_('Pazardzhik') ),
                            '14' => array( 'name' => self::_('Pernik') ),
                            '15' => array( 'name' => self::_('Pleven') ),
                            '16' => array( 'name' => self::_('Plovdiv') ),
                            '17' => array( 'name' => self::_('Razgrad') ),
                            '18' => array( 'name' => self::_('Ruse') ),
                            '27' => array( 'name' => self::_('Shumen') ),
                            '19' => array( 'name' => self::_('Silistra') ),
                            '20' => array( 'name' => self::_('Sliven') ),
                            '21' => array( 'name' => self::_('Smolyan') ),
                            '23' => array( 'name' => self::_('Sofia') ),
                            '22' => array( 'name' => self::_('Sofia-Grad') ),
                            '24' => array( 'name' => self::_('Stara Zagora') ),
                            '25' => array( 'name' => self::_('Targovishte') ),
                            '03' => array( 'name' => self::_('Varna') ),
                            '04' => array( 'name' => self::_('Veliko Tarnovo') ),
                            '05' => array( 'name' => self::_('Vidin') ),
                            '06' => array( 'name' => self::_('Vratsa') ),
                            '28' => array( 'name' => self::_('Yambol') )
                        ) );
                    break;
                case 'BH':
                    return array(
                        'regions_label' => self::_('Governorate'),
                        'regions'       => array(
                            '13' => array( 'name' => self::_('Al Manāmah (Al \'Āşimah)') ),
                            '14' => array( 'name' => self::_('Al Janūbīyah') ),
                            '15' => array( 'name' => self::_('Al Muḩarraq') ),
                            '16' => array( 'name' => self::_('Al Wusţá') ),
                            '17' => array( 'name' => self::_('Ash Shamālīyah') )
                        ) );
                    break;
                case 'BI':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'BB' => array( 'name' => self::_('Bubanza') ),
                            'BM' => array( 'name' => self::_('Bujumbura Mairie') ),
                            'BL' => array( 'name' => self::_('Bujumbura Rural') ),
                            'BR' => array( 'name' => self::_('Bururi') ),
                            'CA' => array( 'name' => self::_('Cankuzo') ),
                            'CI' => array( 'name' => self::_('Cibitoke') ),
                            'GI' => array( 'name' => self::_('Gitega') ),
                            'KR' => array( 'name' => self::_('Karuzi') ),
                            'KY' => array( 'name' => self::_('Kayanza') ),
                            'KI' => array( 'name' => self::_('Kirundo') ),
                            'MA' => array( 'name' => self::_('Makamba') ),
                            'MU' => array( 'name' => self::_('Muramvya') ),
                            'MW' => array( 'name' => self::_('Mwaro') ),
                            'NG' => array( 'name' => self::_('Ngozi') ),
                            'RT' => array( 'name' => self::_('Rutana') ),
                            'RY' => array( 'name' => self::_('Ruyigi') )
                        ) );
                    break;
                case 'BJ':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'AL' => array( 'name' => self::_('Alibori') ),
                            'AK' => array( 'name' => self::_('Atakora') ),
                            'AQ' => array( 'name' => self::_('Atlantique') ),
                            'BO' => array( 'name' => self::_('Borgou') ),
                            'CO' => array( 'name' => self::_('Collines') ),
                            'DO' => array( 'name' => self::_('Donga') ),
                            'KO' => array( 'name' => self::_('Kouffo') ),
                            'LI' => array( 'name' => self::_('Littoral') ),
                            'MO' => array( 'name' => self::_('Mono') ),
                            'OU' => array( 'name' => self::_('Ouémé') ),
                            'PL' => array( 'name' => self::_('Plateau') ),
                            'ZO' => array( 'name' => self::_('Zou') )
                        ) );
                    break;
                case 'BN':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'BE' => array( 'name' => self::_('Belait') ),
                            'BM' => array( 'name' => self::_('Brunei-Muara') ),
                            'TE' => array( 'name' => self::_('Temburong') ),
                            'TU' => array( 'name' => self::_('Tutong') )
                        ) );
                    break;
                case 'BO':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'H' => array( 'name' => self::_('Chuquisaca') ),
                            'C' => array( 'name' => self::_('Cochabamba') ),
                            'B' => array( 'name' => self::_('El Beni') ),
                            'L' => array( 'name' => self::_('La Paz') ),
                            'O' => array( 'name' => self::_('Oruro') ),
                            'N' => array( 'name' => self::_('Pando') ),
                            'P' => array( 'name' => self::_('Potosí') ),
                            'S' => array( 'name' => self::_('Santa Cruz') ),
                            'T' => array( 'name' => self::_('Tarija') )
                        ) );
                    break;
                case 'BR':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            'AC' => array( 'name' => self::_('Acre') ),
                            'AL' => array( 'name' => self::_('Alagoas') ),
                            'AM' => array( 'name' => self::_('Amazonas') ),
                            'AP' => array( 'name' => self::_('Amapá') ),
                            'BA' => array( 'name' => self::_('Bahia') ),
                            'CE' => array( 'name' => self::_('Ceará') ),
                            'ES' => array( 'name' => self::_('Espírito Santo') ),
                            'FN' => array( 'name' => self::_('Fernando de Noronha') ),
                            'GO' => array( 'name' => self::_('Goiás') ),
                            'MA' => array( 'name' => self::_('Maranhão') ),
                            'MG' => array( 'name' => self::_('Minas Gerais') ),
                            'MS' => array( 'name' => self::_('Mato Grosso do Sul') ),
                            'MT' => array( 'name' => self::_('Mato Grosso') ),
                            'PA' => array( 'name' => self::_('Pará') ),
                            'PB' => array( 'name' => self::_('Paraíba') ),
                            'PE' => array( 'name' => self::_('Pernambuco') ),
                            'PI' => array( 'name' => self::_('Piauí') ),
                            'PR' => array( 'name' => self::_('Paraná') ),
                            'RJ' => array( 'name' => self::_('Rio de Janeiro') ),
                            'RN' => array( 'name' => self::_('Rio Grande do Norte') ),
                            'RO' => array( 'name' => self::_('Rondônia') ),
                            'RR' => array( 'name' => self::_('Roraima') ),
                            'RS' => array( 'name' => self::_('Rio Grande do Sul') ),
                            'SC' => array( 'name' => self::_('Santa Catarina') ),
                            'SE' => array( 'name' => self::_('Sergipe') ),
                            'SP' => array( 'name' => self::_('Sâo Paulo') ),
                            'TO' => array( 'name' => self::_('Tocantins') ),
                            'DF' => array( 'name' => self::_('Distrito Federal') )
                        ) );
                    break;
                case 'BS':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'AC' => array( 'name' => self::_('Acklins Islands') ),
                            'BY' => array( 'name' => self::_('Berry Islands') ),
                            'BI' => array( 'name' => self::_('Bimini and Cat Cay') ),
                            'BP' => array( 'name' => self::_('Black Point') ),
                            'CI' => array( 'name' => self::_('Cat Island') ),
                            'CO' => array( 'name' => self::_('Central Abaco') ),
                            'CS' => array( 'name' => self::_('Central Andros') ),
                            'CE' => array( 'name' => self::_('Central Eleuthera') ),
                            'FP' => array( 'name' => self::_('City of Freeport') ),
                            'CK' => array( 'name' => self::_('Crooked Island and Long Cay') ),
                            'EG' => array( 'name' => self::_('East Grand Bahama') ),
                            'EX' => array( 'name' => self::_('Exuma') ),
                            'GC' => array( 'name' => self::_('Grand Cay') ),
                            'GT' => array( 'name' => self::_('Green Turtle Cay') ),
                            'HI' => array( 'name' => self::_('Harbour Island') ),
                            'HT' => array( 'name' => self::_('Hope Town') ),
                            'IN' => array( 'name' => self::_('Inagua') ),
                            'LI' => array( 'name' => self::_('Long Island') ),
                            'MC' => array( 'name' => self::_('Mangrove Cay') ),
                            'MG' => array( 'name' => self::_('Mayaguana') ),
                            'MI' => array( 'name' => self::_('Moore\'s Island') ),
                            'NO' => array( 'name' => self::_('North Abaco') ),
                            'NS' => array( 'name' => self::_('North Andros') ),
                            'NE' => array( 'name' => self::_('North Eleuthera') ),
                            'RI' => array( 'name' => self::_('Ragged Island') ),
                            'RC' => array( 'name' => self::_('Rum Cay') ),
                            'SS' => array( 'name' => self::_('San Salvador') ),
                            'SO' => array( 'name' => self::_('South Abaco') ),
                            'SA' => array( 'name' => self::_('South Andros') ),
                            'SE' => array( 'name' => self::_('South Eleuthera') ),
                            'SW' => array( 'name' => self::_('Spanish Wells') ),
                            'WG' => array( 'name' => self::_('West Grand Bahama') )
                        ) );
                    break;
                case 'BT':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            33   => array( 'name' => self::_('Bumthang') ),
                            12   => array( 'name' => self::_('Chhukha') ),
                            22   => array( 'name' => self::_('Dagana') ),
                            'GA' => array( 'name' => self::_('Gasa') ),
                            13   => array( 'name' => self::_('Ha') ),
                            44   => array( 'name' => self::_('Lhuentse') ),
                            42   => array( 'name' => self::_('Monggar') ),
                            11   => array( 'name' => self::_('Paro') ),
                            43   => array( 'name' => self::_('Pemagatshel') ),
                            23   => array( 'name' => self::_('Punakha') ),
                            45   => array( 'name' => self::_('Samdrup Jongkha') ),
                            14   => array( 'name' => self::_('Samtee') ),
                            31   => array( 'name' => self::_('Sarpang') ),
                            15   => array( 'name' => self::_('Thimphu') ),
                            41   => array( 'name' => self::_('Trashigang') ),
                            'TY' => array( 'name' => self::_('Trashi Yangtse') ),
                            32   => array( 'name' => self::_('Trongsa') ),
                            21   => array( 'name' => self::_('Tsirang') ),
                            24   => array( 'name' => self::_('Wangdue Phodrang') ),
                            34   => array( 'name' => self::_('Zhemgang') )
                        ) );
                    break;
                case 'BW':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'CE' => array( 'name' => self::_('Central') ),
                            'GH' => array( 'name' => self::_('Ghanzi') ),
                            'KG' => array( 'name' => self::_('Kgalagadi') ),
                            'KL' => array( 'name' => self::_('Kgatleng') ),
                            'KW' => array( 'name' => self::_('Kweneng') ),
                            'NG' => array( 'name' => self::_('Ngamiland') ),
                            'NE' => array( 'name' => self::_('North-East') ),
                            'NW' => array( 'name' => self::_('North-West (Botswana)') ),
                            'SE' => array( 'name' => self::_('South-East') ),
                            'SO' => array( 'name' => self::_('Southern (Botswana)') )
                        ) );
                    break;
                case 'BY':
                    return array(
                        'regions_label' => self::_('Oblast'),
                        'regions'       => array(
                            'HM' => array( 'name' => self::_('Horad Minsk') ),
                            'BR' => array( 'name' => self::_('Brèsckaja voblasc\'') ),
                            'HO' => array( 'name' => self::_('Homel\'skaja voblasc\'') ),
                            'HR' => array( 'name' => self::_('Hrodzenskaja voblasc\'') ),
                            'MA' => array( 'name' => self::_('Mahilëuskaja voblasc\'') ),
                            'MI' => array( 'name' => self::_('Minskaja voblasc\'') ),
                            'VI' => array( 'name' => self::_('Vicebskaja voblasc\'') )
                        ) );
                    break;
                case 'BZ':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'BZ'  => array( 'name' => self::_('Belize') ),
                            'CY'  => array( 'name' => self::_('Cayo') ),
                            'CZL' => array( 'name' => self::_('Corozal') ),
                            'OW'  => array( 'name' => self::_('Orange Walk') ),
                            'SC'  => array( 'name' => self::_('Stann Creek') ),
                            'TOL' => array( 'name' => self::_('Toledo') )
                        ) );
                    break;
                case 'CA':
                    return array(
                        'regions_label' => self::_('Province'), // label territory as province
                        'regions'       => array(
                            'AB' => array( 'name' => self::_('Alberta') ),
                            'BC' => array( 'name' => self::_('British Columbia') ),
                            'MB' => array( 'name' => self::_('Manitoba') ),
                            'NB' => array( 'name' => self::_('New Brunswick') ),
                            'NL' => array( 'name' => self::_('Newfoundland and Labrador') ),
                            'NS' => array( 'name' => self::_('Nova Scotia') ),
                            'ON' => array( 'name' => self::_('Ontario') ),
                            'PE' => array( 'name' => self::_('Prince Edward Island') ),
                            'QC' => array( 'name' => self::_('Quebec') ),
                            'SK' => array( 'name' => self::_('Saskatchewan') ),
                            'NT' => array( 'name' => self::_('Northwest Territories') ),
                            'NU' => array( 'name' => self::_('Nunavut') ),
                            'YT' => array( 'name' => self::_('Yukon Territory') )
                        ) );
                    break;
                case 'CD':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'KN' => array( 'name' => self::_('Kinshasa') ),
                            'BN' => array( 'name' => self::_('Bandundu') ),
                            'BC' => array( 'name' => self::_('Bas-Congo') ),
                            'EQ' => array( 'name' => self::_('Équateur') ),
                            'HC' => array( 'name' => self::_('Haut-Congo') ),
                            'KW' => array( 'name' => self::_('Kasai-Occidental') ),
                            'KE' => array( 'name' => self::_('Kasai-Oriental') ),
                            'KA' => array( 'name' => self::_('Katanga') ),
                            'MA' => array( 'name' => self::_('Maniema') ),
                            'NK' => array( 'name' => self::_('Nord-Kivu') ),
                            'OR' => array( 'name' => self::_('Orientale') ),
                            'SK' => array( 'name' => self::_('Sud-Kivu') )
                        ) );
                    break;
                case 'CF':
                    return array(
                        'regions_label' => self::_('Prefecture'),
                        'regions'       => array(
                            'BGF' => array( 'name' => self::_('Bangui') ),
                            'BB'  => array( 'name' => self::_('Bamingui-Bangoran') ),
                            'BK'  => array( 'name' => self::_('Basse-Kotto') ),
                            'HK'  => array( 'name' => self::_('Haute-Kotto') ),
                            'HM'  => array( 'name' => self::_('Haut-Mbomou') ),
                            'KG'  => array( 'name' => self::_('Kémo-Gribingui') ),
                            'LB'  => array( 'name' => self::_('Lobaye') ),
                            'HS'  => array( 'name' => self::_('Haute-Sangha / Mambéré-Kadéï') ),
                            'MB'  => array( 'name' => self::_('Mbomou') ),
                            'NM'  => array( 'name' => self::_('Nana-Mambéré') ),
                            'MP'  => array( 'name' => self::_('Ombella-M\'poko') ),
                            'UK'  => array( 'name' => self::_('Ouaka') ),
                            'AC'  => array( 'name' => self::_('Ouham') ),
                            'OP'  => array( 'name' => self::_('Ouham-Pendé') ),
                            'VR'  => array( 'name' => self::_('Vakaga') ),
                            'KB'  => array( 'name' => self::_('Gribingui') ),
                            'SE'  => array( 'name' => self::_('Sangha') )
                        ) );
                    break;
                case 'CG':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            '11'  => array( 'name' => self::_('Bouenza') ),
                            '8'   => array( 'name' => self::_('Cuvette') ),
                            '15'  => array( 'name' => self::_('Cuvette-Ouest') ),
                            '5'   => array( 'name' => self::_('Kouilou') ),
                            '2'   => array( 'name' => self::_('Lékoumou') ),
                            '7'   => array( 'name' => self::_('Likouala') ),
                            '9'   => array( 'name' => self::_('Niari') ),
                            '14'  => array( 'name' => self::_('Plateaux') ),
                            '12'  => array( 'name' => self::_('Pool') ),
                            '13'  => array( 'name' => self::_('Sangha') ),
                            'BZV' => array( 'name' => self::_('Brazzaville') )
                        ) );
                    break;
                case 'CH':
                    return array(
                        'regions_label' => self::_('Canton'),
                        'regions'       => array(
                            'AG' => array( 'name' => self::_('Aargau') ),
                            'AI' => array( 'name' => self::_('Appenzell Innerrhoden') ),
                            'AR' => array( 'name' => self::_('Appenzell Ausserrhoden') ),
                            'BE' => array( 'name' => self::_('Bern') ),
                            'BL' => array( 'name' => self::_('Basel-Landschaft') ),
                            'BS' => array( 'name' => self::_('Basel-Stadt') ),
                            'FR' => array( 'name' => self::_('Fribourg') ),
                            'GE' => array( 'name' => self::_('Genève') ),
                            'GL' => array( 'name' => self::_('Glarus') ),
                            'GR' => array( 'name' => self::_('Graubünden') ),
                            'JU' => array( 'name' => self::_('Jura') ),
                            'LU' => array( 'name' => self::_('Luzern') ),
                            'NE' => array( 'name' => self::_('Neuchâtel') ),
                            'NW' => array( 'name' => self::_('Nidwalden') ),
                            'OW' => array( 'name' => self::_('Obwalden') ),
                            'SG' => array( 'name' => self::_('Sankt Gallen') ),
                            'SH' => array( 'name' => self::_('Schaffhausen') ),
                            'SO' => array( 'name' => self::_('Solothurn') ),
                            'SZ' => array( 'name' => self::_('Schwyz') ),
                            'TG' => array( 'name' => self::_('Thurgau') ),
                            'TI' => array( 'name' => self::_('Ticino') ),
                            'UR' => array( 'name' => self::_('Uri') ),
                            'VD' => array( 'name' => self::_('Vaud') ),
                            'VS' => array( 'name' => self::_('Valais') ),
                            'ZG' => array( 'name' => self::_('Zug') ),
                            'ZH' => array( 'name' => self::_('Zürich') )
                        ) );
                    break;
                case 'CI':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            '06' => array( 'name' => self::_('18 Montagnes (Région des)') ),
                            16   => array( 'name' => self::_('Agnébi (Région de l\')') ),
                            17   => array( 'name' => self::_('Bafing (Région du)') ),
                            '09' => array( 'name' => self::_('Bas-Sassandra (Région du)') ),
                            10   => array( 'name' => self::_('Denguélé (Région du)') ),
                            18   => array( 'name' => self::_('Fromager (Région du)') ),
                            '02' => array( 'name' => self::_('Haut-Sassandra (Région du)') ),
                            '07' => array( 'name' => self::_('Lacs (Région des)') ),
                            '01' => array( 'name' => self::_('Lagunes (Région des)') ),
                            12   => array( 'name' => self::_('Marahoué (Région de la)') ),
                            19   => array( 'name' => self::_('Moyen-Cavally (Région du)') ),
                            '05' => array( 'name' => self::_('Moyen-Comoé (Région du)') ),
                            11   => array( 'name' => self::_('Nzi-Comoé (Région)') ),
                            '03' => array( 'name' => self::_('Savanes (Région des)') ),
                            15   => array( 'name' => self::_('Sud-Bandama (Région du)') ),
                            13   => array( 'name' => self::_('Sud-Comoé (Région du)') ),
                            '04' => array( 'name' => self::_('Vallée du Bandama (Région de la)') ),
                            14   => array( 'name' => self::_('Worodouqou (Région du)') ),
                            '08' => array( 'name' => self::_('Zanzan (Région du)') )
                        ) );
                    break;
                case 'CL':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'AI' => array( 'name' => self::_('Aisén del General Carlos Ibáñez del Campo') ),
                            'AN' => array( 'name' => self::_('Antofagasta') ),
                            'AR' => array( 'name' => self::_('Araucanía') ),
                            'AP' => array( 'name' => self::_('Arica y Parinacota') ),
                            'AT' => array( 'name' => self::_('Atacama') ),
                            'BI' => array( 'name' => self::_('Bío-Bío') ),
                            'CO' => array( 'name' => self::_('Coquimbo') ),
                            'LI' => array( 'name' => self::_('Libertador General Bernardo O\'Higgins') ),
                            'LL' => array( 'name' => self::_('Los Lagos') ),
                            'LR' => array( 'name' => self::_('Los Ríos') ),
                            'MA' => array( 'name' => self::_('Magallanes y Antártica Chilena') ),
                            'ML' => array( 'name' => self::_('Maule') ),
                            'RM' => array( 'name' => self::_('Región Metropolitana de Santiago') ),
                            'TA' => array( 'name' => self::_('Tarapacá') ),
                            'VS' => array( 'name' => self::_('Valparaíso') )
                        ) );
                    break;
                case 'CM':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'AD' => array( 'name' => self::_('Adamaoua') ),
                            'CE' => array( 'name' => self::_('Centre') ),
                            'ES' => array( 'name' => self::_('East') ),
                            'EN' => array( 'name' => self::_('Far North') ),
                            'LT' => array( 'name' => self::_('Littoral') ),
                            'NO' => array( 'name' => self::_('North') ),
                            'NW' => array( 'name' => self::_('North-West (Cameroon)') ),
                            'SU' => array( 'name' => self::_('South') ),
                            'SW' => array( 'name' => self::_('South-West') ),
                            'OU' => array( 'name' => self::_('West') )
                        ) );
                    break;
                case 'CN':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '11' => array( 'name' => self::_('Beijing') ),
                            '50' => array( 'name' => self::_('Chongqing') ),
                            '31' => array( 'name' => self::_('Shanghai') ),
                            '12' => array( 'name' => self::_('Tianjin') ),
                            '34' => array( 'name' => self::_('Anhui') ),
                            '35' => array( 'name' => self::_('Fujian') ),
                            '62' => array( 'name' => self::_('Gansu') ),
                            '44' => array( 'name' => self::_('Guangdong') ),
                            '52' => array( 'name' => self::_('Guizhou') ),
                            '46' => array( 'name' => self::_('Hainan') ),
                            '13' => array( 'name' => self::_('Hebei') ),
                            '23' => array( 'name' => self::_('Heilongjiang') ),
                            '41' => array( 'name' => self::_('Henan') ),
                            '42' => array( 'name' => self::_('Hubei') ),
                            '43' => array( 'name' => self::_('Hunan') ),
                            '32' => array( 'name' => self::_('Jiangsu') ),
                            '36' => array( 'name' => self::_('Jiangxi') ),
                            '22' => array( 'name' => self::_('Jilin') ),
                            '21' => array( 'name' => self::_('Liaoning') ),
                            '63' => array( 'name' => self::_('Qinghai') ),
                            '61' => array( 'name' => self::_('Shaanxi') ),
                            '37' => array( 'name' => self::_('Shandong') ),
                            '14' => array( 'name' => self::_('Shanxi') ),
                            '51' => array( 'name' => self::_('Sichuan') ),
                            '71' => array( 'name' => self::_('Taiwan') ),
                            '53' => array( 'name' => self::_('Yunnan') ),
                            '33' => array( 'name' => self::_('Zhejiang') ),
                            '45' => array( 'name' => self::_('Guangxi') ),
                            '15' => array( 'name' => self::_('Nei Mongol') ),
                            '64' => array( 'name' => self::_('Ningxia') ),
                            '65' => array( 'name' => self::_('Xinjiang') ),
                            '54' => array( 'name' => self::_('Xizang') ),
                            '91' => array( 'name' => self::_('Xianggang (Hong-Kong)') ),
                            '92' => array( 'name' => self::_('Aomen (Macau)') )
                        ) );
                    break;
                case 'CO':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'DC'  => array( 'name' => self::_('Distrito Capital de Bogotá') ),
                            'AMA' => array( 'name' => self::_('Amazonas') ),
                            'ANT' => array( 'name' => self::_('Antioquia') ),
                            'ARA' => array( 'name' => self::_('Arauca') ),
                            'ATL' => array( 'name' => self::_('Atlántico') ),
                            'BOL' => array( 'name' => self::_('Bolívar') ),
                            'BOY' => array( 'name' => self::_('Boyacá') ),
                            'CAL' => array( 'name' => self::_('Caldas') ),
                            'CAQ' => array( 'name' => self::_('Caquetá') ),
                            'CAS' => array( 'name' => self::_('Casanare') ),
                            'CAU' => array( 'name' => self::_('Cauca') ),
                            'CES' => array( 'name' => self::_('Cesar') ),
                            'CHO' => array( 'name' => self::_('Chocó') ),
                            'COR' => array( 'name' => self::_('Córdoba') ),
                            'CUN' => array( 'name' => self::_('Cundinamarca') ),
                            'GUA' => array( 'name' => self::_('Guainía') ),
                            'GUV' => array( 'name' => self::_('Guaviare') ),
                            'HUI' => array( 'name' => self::_('Huila') ),
                            'LAG' => array( 'name' => self::_('La Guajira') ),
                            'MAG' => array( 'name' => self::_('Magdalena') ),
                            'MET' => array( 'name' => self::_('Meta') ),
                            'NAR' => array( 'name' => self::_('Nariño') ),
                            'NSA' => array( 'name' => self::_('Norte de Santander') ),
                            'PUT' => array( 'name' => self::_('Putumayo') ),
                            'QUI' => array( 'name' => self::_('Quindío') ),
                            'RIS' => array( 'name' => self::_('Risaralda') ),
                            'SAP' => array( 'name' => self::_('San Andrés, Providencia y Santa Catalina') ),
                            'SAN' => array( 'name' => self::_('Santander') ),
                            'SUC' => array( 'name' => self::_('Sucre') ),
                            'TOL' => array( 'name' => self::_('Tolima') ),
                            'VAC' => array( 'name' => self::_('Valle del Cauca') ),
                            'VAU' => array( 'name' => self::_('Vaupés') ),
                            'VID' => array( 'name' => self::_('Vichada') )
                        ) );
                    break;
                case 'CR':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'A'  => array( 'name' => self::_('Alajuela') ),
                            'C'  => array( 'name' => self::_('Cartago') ),
                            'G'  => array( 'name' => self::_('Guanacaste') ),
                            'H'  => array( 'name' => self::_('Heredia') ),
                            'L'  => array( 'name' => self::_('Limón') ),
                            'P'  => array( 'name' => self::_('Puntarenas') ),
                            'SJ' => array( 'name' => self::_('San José') )
                        ) );
                    break;
                case 'CU':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '09' => array( 'name' => self::_('Camagüey') ),
                            '08' => array( 'name' => self::_('Ciego de Ávila') ),
                            '06' => array( 'name' => self::_('Cienfuegos') ),
                            '03' => array( 'name' => self::_('Ciudad de La Habana') ),
                            '12' => array( 'name' => self::_('Granma') ),
                            '14' => array( 'name' => self::_('Guantánamo') ),
                            '11' => array( 'name' => self::_('Holguín') ),
                            '02' => array( 'name' => self::_('La Habana') ),
                            '10' => array( 'name' => self::_('Las Tunas') ),
                            '04' => array( 'name' => self::_('Matanzas') ),
                            '01' => array( 'name' => self::_('Pinar del Rio') ),
                            '07' => array( 'name' => self::_('Sancti Spíritus') ),
                            '13' => array( 'name' => self::_('Santiago de Cuba') ),
                            '05' => array( 'name' => self::_('Villa Clara') ),
                            '99' => array( 'name' => self::_('Isla de la Juventud') )
                        ) );
                    break;
                case 'CV':
                    return array(
                        'regions_label' => self::_('Municipality'),
                        'regions'       => array(
                            'BV' => array( 'name' => self::_('Boa Vista') ),
                            'PA' => array( 'name' => self::_('Paul') ),
                            'PN' => array( 'name' => self::_('Porto Novo') ),
                            'RB' => array( 'name' => self::_('Ribeira Brava') ),
                            'RG' => array( 'name' => self::_('Ribeira Grande') ),
                            'SL' => array( 'name' => self::_('Sal') ),
                            'SV' => array( 'name' => self::_('São Vicente') ),
                            'BR' => array( 'name' => self::_('Brava') ),
                            'MA' => array( 'name' => self::_('Maio') ),
                            'MO' => array( 'name' => self::_('Mosteiros') ),
                            'PR' => array( 'name' => self::_('Praia') ),
                            'RS' => array( 'name' => self::_('Ribeira Grande de Santiago') ),
                            'CA' => array( 'name' => self::_('Santa Catarina') ),
                            'CF' => array( 'name' => self::_('Santa Catarina de Fogo') ),
                            'CR' => array( 'name' => self::_('Santa Cruz') ),
                            'SD' => array( 'name' => self::_('São Domingos') ),
                            'SF' => array( 'name' => self::_('São Filipe') ),
                            'SL' => array( 'name' => self::_('São Lourenço dos Órgãos') ),
                            'SM' => array( 'name' => self::_('São Miguel') ),
                            'SS' => array( 'name' => self::_('São Salvador do Mundo') ),
                            'TA' => array( 'name' => self::_('Tarrafal') ),
                            'TS' => array( 'name' => self::_('Tarrafal de São Nicolau') )
                        ) );
                    break;
                case 'CY':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            '04' => array( 'name' => self::_('Ammóchostos') ),
                            '06' => array( 'name' => self::_('Kerýneia') ),
                            '03' => array( 'name' => self::_('Lárnaka') ),
                            '01' => array( 'name' => self::_('Lefkosía') ),
                            '02' => array( 'name' => self::_('Lemesós') ),
                            '05' => array( 'name' => self::_('Páfos') )
                        ) );
                    break;
                case 'CZ':
                    return array(
                        'regions_label'    => self::_('Region'),
                        'subregions_label' => self::_('District'),
                        'regions'          => array(
                            'JC' => array(
                                'name'       => self::_('Jihočeský kraj'),
                                'subregions' => array(
                                    '311' => array( 'name' => self::_('České Budějovice') ),
                                    '312' => array( 'name' => self::_('Český Krumlov') ),
                                    '313' => array( 'name' => self::_('Jindřichův Hradec') ),
                                    '314' => array( 'name' => self::_('Písek') ),
                                    '315' => array( 'name' => self::_('Prachatice') ),
                                    '316' => array( 'name' => self::_('Strakonice') ),
                                    '317' => array( 'name' => self::_('Tábor') )
                                ),
                            ),
                            'JM' => array(
                                'name'       => self::_('Jihomoravský kraj'),
                                'subregions' => array(
                                    '621' => array( 'name' => self::_('Blansko') ),
                                    '622' => array( 'name' => self::_('Brno-město') ),
                                    '623' => array( 'name' => self::_('Brno-venkov') ),
                                    '624' => array( 'name' => self::_('Břeclav') ),
                                    '625' => array( 'name' => self::_('Hodonín') ),
                                    '626' => array( 'name' => self::_('Vyškov') ),
                                    '627' => array( 'name' => self::_('Znojmo') )
                                )
                            ),
                            'KA' => array(
                                'name'       => self::_('Karlovarský kraj'),
                                'subregions' => array(
                                    '411' => array( 'name' => self::_('Cheb') ),
                                    '412' => array( 'name' => self::_('Karlovy Vary') ),
                                    '413' => array( 'name' => self::_('Sokolov') )
                                )
                            ),
                            'KR' => array(
                                'name'       => self::_('Královéhradecký kraj'),
                                'subregions' => array(
                                    '521' => array( 'name' => self::_('Hradec Králové') ),
                                    '522' => array( 'name' => self::_('Jičín') ),
                                    '523' => array( 'name' => self::_('Náchod') ),
                                    '524' => array( 'name' => self::_('Rychnov nad Kněžnou') ),
                                    '525' => array( 'name' => self::_('Trutnov') )
                                )
                            ),
                            'LI' => array(
                                'name'       => self::_('Liberecký kraj'),
                                'subregions' => array(
                                    '511' => array( 'name' => self::_('Česká Lípa') ),
                                    '512' => array( 'name' => self::_('Jablonec nad Nisou') ),
                                    '513' => array( 'name' => self::_('Liberec') ),
                                    '514' => array( 'name' => self::_('Semily') )
                                )
                            ),
                            'MO' => array(
                                'name'       => self::_('Moravskoslezský kraj'),
                                'subregions' => array(
                                    '801' => array( 'name' => self::_('Bruntál') ),
                                    '802' => array( 'name' => self::_('Frýdek Místek') ),
                                    '803' => array( 'name' => self::_('Karviná') ),
                                    '804' => array( 'name' => self::_('Nový Jičín') ),
                                    '805' => array( 'name' => self::_('Opava') ),
                                    '806' => array( 'name' => self::_('Ostrava město') )
                                )
                            ),
                            'OL' => array(
                                'name'       => self::_('Olomoucký kraj'),
                                'subregions' => array(
                                    '711' => array( 'name' => self::_('Jeseník') ),
                                    '712' => array( 'name' => self::_('Olomouc') ),
                                    '713' => array( 'name' => self::_('Prostĕjov') ),
                                    '714' => array( 'name' => self::_('Přerov') ),
                                    '715' => array( 'name' => self::_('Šumperk') )
                                )
                            ),
                            'PA' => array(
                                'name'       => self::_('Pardubický kraj'),
                                'subregions' => array(
                                    '531' => array( 'name' => self::_('Chrudim') ),
                                    '532' => array( 'name' => self::_('Pardubice') ),
                                    '533' => array( 'name' => self::_('Svitavy') ),
                                    '534' => array( 'name' => self::_('Ústí nad Orlicí') )
                                )
                            ),
                            'PL' => array(
                                'name'       => self::_('Plzeňský kraj'),
                                'subregions' => array(
                                    '321' => array( 'name' => self::_('Domažlice') ),
                                    '322' => array( 'name' => self::_('Klatovy') ),
                                    '324' => array( 'name' => self::_('Plzeň jih') ),
                                    '323' => array( 'name' => self::_('Plzeň město') ),
                                    '325' => array( 'name' => self::_('Plzeň sever') ),
                                    '326' => array( 'name' => self::_('Rokycany') ),
                                    '327' => array( 'name' => self::_('Tachov') )
                                )
                            ),
                            'PR' => array(
                                'name'       => self::_('Praha, hlavní město'),
                                'subregions' => array(
                                    '101' => array( 'name' => self::_('Praha 1') ),
                                    '102' => array( 'name' => self::_('Praha 2') ),
                                    '103' => array( 'name' => self::_('Praha 3') ),
                                    '104' => array( 'name' => self::_('Praha 4') ),
                                    '105' => array( 'name' => self::_('Praha 5') ),
                                    '106' => array( 'name' => self::_('Praha 6') ),
                                    '107' => array( 'name' => self::_('Praha 7') ),
                                    '108' => array( 'name' => self::_('Praha 8') ),
                                    '109' => array( 'name' => self::_('Praha 9') ),
                                    '10A' => array( 'name' => self::_('Praha 10') ),
                                    '10B' => array( 'name' => self::_('Praha 11') ),
                                    '10C' => array( 'name' => self::_('Praha 12') ),
                                    '10D' => array( 'name' => self::_('Praha 13') ),
                                    '10E' => array( 'name' => self::_('Praha 14') ),
                                    '10F' => array( 'name' => self::_('Praha 15') )
                                )
                            ),
                            'ST' => array(
                                'name'       => self::_('Středočeský kraj'),
                                'subregions' => array(
                                    '201' => array( 'name' => self::_('Benešov') ),
                                    '202' => array( 'name' => self::_('Beroun') ),
                                    '203' => array( 'name' => self::_('Kladno') ),
                                    '204' => array( 'name' => self::_('Kolín') ),
                                    '205' => array( 'name' => self::_('Kutná Hora') ),
                                    '206' => array( 'name' => self::_('Mělník') ),
                                    '207' => array( 'name' => self::_('Mladá Boleslav') ),
                                    '208' => array( 'name' => self::_('Nymburk') ),
                                    '209' => array( 'name' => self::_('Praha východ') ),
                                    '20A' => array( 'name' => self::_('Praha západ') ),
                                    '20B' => array( 'name' => self::_('Příbram') ),
                                    '20C' => array( 'name' => self::_('Rakovník') )
                                )
                            ),
                            'US' => array(
                                'name'       => self::_('Ústecký kraj'),
                                'subregions' => array(
                                    '421' => array( 'name' => self::_('Děčín') ),
                                    '422' => array( 'name' => self::_('Chomutov') ),
                                    '423' => array( 'name' => self::_('Litoměřice') ),
                                    '424' => array( 'name' => self::_('Louny') ),
                                    '425' => array( 'name' => self::_('Most') ),
                                    '426' => array( 'name' => self::_('Teplice') ),
                                    '427' => array( 'name' => self::_('Ústí nad Labem') )
                                )
                            ),
                            'VY' => array(
                                'name'       => self::_('Vysočina'),
                                'subregions' => array(
                                    '611' => array( 'name' => self::_('Havlíčkův Brod') ),
                                    '612' => array( 'name' => self::_('Jihlava') ),
                                    '613' => array( 'name' => self::_('Pelhřimov') ),
                                    '614' => array( 'name' => self::_('Třebíč') ),
                                    '615' => array( 'name' => self::_('Žd\'ár nad Sázavou') )
                                )
                            ),
                            'ZL' => array(
                                'name'       => self::_('Zlínský kraj'),
                                'subregions' => array(
                                    '721' => array( 'name' => self::_('Kromĕříž') ),
                                    '722' => array( 'name' => self::_('Uherské Hradištĕ') ),
                                    '723' => array( 'name' => self::_('Vsetín') ),
                                    '724' => array( 'name' => self::_('Zlín') )
                                )
                            )
                        ) );
                    break;
                case 'DE':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            'BW' => array( 'name' => self::_('Baden-Württemberg') ),
                            'BY' => array( 'name' => self::_('Bayern') ),
                            'HB' => array( 'name' => self::_('Bremen') ),
                            'HH' => array( 'name' => self::_('Hamburg') ),
                            'HE' => array( 'name' => self::_('Hessen') ),
                            'NI' => array( 'name' => self::_('Niedersachsen') ),
                            'NW' => array( 'name' => self::_('Nordrhein-Westfalen') ),
                            'RP' => array( 'name' => self::_('Rheinland-Pfalz') ),
                            'SL' => array( 'name' => self::_('Saarland') ),
                            'SH' => array( 'name' => self::_('Schleswig-Holstein') ),
                            'BE' => array( 'name' => self::_('Berlin') ),
                            'BB' => array( 'name' => self::_('Brandenburg') ),
                            'MV' => array( 'name' => self::_('Mecklenburg-Vorpommern') ),
                            'SN' => array( 'name' => self::_('Sachsen') ),
                            'ST' => array( 'name' => self::_('Sachsen-Anhalt') ),
                            'TH' => array( 'name' => self::_('Thüringen') )
                        ) );
                    break;
                case 'DJ':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'AS' => array( 'name' => self::_('Ali Sabieh') ),
                            'AR' => array( 'name' => self::_('Arta') ),
                            'DI' => array( 'name' => self::_('Dikhil') ),
                            'OB' => array( 'name' => self::_('Obock') ),
                            'TA' => array( 'name' => self::_('Tadjourah') ),
                            'DJ' => array( 'name' => self::_('Djibouti') )
                        ) );
                    break;
                case 'DK':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            '81' => array( 'name' => self::_('Nordjylland') ),
                            '82' => array( 'name' => self::_('Midtjylland') ),
                            '83' => array( 'name' => self::_('Syddanmark') ),
                            '84' => array( 'name' => self::_('Hovedstaden') ),
                            '85' => array( 'name' => self::_('Sjælland') )
                        ) );
                    break;
                case 'DM':
                    return array(
                        'regions_label' => self::_('Parish'),
                        'regions'       => array(
                            '02' => array( 'name' => self::_('Saint Andrew') ),
                            '03' => array( 'name' => self::_('Saint David') ),
                            '04' => array( 'name' => self::_('Saint George') ),
                            '05' => array( 'name' => self::_('Saint John') ),
                            '06' => array( 'name' => self::_('Saint Joseph') ),
                            '07' => array( 'name' => self::_('Saint Luke') ),
                            '08' => array( 'name' => self::_('Saint Mark') ),
                            '09' => array( 'name' => self::_('Saint Patrick') ),
                            '10' => array( 'name' => self::_('Saint Paul') ),
                            '01' => array( 'name' => self::_('Saint Peter') )
                        ) );
                    break;
                case 'DO':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Distrito Nacional (Santo Domingo)') ),
                            '02' => array( 'name' => self::_('Azua') ),
                            '03' => array( 'name' => self::_('Bahoruco') ),
                            '04' => array( 'name' => self::_('Barahona') ),
                            '05' => array( 'name' => self::_('Dajabón') ),
                            '06' => array( 'name' => self::_('Duarte') ),
                            '08' => array( 'name' => self::_('El Seybo [El Seibo]') ),
                            '09' => array( 'name' => self::_('Espaillat') ),
                            '30' => array( 'name' => self::_('Hato Mayor') ),
                            '10' => array( 'name' => self::_('Independencia') ),
                            '11' => array( 'name' => self::_('La Altagracia') ),
                            '07' => array( 'name' => self::_('La Estrelleta [Elías Piña]') ),
                            '12' => array( 'name' => self::_('La Romana') ),
                            '13' => array( 'name' => self::_('La Vega') ),
                            '14' => array( 'name' => self::_('María Trinidad Sánchez') ),
                            '28' => array( 'name' => self::_('Monseñor Nouel') ),
                            '15' => array( 'name' => self::_('Monte Cristi') ),
                            '29' => array( 'name' => self::_('Monte Plata') ),
                            '16' => array( 'name' => self::_('Pedernales') ),
                            '17' => array( 'name' => self::_('Peravia') ),
                            '18' => array( 'name' => self::_('Puerto Plata') ),
                            '19' => array( 'name' => self::_('Salcedo') ),
                            '20' => array( 'name' => self::_('Samaná') ),
                            '21' => array( 'name' => self::_('San Cristóbal') ),
                            '22' => array( 'name' => self::_('San Juan') ),
                            '23' => array( 'name' => self::_('San Pedro de Macorís') ),
                            '24' => array( 'name' => self::_('Sánchez Ramírez') ),
                            '25' => array( 'name' => self::_('Santiago') ),
                            '26' => array( 'name' => self::_('Santiago Rodríguez') ),
                            '27' => array( 'name' => self::_('Valverde') )
                        ) );
                    break;
                case 'DZ':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Adrar') ),
                            44   => array( 'name' => self::_('Aïn Defla') ),
                            46   => array( 'name' => self::_('Aïn Témouchent') ),
                            16   => array( 'name' => self::_('Alger') ),
                            23   => array( 'name' => self::_('Annaba') ),
                            '05' => array( 'name' => self::_('Batna') ),
                            '08' => array( 'name' => self::_('Béchar') ),
                            '06' => array( 'name' => self::_('Béjaïa') ),
                            '07' => array( 'name' => self::_('Biskra') ),
                            '09' => array( 'name' => self::_('Blida') ),
                            34   => array( 'name' => self::_('Bordj Bou Arréridj') ),
                            10   => array( 'name' => self::_('Bouira') ),
                            35   => array( 'name' => self::_('Boumerdès') ),
                            '02' => array( 'name' => self::_('Chlef') ),
                            25   => array( 'name' => self::_('Constantine') ),
                            17   => array( 'name' => self::_('Djelfa') ),
                            32   => array( 'name' => self::_('El Bayadh') ),
                            39   => array( 'name' => self::_('El Oued') ),
                            36   => array( 'name' => self::_('El Tarf') ),
                            47   => array( 'name' => self::_('Ghardaïa') ),
                            24   => array( 'name' => self::_('Guelma') ),
                            33   => array( 'name' => self::_('Illizi') ),
                            18   => array( 'name' => self::_('Jijel') ),
                            40   => array( 'name' => self::_('Khenchela') ),
                            '03' => array( 'name' => self::_('Laghouat') ),
                            29   => array( 'name' => self::_('Mascara') ),
                            26   => array( 'name' => self::_('Médéa') ),
                            43   => array( 'name' => self::_('Mila') ),
                            27   => array( 'name' => self::_('Mostaganem') ),
                            28   => array( 'name' => self::_('Msila') ),
                            45   => array( 'name' => self::_('Naama') ),
                            31   => array( 'name' => self::_('Oran') ),
                            30   => array( 'name' => self::_('Ouargla') ),
                            '04' => array( 'name' => self::_('Oum el Bouaghi') ),
                            48   => array( 'name' => self::_('Relizane') ),
                            20   => array( 'name' => self::_('Saïda') ),
                            19   => array( 'name' => self::_('Sétif') ),
                            22   => array( 'name' => self::_('Sidi Bel Abbès') ),
                            21   => array( 'name' => self::_('Skikda') ),
                            41   => array( 'name' => self::_('Souk Ahras') ),
                            11   => array( 'name' => self::_('Tamanghasset') ),
                            12   => array( 'name' => self::_('Tébessa') ),
                            14   => array( 'name' => self::_('Tiaret') ),
                            37   => array( 'name' => self::_('Tindouf') ),
                            42   => array( 'name' => self::_('Tipaza') ),
                            38   => array( 'name' => self::_('Tissemsilt') ),
                            15   => array( 'name' => self::_('Tizi Ouzou') ),
                            13   => array( 'name' => self::_('Tlemcen') )
                        ) );
                    break;
                case 'EC':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'A'  => array( 'name' => self::_('Azuay') ),
                            'B'  => array( 'name' => self::_('Bolívar') ),
                            'F'  => array( 'name' => self::_('Cañar') ),
                            'C'  => array( 'name' => self::_('Carchi') ),
                            'X'  => array( 'name' => self::_('Cotopaxi') ),
                            'H'  => array( 'name' => self::_('Chimborazo') ),
                            'O'  => array( 'name' => self::_('El Oro') ),
                            'E'  => array( 'name' => self::_('Esmeraldas') ),
                            'W'  => array( 'name' => self::_('Galápagos') ),
                            'G'  => array( 'name' => self::_('Guayas') ),
                            'I'  => array( 'name' => self::_('Imbabura') ),
                            'L'  => array( 'name' => self::_('Loja') ),
                            'R'  => array( 'name' => self::_('Los Ríos') ),
                            'M'  => array( 'name' => self::_('Manabí') ),
                            'S'  => array( 'name' => self::_('Morona-Santiago') ),
                            'N'  => array( 'name' => self::_('Napo') ),
                            'D'  => array( 'name' => self::_('Orellana') ),
                            'Y'  => array( 'name' => self::_('Pastaza') ),
                            'P'  => array( 'name' => self::_('Pichincha') ),
                            'SE' => array( 'name' => self::_('Santa Elena') ),
                            'SD' => array( 'name' => self::_('Santo Domingo de los Tsáchilas') ),
                            'U'  => array( 'name' => self::_('Sucumbíos') ),
                            'T'  => array( 'name' => self::_('Tungurahua') ),
                            'Z'  => array( 'name' => self::_('Zamora-Chinchipe') )
                        ) );
                    break;
                case 'EE':
                    return array(
                        'regions_label' => self::_('County'),
                        'regions'       => array(
                            37 => array( 'name' => self::_('Harjumaa') ),
                            39 => array( 'name' => self::_('Hiiumaa') ),
                            44 => array( 'name' => self::_('Ida-Virumaa') ),
                            49 => array( 'name' => self::_('Jõgevamaa') ),
                            51 => array( 'name' => self::_('Järvamaa') ),
                            57 => array( 'name' => self::_('Läänemaa') ),
                            59 => array( 'name' => self::_('Lääne-Virumaa') ),
                            65 => array( 'name' => self::_('Põlvamaa') ),
                            67 => array( 'name' => self::_('Pärnumaa') ),
                            70 => array( 'name' => self::_('Raplamaa') ),
                            74 => array( 'name' => self::_('Saaremaa') ),
                            78 => array( 'name' => self::_('Tartumaa') ),
                            82 => array( 'name' => self::_('Valgamaa') ),
                            84 => array( 'name' => self::_('Viljandimaa') ),
                            86 => array( 'name' => self::_('Võrumaa') )
                        ) );
                    break;
                case 'EG':
                    return array(
                        'regions_label' => self::_('Governorate'),
                        'regions'       => array(
                            'DK'  => array( 'name' => self::_('Ad Daqahlīyah') ),
                            'BA'  => array( 'name' => self::_('Al Bahr al Ahmar') ),
                            'BH'  => array( 'name' => self::_('Al Buhayrah') ),
                            'FYM' => array( 'name' => self::_('Al Fayyūm') ),
                            'GH'  => array( 'name' => self::_('Al Gharbīyah') ),
                            'ALX' => array( 'name' => self::_('Al Iskandarīyah') ),
                            'IS'  => array( 'name' => self::_('Al Ismā`īlīyah') ),
                            'GZ'  => array( 'name' => self::_('Al Jīzah') ),
                            'MNF' => array( 'name' => self::_('Al Minūfīyah') ),
                            'MN'  => array( 'name' => self::_('Al Minyā') ),
                            'C'   => array( 'name' => self::_('Al Qāhirah') ),
                            'KB'  => array( 'name' => self::_('Al Qalyūbīyah') ),
                            'WAD' => array( 'name' => self::_('Al Wādī al Jadīd') ),
                            'SU'  => array( 'name' => self::_('As Sādis min Uktūbar') ),
                            'SHR' => array( 'name' => self::_('Ash Sharqīyah') ),
                            'SUZ' => array( 'name' => self::_('As Suways') ),
                            'ASN' => array( 'name' => self::_('Aswān') ),
                            'AST' => array( 'name' => self::_('Asyūt') ),
                            'BNS' => array( 'name' => self::_('Banī Suwayf') ),
                            'PTS' => array( 'name' => self::_('Būr Sa`īd') ),
                            'DT'  => array( 'name' => self::_('Dumyāt') ),
                            'HU'  => array( 'name' => self::_('Ḩulwān') ),
                            'JS'  => array( 'name' => self::_('Janūb Sīnā\'') ),
                            'KFS' => array( 'name' => self::_('Kafr ash Shaykh') ),
                            'MT'  => array( 'name' => self::_('Matrūh') ),
                            'KN'  => array( 'name' => self::_('Qinā') ),
                            'SIN' => array( 'name' => self::_('Shamal Sīnā\'') ),
                            'SHG' => array( 'name' => self::_('Sūhāj') )
                        ) );
                    break;
                case 'ER':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'AN' => array( 'name' => self::_('Anseba') ),
                            'DU' => array( 'name' => self::_('Debub') ),
                            'DK' => array( 'name' => self::_('Debubawi Keyih Bahri [Debub-Keih-Bahri]') ),
                            'GB' => array( 'name' => self::_('Gash-Barka') ),
                            'MA' => array( 'name' => self::_('Maakel [Maekel]') ),
                            'SK' => array( 'name' => self::_('Semenawi Keyih Bahri [Semien-Keih-Bahri]') )
                        ) );
                    break;
                case 'ES':
                    return array(
                        'regions_label'    => self::_('Autonomous community'),
                        'subregions_label' => self::_('Province'),
                        'regions'          => array(
                            'AN' => array(
                                'name'       => self::_('Andalucía'),
                                'subregions' => array(
                                    'AL' => array( 'name' => self::_('Almería') ),
                                    'CA' => array( 'name' => self::_('Cádiz') ),
                                    'CO' => array( 'name' => self::_('Córdoba') ),
                                    'GR' => array( 'name' => self::_('Granada') ),
                                    'H'  => array( 'name' => self::_('Huelva') ),
                                    'J'  => array( 'name' => self::_('Jaén') ),
                                    'MA' => array( 'name' => self::_('Málaga') ),
                                    'SE' => array( 'name' => self::_('Sevilla') )
                                )
                            ),
                            'AR' => array(
                                'name'       => self::_('Aragón'),
                                'subregions' => array(
                                    'HU' => array( 'name' => self::_('Huesca') ),
                                    'TE' => array( 'name' => self::_('Teruel') ),
                                    'Z'  => array( 'name' => self::_('Zaragoza') )
                                )
                            ),
                            'AS' => array( 'name' => self::_('Asturias, Principado de') ),
                            'CN' => array(
                                'name'       => self::_('Canarias'),
                                'subregions' => array(
                                    'GC' => array( 'name' => self::_('Las Palmas') ),
                                    'TF' => array( 'name' => self::_('Santa Cruz de Tenerife') )
                                )
                            ),
                            'CB' => array( 'name' => self::_('Cantabria') ),
                            'CM' => array(
                                'name'       => self::_('Castilla-La Mancha'),
                                'subregions' => array(
                                    'AB' => array( 'name' => self::_('Albacete') ),
                                    'CR' => array( 'name' => self::_('Ciudad Real') ),
                                    'CU' => array( 'name' => self::_('Cuenca') ),
                                    'GU' => array( 'name' => self::_('Guadalajara') ),
                                    'TO' => array( 'name' => self::_('Toledo') )
                                ),
                            ),
                            'CL' => array(
                                'name'       => self::_('Castilla y León'),
                                'subregions' => array(
                                    'AV' => array( 'name' => self::_('Ávila') ),
                                    'BU' => array( 'name' => self::_('Burgos') ),
                                    'LE' => array( 'name' => self::_('León') ),
                                    'P'  => array( 'name' => self::_('Palencia') ),
                                    'SA' => array( 'name' => self::_('Salamanca') ),
                                    'SG' => array( 'name' => self::_('Segovia') ),
                                    'SO' => array( 'name' => self::_('Soria') ),
                                    'VA' => array( 'name' => self::_('Valladolid') ),
                                    'ZA' => array( 'name' => self::_('Zamora') )
                                )
                            ),
                            'CT' => array(
                                'name'       => self::_('Catalunya'),
                                'subregions' => array(
                                    'B'  => array( 'name' => self::_('Barcelona') ),
                                    'GI' => array( 'name' => self::_('Girona') ),
                                    'L'  => array( 'name' => self::_('Lleida') ),
                                    'T'  => array( 'name' => self::_('Tarragona') )
                                )
                            ),
                            'EX' => array(
                                'name'       => self::_('Extremadura'),
                                'subregions' => array(
                                    'BA' => array( 'name' => self::_('Badajoz') ),
                                    'CC' => array( 'name' => self::_('Cáceres') )
                                )
                            ),
                            'GA' => array(
                                'name'       => self::_('Galicia'),
                                'subregions' => array(
                                    'C'  => array( 'name' => self::_('A Coruña') ),
                                    'LU' => array( 'name' => self::_('Lugo') ),
                                    'OR' => array( 'name' => self::_('Ourense') ),
                                    'PO' => array( 'name' => self::_('Pontevedra') )
                                )
                            ),
                            'PM' => array( 'name' => self::_('Illes Balears') ),
                            'RI' => array( 'name' => self::_('La Rioja') ),
                            'MD' => array( 'name' => self::_('Madrid, Comunidad de') ),
                            'MC' => array( 'name' => self::_('Murcia, Región de') ),
                            'NC' => array( 'name' => self::_('Navarra, Comunidad Foral de / Nafarroako Foru Komunitatea') ),
                            'PV' => array(
                                'name'       => self::_('País Vasco / Euskal Herria'),
                                'subregions' => array(
                                    'VI' => array( 'name' => self::_('Álava') ),
                                    'SS' => array( 'name' => self::_('Guipúzcoa / Gipuzkoa') ),
                                    'BI' => array( 'name' => self::_('Vizcayaa / Bizkaia') )
                                )
                            ),
                            'VC' => array(
                                'name'       => self::_('Valenciana, Comunidad / Valenciana, Comunitat '),
                                'subregions' => array(
                                    'A'  => array( 'name' => self::_('Alicante') ),
                                    'CS' => array( 'name' => self::_('Castellón') ),
                                    'V'  => array( 'name' => self::_('Valencia / València') )
                                )
                            ),
                            'IB' => array( 'name' => self::_('Balears') ),
                            'CE' => array( 'name' => self::_('Ceuta') ),
                            'ML' => array( 'name' => self::_('Melilla') )
                        ) );
                    break;
                case 'ET':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            'AA' => array( 'name' => self::_('Ādīs Ābeba') ),
                            'DD' => array( 'name' => self::_('Dirē Dawa') ),
                            'AF' => array( 'name' => self::_('Āfar') ),
                            'AM' => array( 'name' => self::_('Āmara') ),
                            'BE' => array( 'name' => self::_('Bīnshangul Gumuz') ),
                            'GA' => array( 'name' => self::_('Gambēla Hizboch') ),
                            'HA' => array( 'name' => self::_('Hārerī Hizb') ),
                            'OR' => array( 'name' => self::_('Oromīya') ),
                            'SO' => array( 'name' => self::_('Sumalē') ),
                            'TI' => array( 'name' => self::_('Tigray') ),
                            'SN' => array( 'name' => self::_('YeDebub Bihēroch Bihēreseboch na Hizboch') )
                        ) );
                    break;
                case 'FI':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'AL' => array( 'name' => self::_('Ahvenanmaan lääni') ),
                            'ES' => array( 'name' => self::_('Etelä-Suomen lääni') ),
                            'IS' => array( 'name' => self::_('Itä-Suomen lääni') ),
                            'LL' => array( 'name' => self::_('Lapin lääni') ),
                            'LS' => array( 'name' => self::_('Länsi-Suomen lääni') ),
                            'OL' => array( 'name' => self::_('Oulun lääni') )
                        ) );
                    break;
                case 'FJ':
                    return array(
                        'regions_label' => self::_('Division'),
                        'regions'       => array(
                            'C' => array( 'name' => self::_('Central') ),
                            'E' => array( 'name' => self::_('Eastern') ),
                            'N' => array( 'name' => self::_('Northern') ),
                            'W' => array( 'name' => self::_('Western') ),
                            'R' => array( 'name' => self::_('Rotuma') )
                        ) );
                    break;
                case 'FM':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            'TRK' => array( 'name' => self::_('Chuuk') ),
                            'KSA' => array( 'name' => self::_('Kosrae') ),
                            'PNI' => array( 'name' => self::_('Pohnpei') ),
                            'YAP' => array( 'name' => self::_('Yap') )
                        ) );
                    break;
                case 'FR':
                    return array(
                        'regions_label'    => self::_('Region'),
                        'subregions_label' => self::_('Department'),
                        'regions'          => array(
                            'A'  => array(
                                'name'       => self::_('Alsace'),
                                'subregions' => array(
                                    '67' => array( 'name' => self::_('Bas-Rhin') ),
                                    '68' => array( 'name' => self::_('Haut-Rhin') )
                                )
                            ),
                            'B'  => array(
                                'name'       => self::_('Aquitaine'),
                                'subregions' => array(
                                    '24' => array( 'name' => self::_('Dordogne') ),
                                    '33' => array( 'name' => self::_('Gironde') ),
                                    '40' => array( 'name' => self::_('Landes') ),
                                    '47' => array( 'name' => self::_('Lot-et-Garonne') ),
                                    '64' => array( 'name' => self::_('Pyrénées-Atlantiques') )
                                )
                            ),
                            'C'  => array(
                                'name'       => self::_('Auvergne'),
                                'subregions' => array(
                                    '03' => array( 'name' => self::_('Allier') ),
                                    '15' => array( 'name' => self::_('Cantal') ),
                                    '43' => array( 'name' => self::_('Haute-Loire') ),
                                    '63' => array( 'name' => self::_('Puy-de-Dôme') )
                                )
                            ),
                            'P'  => array(
                                'name'       => self::_('Basse-Normandie'),
                                'subregions' => array(
                                    '14' => array( 'name' => self::_('Calvados') ),
                                    '50' => array( 'name' => self::_('Manche') ),
                                    '61' => array( 'name' => self::_('Orne') )
                                )
                            ),
                            'D'  => array(
                                'name'       => self::_('Bourgogne'),
                                'subregions' => array(
                                    '21' => array( 'name' => self::_('Côte-d\'Or') ),
                                    '58' => array( 'name' => self::_('Nièvre') ),
                                    '71' => array( 'name' => self::_('Saône-et-Loire') ),
                                    '89' => array( 'name' => self::_('Yonne') )
                                )
                            ),
                            'E'  => array(
                                'name'       => self::_('Bretagne'),
                                'subregions' => array(
                                    '22' => array( 'name' => self::_('Côtes-d\'Armor') ),
                                    '29' => array( 'name' => self::_('Finistère') ),
                                    '35' => array( 'name' => self::_('Ille-et-Vilaine') ),
                                    '56' => array( 'name' => self::_('Morbihan') )
                                )
                            ),
                            'F'  => array(
                                'name'       => self::_('Centre'),
                                'subregions' => array(
                                    '18' => array( 'name' => self::_('Cher') ),
                                    '28' => array( 'name' => self::_('Eure-et-Loir') ),
                                    '36' => array( 'name' => self::_('Indre') ),
                                    '37' => array( 'name' => self::_('Indre-et-Loire') ),
                                    '41' => array( 'name' => self::_('Loir-et-Cher') ),
                                    '45' => array( 'name' => self::_('Loiret') )
                                )
                            ),
                            'G'  => array(
                                'name'       => self::_('Champagne-Ardenne'),
                                'subregions' => array(
                                    '08' => array( 'name' => self::_('Ardennes') ),
                                    '10' => array( 'name' => self::_('Aube') ),
                                    '52' => array( 'name' => self::_('Haute-Marne') ),
                                    '51' => array( 'name' => self::_('Marne') )
                                )
                            ),
                            'H'  => array(
                                'name'       => self::_('Corse'),
                                'subregions' => array(
                                    '2A' => array( 'name' => self::_('Corse-du-Sud') ),
                                    '2B' => array( 'name' => self::_('Haute-Corse') )
                                )
                            ),
                            'I'  => array(
                                'name'       => self::_('Franche-Comté'),
                                'subregions' => array(
                                    '25' => array( 'name' => self::_('Doubs') ),
                                    '70' => array( 'name' => self::_('Haute-Saône') ),
                                    '39' => array( 'name' => self::_('Jura') ),
                                    '90' => array( 'name' => self::_('Territoire de Belfort') )
                                ) ),
                            'Q'  => array(
                                'name'       => self::_('Haute-Normandie'),
                                'subregions' => array(
                                    '27' => array( 'name' => self::_('Eure') ),
                                    '76' => array( 'name' => self::_('Seine-Maritime') )
                                )
                            ),
                            'J'  => array(
                                'name'       => self::_('Île-de-France'),
                                'subregions' => array(
                                    '91' => array( 'name' => self::_('Essonne') ),
                                    '92' => array( 'name' => self::_('Hauts-de-Seine') ),
                                    '75' => array( 'name' => self::_('Paris') ),
                                    '77' => array( 'name' => self::_('Seine-et-Marne') ),
                                    '93' => array( 'name' => self::_('Seine-Saint-Denis') ),
                                    '94' => array( 'name' => self::_('Val-de-Marne') ),
                                    '95' => array( 'name' => self::_('Val d\'Oise') ),
                                    '78' => array( 'name' => self::_('Yvelines') )
                                )
                            ),
                            'K'  => array(
                                'name'       => self::_('Languedoc-Roussillon'),
                                'subregions' => array(
                                    '11' => array( 'name' => self::_('Aude') ),
                                    '30' => array( 'name' => self::_('Gard') ),
                                    '34' => array( 'name' => self::_('Hérault') ),
                                    '48' => array( 'name' => self::_('Lozère') ),
                                    '66' => array( 'name' => self::_('Pyrénées-Orientales') )
                                )
                            ),
                            'L'  => array(
                                'name'       => self::_('Limousin'),
                                'subregions' => array(
                                    '19' => array( 'name' => self::_('Corrèze') ),
                                    '23' => array( 'name' => self::_('Creuse') ),
                                    '87' => array( 'name' => self::_('Haute-Vienne') )
                                )
                            ),
                            'M'  => array(
                                'name'       => self::_('Lorraine'),
                                'subregions' => array(
                                    '54' => array( 'name' => self::_('Meurthe-et-Moselle') ),
                                    '55' => array( 'name' => self::_('Meuse') ),
                                    '57' => array( 'name' => self::_('Moselle') ),
                                    '88' => array( 'name' => self::_('Vosges') )
                                )
                            ),
                            'N'  => array(
                                'name'       => self::_('Midi-Pyrénées'),
                                'subregions' => array(
                                    '09' => array( 'name' => self::_('Ariège') ),
                                    '12' => array( 'name' => self::_('Aveyron') ),
                                    '32' => array( 'name' => self::_('Gers') ),
                                    '31' => array( 'name' => self::_('Haute-Garonne') ),
                                    '65' => array( 'name' => self::_('Hautes-Pyrénées') ),
                                    '46' => array( 'name' => self::_('Lot') ),
                                    '81' => array( 'name' => self::_('Tarn') ),
                                    '82' => array( 'name' => self::_('Tarn-et-Garonne') )
                                )
                            ),
                            'O'  => array(
                                'name'       => self::_('Nord - Pas-de-Calais'),
                                'subregions' => array(
                                    '59' => array( 'name' => self::_('Nord') ),
                                    '62' => array( 'name' => self::_('Pas-de-Calais') )
                                )
                            ),
                            'R'  => array(
                                'name'       => self::_('Pays de la Loire'),
                                'subregions' => array(
                                    '44' => array( 'name' => self::_('Loire-Atlantique') ),
                                    '49' => array( 'name' => self::_('Maine-et-Loire') ),
                                    '53' => array( 'name' => self::_('Mayenne') ),
                                    '72' => array( 'name' => self::_('Sarthe') ),
                                    '85' => array( 'name' => self::_('Vendée') )
                                )
                            ),
                            'S'  => array(
                                'name'       => self::_('Picardie'),
                                'subregions' => array(
                                    '02' => array( 'name' => self::_('Aisne') ),
                                    '60' => array( 'name' => self::_('Oise') ),
                                    '80' => array( 'name' => self::_('Somme') )
                                )
                            ),
                            'T'  => array(
                                'name'       => self::_('Poitou-Charentes'),
                                'subregions' => array(
                                    '16' => array( 'name' => self::_('Charente') ),
                                    '17' => array( 'name' => self::_('Charente-Maritime') ),
                                    '79' => array( 'name' => self::_('Deux-Sèvres') ),
                                    '86' => array( 'name' => self::_('Vienne') )
                                )
                            ),
                            'U'  => array(
                                'name'       => self::_('Provence-Alpes-Côte d\'Azur'),
                                'subregions' => array(
                                    '04' => array( 'name' => self::_('Alpes-de-Haute-Provence') ),
                                    '06' => array( 'name' => self::_('Alpes-Maritimes') ),
                                    '13' => array( 'name' => self::_('Bouches-du-Rhône') ),
                                    '05' => array( 'name' => self::_('Hautes-Alpes') ),
                                    '83' => array( 'name' => self::_('Var') ),
                                    '84' => array( 'name' => self::_('Vaucluse') )
                                )
                            ),
                            'V'  => array(
                                'name'       => self::_('Rhône-Alpes'),
                                'subregions' => array(
                                    '01' => array( 'name' => self::_('Ain') ),
                                    '07' => array( 'name' => self::_('Ardèche') ),
                                    '26' => array( 'name' => self::_('Drôme') ),
                                    '74' => array( 'name' => self::_('Haute-Savoie') ),
                                    '38' => array( 'name' => self::_('Isère') ),
                                    '42' => array( 'name' => self::_('Loire') ),
                                    '69' => array( 'name' => self::_('Rhône') ),
                                    '73' => array( 'name' => self::_('Savoie') )
                                )
                            ),
                            'GP' => array( 'name' => self::_('Guadeloupe') ),
                            'GF' => array( 'name' => self::_('Guyane') ),
                            'MQ' => array( 'name' => self::_('Martinique') ),
                            'RE' => array( 'name' => self::_('Réunion') ),
                            'CP' => array( 'name' => self::_('Clipperton') ),
                            'YT' => array( 'name' => self::_('Mayotte') ),
                            'NC' => array( 'name' => self::_('Nouvelle-Calédonie') ),
                            'PF' => array( 'name' => self::_('Polynésie française') ),
                            'BL' => array( 'name' => self::_('Saint-Barthélemy') ),
                            'MF' => array( 'name' => self::_('Saint-Martin') ),
                            'PM' => array( 'name' => self::_('Saint-Pierre-et-Miquelon') ),
                            'TF' => array( 'name' => self::_('Terres australes françaises') ),
                            'WF' => array( 'name' => self::_('Wallis-et-Futuna') )
                        ) );
                    break;
                case 'GA':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            1 => array( 'name' => self::_('Estuaire') ),
                            2 => array( 'name' => self::_('Haut-Ogooué') ),
                            3 => array( 'name' => self::_('Moyen-Ogooué') ),
                            4 => array( 'name' => self::_('Ngounié') ),
                            5 => array( 'name' => self::_('Nyanga') ),
                            6 => array( 'name' => self::_('Ogooué-Ivindo') ),
                            7 => array( 'name' => self::_('Ogooué-Lolo') ),
                            8 => array( 'name' => self::_('Ogooué-Maritime') ),
                            9 => array( 'name' => self::_('Woleu-Ntem') )
                        ) );
                    break;
                case 'GB':
                    /**
                     * @todo Provinces as children of major regions: England, Scotland, NI, Wales, etc.
                     */
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            /*          'ENG' => array( 'name' => self::_( 'England' ) ),
                'SCT' => array( 'name' => self::_( 'Scotland' ) ),
                'NIR' => array( 'name' => self::_( 'Northern Ireland' ) ),
                'WLS' => array( 'name' => self::_( 'Wales' ) ),
                'EAW' => array( 'name' => self::_( 'England and Wales' ) ),
                'GBN' => array( 'name' => self::_( 'Great Britain' ) ),
                'UKM' => array( 'name' => self::_( 'United Kingdom' ) ), */
                            'BKM' => array( 'name' => self::_('Buckinghamshire') ),
                            'CAM' => array( 'name' => self::_('Cambridgeshire') ),
                            'CMA' => array( 'name' => self::_('Cumbria') ),
                            'DBY' => array( 'name' => self::_('Derbyshire') ),
                            'DEV' => array( 'name' => self::_('Devon') ),
                            'DOR' => array( 'name' => self::_('Dorset') ),
                            'ESX' => array( 'name' => self::_('East Sussex') ),
                            'ESS' => array( 'name' => self::_('Essex') ),
                            'GLS' => array( 'name' => self::_('Gloucestershire') ),
                            'HAM' => array( 'name' => self::_('Hampshire') ),
                            'HRT' => array( 'name' => self::_('Hertfordshire') ),
                            'KEN' => array( 'name' => self::_('Kent') ),
                            'LAN' => array( 'name' => self::_('Lancashire') ),
                            'LEC' => array( 'name' => self::_('Leicestershire') ),
                            'LIN' => array( 'name' => self::_('Lincolnshire') ),
                            'NFK' => array( 'name' => self::_('Norfolk') ),
                            'NYK' => array( 'name' => self::_('North Yorkshire') ),
                            'NTH' => array( 'name' => self::_('Northamptonshire') ),
                            'NTT' => array( 'name' => self::_('Nottinghamshire') ),
                            'OXF' => array( 'name' => self::_('Oxfordshire') ),
                            'SOM' => array( 'name' => self::_('Somerset') ),
                            'STS' => array( 'name' => self::_('Staffordshire') ),
                            'SFK' => array( 'name' => self::_('Suffolk') ),
                            'SRY' => array( 'name' => self::_('Surrey') ),
                            'WAR' => array( 'name' => self::_('Warwickshire') ),
                            'WSX' => array( 'name' => self::_('West Sussex') ),
                            'WOR' => array( 'name' => self::_('Worcestershire') ),
                            'BDG' => array( 'name' => self::_('Barking and Dagenham') ),
                            'BNE' => array( 'name' => self::_('Barnet') ),
                            'BEX' => array( 'name' => self::_('Bexley') ),
                            'BEN' => array( 'name' => self::_('Brent') ),
                            'BRY' => array( 'name' => self::_('Bromley') ),
                            'CMD' => array( 'name' => self::_('Camden') ),
                            'CRY' => array( 'name' => self::_('Croydon') ),
                            'EAL' => array( 'name' => self::_('Ealing') ),
                            'ENF' => array( 'name' => self::_('Enfield') ),
                            'GRE' => array( 'name' => self::_('Greenwich') ),
                            'HCK' => array( 'name' => self::_('Hackney') ),
                            'HMF' => array( 'name' => self::_('Hammersmith and Fulham') ),
                            'HRY' => array( 'name' => self::_('Haringey') ),
                            'HRW' => array( 'name' => self::_('Harrow') ),
                            'HAV' => array( 'name' => self::_('Havering') ),
                            'HIL' => array( 'name' => self::_('Hillingdon') ),
                            'HNS' => array( 'name' => self::_('Hounslow') ),
                            'ISL' => array( 'name' => self::_('Islington') ),
                            'KEC' => array( 'name' => self::_('Kensington and Chelsea') ),
                            'KTT' => array( 'name' => self::_('Kingston upon Thames') ),
                            'LBH' => array( 'name' => self::_('Lambeth') ),
                            'LEW' => array( 'name' => self::_('Lewisham') ),
                            'MRT' => array( 'name' => self::_('Merton') ),
                            'NWM' => array( 'name' => self::_('Newham') ),
                            'RDB' => array( 'name' => self::_('Redbridge') ),
                            'RIC' => array( 'name' => self::_('Richmond upon Thames') ),
                            'SWK' => array( 'name' => self::_('Southwark') ),
                            'STN' => array( 'name' => self::_('Sutton') ),
                            'TWH' => array( 'name' => self::_('Tower Hamlets') ),
                            'WFT' => array( 'name' => self::_('Waltham Forest') ),
                            'WND' => array( 'name' => self::_('Wandsworth') ),
                            'WSM' => array( 'name' => self::_('Westminster') ),
                            'BNS' => array( 'name' => self::_('Barnsley') ),
                            'BIR' => array( 'name' => self::_('Birmingham') ),
                            'BOL' => array( 'name' => self::_('Bolton') ),
                            'BRD' => array( 'name' => self::_('Bradford') ),
                            'BUR' => array( 'name' => self::_('Bury') ),
                            'CLD' => array( 'name' => self::_('Calderdale') ),
                            'COV' => array( 'name' => self::_('Coventry') ),
                            'DNC' => array( 'name' => self::_('Doncaster') ),
                            'DUD' => array( 'name' => self::_('Dudley') ),
                            'GAT' => array( 'name' => self::_('Gateshead') ),
                            'KIR' => array( 'name' => self::_('Kirklees') ),
                            'KWL' => array( 'name' => self::_('Knowsley') ),
                            'LDS' => array( 'name' => self::_('Leeds') ),
                            'LIV' => array( 'name' => self::_('Liverpool') ),
                            'MAN' => array( 'name' => self::_('Manchester') ),
                            'NET' => array( 'name' => self::_('Newcastle upon Tyne') ),
                            'NTY' => array( 'name' => self::_('North Tyneside') ),
                            'OLD' => array( 'name' => self::_('Oldham') ),
                            'RCH' => array( 'name' => self::_('Rochdale') ),
                            'ROT' => array( 'name' => self::_('Rotherham') ),
                            'SHN' => array( 'name' => self::_('St. Helens') ),
                            'SLF' => array( 'name' => self::_('Salford') ),
                            'SAW' => array( 'name' => self::_('Sandwell') ),
                            'SFT' => array( 'name' => self::_('Sefton') ),
                            'SHF' => array( 'name' => self::_('Sheffield') ),
                            'SOL' => array( 'name' => self::_('Solihull') ),
                            'STY' => array( 'name' => self::_('South Tyneside') ),
                            'SKP' => array( 'name' => self::_('Stockport') ),
                            'SND' => array( 'name' => self::_('Sunderland') ),
                            'TAM' => array( 'name' => self::_('Tameside') ),
                            'TRF' => array( 'name' => self::_('Trafford') ),
                            'WKF' => array( 'name' => self::_('Wakefield') ),
                            'WLL' => array( 'name' => self::_('Walsall') ),
                            'WGN' => array( 'name' => self::_('Wigan') ),
                            'WRL' => array( 'name' => self::_('Wirral') ),
                            'WLV' => array( 'name' => self::_('Wolverhampton') ),
                            'LND' => array( 'name' => self::_('London, City of') ),
                            'ABE' => array( 'name' => self::_('Aberdeen City') ),
                            'ABD' => array( 'name' => self::_('Aberdeenshire') ),
                            'ANS' => array( 'name' => self::_('Angus') ),
                            'AGB' => array( 'name' => self::_('Argyll and Bute') ),
                            'CLK' => array( 'name' => self::_('Clackmannanshire') ),
                            'DGY' => array( 'name' => self::_('Dumfries and Galloway') ),
                            'DND' => array( 'name' => self::_('Dundee City') ),
                            'EAY' => array( 'name' => self::_('East Ayrshire') ),
                            'EDU' => array( 'name' => self::_('East Dunbartonshire') ),
                            'ELN' => array( 'name' => self::_('East Lothian') ),
                            'ERW' => array( 'name' => self::_('East Renfrewshire') ),
                            'EDH' => array( 'name' => self::_('Edinburgh, City of') ),
                            'ELS' => array( 'name' => self::_('Eilean Siar') ),
                            'FAL' => array( 'name' => self::_('Falkirk') ),
                            'FIF' => array( 'name' => self::_('Fife') ),
                            'GLG' => array( 'name' => self::_('Glasgow City') ),
                            'HED' => array( 'name' => self::_('Highland') ),
                            'IVC' => array( 'name' => self::_('Inverclyde') ),
                            'MLN' => array( 'name' => self::_('Midlothian') ),
                            'MRY' => array( 'name' => self::_('Moray') ),
                            'NAY' => array( 'name' => self::_('North Ayrshire') ),
                            'NLK' => array( 'name' => self::_('North Lanarkshire') ),
                            'ORR' => array( 'name' => self::_('Orkney Islands') ),
                            'PKN' => array( 'name' => self::_('Perth and Kinross') ),
                            'RFW' => array( 'name' => self::_('Renfrewshire') ),
                            'SCB' => array( 'name' => self::_('Scottish Borders, The') ),
                            'ZET' => array( 'name' => self::_('Shetland Islands') ),
                            'SAY' => array( 'name' => self::_('South Ayrshire') ),
                            'SLK' => array( 'name' => self::_('South Lanarkshire') ),
                            'STG' => array( 'name' => self::_('Stirling') ),
                            'WDU' => array( 'name' => self::_('West Dunbartonshire') ),
                            'WLN' => array( 'name' => self::_('West Lothian') ),
                            'ANT' => array( 'name' => self::_('Antrim') ),
                            'ARD' => array( 'name' => self::_('Ards') ),
                            'ARM' => array( 'name' => self::_('Armagh') ),
                            'BLA' => array( 'name' => self::_('Ballymena') ),
                            'BLY' => array( 'name' => self::_('Ballymoney') ),
                            'BNB' => array( 'name' => self::_('Banbridge') ),
                            'BFS' => array( 'name' => self::_('Belfast') ),
                            'CKF' => array( 'name' => self::_('Carrickfergus') ),
                            'CSR' => array( 'name' => self::_('Castlereagh') ),
                            'CLR' => array( 'name' => self::_('Coleraine') ),
                            'CKT' => array( 'name' => self::_('Cookstown') ),
                            'CGV' => array( 'name' => self::_('Craigavon') ),
                            'DRY' => array( 'name' => self::_('Derry') ),
                            'DOW' => array( 'name' => self::_('Down') ),
                            'DGN' => array( 'name' => self::_('Dungannon') ),
                            'FER' => array( 'name' => self::_('Fermanagh') ),
                            'LRN' => array( 'name' => self::_('Larne') ),
                            'LMV' => array( 'name' => self::_('Limavady') ),
                            'LSB' => array( 'name' => self::_('Lisburn') ),
                            'MFT' => array( 'name' => self::_('Magherafelt') ),
                            'MYL' => array( 'name' => self::_('Moyle') ),
                            'NYM' => array( 'name' => self::_('Newry and Mourne') ),
                            'NTA' => array( 'name' => self::_('Newtownabbey') ),
                            'NDN' => array( 'name' => self::_('North Down') ),
                            'OMH' => array( 'name' => self::_('Omagh') ),
                            'STB' => array( 'name' => self::_('Strabane') ),
                            'BAS' => array( 'name' => self::_('Bath and North East Somerset') ),
                            'BBD' => array( 'name' => self::_('Blackburn with Darwen') ),
                            'BDF' => array( 'name' => self::_('Bedford') ),
                            'BPL' => array( 'name' => self::_('Blackpool') ),
                            'BMH' => array( 'name' => self::_('Bournemouth') ),
                            'BRC' => array( 'name' => self::_('Bracknell Forest') ),
                            'BNH' => array( 'name' => self::_('Brighton and Hove') ),
                            'BST' => array( 'name' => self::_('Bristol, City of') ),
                            'CBF' => array( 'name' => self::_('Central Bedfordshire') ),
                            'CHE' => array( 'name' => self::_('Cheshire East') ),
                            'CHW' => array( 'name' => self::_('Cheshire West and Chester') ),
                            'CON' => array( 'name' => self::_('Cornwall') ),
                            'DAL' => array( 'name' => self::_('Darlington') ),
                            'DER' => array( 'name' => self::_('Derby') ),
                            'DUR' => array( 'name' => self::_('Durham') ),
                            'ERY' => array( 'name' => self::_('East Riding of Yorkshire') ),
                            'HAL' => array( 'name' => self::_('Halton') ),
                            'HPL' => array( 'name' => self::_('Hartlepool') ),
                            'HEF' => array( 'name' => self::_('Herefordshire') ),
                            'IOW' => array( 'name' => self::_('Isle of Wight') ),
                            'KHL' => array( 'name' => self::_('Kingston upon Hull') ),
                            'LCE' => array( 'name' => self::_('Leicester') ),
                            'LUT' => array( 'name' => self::_('Luton') ),
                            'MDW' => array( 'name' => self::_('Medway') ),
                            'MDB' => array( 'name' => self::_('Middlesbrough') ),
                            'MIK' => array( 'name' => self::_('Milton Keynes') ),
                            'NEL' => array( 'name' => self::_('North East Lincolnshire') ),
                            'NLN' => array( 'name' => self::_('North Lincolnshire') ),
                            'NSM' => array( 'name' => self::_('North Somerset') ),
                            'NBL' => array( 'name' => self::_('Northumberland') ),
                            'NGM' => array( 'name' => self::_('Nottingham') ),
                            'PTE' => array( 'name' => self::_('Peterborough') ),
                            'PLY' => array( 'name' => self::_('Plymouth') ),
                            'POL' => array( 'name' => self::_('Poole') ),
                            'POR' => array( 'name' => self::_('Portsmouth') ),
                            'RDG' => array( 'name' => self::_('Reading') ),
                            'RCC' => array( 'name' => self::_('Redcar and Cleveland') ),
                            'RUT' => array( 'name' => self::_('Rutland') ),
                            'SHR' => array( 'name' => self::_('Shropshire') ),
                            'SLG' => array( 'name' => self::_('Slough') ),
                            'SGC' => array( 'name' => self::_('South Gloucestershire') ),
                            'STH' => array( 'name' => self::_('Southampton') ),
                            'SOS' => array( 'name' => self::_('Southend-on-Sea') ),
                            'STT' => array( 'name' => self::_('Stockton-on-Tees') ),
                            'STE' => array( 'name' => self::_('Stoke-on-Trent') ),
                            'SWD' => array( 'name' => self::_('Swindon') ),
                            'TFW' => array( 'name' => self::_('Telford and Wrekin') ),
                            'THR' => array( 'name' => self::_('Thurrock') ),
                            'TOB' => array( 'name' => self::_('Torbay') ),
                            'WRT' => array( 'name' => self::_('Warrington') ),
                            'WBX' => array( 'name' => self::_('West Berkshire') ),
                            'WNM' => array( 'name' => self::_('Windsor and Maidenhead') ),
                            'WOK' => array( 'name' => self::_('Wokingham') ),
                            'YOR' => array( 'name' => self::_('York') ),
                            'BGW' => array( 'name' => self::_('Blaenau Gwent') ),
                            'BGE' => array( 'name' => self::_('Bridgend;Pen-y-bont ar Ogwr') ),
                            'CAY' => array( 'name' => self::_('Caerphilly;Caerffili') ),
                            'CRF' => array( 'name' => self::_('Cardiff;Caerdydd') ),
                            'CMN' => array( 'name' => self::_('Carmarthenshire;Sir Gaerfyrddin') ),
                            'CGN' => array( 'name' => self::_('Ceredigion;Sir Ceredigion') ),
                            'CWY' => array( 'name' => self::_('Conwy') ),
                            'DEN' => array( 'name' => self::_('Denbighshire;Sir Ddinbych') ),
                            'FLN' => array( 'name' => self::_('Flintshire;Sir y Fflint') ),
                            'GWN' => array( 'name' => self::_('Gwynedd') ),
                            'AGY' => array( 'name' => self::_('Isle of Anglesey;Sir Ynys Môn') ),
                            'MTY' => array( 'name' => self::_('Merthyr Tydfil;Merthyr Tudful') ),
                            'MON' => array( 'name' => self::_('Monmouthshire;Sir Fynwy') ),
                            'NTL' => array( 'name' => self::_('Neath Port Talbot;Castell-nedd Port Talbot') ),
                            'NWP' => array( 'name' => self::_('Newport;Casnewydd') ),
                            'PEM' => array( 'name' => self::_('Pembrokeshire;Sir Benfro') ),
                            'POW' => array( 'name' => self::_('Powys') ),
                            'RCT' => array( 'name' => self::_('Rhondda, Cynon, Taff;Rhondda, Cynon,Taf') ),
                            'SWA' => array( 'name' => self::_('Swansea;Abertawe') ),
                            'TOF' => array( 'name' => self::_('Torfaen;Tor-faen') ),
                            'VGL' => array( 'name' => self::_('Vale of Glamorgan, The;Bro Morgannwg') ),
                            'WRX' => array( 'name' => self::_('Wrexham;Wrecsam') )
                        ) );
                    break;
                case 'GD':
                    return array(
                        'regions_label' => self::_('Parish'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Saint Andrew') ),
                            '02' => array( 'name' => self::_('Saint David') ),
                            '03' => array( 'name' => self::_('Saint George') ),
                            '04' => array( 'name' => self::_('Saint John') ),
                            '05' => array( 'name' => self::_('Saint Mark') ),
                            '06' => array( 'name' => self::_('Saint Patrick') ),
                            '10' => array( 'name' => self::_('Southern Grenadine Islands') )
                        ) );
                    break;
                case 'GE':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'AB' => array( 'name' => self::_('Abkhazia') ),
                            'AJ' => array( 'name' => self::_('Ajaria') ),
                            'TB' => array( 'name' => self::_('T\'bilisi') ),
                            'GU' => array( 'name' => self::_('Guria') ),
                            'IM' => array( 'name' => self::_('Imeret\'i') ),
                            'KA' => array( 'name' => self::_('Kakhet\'i') ),
                            'KK' => array( 'name' => self::_('K\'vemo K\'art\'li') ),
                            'MM' => array( 'name' => self::_('Mts\'khet\'a-Mt\'ianet\'i') ),
                            'RL' => array( 'name' => self::_('Racha-Lech\'khumi-K\'vemo Svanet\'i') ),
                            'SZ' => array( 'name' => self::_('Samegrelo-Zemo Svanet\'i') ),
                            'SJ' => array( 'name' => self::_('Samts\'khe-Javakhet\'i') ),
                            'SK' => array( 'name' => self::_('Shida K\'art\'li') )
                        ) );
                    break;
                case 'GH':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'AH' => array( 'name' => self::_('Ashanti') ),
                            'BA' => array( 'name' => self::_('Brong-Ahafo') ),
                            'CP' => array( 'name' => self::_('Central') ),
                            'EP' => array( 'name' => self::_('Eastern') ),
                            'AA' => array( 'name' => self::_('Greater Accra') ),
                            'NP' => array( 'name' => self::_('Northern') ),
                            'UE' => array( 'name' => self::_('Upper East') ),
                            'UW' => array( 'name' => self::_('Upper West') ),
                            'TV' => array( 'name' => self::_('Volta') ),
                            'WP' => array( 'name' => self::_('Western') ),
                            'KU' => array( 'name' => self::_('Kommune Kujalleq') ),
                            'SM' => array( 'name' => self::_('Kommuneqarfik Sermersooq') ),
                            'QA' => array( 'name' => self::_('Qaasuitsup Kommunia') ),
                            'QE' => array( 'name' => self::_('Qeqqata Kommunia') )
                        ) );
                    break;
                case 'GM':
                    return array(
                        'regions_label'    => self::_('Division'),
                        'subregions_label' => self::_('City'),
                        'regions'          => array(
                            'L' => array( 'name' => self::_('Lower River') ),
                            'M' => array( 'name' => self::_('Central River') ),
                            'N' => array( 'name' => self::_('North Bank') ),
                            'U' => array( 'name' => self::_('Upper River') ),
                            'W' => array( 'name' => self::_('Western') ),
                            'B' => array( 'name' => self::_('Banjul') )
                        ) );
                    break;
                case 'GN':
                    return array(
                        'regions_label'    => self::_('Governorate'),
                        'subregions_label' => self::_('Prefecture'),
                        'regions'          => array(
                            'B' => array(
                                'name'       => self::_('Boké'),
                                'subregions' => array(
                                    'BF' => array( 'name' => self::_('Boffa') ),
                                    'BK' => array( 'name' => self::_('Boké') ),
                                    'FR' => array( 'name' => self::_('Fria') ),
                                    'GA' => array( 'name' => self::_('Gaoual') ),
                                    'KN' => array( 'name' => self::_('Koundara') )
                                )
                            ),
                            'F' => array(
                                'name'       => self::_('Faranah'),
                                'subregions' => array(
                                    'DB' => array( 'name' => self::_('Dabola') ),
                                    'DI' => array( 'name' => self::_('Dinguiraye') ),
                                    'FA' => array( 'name' => self::_('Faranah') ),
                                    'KS' => array( 'name' => self::_('Kissidougou') )
                                )
                            ),
                            'K' => array(
                                'name'       => self::_('Kankan'),
                                'subregions' => array(
                                    'KA' => array( 'name' => self::_('Kankan') ),
                                    'KE' => array( 'name' => self::_('Kérouané') ),
                                    'KO' => array( 'name' => self::_('Kouroussa') ),
                                    'MD' => array( 'name' => self::_('Mandiana') ),
                                    'SI' => array( 'name' => self::_('Siguiri') )
                                )
                            ),
                            'D' => array(
                                'name'       => self::_('Kindia'),
                                'subregions' => array(
                                    'CO' => array( 'name' => self::_('Coyah') ),
                                    'DU' => array( 'name' => self::_('Dubréka') ),
                                    'FO' => array( 'name' => self::_('Forécariah') ),
                                    'KD' => array( 'name' => self::_('Kindia') ),
                                    'TE' => array( 'name' => self::_('Télimélé') )
                                )
                            ),
                            'L' => array(
                                'name'       => self::_('Labé'),
                                'subregions' => array(
                                    'KB' => array( 'name' => self::_('Koubia') ),
                                    'LA' => array( 'name' => self::_('Labé') ),
                                    'LE' => array( 'name' => 'Lélouma' ) ),
                                'ML'         => array( 'name' => self::_('Mali') ),
                                'TO'         => array( 'name' => self::_('Tougué') )
                            )
                        ),
                        'M'                => array(
                            'name'       => self::_('Mamou'),
                            'subregions' => array(
                                'DL' => array( 'name' => self::_('Dalaba') ),
                                'MM' => array( 'name' => self::_('Mamou') ),
                                'PI' => array( 'name' => self::_('Pita') )
                            )
                        ),
                        'N'                => array(
                            'name'       => self::_('Nzérékoré'),
                            'subregions' => array(
                                'BE' => array( 'name' => self::_('Beyla') ),
                                'GU' => array( 'name' => self::_('Guékédou') ),
                                'LO' => array( 'name' => self::_('Lola') ),
                                'MC' => array( 'name' => self::_('Macenta') ),
                                'NZ' => array( 'name' => self::_('Nzérékoré') ),
                                'YO' => array( 'name' => self::_('Yomou') )
                            )
                        ),
                        'C'                => array( 'name' => self::_('Conakry') )
                    );
                    break;
                case 'GQ':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'C'  => array( 'name' => self::_('Región Continental') ),
                            'I'  => array( 'name' => self::_('Región Insular') ),
                            'AN' => array( 'name' => self::_('Annobón') ),
                            'BN' => array( 'name' => self::_('Bioko Norte') ),
                            'BS' => array( 'name' => self::_('Bioko Sur') ),
                            'CS' => array( 'name' => self::_('Centro Sur') ),
                            'KN' => array( 'name' => self::_('Kié-Ntem') ),
                            'LI' => array( 'name' => self::_('Litoral') ),
                            'WN' => array( 'name' => self::_('Wele-Nzás') )
                        ) );
                    break;
                case 'GR':
                    return array(
                        'regions_label'    => self::_('Administrative region'),
                        'subregions_label' => ( 'Department' ),
                        'regions'          => array(
                            'A'  => array(
                                'name'       => self::_('Anatoliki Makedonia kai Thraki'),
                                'subregions' => array(
                                    '52' => array( 'name' => self::_('Drama') ),
                                    '71' => array( 'name' => self::_('Evros') ),
                                    '55' => array( 'name' => self::_('Kavala') ),
                                    '73' => array( 'name' => self::_('Rodopi') ),
                                    '72' => array( 'name' => self::_('Xanthi') )
                                )
                            ),
                            'I'  => array( 'name' => self::_('Attiki') ),
                            'G'  => array(
                                'name'       => self::_('Dytiki Ellada'),
                                'subregions' => array(
                                    '13' => array( 'name' => self::_('Achaïa') ),
                                    '01' => array( 'name' => self::_('Aitolia kai Akarnania') ),
                                    '14' => array( 'name' => self::_('Ileia') )
                                )
                            ),
                            'C'  => array(
                                'name'       => self::_('Dytiki Makedonia'),
                                'subregions' => array(
                                    '63' => array( 'name' => self::_('Florina') ),
                                    '51' => array( 'name' => self::_('Grevena') ),
                                    '56' => array( 'name' => self::_('Kastoria') ),
                                    '58' => array( 'name' => self::_('Kozani') )
                                )
                            ),
                            'F'  => array(
                                'name'       => self::_('Ionia Nisia'),
                                'subregions' => array(
                                    '31' => array( 'name' => self::_('Arta') ),
                                    '23' => array( 'name' => self::_('Kefallonia') ),
                                    '22' => array( 'name' => self::_('Kerkyra') ),
                                    '24' => array( 'name' => self::_('Lefkada') ),
                                    '21' => array( 'name' => self::_('Zakynthos') )
                                )
                            ),
                            'D'  => array(
                                'name'       => self::_('Ipeiros'),
                                'subregions' => array(
                                    '33' => array( 'name' => self::_('Ioannina') ),
                                    '34' => array( 'name' => self::_('Preveza') ),
                                    '32' => array( 'name' => self::_('Thesprotia') )
                                )
                            ),
                            'B'  => array(
                                'name'       => self::_('Kentriki Makedonia'),
                                'subregions' => array(
                                    '64' => array( 'name' => self::_('Chalkidiki') ),
                                    '53' => array( 'name' => self::_('Imathia') ),
                                    '57' => array( 'name' => self::_('Kilkis') ),
                                    '59' => array( 'name' => self::_('Pella') ),
                                    '61' => array( 'name' => self::_('Pieria') ),
                                    '62' => array( 'name' => self::_('Serres') ),
                                    '54' => array( 'name' => self::_('Thessaloniki') )
                                ) ),
                            'M'  => array(
                                'name'       => self::_('Kriti'),
                                'subregions' => array(
                                    '94' => array( 'name' => self::_('Chania') ),
                                    '91' => array( 'name' => self::_('Irakleio') ),
                                    '92' => array( 'name' => self::_('Lasithi') ),
                                    '93' => array( 'name' => self::_('Rethymno') )
                                ) ),

                            'L'  => array(
                                'name'       => self::_('Notio Aigaio'),
                                'subregions' => array(
                                    '81' => array( 'name' => self::_('Dodekanisos') ),
                                    '82' => array( 'name' => self::_('Kyklades') )
                                ) ),
                            'J'  => array(
                                'name'       => self::_('Peloponnisos'),
                                'subregions' => array(
                                    '11' => array( 'name' => self::_('Argolida') ),
                                    '12' => array( 'name' => self::_('Arkadia') ),
                                    '15' => array( 'name' => self::_('Korinthia') ),
                                    '16' => array( 'name' => self::_('Lakonia') ),
                                    '17' => array( 'name' => self::_('Messinia') )
                                ) ),
                            'H'  => array(
                                'name'       => self::_('Sterea Ellada'),
                                'subregions' => array(
                                    '05' => array( 'name' => self::_('Evrytania') ),
                                    '04' => array( 'name' => self::_('Evvoias') ),
                                    '07' => array( 'name' => self::_('Fokida') ),
                                    '06' => array( 'name' => self::_('Fthiotida') ),
                                    '03' => array( 'name' => self::_('Voiotia') )
                                ) ),
                            'E'  => array(
                                'name'       => self::_('Thessalia'),
                                'subregions' => array(
                                    '41' => array( 'name' => self::_('Karditsa') ),
                                    '42' => array( 'name' => self::_('Larisa') ),
                                    '43' => array( 'name' => self::_('Magnisia') ),
                                    '44' => array( 'name' => self::_('Trikala') )
                                ) ),
                            'K'  => array(
                                'name'       => self::_('Voreio Aigaio'),
                                'subregions' => array(
                                    '85' => array( 'name' => self::_('Chios') ),
                                    '83' => array( 'name' => self::_('Lesvos') ),
                                    '84' => array( 'name' => self::_('Samos') )
                                ) ),
                            '69' => array( 'name' => self::_('Agio Oros') )
                        ) );
                    break;
                case 'GT':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'AV' => array( 'name' => self::_('Alta Verapaz') ),
                            'BV' => array( 'name' => self::_('Baja Verapaz') ),
                            'CM' => array( 'name' => self::_('Chimaltenango') ),
                            'CQ' => array( 'name' => self::_('Chiquimula') ),
                            'PR' => array( 'name' => self::_('El Progreso') ),
                            'ES' => array( 'name' => self::_('Escuintla') ),
                            'GU' => array( 'name' => self::_('Guatemala') ),
                            'HU' => array( 'name' => self::_('Huehuetenango') ),
                            'IZ' => array( 'name' => self::_('Izabal') ),
                            'JA' => array( 'name' => self::_('Jalapa') ),
                            'JU' => array( 'name' => self::_('Jutiapa') ),
                            'PE' => array( 'name' => self::_('Petén') ),
                            'QZ' => array( 'name' => self::_('Quetzaltenango') ),
                            'QC' => array( 'name' => self::_('Quiché') ),
                            'RE' => array( 'name' => self::_('Retalhuleu') ),
                            'SA' => array( 'name' => self::_('Sacatepéquez') ),
                            'SM' => array( 'name' => self::_('San Marcos') ),
                            'SR' => array( 'name' => self::_('Santa Rosa') ),
                            'SO' => array( 'name' => self::_('Sololá') ),
                            'SU' => array( 'name' => self::_('Suchitepéquez') ),
                            'TO' => array( 'name' => self::_('Totonicapán') ),
                            'ZA' => array( 'name' => self::_('Zacapa') )
                        ) );
                    break;
                case 'GW':
                    return array(
                        'regions_label'    => self::_('Province'),
                        'subregions_label' => self::_('Region'),
                        'regions'          => array(
                            'BS' => array( 'name' => self::_('Bissau') ),
                            'L'  => array(
                                'name'       => self::_('Leste'),
                                'subregions' => array(
                                    'BA' => array( 'name' => self::_('Bafatá') ),
                                    'GA' => array( 'name' => self::_('Gabú') )
                                ) ),
                            'N'  => array(
                                'name'       => self::_('Norte'),
                                'subregions' => array(
                                    'BM' => array( 'name' => self::_('Biombo') ),
                                    'CA' => array( 'name' => self::_('Cacheu') ),
                                    'OI' => array( 'name' => self::_('Oio') )
                                ) ),
                            'S'  => array(
                                'name'       => self::_('Sul'),
                                'subregions' => array(
                                    'BL' => array( 'name' => self::_('Bolama') ),
                                    'QU' => array( 'name' => self::_('Quinara') ),
                                    'TO' => array( 'name' => self::_('Tombali') )
                                ) )
                        ) );
                    break;
                case 'GY':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'BA' => array( 'name' => self::_('Barima-Waini') ),
                            'CU' => array( 'name' => self::_('Cuyuni-Mazaruni') ),
                            'DE' => array( 'name' => self::_('Demerara-Mahaica') ),
                            'EB' => array( 'name' => self::_('East Berbice-Corentyne') ),
                            'ES' => array( 'name' => self::_('Essequibo Islands-West Demerara') ),
                            'MA' => array( 'name' => self::_('Mahaica-Berbice') ),
                            'PM' => array( 'name' => self::_('Pomeroon-Supenaam') ),
                            'PT' => array( 'name' => self::_('Potaro-Siparuni') ),
                            'UD' => array( 'name' => self::_('Upper Demerara-Berbice') ),
                            'UT' => array( 'name' => self::_('Upper Takutu-Upper Essequibo') )
                        ) );
                    break;
                case 'HN':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'AT' => array( 'name' => self::_('Atlántida') ),
                            'CL' => array( 'name' => self::_('Colón') ),
                            'CM' => array( 'name' => self::_('Comayagua') ),
                            'CP' => array( 'name' => self::_('Copán') ),
                            'CR' => array( 'name' => self::_('Cortés') ),
                            'CH' => array( 'name' => self::_('Choluteca') ),
                            'EP' => array( 'name' => self::_('El Paraíso') ),
                            'FM' => array( 'name' => self::_('Francisco Morazán') ),
                            'GD' => array( 'name' => self::_('Gracias a Dios') ),
                            'IN' => array( 'name' => self::_('Intibucá') ),
                            'IB' => array( 'name' => self::_('Islas de la Bahía') ),
                            'LP' => array( 'name' => self::_('La Paz') ),
                            'LE' => array( 'name' => self::_('Lempira') ),
                            'OC' => array( 'name' => self::_('Ocotepeque') ),
                            'OL' => array( 'name' => self::_('Olancho') ),
                            'SB' => array( 'name' => self::_('Santa Bárbara') ),
                            'VA' => array( 'name' => self::_('Valle') ),
                            'YO' => array( 'name' => self::_('Yoro') )
                        ) );
                    break;
                case 'HR':
                    return array(
                        'regions_label' => self::_('County'),
                        'regions'       => array(
                            '21' => array( 'name' => self::_('Grad Zagreb') ),
                            '07' => array( 'name' => self::_('Bjelovarsko-bilogorska županija') ),
                            '12' => array( 'name' => self::_('Brodsko-posavska županija') ),
                            '19' => array( 'name' => self::_('Dubrovačko-neretvanska županija') ),
                            '18' => array( 'name' => self::_('Istarska županija') ),
                            '04' => array( 'name' => self::_('Karlovačka županija') ),
                            '06' => array( 'name' => self::_('Koprivničko-križevačka županija') ),
                            '02' => array( 'name' => self::_('Krapinsko-zagorska županija') ),
                            '09' => array( 'name' => self::_('Ličko-senjska županija') ),
                            '20' => array( 'name' => self::_('Međimurska županija') ),
                            '14' => array( 'name' => self::_('Osječko-baranjska županija') ),
                            '11' => array( 'name' => self::_('Požeško-slavonska županija') ),
                            '08' => array( 'name' => self::_('Primorsko-goranska županija') ),
                            '03' => array( 'name' => self::_('Sisačko-moslavačka županija') ),
                            '17' => array( 'name' => self::_('Splitsko-dalmatinska županija') ),
                            '15' => array( 'name' => self::_('Šibensko-kninska županija') ),
                            '05' => array( 'name' => self::_('Varaždinska županija') ),
                            '10' => array( 'name' => self::_('Virovitičko-podravska županija') ),
                            '16' => array( 'name' => self::_('Vukovarsko-srijemska županija') ),
                            '13' => array( 'name' => self::_('Zadarska županija') ),
                            '01' => array( 'name' => self::_('Zagrebačka županija') )
                        ) );
                    break;
                case 'HT':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'AR' => array( 'name' => self::_('Artibonite') ),
                            'CE' => array( 'name' => self::_('Centre') ),
                            'GA' => array( 'name' => self::_('Grande-Anse') ),
                            'ND' => array( 'name' => self::_('Nord') ),
                            'NE' => array( 'name' => self::_('Nord-Est') ),
                            'NO' => array( 'name' => self::_('Nord-Ouest') ),
                            'OU' => array( 'name' => self::_('Ouest') ),
                            'SD' => array( 'name' => self::_('Sud') ),
                            'SE' => array( 'name' => self::_('Sud-Est') )
                        ) );
                    break;
                case 'HU':
                    return array(
                        'regions_label' => self::_('County'),
                        'regions'       => array(
                            'BK' => array( 'name' => self::_('Bács-Kiskun') ),
                            'BA' => array( 'name' => self::_('Baranya') ),
                            'BE' => array( 'name' => self::_('Békés') ),
                            'BZ' => array( 'name' => self::_('Borsod-Abaúj-Zemplén') ),
                            'CS' => array( 'name' => self::_('Csongrád') ),
                            'FE' => array( 'name' => self::_('Fejér') ),
                            'GS' => array( 'name' => self::_('Győr-Moson-Sopron') ),
                            'HB' => array( 'name' => self::_('Hajdú-Bihar') ),
                            'HE' => array( 'name' => self::_('Heves') ),
                            'JN' => array( 'name' => self::_('Jász-Nagykun-Szolnok') ),
                            'KE' => array( 'name' => self::_('Komárom-Esztergom') ),
                            'NO' => array( 'name' => self::_('Nógrád') ),
                            'PE' => array( 'name' => self::_('Pest') ),
                            'SO' => array( 'name' => self::_('Somogy') ),
                            'SZ' => array( 'name' => self::_('Szabolcs-Szatmár-Bereg') ),
                            'TO' => array( 'name' => self::_('Tolna') ),
                            'VA' => array( 'name' => self::_('Vas') ),
                            'VE' => array( 'name' => self::_('Veszprém (county)') ),
                            'ZA' => array( 'name' => self::_('Zala') ),
                            'BC' => array( 'name' => self::_('Békéscsaba') ),
                            'DE' => array( 'name' => self::_('Debrecen') ),
                            'DU' => array( 'name' => self::_('Dunaújváros') ),
                            'EG' => array( 'name' => self::_('Eger') ),
                            'ER' => array( 'name' => self::_('Érd') ),
                            'GY' => array( 'name' => self::_('Győr') ),
                            'HV' => array( 'name' => self::_('Hódmezővásárhely') ),
                            'KV' => array( 'name' => self::_('Kaposvár') ),
                            'KM' => array( 'name' => self::_('Kecskemét') ),
                            'MI' => array( 'name' => self::_('Miskolc') ),
                            'NK' => array( 'name' => self::_('Nagykanizsa') ),
                            'NY' => array( 'name' => self::_('Nyíregyháza') ),
                            'PS' => array( 'name' => self::_('Pécs') ),
                            'ST' => array( 'name' => self::_('Salgótarján') ),
                            'SN' => array( 'name' => self::_('Sopron') ),
                            'SD' => array( 'name' => self::_('Szeged') ),
                            'SF' => array( 'name' => self::_('Székesfehérvár') ),
                            'SS' => array( 'name' => self::_('Szekszárd') ),
                            'SK' => array( 'name' => self::_('Szolnok') ),
                            'SH' => array( 'name' => self::_('Szombathely') ),
                            'TB' => array( 'name' => self::_('Tatabánya') ),
                            'VM' => array( 'name' => self::_('Veszprém') ),
                            'ZE' => array( 'name' => self::_('Zalaegerszeg') ),
                            'BU' => array( 'name' => self::_('Budapest') )
                        ) );
                    break;
                case 'ID':
                    return array(
                        'regions_label'    => self::_('Geographical unit'),
                        'subregions_label' => self::_('Province'),
                        'regions'          => array(
                            'JW' => array(
                                'name'       => self::_('Jawa'),
                                'subregions' => array(
                                    'BT' => array( 'name' => self::_('Banten') ),
                                    'JB' => array( 'name' => self::_('Jawa Barat') ),
                                    'JT' => array( 'name' => self::_('Jawa Tengah') ),
                                    'JI' => array( 'name' => self::_('Jawa Timur') ),
                                    'JK' => array( 'name' => self::_('Jakarta Raya') ),
                                    'YO' => array( 'name' => self::_('Yogyakarta') )
                                ) ),
                            'KA' => array(
                                'name'       => self::_('Kalimantan'),
                                'subregions' => array(
                                    'KB' => array( 'name' => self::_('Kalimantan Barat') ),
                                    'KT' => array( 'name' => self::_('Kalimantan Tengah') ),
                                    'KS' => array( 'name' => self::_('Kalimantan Selatan') ),
                                    'KI' => array( 'name' => self::_('Kalimantan Timur') )
                                ) ),
                            'MA' => array(
                                'name'       => self::_('Maluku'),
                                'subregions' => array(
                                    'MA' => array( 'name' => self::_('Maluku') ),
                                    'MU' => array( 'name' => self::_('Maluku Utara') )
                                ) ),
                            'NU' => array(
                                'name'       => self::_('Nusa Tenggara'),
                                'subregions' => array(
                                    'BA' => array( 'name' => self::_('Bali') ),
                                    'NB' => array( 'name' => self::_('Nusa Tenggara Barat') ),
                                    'NT' => array( 'name' => self::_('Nusa Tenggara Timur') )
                                ) ),
                            'IJ' => array(
                                'name'       => self::_('Papua'),
                                'subregions' => array(
                                    'PA' => array( 'name' => self::_('Papua') ),
                                    'PB' => array( 'name' => self::_('Papua Barat') )
                                ) ),
                            'SL' => array(
                                'name'       => self::_('Sulawesi'),
                                'subregions' => array(
                                    'GO' => array( 'name' => self::_('Gorontalo') ),
                                    'SR' => array( 'name' => self::_('Sulawesi Barat') ),
                                    'SN' => array( 'name' => self::_('Sulawesi Selatan') ),
                                    'ST' => array( 'name' => self::_('Sulawesi Tengah') ),
                                    'SG' => array( 'name' => self::_('Sulawesi Tenggara') ),
                                    'SA' => array( 'name' => self::_('Sulawesi Utara') )
                                ) ),
                            'SM' => array(
                                'name'       => self::_('Sumatera'),
                                'subregions' => array(
                                    'AC' => array( 'name' => self::_('Aceh') ),
                                    'BB' => array( 'name' => self::_('Bangka Belitung') ),
                                    'BE' => array( 'name' => self::_('Bengkulu') ),
                                    'JA' => array( 'name' => self::_('Jambi') ),
                                    'KR' => array( 'name' => self::_('Kepulauan Riau') ),
                                    'LA' => array( 'name' => self::_('Lampung') ),
                                    'RI' => array( 'name' => self::_('Riau') ),
                                    'SB' => array( 'name' => self::_('Sumatra Barat') ),
                                    'SS' => array( 'name' => self::_('Sumatra Selatan') ),
                                    'SU' => array( 'name' => self::_('Sumatera Utara') )
                                ) ),
                        ) );
                    break;
                case 'IE':
                    return array(
                        'regions_label'    => self::_('Province'),
                        'subregions_label' => self::_('County'),
                        'regions'          => array(
                            'C' => array(
                                'name'       => self::_('Connacht'),
                                'subregions' => array(
                                    'G'  => array( 'name' => self::_('Galway') ),
                                    'LM' => array( 'name' => self::_('Leitrim') ),
                                    'MO' => array( 'name' => self::_('Mayo') ),
                                    'RN' => array( 'name' => self::_('Roscommon') ),
                                    'SO' => array( 'name' => self::_('Sligo') ),
                                ) ),
                            'L' => array(
                                'name'       => self::_('Leinster'),
                                'subregions' => array(
                                    'CW' => array( 'name' => self::_('Carlow') ),
                                    'D'  => array( 'name' => self::_('Dublin') ),
                                    'KE' => array( 'name' => self::_('Kildare') ),
                                    'KK' => array( 'name' => self::_('Kilkenny') ),
                                    'LS' => array( 'name' => self::_('Laois') ),
                                    'LD' => array( 'name' => self::_('Longford') ),
                                    'LH' => array( 'name' => self::_('Louth') ),
                                    'MH' => array( 'name' => self::_('Meath') ),
                                    'OY' => array( 'name' => self::_('Offaly') ),
                                    'WH' => array( 'name' => self::_('Westmeath') ),
                                    'WX' => array( 'name' => self::_('Wexford') ),
                                    'WW' => array( 'name' => self::_('Wicklow') )
                                ) ),
                            'M' => array(
                                'name'       => self::_('Munster'),
                                'subregions' => array(
                                    'CE' => array( 'name' => self::_('Clare') ),
                                    'C'  => array( 'name' => self::_('Cork') ),
                                    'KY' => array( 'name' => self::_('Kerry') ),
                                    'LK' => array( 'name' => self::_('Limerick') ),
                                    'TA' => array( 'name' => self::_('Tipperary') ),
                                    'WD' => array( 'name' => self::_('Waterford') )
                                ) ),
                            'U' => array(
                                'name'       => self::_('Ulster'),
                                'subregions' => array(
                                    'CN' => array( 'name' => self::_('Cavan') ),
                                    'DL' => array( 'name' => self::_('Donegal') ),
                                    'MN' => array( 'name' => self::_('Monaghan') ),
                                ) ),
                        ) );
                    break;
                case 'IL':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'D'  => array( 'name' => self::_('HaDarom') ),
                            'M'  => array( 'name' => self::_('HaMerkaz') ),
                            'Z'  => array( 'name' => self::_('HaZafon') ),
                            'HA' => array( 'name' => self::_('Hefa') ),
                            'TA' => array( 'name' => self::_('Tel-Aviv') ),
                            'JM' => array( 'name' => self::_('Yerushalayim Al Quds') )
                        ) );
                    break;
                case 'IN':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            'AP' => array( 'name' => self::_('Andhra Pradesh') ),
                            'AR' => array( 'name' => self::_('Arunāchal Pradesh') ),
                            'AS' => array( 'name' => self::_('Assam') ),
                            'BR' => array( 'name' => self::_('Bihār') ),
                            'CT' => array( 'name' => self::_('Chhattīsgarh') ),
                            'GA' => array( 'name' => self::_('Goa') ),
                            'GJ' => array( 'name' => self::_('Gujarāt') ),
                            'HR' => array( 'name' => self::_('Haryāna') ),
                            'HP' => array( 'name' => self::_('Himāchal Pradesh') ),
                            'JK' => array( 'name' => self::_('Jammu and Kashmīr') ),
                            'JH' => array( 'name' => self::_('Jharkhand') ),
                            'KA' => array( 'name' => self::_('Karnātaka') ),
                            'KL' => array( 'name' => self::_('Kerala') ),
                            'MP' => array( 'name' => self::_('Madhya Pradesh') ),
                            'MH' => array( 'name' => self::_('Mahārāshtra') ),
                            'MN' => array( 'name' => self::_('Manipur') ),
                            'ML' => array( 'name' => self::_('Meghālaya') ),
                            'MZ' => array( 'name' => self::_('Mizoram') ),
                            'NL' => array( 'name' => self::_('Nāgāland') ),
                            'OR' => array( 'name' => self::_('Orissa') ),
                            'PB' => array( 'name' => self::_('Punjab') ),
                            'RJ' => array( 'name' => self::_('Rājasthān') ),
                            'SK' => array( 'name' => self::_('Sikkim') ),
                            'TN' => array( 'name' => self::_('Tamil Nādu') ),
                            'TR' => array( 'name' => self::_('Tripura') ),
                            'UL' => array( 'name' => self::_('Uttaranchal') ),
                            'UP' => array( 'name' => self::_('Uttar Pradesh') ),
                            'WB' => array( 'name' => self::_('West Bengal') ),
                            'AN' => array( 'name' => self::_('Andaman and Nicobar Islands') ),
                            'CH' => array( 'name' => self::_('Chandīgarh') ),
                            'DN' => array( 'name' => self::_('Dādra and Nagar Haveli') ),
                            'DD' => array( 'name' => self::_('Damān and Diu') ),
                            'DL' => array( 'name' => self::_('Delhi') ),
                            'LD' => array( 'name' => self::_('Lakshadweep') ),
                            'PY' => array( 'name' => self::_('Pondicherry') )
                        ) );
                    break;
                case 'IQ':
                    return array(
                        'regions_label' => self::_('Governorate'),
                        'regions'       => array(
                            'AN' => array( 'name' => self::_('Al Anbar') ),
                            'BA' => array( 'name' => self::_('Al Basrah') ),
                            'MU' => array( 'name' => self::_('Al Muthanna') ),
                            'QA' => array( 'name' => self::_('Al Qadisiyah') ),
                            'NA' => array( 'name' => self::_('An Najef') ),
                            'AR' => array( 'name' => self::_('Arbil') ),
                            'SW' => array( 'name' => self::_('As Sulaymaniyah') ),
                            'TS' => array( 'name' => self::_('At Ta\'mim') ),
                            'BB' => array( 'name' => self::_('Babil') ),
                            'BG' => array( 'name' => self::_('Baghdad') ),
                            'DA' => array( 'name' => self::_('Dahuk') ),
                            'DQ' => array( 'name' => self::_('Dhi Qar') ),
                            'DI' => array( 'name' => self::_('Diyala') ),
                            'KA' => array( 'name' => self::_('Karbala\'') ),
                            'MA' => array( 'name' => self::_('Maysan') ),
                            'NI' => array( 'name' => self::_('Ninawa') ),
                            'SD' => array( 'name' => self::_('Salah ad Din') ),
                            'WA' => array( 'name' => self::_('Wasit') )
                        ) );
                    break;
                case 'IR':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '03' => array( 'name' => self::_('Ardabīl') ),
                            '02' => array( 'name' => self::_('Āzarbāyjān-e Gharbī') ),
                            '01' => array( 'name' => self::_('Āzarbāyjān-e Sharqī') ),
                            '06' => array( 'name' => self::_('Būshehr') ),
                            '08' => array( 'name' => self::_('Chahār Mahāll va Bakhtīārī') ),
                            '04' => array( 'name' => self::_('Eşfahān') ),
                            '14' => array( 'name' => self::_('Fārs') ),
                            '19' => array( 'name' => self::_('Gīlān') ),
                            '27' => array( 'name' => self::_('Golestān') ),
                            '24' => array( 'name' => self::_('Hamadān') ),
                            '23' => array( 'name' => self::_('Hormozgān') ),
                            '05' => array( 'name' => self::_('Īlām') ),
                            '15' => array( 'name' => self::_('Kermān') ),
                            '17' => array( 'name' => self::_('Kermānshāh') ),
                            '29' => array( 'name' => self::_('Khorāsān-e Janūbī') ),
                            '30' => array( 'name' => self::_('Khorāsān-e Razavī') ),
                            '31' => array( 'name' => self::_('Khorāsān-e Shemālī') ),
                            '10' => array( 'name' => self::_('Khūzestān') ),
                            '18' => array( 'name' => self::_('Kohgīlūyeh va Būyer Ahmad') ),
                            '16' => array( 'name' => self::_('Kordestān') ),
                            '20' => array( 'name' => self::_('Lorestān') ),
                            '22' => array( 'name' => self::_('Markazī') ),
                            '21' => array( 'name' => self::_('Māzandarān') ),
                            '28' => array( 'name' => self::_('Qazvīn') ),
                            '26' => array( 'name' => self::_('Qom') ),
                            '12' => array( 'name' => self::_('Semnān') ),
                            '13' => array( 'name' => self::_('Sīstān va Balūchestān') ),
                            '07' => array( 'name' => self::_('Tehrān') ),
                            '25' => array( 'name' => self::_('Yazd') ),
                            '11' => array( 'name' => self::_('Zanjān') )
                        ) );
                    break;
                case 'IS':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            '7' => array( 'name' => self::_('Austurland') ),
                            '1' => array( 'name' => self::_('Höfuðborgarsvæðið') ),
                            '6' => array( 'name' => self::_('Norðurland eystra') ),
                            '5' => array( 'name' => self::_('Norðurland vestra') ),
                            '8' => array( 'name' => self::_('Suðurland') ),
                            '2' => array( 'name' => self::_('Suðurnes') ),
                            '4' => array( 'name' => self::_('Vestfirðir') ),
                            '3' => array( 'name' => self::_('Vesturland') ),
                            '0' => array( 'name' => self::_('Reykjavík') )
                        ) );
                    break;
                case 'IT':
                    return array(
                        'regions_label'    => self::_('Region'),
                        'subregions_label' => self::_('Province'),
                        'regions'          => array(
                            '65' => array(
                                'name'       => self::_('Abruzzo'),
                                'subregions' => array(
                                    'CH' => array( 'name' => self::_('Chieti') ),
                                    'AQ' => array( 'name' => self::_('L\'Aquila') ),
                                    'PE' => array( 'name' => self::_('Pescara') ),
                                    'TE' => array( 'name' => self::_('Teramo') )
                                ) ),
                            '77' => array(
                                'name'       => self::_('Basilicata'),
                                'subregions' => array(
                                    'MT' => array( 'name' => self::_('Matera') ),
                                    'PZ' => array( 'name' => self::_('Potenza') )
                                ) ),
                            '78' => array(
                                'name'       => self::_('Calabria'),
                                'subregions' => array(
                                    'CZ' => array( 'name' => self::_('Catanzaro') ),
                                    'CS' => array( 'name' => self::_('Cosenza') ),
                                    'KR' => array( 'name' => self::_('Crotone') ),
                                    'RC' => array( 'name' => self::_('Reggio Calabria') ),
                                    'VV' => array( 'name' => self::_('Vibo Valentia') )
                                ) ),
                            '72' => array(
                                'name'       => self::_('Campania'),
                                'subregions' => array(
                                    'AV' => array( 'name' => self::_('Avellino') ),
                                    'BN' => array( 'name' => self::_('Benevento') ),
                                    'CE' => array( 'name' => self::_('Caserta') ),
                                    'NA' => array( 'name' => self::_('Napoli') ),
                                    'SA' => array( 'name' => self::_('Salerno') )
                                ) ),
                            '45' => array(
                                'name'       => self::_('Emilia-Romagna'),
                                'subregions' => array(
                                    'BO' => array( 'name' => self::_('Bologna') ),
                                    'FE' => array( 'name' => self::_('Ferrara') ),
                                    'FC' => array( 'name' => self::_('Forlì-Cesena') ),
                                    'MO' => array( 'name' => self::_('Modena') ),
                                    'PR' => array( 'name' => self::_('Parma') ),
                                    'PC' => array( 'name' => self::_('Piacenza') ),
                                    'RA' => array( 'name' => self::_('Ravenna') ),
                                    'RE' => array( 'name' => self::_('Reggio Emilia') ),
                                    'RN' => array( 'name' => self::_('Rimini') )
                                ) ),
                            '36' => array(
                                'name'       => self::_('Friuli-Venezia Giulia'),
                                'subregions' => array(
                                    'GO' => array( 'name' => self::_('Gorizia') ),
                                    'PN' => array( 'name' => self::_('Pordenone') ),
                                    'TS' => array( 'name' => self::_('Trieste') ),
                                    'UD' => array( 'name' => self::_('Udine') )
                                ) ),
                            '62' => array(
                                'name'       => self::_('Lazio'),
                                'subregions' => array(
                                    'FR' => array( 'name' => self::_('Frosinone') ),
                                    'LT' => array( 'name' => self::_('Latina') ),
                                    'RI' => array( 'name' => self::_('Rieti') ),
                                    'RM' => array( 'name' => self::_('Roma') ),
                                    'VT' => array( 'name' => self::_('Viterbo') )
                                ) ),
                            '42' => array(
                                'name'       => self::_('Liguria'),
                                'subregions' => array(
                                    'GE' => array( 'name' => self::_('Genova') ),
                                    'IM' => array( 'name' => self::_('Imperia') ),
                                    'SP' => array( 'name' => self::_('La Spezia') ),
                                    'SV' => array( 'name' => self::_('Savona') )
                                ) ),
                            '25' => array(
                                'name'       => self::_('Lombardia'),
                                'subregions' => array(
                                    'BG' => array( 'name' => self::_('Bergamo') ),
                                    'BS' => array( 'name' => self::_('Brescia') ),
                                    'CO' => array( 'name' => self::_('Como') ),
                                    'CR' => array( 'name' => self::_('Cremona') ),
                                    'LC' => array( 'name' => self::_('Lecco') ),
                                    'LO' => array( 'name' => self::_('Lodi') ),
                                    'MN' => array( 'name' => self::_('Mantova') ),
                                    'MI' => array( 'name' => self::_('Milano') ),
                                    'MB' => array( 'name' => self::_('Monza e Brianza') ),
                                    'PV' => array( 'name' => self::_('Pavia') ),
                                    'SO' => array( 'name' => self::_('Sondrio') ),
                                    'VA' => array( 'name' => self::_('Varese') )
                                ) ),
                            '57' => array(
                                'name'       => self::_('Marche'),
                                'subregions' => array(
                                    'AN' => array( 'name' => self::_('Ancona') ),
                                    'AP' => array( 'name' => self::_('Ascoli Piceno') ),
                                    'FM' => array( 'name' => self::_('Fermo') ),
                                    'SC' => array( 'name' => self::_('Macerata') ),
                                    'PU' => array( 'name' => self::_('Pesaro e Urbino') )
                                ) ),
                            '67' => array(
                                'name'       => self::_('Molise'),
                                'subregions' => array(
                                    'CB' => array( 'name' => self::_('Campobasso') ),
                                    'IS' => array( 'name' => self::_('Isernia') )
                                ) ),
                            '21' => array(
                                'name'       => self::_('Piemonte'),
                                'subregions' => array(
                                    'AL' => array( 'name' => self::_('Alessandria') ),
                                    'AT' => array( 'name' => self::_('Asti') ),
                                    'BI' => array( 'name' => self::_('Biella') ),
                                    'CN' => array( 'name' => self::_('Cuneo') ),
                                    'NO' => array( 'name' => self::_('Novara') ),
                                    'TO' => array( 'name' => self::_('Torino') ),
                                    'VB' => array( 'name' => self::_('Verbano-Cusio-Ossola') ),
                                    'VC' => array( 'name' => self::_('Vercelli') )
                                ) ),
                            '75' => array(
                                'name'       => self::_('Puglia'),
                                'subregions' => array(
                                    'BA' => array( 'name' => self::_('Bari') ),
                                    'BT' => array( 'name' => self::_('Barletta-Andria-Trani') ),
                                    'BR' => array( 'name' => self::_('Brindisi') ),
                                    'FG' => array( 'name' => self::_('Foggia') ),
                                    'LE' => array( 'name' => self::_('Lecce') ),
                                    'TA' => array( 'name' => self::_('Taranto') )
                                ) ),
                            '88' => array(
                                'name'       => self::_('Sardegna'),
                                'subregions' => array(
                                    'CA' => array( 'name' => self::_('Cagliari') ),
                                    'CI' => array( 'name' => self::_('Carbonia-Iglesias') ),
                                    'VS' => array( 'name' => self::_('Medio Campidano') ),
                                    'NU' => array( 'name' => self::_('Nuoro') ),
                                    'OG' => array( 'name' => self::_('Ogliastra') ),
                                    'OT' => array( 'name' => self::_('Olbia-Tempio') ),
                                    'OR' => array( 'name' => self::_('Oristano') ),
                                    'SS' => array( 'name' => self::_('Sassari') )
                                ) ),
                            '82' => array(
                                'name'       => self::_('Sicilia'),
                                'subregions' => array(
                                    'AG' => array( 'name' => self::_('Agrigento') ),
                                    'CL' => array( 'name' => self::_('Caltanissetta') ),
                                    'CT' => array( 'name' => self::_('Catania') ),
                                    'EN' => array( 'name' => self::_('Enna') ),
                                    'ME' => array( 'name' => self::_('Messina') ),
                                    'PA' => array( 'name' => self::_('Palermo') ),
                                    'RG' => array( 'name' => self::_('Ragusa') ),
                                    'SR' => array( 'name' => self::_('Siracusa') ),
                                    'TP' => array( 'name' => self::_('Trapani') )
                                ) ),
                            '52' => array(
                                'name'       => self::_('Toscana'),
                                'subregions' => array(
                                    'AR' => array( 'name' => self::_('Arezzo') ),
                                    'FI' => array( 'name' => self::_('Firenze') ),
                                    'GR' => array( 'name' => self::_('Grosseto') ),
                                    'LI' => array( 'name' => self::_('Livorno') ),
                                    'LU' => array( 'name' => self::_('Lucca') ),
                                    'MS' => array( 'name' => self::_('Massa-Carrara') ),
                                    'PI' => array( 'name' => self::_('Pisa') ),
                                    'PT' => array( 'name' => self::_('Pistoia') ),
                                    'PO' => array( 'name' => self::_('Prato') ),
                                    'SI' => array( 'name' => self::_('Siena') )
                                ) ),
                            '32' => array(
                                'name'       => self::_('Trentino-Alto Adige'),
                                'subregions' => array(
                                    'BZ' => array( 'name' => self::_('Bolzano') ),
                                    'TN' => array( 'name' => self::_('Trento') )
                                ) ),
                            '55' => array(
                                'name'       => self::_('Umbria'),
                                'subregions' => array(
                                    'PG' => array( 'name' => self::_('Perugia') ),
                                    'TR' => array( 'name' => self::_('Terni') )
                                ) ),
                            '23' => array(
                                'name'       => self::_('Valle d\'Aosta'),
                                'subregions' => array(
                                    'AO' => array( 'name' => self::_('Aosta') )
                                ) ),
                            '34' => array(
                                'name'       => self::_('Veneto'),
                                'subregions' => array(
                                    'BL' => array( 'name' => self::_('Belluno') ),
                                    'PD' => array( 'name' => self::_('Padova') ),
                                    'RO' => array( 'name' => self::_('Rovigo') ),
                                    'TV' => array( 'name' => self::_('Treviso') ),
                                    'VE' => array( 'name' => self::_('Venezia') ),
                                    'VR' => array( 'name' => self::_('Verona') ),
                                    'VI' => array( 'name' => self::_('Vicenza') )
                                ) ),
                        ) );
                    break;
                case 'JM':
                    return array(
                        'regions_label' => self::_('Parish'),
                        'regions'       => array(
                            '13' => array( 'name' => self::_('Clarendon') ),
                            '09' => array( 'name' => self::_('Hanover') ),
                            '01' => array( 'name' => self::_('Kingston') ),
                            '12' => array( 'name' => self::_('Manchester') ),
                            '04' => array( 'name' => self::_('Portland') ),
                            '02' => array( 'name' => self::_('Saint Andrew') ),
                            '06' => array( 'name' => self::_('Saint Ann') ),
                            '14' => array( 'name' => self::_('Saint Catherine') ),
                            '11' => array( 'name' => self::_('Saint Elizabeth') ),
                            '08' => array( 'name' => self::_('Saint James') ),
                            '05' => array( 'name' => self::_('Saint Mary') ),
                            '03' => array( 'name' => self::_('Saint Thomas') ),
                            '07' => array( 'name' => self::_('Trelawny') ),
                            '10' => array( 'name' => self::_('Westmoreland') )
                        ) );
                    break;
                case 'JO':
                    return array(
                        'regions_label' => self::_('Governorate'),
                        'regions'       => array(
                            'AJ' => array( 'name' => self::_('`Ajlun') ),
                            'AQ' => array( 'name' => self::_('Al `Aqabah') ),
                            'BA' => array( 'name' => self::_('Al Balqā\'') ),
                            'KA' => array( 'name' => self::_('Al Karak') ),
                            'MA' => array( 'name' => self::_('Al Mafraq') ),
                            'AM' => array( 'name' => self::_('Amman') ),
                            'AT' => array( 'name' => self::_('Aţ Ţafīlah') ),
                            'AZ' => array( 'name' => self::_('Az Zarqā\'') ),
                            'JR' => array( 'name' => self::_('Irbid') ),
                            'JA' => array( 'name' => self::_('Jarash') ),
                            'MN' => array( 'name' => self::_('Ma`ān') ),
                            'MD' => array( 'name' => self::_('Mādabā') )
                        ) );
                    break;
                case 'JP':
                    return array(
                        'regions_label' => self::_('Prefecture'),
                        'regions'       => array(
                            '23' => array( 'name' => self::_('Aichi') ),
                            '05' => array( 'name' => self::_('Akita') ),
                            '02' => array( 'name' => self::_('Aomori') ),
                            '12' => array( 'name' => self::_('Chiba') ),
                            '38' => array( 'name' => self::_('Ehime') ),
                            '18' => array( 'name' => self::_('Fukui') ),
                            '40' => array( 'name' => self::_('Fukuoka') ),
                            '07' => array( 'name' => self::_('Fukushima') ),
                            '21' => array( 'name' => self::_('Gifu') ),
                            '10' => array( 'name' => self::_('Gunma') ),
                            '34' => array( 'name' => self::_('Hiroshima') ),
                            '01' => array( 'name' => self::_('Hokkaido') ),
                            '28' => array( 'name' => self::_('Hyogo') ),
                            '08' => array( 'name' => self::_('Ibaraki') ),
                            '17' => array( 'name' => self::_('Ishikawa') ),
                            '03' => array( 'name' => self::_('Iwate') ),
                            '37' => array( 'name' => self::_('Kagawa') ),
                            '46' => array( 'name' => self::_('Kagoshima') ),
                            '14' => array( 'name' => self::_('Kanagawa') ),
                            '39' => array( 'name' => self::_('Kochi') ),
                            '43' => array( 'name' => self::_('Kumamoto') ),
                            '26' => array( 'name' => self::_('Kyoto') ),
                            '24' => array( 'name' => self::_('Mie') ),
                            '04' => array( 'name' => self::_('Miyagi') ),
                            '45' => array( 'name' => self::_('Miyazaki') ),
                            '20' => array( 'name' => self::_('Nagano') ),
                            '42' => array( 'name' => self::_('Nagasaki') ),
                            '29' => array( 'name' => self::_('Nara') ),
                            '15' => array( 'name' => self::_('Niigata') ),
                            '44' => array( 'name' => self::_('Oita') ),
                            '33' => array( 'name' => self::_('Okayama') ),
                            '47' => array( 'name' => self::_('Okinawa') ),
                            '27' => array( 'name' => self::_('Osaka') ),
                            '41' => array( 'name' => self::_('Saga') ),
                            '11' => array( 'name' => self::_('Saitama') ),
                            '25' => array( 'name' => self::_('Shiga') ),
                            '32' => array( 'name' => self::_('Shimane') ),
                            '22' => array( 'name' => self::_('Shizuoka') ),
                            '09' => array( 'name' => self::_('Tochigi') ),
                            '36' => array( 'name' => self::_('Tokushima') ),
                            '13' => array( 'name' => self::_('Tokyo') ),
                            '31' => array( 'name' => self::_('Tottori') ),
                            '16' => array( 'name' => self::_('Toyama') ),
                            '30' => array( 'name' => self::_('Wakayama') ),
                            '06' => array( 'name' => self::_('Yamagata') ),
                            '35' => array( 'name' => self::_('Yamaguchi') ),
                            '19' => array( 'name' => self::_('Yamanashi') )
                        ) );
                    break;
                case 'KE':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '110' => array( 'name' => self::_('Nairobi Municipality') ),
                            '200' => array( 'name' => self::_('Central') ),
                            '300' => array( 'name' => self::_('Coast') ),
                            '400' => array( 'name' => self::_('Eastern') ),
                            '500' => array( 'name' => self::_('North-Eastern Kaskazini Mashariki') ),
                            '700' => array( 'name' => self::_('Rift Valley') ),
                            '900' => array( 'name' => self::_('Western Magharibi') )
                        ) );
                    break;
                case 'KG':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'GB' => array( 'name' => self::_('Bishkek') ),
                            'B'  => array( 'name' => self::_('Batken') ),
                            'C'  => array( 'name' => self::_('Chü') ),
                            'J'  => array( 'name' => self::_('Jalal-Abad') ),
                            'N'  => array( 'name' => self::_('Naryn') ),
                            'O'  => array( 'name' => self::_('Osh') ),
                            'T'  => array( 'name' => self::_('Talas') ),
                            'Y'  => array( 'name' => self::_('Ysyk-Köl') )
                        ) );
                    break;
                case 'KH':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '23' => array( 'name' => self::_('Krong Kaeb') ),
                            '24' => array( 'name' => self::_('Krong Pailin') ),
                            '18' => array( 'name' => self::_('Krong Preah Sihanouk') ),
                            '12' => array( 'name' => self::_('Phnom Penh') ),
                            '2'  => array( 'name' => self::_('Battambang') ),
                            '1'  => array( 'name' => self::_('Banteay Mean Chey') ),
                            '3'  => array( 'name' => self::_('Kampong Cham') ),
                            '4'  => array( 'name' => self::_('Kampong Chhnang') ),
                            '5'  => array( 'name' => self::_('Kampong Speu') ),
                            '6'  => array( 'name' => self::_('Kampong Thom') ),
                            '7'  => array( 'name' => self::_('Kampot') ),
                            '8'  => array( 'name' => self::_('Kandal') ),
                            '9'  => array( 'name' => self::_('Kach Kong') ),
                            '10' => array( 'name' => self::_('Krachoh') ),
                            '11' => array( 'name' => self::_('Mondol Kiri') ),
                            '22' => array( 'name' => self::_('Otdar Mean Chey') ),
                            '15' => array( 'name' => self::_('Pousaat') ),
                            '13' => array( 'name' => self::_('Preah Vihear') ),
                            '14' => array( 'name' => self::_('Prey Veaeng') ),
                            '16' => array( 'name' => self::_('Rotanak Kiri') ),
                            '17' => array( 'name' => self::_('Siem Reab') ),
                            '19' => array( 'name' => self::_('Stueng Traeng') ),
                            '20' => array( 'name' => self::_('Svaay Rieng') ),
                            '21' => array( 'name' => self::_('Taakaev') )
                        ) );
                    break;
                case 'KI':
                    return array(
                        'regions_label' => self::_('Island group'),
                        'regions'       => array(
                            'G' => array( 'name' => self::_('Gilbert Islands') ),
                            'L' => array( 'name' => self::_('Line Islands') ),
                            'P' => array( 'name' => self::_('Phoenix Islands') )
                        ) );
                    break;
                case 'KN':
                    return array(
                        'regions_label'    => self::_('State'),
                        'subregions_label' => self::_('Parish'),
                        'regions'          => array(
                            'K' => array(
                                'name'       => self::_('Saint Kitts'),
                                'subregions' => array(
                                    '01' => array( 'name' => self::_('Christ Church Nichola Town') ),
                                    '02' => array( 'name' => self::_('Saint Anne Sandy Point') ),
                                    '03' => array( 'name' => self::_('Saint George Basseterre') ),
                                    '06' => array( 'name' => self::_('Saint John Capisterre') ),
                                    '08' => array( 'name' => self::_('Saint Mary Cayon') ),
                                    '09' => array( 'name' => self::_('Saint Paul Capisterre') ),
                                    '11' => array( 'name' => self::_('Saint Peter Basseterre') ),
                                    '13' => array( 'name' => self::_('Saint Thomas Middle Island') ),
                                    '15' => array( 'name' => self::_('Trinity Palmetto Point') )
                                ) ),
                            'N' => array(
                                'name'       => self::_('Nevis'),
                                'subregions' => array(
                                    '04' => array( 'name' => self::_('Saint George Gingerland') ),
                                    '05' => array( 'name' => self::_('Saint James Windward') ),
                                    '07' => array( 'name' => self::_('Saint John Figtree') ),
                                    '10' => array( 'name' => self::_('Saint Paul Charlestown') ),
                                    '12' => array( 'name' => self::_('Saint Thomas Lowland')
                                    ) ) )
                        ) );
                    break;
                case 'KM':
                    return array(
                        'regions_label' => self::_('Island'),
                        'regions'       => array(
                            'A' => array( 'name' => self::_('Andjouân (Anjwān)') ),
                            'G' => array( 'name' => self::_('Andjazîdja (Anjazījah)') ),
                            'M' => array( 'name' => self::_('Moûhîlî (Mūhīlī)') )
                        ) );
                    break;
                case 'KP':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('P\'yŏngyang') ),
                            '13' => array( 'name' => self::_('Nasŏn (Najin-Sŏnbong)') ),
                            '02' => array( 'name' => self::_('P\'yŏngan-namdo') ),
                            '03' => array( 'name' => self::_('P\'yŏngan-bukto') ),
                            '04' => array( 'name' => self::_('Chagang-do') ),
                            '05' => array( 'name' => self::_('Hwanghae-namdo') ),
                            '06' => array( 'name' => self::_('Hwanghae-bukto') ),
                            '07' => array( 'name' => self::_('Kangwŏn-do') ),
                            '08' => array( 'name' => self::_('Hamgyŏng-namdo') ),
                            '09' => array( 'name' => self::_('Hamgyŏng-bukto') ),
                            '10' => array( 'name' => self::_('Yanggang-do') )
                        ) );
                    break;
                case 'KR':
                    return array(
                        'regions_label' => self::_('Province'), // label metropolitan cities as provinces
                        'regions'       => array(
                            '11' => array( 'name' => self::_('Seoul Teugbyeolsi') ),
                            '26' => array( 'name' => self::_('Busan Gwang\'yeogsi') ),
                            '27' => array( 'name' => self::_('Daegu Gwang\'yeogsi') ),
                            '30' => array( 'name' => self::_('Daejeon Gwang\'yeogsi') ),
                            '29' => array( 'name' => self::_('Gwangju Gwang\'yeogsi') ),
                            '28' => array( 'name' => self::_('Incheon Gwang\'yeogsi') ),
                            '31' => array( 'name' => self::_('Ulsan Gwang\'yeogsi') ),
                            '43' => array( 'name' => self::_('Chungcheongbukdo') ),
                            '44' => array( 'name' => self::_('Chungcheongnamdo') ),
                            '42' => array( 'name' => self::_('Gang\'weondo') ),
                            '41' => array( 'name' => self::_('Gyeonggido') ),
                            '47' => array( 'name' => self::_('Gyeongsangbukdo') ),
                            '48' => array( 'name' => self::_('Gyeongsangnamdo') ),
                            '49' => array( 'name' => self::_('Jejudo') ),
                            '45' => array( 'name' => self::_('Jeonrabukdo') ),
                            '46' => array( 'name' => self::_('Jeonranamdo') )
                        ) );
                    break;
                case 'KW':
                    return array(
                        'regions_label' => self::_('Governorate'),
                        'regions'       => array(
                            'AH' => array( 'name' => self::_('Al Ahmadi') ),
                            'FA' => array( 'name' => self::_('Al Farwānīyah') ),
                            'JA' => array( 'name' => self::_('Al Jahrah') ),
                            'KU' => array( 'name' => self::_('Al Kuwayt') ),
                            'HA' => array( 'name' => self::_('Hawallī') )
                        ) );
                    break;
                case 'KZ':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'ALA' => array( 'name' => self::_('Almaty') ),
                            'AST' => array( 'name' => self::_('Astana') ),
                            'ALM' => array( 'name' => self::_('Almaty oblysy') ),
                            'AKM' => array( 'name' => self::_('Aqmola oblysy') ),
                            'AKT' => array( 'name' => self::_('Aqtöbe oblysy') ),
                            'ATY' => array( 'name' => self::_('Atyraū oblysy') ),
                            'ZAP' => array( 'name' => self::_('Batys Quzaqstan oblysy') ),
                            'MAN' => array( 'name' => self::_('Mangghystaū oblysy') ),
                            'YUZ' => array( 'name' => self::_('Ongtüstik Qazaqstan oblysy') ),
                            'PAV' => array( 'name' => self::_('Pavlodar oblysy') ),
                            'KAR' => array( 'name' => self::_('Qaraghandy oblysy') ),
                            'KUS' => array( 'name' => self::_('Qostanay oblysy') ),
                            'KZY' => array( 'name' => self::_('Qyzylorda oblysy') ),
                            'VOS' => array( 'name' => self::_('Shyghys Qazaqstan oblysy') ),
                            'SEV' => array( 'name' => self::_('Soltüstik Quzaqstan oblysy') ),
                            'ZHA' => array( 'name' => self::_('Zhambyl oblysy') )
                        ) );
                    break;
                case 'LA':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'VT' => array( 'name' => self::_('Vientiane') ),
                            'AT' => array( 'name' => self::_('Attapu') ),
                            'BK' => array( 'name' => self::_('Bokèo') ),
                            'BL' => array( 'name' => self::_('Bolikhamxai') ),
                            'CH' => array( 'name' => self::_('Champasak') ),
                            'HO' => array( 'name' => self::_('Houaphan') ),
                            'KH' => array( 'name' => self::_('Khammouan') ),
                            'LM' => array( 'name' => self::_('Louang Namtha') ),
                            'LP' => array( 'name' => self::_('Louangphabang') ),
                            'OU' => array( 'name' => self::_('Oudômxai') ),
                            'PH' => array( 'name' => self::_('Phôngsali') ),
                            'SL' => array( 'name' => self::_('Salavan') ),
                            'SV' => array( 'name' => self::_('Savannakhét') ),
                            'VI' => array( 'name' => self::_('Vientiane') ),
                            'XA' => array( 'name' => self::_('Xaignabouli') ),
                            'XE' => array( 'name' => self::_('Xékong') ),
                            'XI' => array( 'name' => self::_('Xiangkhoang') ),
                            'XN' => array( 'name' => self::_('Xiasômboun') )
                        ) );
                    break;
                case 'LI':
                    return array(
                        'regions_label' => self::_('Commune'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Balzers') ),
                            '02' => array( 'name' => self::_('Eschen') ),
                            '03' => array( 'name' => self::_('Gamprin') ),
                            '04' => array( 'name' => self::_('Mauren') ),
                            '05' => array( 'name' => self::_('Planken') ),
                            '06' => array( 'name' => self::_('Ruggell') ),
                            '07' => array( 'name' => self::_('Schaan') ),
                            '08' => array( 'name' => self::_('Schellenberg') ),
                            '09' => array( 'name' => self::_('Triesen') ),
                            '10' => array( 'name' => self::_('Triesenberg') ),
                            '11' => array( 'name' => self::_('Vaduz') )
                        ) );
                    break;
                case 'LB':
                    return array(
                        'regions_label' => self::_('Governorate'),
                        'regions'       => array(
                            'AK' => array( 'name' => self::_('Aakkâr') ),
                            'BH' => array( 'name' => self::_('Baalbek-Hermel') ),
                            'BI' => array( 'name' => self::_('Béqaa') ),
                            'BA' => array( 'name' => self::_('Beyrouth') ),
                            'AS' => array( 'name' => self::_('Liban-Nord') ),
                            'JA' => array( 'name' => self::_('Liban-Sud') ),
                            'JL' => array( 'name' => self::_('Mont-Liban') ),
                            'NA' => array( 'name' => self::_('Nabatîyé') )
                        ) );
                    break;
                case 'LK':
                    return array(
                        'regions_label'    => self::_('Province'),
                        'subregions_label' => self::_('District'),
                        'regions'          => array(
                            '1' => array(
                                'name'       => self::_('Basnāhira paḷāta'),
                                'subregions' => array(
                                    '11' => array( 'name' => self::_('Kŏḷamba') ),
                                    '12' => array( 'name' => self::_('Gampaha') ),
                                    '13' => array( 'name' => self::_('Kaḷutara') )
                                ) ),
                            '3' => array(
                                'name'       => self::_('Dakuṇu paḷāta'),
                                'subregions' => array(
                                    '31' => array( 'name' => self::_('Gālla') ),
                                    '33' => array( 'name' => self::_('Hambantŏṭa') ),
                                    '32' => array( 'name' => self::_('Mātara') )
                                ) ),
                            '2' => array(
                                'name'       => self::_('Madhyama paḷāta'),
                                'subregions' => array(
                                    '21' => array( 'name' => self::_('Mahanuvara') ),
                                    '22' => array( 'name' => self::_('Mātale') ),
                                    '23' => array( 'name' => self::_('Nuvara Ĕliya') )
                                ) ),
                            '5' => array(
                                'name'       => self::_('Næ̆gĕnahira paḷāta'),
                                'subregions' => array(
                                    '52' => array( 'name' => self::_('Ampāara') ),
                                    '51' => array( 'name' => self::_('Maḍakalapuva') ),
                                    '53' => array( 'name' => self::_('Trikuṇāmalaya') )
                                ) ),
                            '9' => array(
                                'name'       => self::_('Sabaragamuva paḷāta'),
                                'subregions' => array(
                                    '92' => array( 'name' => self::_('Kægalla') ),
                                    '91' => array( 'name' => self::_('Ratnapura') ),
                                ) ),
                            '7' => array(
                                'name'       => self::_('Uturumæ̆da paḷāta'),
                                'subregions' => array(
                                    '71' => array( 'name' => self::_('Anurādhapura') ),
                                    '72' => array( 'name' => self::_('Pŏḷŏnnaruva') )
                                ) ),
                            '4' => array(
                                'name'       => self::_('Uturu paḷāta'),
                                'subregions' => array(
                                    '41' => array( 'name' => self::_('Yāpanaya') ),
                                    '42' => array( 'name' => self::_('Kilinŏchchi') ),
                                    '43' => array( 'name' => self::_('Mannārama') ),
                                    '45' => array( 'name' => self::_('Mulativ') ),
                                    '44' => array( 'name' => self::_('Vavuniyāva') )
                                ) ),
                            '8' => array(
                                'name'       => self::_('Ūva paḷāta'),
                                'subregions' => array(
                                    '81' => array( 'name' => self::_('Badulla') ),
                                    '82' => array( 'name' => self::_('Mŏṇarāgala') )
                                ) ),
                            '6' => array(
                                'name'       => self::_('Vayamba paḷāta'),
                                'subregions' => array(
                                    '61' => array( 'name' => self::_('Kuruṇægala') ),
                                    '62' => array( 'name' => self::_('Puttalama') )
                                ) ),
                        ) );
                    break;
                case 'LR':
                    return array(
                        'regions_label' => self::_('County'),
                        'regions'       => array(
                            'BM' => array( 'name' => self::_('Bomi') ),
                            'BG' => array( 'name' => self::_('Bong') ),
                            'GB' => array( 'name' => self::_('Grand Bassa') ),
                            'CM' => array( 'name' => self::_('Grand Cape Mount') ),
                            'GG' => array( 'name' => self::_('Grand Gedeh') ),
                            'GK' => array( 'name' => self::_('Grand Kru') ),
                            'LO' => array( 'name' => self::_('Lofa') ),
                            'MG' => array( 'name' => self::_('Margibi') ),
                            'MY' => array( 'name' => self::_('Maryland') ),
                            'MO' => array( 'name' => self::_('Montserrado') ),
                            'NI' => array( 'name' => self::_('Nimba') ),
                            'RI' => array( 'name' => self::_('Rivercess') ),
                            'SI' => array( 'name' => self::_('Sinoe') )
                        ) );
                    break;
                case 'LS':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'D' => array( 'name' => self::_('Berea') ),
                            'B' => array( 'name' => self::_('Butha-Buthe') ),
                            'C' => array( 'name' => self::_('Leribe') ),
                            'E' => array( 'name' => self::_('Mafeteng') ),
                            'A' => array( 'name' => self::_('Maseru') ),
                            'F' => array( 'name' => self::_('Mohale\'s Hoek') ),
                            'J' => array( 'name' => self::_('Mokhotlong') ),
                            'H' => array( 'name' => self::_('Qacha\'s Nek') ),
                            'G' => array( 'name' => self::_('Quthing') ),
                            'K' => array( 'name' => self::_('Thaba-Tseka') )
                        ) );
                    break;
                case 'LT':
                    return array(
                        'regions_label' => self::_('County'),
                        'regions'       => array(
                            'AL' => array( 'name' => self::_('Alytaus Apskritis') ),
                            'KU' => array( 'name' => self::_('Kauno Apskritis') ),
                            'KL' => array( 'name' => self::_('Klaipėdos Apskritis') ),
                            'MR' => array( 'name' => self::_('Marijampolės Apskritis') ),
                            'PN' => array( 'name' => self::_('Panevėžio Apskritis') ),
                            'SA' => array( 'name' => self::_('Šiaulių Apskritis') ),
                            'TA' => array( 'name' => self::_('Tauragés Apskritis') ),
                            'TE' => array( 'name' => self::_('Telšių Apskritis') ),
                            'UT' => array( 'name' => self::_('Utenos Apskritis') ),
                            'VL' => array( 'name' => self::_('Vilniaus Apskritis') )
                        ) );
                    break;
                case 'LU':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'D' => array( 'name' => self::_('Diekirch') ),
                            'G' => array( 'name' => self::_('Grevenmacher') ),
                            'L' => array( 'name' => self::_('Luxembourg') )
                        ) );
                    break;
                case 'LV':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'AI'  => array( 'name' => self::_('Aizkraukle') ),
                            'AL'  => array( 'name' => self::_('Alūksne') ),
                            'BL'  => array( 'name' => self::_('Balvi') ),
                            'BU'  => array( 'name' => self::_('Bauska') ),
                            'CE'  => array( 'name' => self::_('Cēsis') ),
                            'DA'  => array( 'name' => self::_('Daugavpils') ),
                            'DO'  => array( 'name' => self::_('Dobele') ),
                            'GU'  => array( 'name' => self::_('Gulbene') ),
                            'JK'  => array( 'name' => self::_('Jēkabpils') ),
                            'JL'  => array( 'name' => self::_('Jelgava') ),
                            'KR'  => array( 'name' => self::_('Krāslava') ),
                            'KU'  => array( 'name' => self::_('Kuldīga') ),
                            'LE'  => array( 'name' => self::_('Liepāja') ),
                            'LM'  => array( 'name' => self::_('Limbaži') ),
                            'LU'  => array( 'name' => self::_('Ludza') ),
                            'MA'  => array( 'name' => self::_('Madona') ),
                            'OG'  => array( 'name' => self::_('Ogre') ),
                            'PR'  => array( 'name' => self::_('Preiļi') ),
                            'RE'  => array( 'name' => self::_('Rēzekne') ),
                            'RI'  => array( 'name' => self::_('Rīga') ),
                            'SA'  => array( 'name' => self::_('Saldus') ),
                            'TA'  => array( 'name' => self::_('Talsi') ),
                            'TU'  => array( 'name' => self::_('Tukums') ),
                            'VK'  => array( 'name' => self::_('Valka') ),
                            'VM'  => array( 'name' => self::_('Valmiera') ),
                            'VE'  => array( 'name' => self::_('Ventspils') ),
                            'DGV' => array( 'name' => self::_('Daugavpils') ),
                            'JEL' => array( 'name' => self::_('Jelgava') ),
                            'JUR' => array( 'name' => self::_('Jūrmala') ),
                            'LPX' => array( 'name' => self::_('Liepāja') ),
                            'REZ' => array( 'name' => self::_('Rēzekne') ),
                            'RIX' => array( 'name' => self::_('Rīga') ),
                            'VEN' => array( 'name' => self::_('Ventspils') )
                        ) );
                    break;
                case 'LY':
                    return array(
                        'regions_label' => self::_('Popularates'),
                        'regions'       => array(
                            'BU' => array( 'name' => self::_('Al Buţnān') ),
                            'JA' => array( 'name' => self::_('Al Jabal al Akhḑar') ),
                            'JG' => array( 'name' => self::_('Al Jabal al Gharbī') ),
                            'JI' => array( 'name' => self::_('Al Jifārah') ),
                            'JU' => array( 'name' => self::_('Al Jufrah') ),
                            'KF' => array( 'name' => self::_('Al Kufrah') ),
                            'MJ' => array( 'name' => self::_('Al Marj') ),
                            'MB' => array( 'name' => self::_('Al Marqab') ),
                            'WA' => array( 'name' => self::_('Al Wāḩāt') ),
                            'NQ' => array( 'name' => self::_('An Nuqaţ al Khams') ),
                            'ZA' => array( 'name' => self::_('Az Zāwiyah') ),
                            'BA' => array( 'name' => self::_('Banghāzī') ),
                            'DR' => array( 'name' => self::_('Darnah') ),
                            'GT' => array( 'name' => self::_('Ghāt') ),
                            'JB' => array( 'name' => self::_('Jaghbūb') ),
                            'MI' => array( 'name' => self::_('Mişrātah') ),
                            'MQ' => array( 'name' => self::_('Murzuq') ),
                            'NL' => array( 'name' => self::_('Nālūt') ),
                            'SB' => array( 'name' => self::_('Sabhā') ),
                            'SR' => array( 'name' => self::_('Surt') ),
                            'TB' => array( 'name' => self::_('Ţarābulus') ),
                            'WD' => array( 'name' => self::_('Wādī al Ḩayāt') ),
                            'WS' => array( 'name' => self::_('Wādī ash Shāţiʾ') )
                        ) );
                    break;
                case 'MA':
                    return array(
                        'regions_label'    => self::_('Economic region'),
                        'subregions_label' => self::_('Province'),
                        'regions'          => array(
                            '09' => array(
                                'name'       => self::_('Chaouia-Ouardigha'),
                                'subregions' => array(
                                    'BES' => array( 'name' => self::_('Ben Slimane') ),
                                    'KHO' => array( 'name' => self::_('Khouribga') ),
                                    'SET' => array( 'name' => self::_('Settat') )
                                ) ),
                            '10' => array(
                                'name'       => self::_('Doukhala-Abda'),
                                'subregions' => array(
                                    'JDI' => array( 'name' => self::_('El Jadida') ),
                                    'SAF' => array( 'name' => self::_('Safi') )
                                ) ),
                            '05' => array(
                                'name'       => self::_('Fès-Boulemane'),
                                'subregions' => array(
                                    'BOM' => array( 'name' => self::_('Boulemane') ),
                                    'MOU' => array( 'name' => self::_('Moulay Yacoub') ),
                                    'SEF' => array( 'name' => self::_('Sefrou') ),
                                    'FES' => array( 'name' => self::_('Fès-Dar-Dbibegh') )
                                ) ),
                            '02' => array(
                                'name'       => self::_('Gharb-Chrarda-Beni Hssen'),
                                'subregions' => array(
                                    'KEN' => array( 'name' => self::_('Kénitra') ),
                                    'SIK' => array( 'name' => self::_('Sidl Kacem') )
                                ) ),
                            '08' => array(
                                'name'       => self::_('Grand Casablanca'),
                                'subregions' => array(
                                    'MED' => array( 'name' => self::_('Médiouna') ),
                                    'NOU' => array( 'name' => self::_('Nouaceur') ),
                                    'CAS' => array( 'name' => self::_('Casablanca [Dar el Beïda]') ),
                                    'MOH' => array( 'name' => self::_('Mohammadia') )
                                ) ),
                            '14' => array(
                                'name'       => self::_('Guelmim-Es Smara'),
                                'subregions' => array(
                                    'ASZ' => array( 'name' => self::_('Assa-Zag') ),
                                    'ESM' => array( 'name' => self::_('Es Smara (EH)') ),
                                    'GUE' => array( 'name' => self::_('Guelmim') ),
                                    'TNT' => array( 'name' => self::_('Tan-Tan') ),
                                    'TAT' => array( 'name' => self::_('Tata') )
                                ) ),
                            '15' => array(
                                'name'       => self::_('Laâyoune-Boujdour-Sakia el Hamra'),
                                'subregions' => array(
                                    'BOD' => array( 'name' => self::_('Boujdour (EH)') ),
                                    'LAA' => array( 'name' => self::_('Laâyoune (EH)') )
                                ) ),
                            '04' => array(
                                'name'       => self::_('L\'Oriental'),
                                'subregions' => array(
                                    'BER' => array( 'name' => self::_('Berkane') ),
                                    'FIG' => array( 'name' => self::_('Figuig') ),
                                    'JRA' => array( 'name' => self::_('Jrada') ),
                                    'NAD' => array( 'name' => self::_('Nador') ),
                                    'TAI' => array( 'name' => self::_('Taourirt') ),
                                    'OUJ' => array( 'name' => self::_('Oujda-Angad') ),
                                ) ),
                            '11' => array(
                                'name'       => self::_('Marrakech-Tensift-Al Haouz'),
                                'subregions' => array(
                                    'HAO' => array( 'name' => self::_('Al Haouz') ),
                                    'CHI' => array( 'name' => self::_('Chichaoua') ),
                                    'ESI' => array( 'name' => self::_('Essaouira') ),
                                    'KES' => array( 'name' => self::_('Kelaat es Sraghna') ),
                                    'MMD' => array( 'name' => self::_('Marrakech-Medina') ),
                                    'MMN' => array( 'name' => self::_('Marrakech-Menara') ),
                                    'SYB' => array( 'name' => self::_('Sidi Youssef Ben Ali') )
                                ) ),
                            '06' => array(
                                'name'       => self::_('Meknès-Tafilalet'),
                                'subregions' => array(
                                    'HAJ' => array( 'name' => self::_('El Hajeb') ),
                                    'ERR' => array( 'name' => self::_('Errachidia') ),
                                    'IFR' => array( 'name' => self::_('Ifrane') ),
                                    'KHN' => array( 'name' => self::_('Khenifra') ),
                                    'MEK' => array( 'name' => self::_('Meknès') )
                                ) ),
                            '16' => array(
                                'name'       => self::_('Oued ed Dahab-Lagouira'),
                                'subregions' => array(
                                    'OUD' => array( 'name' => self::_('Oued ed Dahab (EH)') ),
                                    'AOU' => array( 'name' => self::_('Aousserd') )
                                ) ),
                            '07' => array(
                                'name'       => self::_('Rabat-Salé-Zemmour-Zaer'),
                                'subregions' => array(
                                    'KHE' => array( 'name' => self::_('Khemisaet') ),
                                    'RAB' => array( 'name' => self::_('Rabat') ),
                                    'SAL' => array( 'name' => self::_('Salé') ),
                                    'SKH' => array( 'name' => self::_('Skhirate-Témara') )
                                ) ),
                            '13' => array(
                                'name'       => self::_('Sous-Massa-Draa'),
                                'subregions' => array(
                                    'CHT' => array( 'name' => self::_('Chtouka-Ait Baha') ),
                                    'OUA' => array( 'name' => self::_('Ouarzazate') ),
                                    'TAR' => array( 'name' => self::_('Taroudant') ),
                                    'TIZ' => array( 'name' => self::_('Tiznit') ),
                                    'ZAG' => array( 'name' => self::_('Zagora') ),
                                    'AGD' => array( 'name' => self::_('Agadir-Ida-Outanane') ),
                                    'INE' => array( 'name' => self::_('Inezgane-Ait Melloul') )
                                ) ),
                            '12' => array(
                                'name'       => self::_('Tadla-Azilal'),
                                'subregions' => array(
                                    'AZI' => array( 'name' => self::_('Azilal') ),
                                    'BEM' => array( 'name' => self::_('Beni Mellal') )
                                ) ),
                            '01' => array(
                                'name'       => self::_('Tanger-Tétouan'),
                                'subregions' => array(
                                    'CHE' => array( 'name' => self::_('Chefchaouen') ),
                                    'LAP' => array( 'name' => self::_('Larache') ),
                                    'FAH' => array( 'name' => self::_('Fahs-Beni Makada') ),
                                    'TNG' => array( 'name' => self::_('Tanger-Assilah') ),
                                    'TET' => array( 'name' => self::_('Tétouan') )
                                ) ),
                            '03' => array(
                                'name'       => self::_('Taza-Al Hoceima-Taounate'),
                                'subregions' => array(
                                    'HOC' => array( 'name' => self::_('Al Hoceïma') ),
                                    'TAO' => array( 'name' => self::_('Taounate') ),
                                    'TAZ' => array( 'name' => self::_('Taza') )
                                ) ),
                        ) );
                    break;
                case 'MD':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'GA' => array( 'name' => self::_('Găgăuzia, Unitatea teritorială autonomă') ),
                            'BA' => array( 'name' => self::_('Bălți') ),
                            'BD' => array( 'name' => self::_('Tighina') ),
                            'CU' => array( 'name' => self::_('Chișinău') ),
                            'AN' => array( 'name' => self::_('Anenii Noi') ),
                            'BS' => array( 'name' => self::_('Basarabeasca') ),
                            'BR' => array( 'name' => self::_('Briceni') ),
                            'CA' => array( 'name' => self::_('Cahul') ),
                            'CT' => array( 'name' => self::_('Cantemir') ),
                            'CL' => array( 'name' => self::_('Călărași') ),
                            'CS' => array( 'name' => self::_('Căușeni') ),
                            'CM' => array( 'name' => self::_('Cimișlia') ),
                            'CR' => array( 'name' => self::_('Criuleni') ),
                            'DO' => array( 'name' => self::_('Dondușeni') ),
                            'DR' => array( 'name' => self::_('Drochia') ),
                            'DU' => array( 'name' => self::_('Dubăsari') ),
                            'ED' => array( 'name' => self::_('Edineț') ),
                            'FA' => array( 'name' => self::_('Fălești') ),
                            'FL' => array( 'name' => self::_('Florești') ),
                            'GL' => array( 'name' => self::_('Glodeni') ),
                            'HI' => array( 'name' => self::_('Hîncești') ),
                            'IA' => array( 'name' => self::_('Ialoveni') ),
                            'LE' => array( 'name' => self::_('Leova') ),
                            'NI' => array( 'name' => self::_('Nisporeni') ),
                            'OC' => array( 'name' => self::_('Ocnița') ),
                            'OR' => array( 'name' => self::_('Orhei') ),
                            'RE' => array( 'name' => self::_('Rezina') ),
                            'RI' => array( 'name' => self::_('Rîșcani') ),
                            'SI' => array( 'name' => self::_('Sîngerei') ),
                            'SO' => array( 'name' => self::_('Soroca') ),
                            'ST' => array( 'name' => self::_('Strășeni') ),
                            'SD' => array( 'name' => self::_('Șoldănești') ),
                            'SV' => array( 'name' => self::_('Ștefan Vodă') ),
                            'TA' => array( 'name' => self::_('Taraclia') ),
                            'TE' => array( 'name' => self::_('Telenești') ),
                            'UN' => array( 'name' => self::_('Ungheni') ),
                            'SN' => array( 'name' => self::_('Stînga Nistrului, unitatea teritorială din') )
                        ) );
                    break;
                case 'ME':
                    return array(
                        'regions_label' => self::_('Municipality'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Andrijevica') ),
                            '02' => array( 'name' => self::_('Bar') ),
                            '03' => array( 'name' => self::_('Berane') ),
                            '04' => array( 'name' => self::_('Bijelo Polje') ),
                            '05' => array( 'name' => self::_('Budva') ),
                            '06' => array( 'name' => self::_('Cetinje') ),
                            '07' => array( 'name' => self::_('Danilovgrad') ),
                            '08' => array( 'name' => self::_('Herceg-Novi') ),
                            '09' => array( 'name' => self::_('Kolašin') ),
                            '10' => array( 'name' => self::_('Kotor') ),
                            '11' => array( 'name' => self::_('Mojkovac') ),
                            '12' => array( 'name' => self::_('Nikšić') ),
                            '13' => array( 'name' => self::_('Plav') ),
                            '14' => array( 'name' => self::_('Pljevlja') ),
                            '15' => array( 'name' => self::_('Plužine') ),
                            '16' => array( 'name' => self::_('Podgorica') ),
                            '17' => array( 'name' => self::_('Rožaje') ),
                            '18' => array( 'name' => self::_('Šavnik') ),
                            '19' => array( 'name' => self::_('Tivat') ),
                            '20' => array( 'name' => self::_('Ulcinj') ),
                            '21' => array( 'name' => self::_('Žabljak') )
                        ) );
                    break;
                case 'MG':
                    return array(
                        'regions_label' => self::_('Autonomous province'),
                        'regions'       => array(
                            'T' => array( 'name' => self::_('Antananarivo') ),
                            'D' => array( 'name' => self::_('Antsiranana') ),
                            'F' => array( 'name' => self::_('Fianarantsoa') ),
                            'M' => array( 'name' => self::_('Mahajanga') ),
                            'A' => array( 'name' => self::_('Toamasina') ),
                            'U' => array( 'name' => self::_('Toliara') )
                        ) );
                    break;
                case 'MH':
                    return array(
                        'regions_label'    => self::_('Chains (of islands)'),
                        'subregions_label' => self::_('Municipality'),
                        'regions'          => array(
                            'L' => array(
                                'name'       => self::_('Ralik chain'),
                                'subregions' => array(
                                    'ALL' => array( 'name' => self::_('Ailinglaplap') ),
                                    'EBO' => array( 'name' => self::_('Ebon') ),
                                    'ENI' => array( 'name' => self::_('Enewetak') ),
                                    'JAB' => array( 'name' => self::_('Jabat') ),
                                    'JAL' => array( 'name' => self::_('Jaluit') ),
                                    'KIL' => array( 'name' => self::_('Kili') ),
                                    'KWA' => array( 'name' => self::_('Kwajalein') ),
                                    'LAE' => array( 'name' => self::_('Lae') ),
                                    'LIB' => array( 'name' => self::_('Lib') ),
                                    'NMK' => array( 'name' => self::_('Namdrik') ),
                                    'NMU' => array( 'name' => self::_('Namu') ),
                                    'RON' => array( 'name' => self::_('Rongelap') ),
                                    'UJA' => array( 'name' => self::_('Ujae') ),
                                    'WTN' => array( 'name' => self::_('Wotho') )
                                ) ),
                            'T' => array(
                                'name'       => self::_('Ratak chain'),
                                'subregions' => array(
                                    'ALK' => array( 'name' => self::_('Ailuk') ),
                                    'ARN' => array( 'name' => self::_('Arno') ),
                                    'AUR' => array( 'name' => self::_('Aur') ),
                                    'LIK' => array( 'name' => self::_('Likiep') ),
                                    'MAJ' => array( 'name' => self::_('Majuro') ),
                                    'MAL' => array( 'name' => self::_('Maloelap') ),
                                    'MEJ' => array( 'name' => self::_('Mejit') ),
                                    'MIL' => array( 'name' => self::_('Mili') ),
                                    'UTI' => array( 'name' => self::_('Utirik') ),
                                    'WTJ' => array( 'name' => self::_('Wotje') )
                                ) ),
                        ) );
                    break;
                case 'MK':
                    return array(
                        'regions_label' => self::_('Municipality'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Aerodrom') ),
                            '02' => array( 'name' => self::_('Aračinovo') ),
                            '03' => array( 'name' => self::_('Berovo') ),
                            '04' => array( 'name' => self::_('Bitola') ),
                            '05' => array( 'name' => self::_('Bogdanci') ),
                            '06' => array( 'name' => self::_('Bogovinje') ),
                            '07' => array( 'name' => self::_('Bosilovo') ),
                            '08' => array( 'name' => self::_('Brvenica') ),
                            '09' => array( 'name' => self::_('Butel') ),
                            '77' => array( 'name' => self::_('Centar') ),
                            '78' => array( 'name' => self::_('Centar Župa') ),
                            '79' => array( 'name' => self::_('Čair') ),
                            '80' => array( 'name' => self::_('Čaška') ),
                            '81' => array( 'name' => self::_('Češinovo-Obleševo') ),
                            '82' => array( 'name' => self::_('Čučer Sandevo') ),
                            '21' => array( 'name' => self::_('Debar') ),
                            '22' => array( 'name' => self::_('Debarca') ),
                            '23' => array( 'name' => self::_('Delčevo') ),
                            '25' => array( 'name' => self::_('Demir Hisar') ),
                            '24' => array( 'name' => self::_('Demir Kapija') ),
                            '26' => array( 'name' => self::_('Dojran') ),
                            '27' => array( 'name' => self::_('Dolneni') ),
                            '28' => array( 'name' => self::_('Drugovo') ),
                            '17' => array( 'name' => self::_('Gazi Baba') ),
                            '18' => array( 'name' => self::_('Gevgelija') ),
                            '29' => array( 'name' => self::_('Gjorče Petrov') ),
                            '19' => array( 'name' => self::_('Gostivar') ),
                            '20' => array( 'name' => self::_('Gradsko') ),
                            '34' => array( 'name' => self::_('Ilinden') ),
                            '35' => array( 'name' => self::_('Jegunovce') ),
                            '37' => array( 'name' => self::_('Karbinci') ),
                            '38' => array( 'name' => self::_('Karpoš') ),
                            '36' => array( 'name' => self::_('Kavadarci') ),
                            '40' => array( 'name' => self::_('Kičevo') ),
                            '39' => array( 'name' => self::_('Kisela Voda') ),
                            '42' => array( 'name' => self::_('Kočani') ),
                            '41' => array( 'name' => self::_('Konče') ),
                            '43' => array( 'name' => self::_('Kratovo') ),
                            '44' => array( 'name' => self::_('Kriva Palanka') ),
                            '45' => array( 'name' => self::_('Krivogaštani') ),
                            '46' => array( 'name' => self::_('Kruševo') ),
                            '47' => array( 'name' => self::_('Kumanovo') ),
                            '48' => array( 'name' => self::_('Lipkovo') ),
                            '49' => array( 'name' => self::_('Lozovo') ),
                            '51' => array( 'name' => self::_('Makedonska Kamenica') ),
                            '52' => array( 'name' => self::_('Makedonski Brod') ),
                            '50' => array( 'name' => self::_('Mavrovo-i-Rostuša') ),
                            '53' => array( 'name' => self::_('Mogila') ),
                            '54' => array( 'name' => self::_('Negotino') ),
                            '55' => array( 'name' => self::_('Novaci') ),
                            '56' => array( 'name' => self::_('Novo Selo') ),
                            '58' => array( 'name' => self::_('Ohrid') ),
                            '57' => array( 'name' => self::_('Oslomej') ),
                            '60' => array( 'name' => self::_('Pehčevo') ),
                            '59' => array( 'name' => self::_('Petrovec') ),
                            '61' => array( 'name' => self::_('Plasnica') ),
                            '62' => array( 'name' => self::_('Prilep') ),
                            '63' => array( 'name' => self::_('Probištip') ),
                            '64' => array( 'name' => self::_('Radoviš') ),
                            '65' => array( 'name' => self::_('Rankovce') ),
                            '66' => array( 'name' => self::_('Resen') ),
                            '67' => array( 'name' => self::_('Rosoman') ),
                            '68' => array( 'name' => self::_('Saraj') ),
                            '83' => array( 'name' => self::_('Štip') ),
                            '84' => array( 'name' => self::_('Šuto Orizari') ),
                            '70' => array( 'name' => self::_('Sopište') ),
                            '71' => array( 'name' => self::_('Staro Nagoričane') ),
                            '72' => array( 'name' => self::_('Struga') ),
                            '73' => array( 'name' => self::_('Strumica') ),
                            '74' => array( 'name' => self::_('Studeničani') ),
                            '69' => array( 'name' => self::_('Sveti Nikole') ),
                            '75' => array( 'name' => self::_('Tearce') ),
                            '76' => array( 'name' => self::_('Tetovo') ),
                            '10' => array( 'name' => self::_('Valandovo') ),
                            '11' => array( 'name' => self::_('Vasilevo') ),
                            '13' => array( 'name' => self::_('Veles') ),
                            '12' => array( 'name' => self::_('Vevčani') ),
                            '14' => array( 'name' => self::_('Vinica') ),
                            '15' => array( 'name' => self::_('Vraneštica') ),
                            '16' => array( 'name' => self::_('Vrapčište') ),
                            '31' => array( 'name' => self::_('Zajas') ),
                            '32' => array( 'name' => self::_('Zelenikovo') ),
                            '30' => array( 'name' => self::_('Želino') ),
                            '33' => array( 'name' => self::_('Zrnovci') )
                        ) );
                    break;
                case 'ML':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'BK0' => array( 'name' => self::_('Bamako') ),
                            7     => array( 'name' => self::_('Gao') ),
                            1     => array( 'name' => self::_('Kayes') ),
                            8     => array( 'name' => self::_('Kidal') ),
                            2     => array( 'name' => self::_('Koulikoro') ),
                            5     => array( 'name' => self::_('Mopti') ),
                            4     => array( 'name' => self::_('Ségou') ),
                            3     => array( 'name' => self::_('Sikasso') ),
                            6     => array( 'name' => self::_('Tombouctou') )
                        ) );
                    break;
                case 'MM':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            '07' => array( 'name' => self::_('Ayeyarwady') ),
                            '02' => array( 'name' => self::_('Bago') ),
                            '03' => array( 'name' => self::_('Magway') ),
                            '04' => array( 'name' => self::_('Mandalay') ),
                            '01' => array( 'name' => self::_('Sagaing') ),
                            '05' => array( 'name' => self::_('Tanintharyi') ),
                            '06' => array( 'name' => self::_('Yangon') ),
                            '14' => array( 'name' => self::_('Chin') ),
                            '11' => array( 'name' => self::_('Kachin') ),
                            '12' => array( 'name' => self::_('Kayah') ),
                            '13' => array( 'name' => self::_('Kayin') ),
                            '15' => array( 'name' => self::_('Mon') ),
                            '16' => array( 'name' => self::_('Rakhine') ),
                            '17' => array( 'name' => self::_('Shan') )
                        ) );
                    break;
                case 'MN':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '073' => array( 'name' => self::_('Arhangay') ),
                            '069' => array( 'name' => self::_('Bayanhongor') ),
                            '071' => array( 'name' => self::_('Bayan-Ölgiy') ),
                            '067' => array( 'name' => self::_('Bulgan') ),
                            '061' => array( 'name' => self::_('Dornod') ),
                            '063' => array( 'name' => self::_('Dornogovi') ),
                            '059' => array( 'name' => self::_('Dundgovi') ),
                            '057' => array( 'name' => self::_('Dzavhan') ),
                            '065' => array( 'name' => self::_('Govi-Altay') ),
                            '039' => array( 'name' => self::_('Hentiy') ),
                            '043' => array( 'name' => self::_('Hovd') ),
                            '041' => array( 'name' => self::_('Hövsgöl') ),
                            '053' => array( 'name' => self::_('Ömnögovi') ),
                            '055' => array( 'name' => self::_('Övörhangay') ),
                            '049' => array( 'name' => self::_('Selenge') ),
                            '051' => array( 'name' => self::_('Sühbaatar') ),
                            '047' => array( 'name' => self::_('Töv') ),
                            '046' => array( 'name' => self::_('Uvs') ),
                            '1'   => array( 'name' => self::_('Ulanbaatar') ),
                            '037' => array( 'name' => self::_('Darhan uul') ),
                            '064' => array( 'name' => self::_('Govi-Sumber') ),
                            '035' => array( 'name' => self::_('Orhon') )
                        ) );
                    break;
                case 'MR':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'NKC' => array( 'name' => self::_('Nouakchott') ),
                            '07'  => array( 'name' => self::_('Adrar') ),
                            '03'  => array( 'name' => self::_('Assaba') ),
                            '05'  => array( 'name' => self::_('Brakna') ),
                            '08'  => array( 'name' => self::_('Dakhlet Nouadhibou') ),
                            '04'  => array( 'name' => self::_('Gorgol') ),
                            '10'  => array( 'name' => self::_('Guidimaka') ),
                            '01'  => array( 'name' => self::_('Hodh ech Chargui') ),
                            '02'  => array( 'name' => self::_('Hodh el Charbi') ),
                            '12'  => array( 'name' => self::_('Inchiri') ),
                            '09'  => array( 'name' => self::_('Tagant') ),
                            '11'  => array( 'name' => self::_('Tiris Zemmour') ),
                            '06'  => array( 'name' => self::_('Trarza') )
                        ) );
                    break;
                case 'MT':
                    return array(
                        'regions_label' => self::_('Local council'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Attard') ),
                            '02' => array( 'name' => self::_('Balzan') ),
                            '03' => array( 'name' => self::_('Birgu') ),
                            '04' => array( 'name' => self::_('Birkirkara') ),
                            '05' => array( 'name' => self::_('Birżebbuġa') ),
                            '06' => array( 'name' => self::_('Bormla') ),
                            '07' => array( 'name' => self::_('Dingli') ),
                            '08' => array( 'name' => self::_('Fgura') ),
                            '09' => array( 'name' => self::_('Floriana') ),
                            '10' => array( 'name' => self::_('Fontana') ),
                            '11' => array( 'name' => self::_('Gudja') ),
                            '12' => array( 'name' => self::_('Gżira') ),
                            '13' => array( 'name' => self::_('Għajnsielem') ),
                            '14' => array( 'name' => self::_('Għarb') ),
                            '15' => array( 'name' => self::_('Għargħur') ),
                            '16' => array( 'name' => self::_('Għasri') ),
                            '17' => array( 'name' => self::_('Għaxaq') ),
                            '18' => array( 'name' => self::_('Ħamrun') ),
                            '19' => array( 'name' => self::_('Iklin') ),
                            '20' => array( 'name' => self::_('Isla') ),
                            '21' => array( 'name' => self::_('Kalkara') ),
                            '22' => array( 'name' => self::_('Kerċem') ),
                            '23' => array( 'name' => self::_('Kirkop') ),
                            '24' => array( 'name' => self::_('Lija') ),
                            '25' => array( 'name' => self::_('Luqa') ),
                            '26' => array( 'name' => self::_('Marsa') ),
                            '27' => array( 'name' => self::_('Marsaskala') ),
                            '28' => array( 'name' => self::_('Marsaxlokk') ),
                            '29' => array( 'name' => self::_('Mdina') ),
                            '30' => array( 'name' => self::_('Mellieħa') ),
                            '31' => array( 'name' => self::_('Mġarr') ),
                            '32' => array( 'name' => self::_('Mosta') ),
                            '33' => array( 'name' => self::_('Mqabba') ),
                            '34' => array( 'name' => self::_('Msida') ),
                            '35' => array( 'name' => self::_('Mtarfa') ),
                            '36' => array( 'name' => self::_('Munxar') ),
                            '37' => array( 'name' => self::_('Nadur') ),
                            '38' => array( 'name' => self::_('Naxxar') ),
                            '39' => array( 'name' => self::_('Paola') ),
                            '40' => array( 'name' => self::_('Pembroke') ),
                            '41' => array( 'name' => self::_('Pietà') ),
                            '42' => array( 'name' => self::_('Qala') ),
                            '43' => array( 'name' => self::_('Qormi') ),
                            '44' => array( 'name' => self::_('Qrendi') ),
                            '45' => array( 'name' => self::_('Rabat Għawdex') ),
                            '46' => array( 'name' => self::_('Rabat Malta') ),
                            '47' => array( 'name' => self::_('Safi') ),
                            '48' => array( 'name' => self::_('San Ġiljan') ),
                            '49' => array( 'name' => self::_('San Ġwann') ),
                            '50' => array( 'name' => self::_('San Lawrenz') ),
                            '51' => array( 'name' => self::_('San Pawl il-Baħar') ),
                            '52' => array( 'name' => self::_('Sannat') ),
                            '53' => array( 'name' => self::_('Santa Luċija') ),
                            '54' => array( 'name' => self::_('Santa Venera') ),
                            '55' => array( 'name' => self::_('Siġġiewi') ),
                            '56' => array( 'name' => self::_('Sliema') ),
                            '57' => array( 'name' => self::_('Swieqi') ),
                            '58' => array( 'name' => self::_('Ta\' Xbiex') ),
                            '59' => array( 'name' => self::_('Tarxien') ),
                            '60' => array( 'name' => self::_('Valletta') ),
                            '61' => array( 'name' => self::_('Xagħra') ),
                            '62' => array( 'name' => self::_('Xewkija') ),
                            '63' => array( 'name' => self::_('Xgħajra') ),
                            '64' => array( 'name' => self::_('Żabbar') ),
                            '65' => array( 'name' => self::_('Żebbuġ Għawdex') ),
                            '66' => array( 'name' => self::_('Żebbuġ Malta') ),
                            '67' => array( 'name' => self::_('Żejtun') ),
                            '68' => array( 'name' => self::_('Żurrieq') )
                        ) );
                    break;
                case 'MU':
                    return array(
                        'regions_label'    => self::_('City'),
                        'subregions_label' => self::_('Dependency'),
                        'regions'          => array(
                            'BR' => array( 'name' => self::_('Beau Bassin-Rose Hill') ),
                            'CU' => array( 'name' => self::_('Curepipe') ),
                            'PU' => array( 'name' => self::_('Port Louis') ),
                            'QB' => array( 'name' => self::_('Quatre Bornes') ),
                            'VP' => array( 'name' => self::_('Vacoas-Phoenix') ),
                            'AG' => array( 'name' => self::_('Agalega Islands') ),
                            'CC' => array( 'name' => self::_('Cargados Carajos Shoals') ),
                            'RO' => array( 'name' => self::_('Rodrigues Island') ),
                            'BL' => array( 'name' => self::_('Black River') ),
                            'FL' => array( 'name' => self::_('Flacq') ),
                            'GP' => array( 'name' => self::_('Grand Port') ),
                            'MO' => array( 'name' => self::_('Moka') ),
                            'PA' => array( 'name' => self::_('Pamplemousses') ),
                            'PW' => array( 'name' => self::_('Plaines Wilhems') ),
                            'PL' => array( 'name' => self::_('Port Louis') ),
                            'RP' => array( 'name' => self::_('Rivière du Rempart') ),
                            'SA' => array( 'name' => self::_('Savanne') )
                        ) );
                    break;
                case 'MV':
                    return array(
                        'regions_label'    => self::_('City'),
                        'subregions_label' => self::_('Atoll'),
                        'regions'          => array(
                            'MLE' => array( 'name' => self::_('Male') ),
                            '02'  => array( 'name' => self::_('Alif') ),
                            '20'  => array( 'name' => self::_('Baa') ),
                            '17'  => array( 'name' => self::_('Dhaalu') ),
                            '14'  => array( 'name' => self::_('Faafu') ),
                            '27'  => array( 'name' => self::_('Gaafu Aliff') ),
                            '28'  => array( 'name' => self::_('Gaafu Daalu') ),
                            '29'  => array( 'name' => self::_('Gnaviyani') ),
                            '07'  => array( 'name' => self::_('Haa Alif') ),
                            '23'  => array( 'name' => self::_('Haa Dhaalu') ),
                            '26'  => array( 'name' => self::_('Kaafu') ),
                            '05'  => array( 'name' => self::_('Laamu') ),
                            '03'  => array( 'name' => self::_('Lhaviyani') ),
                            '12'  => array( 'name' => self::_('Meemu') ),
                            '25'  => array( 'name' => self::_('Noonu') ),
                            '13'  => array( 'name' => self::_('Raa') ),
                            '01'  => array( 'name' => self::_('Seenu') ),
                            '24'  => array( 'name' => self::_('Shaviyani') ),
                            '08'  => array( 'name' => self::_('Thaa') ),
                            '04'  => array( 'name' => self::_('Vaavu') )
                        ) );
                    break;
                case 'MW':
                    return array(
                        'regions_label'    => self::_('Region'),
                        'subregions_label' => self::_('District'),
                        'regions'          => array(
                            'C' => array(
                                'name'       => self::_('Central Region'),
                                'subregions' => array(
                                    'DE' => array( 'name' => self::_('Dedza') ),
                                    'DO' => array( 'name' => self::_('Dowa') ),
                                    'KS' => array( 'name' => self::_('Kasungu') ),
                                    'LI' => array( 'name' => self::_('Lilongwe') ),
                                    'MC' => array( 'name' => self::_('Mchinji') ),
                                    'NK' => array( 'name' => self::_('Nkhotakota') ),
                                    'NU' => array( 'name' => self::_('Ntcheu') ),
                                    'NI' => array( 'name' => self::_('Ntchisi') ),
                                    'SA' => array( 'name' => self::_('Salima') )
                                ) ),
                            'N' => array(
                                'name'       => self::_('Northern Region'),
                                'subregions' => array(
                                    'CT' => array( 'name' => self::_('Chitipa') ),
                                    'KR' => array( 'name' => self::_('Karonga') ),
                                    'LK' => array( 'name' => self::_('Likoma') ),
                                    'MZ' => array( 'name' => self::_('Mzimba') ),
                                    'NE' => array( 'name' => self::_('Neno') ),
                                    'NB' => array( 'name' => self::_('Nkhata Bay') ),
                                    'RU' => array( 'name' => self::_('Rumphi')
                                    ) ),
                                'S'          => array(
                                    'name'       => self::_('Southern Region'),
                                    'subregions' => array(
                                        'BA' => array( 'name' => self::_('Balaka') ),
                                        'BL' => array( 'name' => self::_('Blantyre') ),
                                        'CK' => array( 'name' => self::_('Chikwawa') ),
                                        'CR' => array( 'name' => self::_('Chiradzulu') ),
                                        'MH' => array( 'name' => self::_('Machinga') ),
                                        'MG' => array( 'name' => self::_('Mangochi') ),
                                        'MU' => array( 'name' => self::_('Mulanje') ),
                                        'MW' => array( 'name' => self::_('Mwanza') ),
                                        'NS' => array( 'name' => self::_('Nsanje') ),
                                        'PH' => array( 'name' => self::_('Phalombe') ),
                                        'TH' => array( 'name' => self::_('Thyolo') ),
                                        'ZO' => array( 'name' => self::_('Zomba') )
                                    ) ) )
                        ) );
                    break;
                case 'MX':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            'DIF' => array( 'name' => self::_('Distrito Federal') ),
                            'AGU' => array( 'name' => self::_('Aguascalientes') ),
                            'BCN' => array( 'name' => self::_('Baja California') ),
                            'BCS' => array( 'name' => self::_('Baja California Sur') ),
                            'CAM' => array( 'name' => self::_('Campeche') ),
                            'COA' => array( 'name' => self::_('Coahuila') ),
                            'COL' => array( 'name' => self::_('Colima') ),
                            'CHP' => array( 'name' => self::_('Chiapas') ),
                            'CHH' => array( 'name' => self::_('Chihuahua') ),
                            'DUR' => array( 'name' => self::_('Durango') ),
                            'GUA' => array( 'name' => self::_('Guanajuato') ),
                            'GRO' => array( 'name' => self::_('Guerrero') ),
                            'HID' => array( 'name' => self::_('Hidalgo') ),
                            'JAL' => array( 'name' => self::_('Jalisco') ),
                            'MEX' => array( 'name' => self::_('México') ),
                            'MIC' => array( 'name' => self::_('Michoacán') ),
                            'MOR' => array( 'name' => self::_('Morelos') ),
                            'NAY' => array( 'name' => self::_('Nayarit') ),
                            'NLE' => array( 'name' => self::_('Nuevo León') ),
                            'OAX' => array( 'name' => self::_('Oaxaca') ),
                            'PUE' => array( 'name' => self::_('Puebla') ),
                            'QUE' => array( 'name' => self::_('Querétaro') ),
                            'ROO' => array( 'name' => self::_('Quintana Roo') ),
                            'SLP' => array( 'name' => self::_('San Luis Potosí') ),
                            'SIN' => array( 'name' => self::_('Sinaloa') ),
                            'SON' => array( 'name' => self::_('Sonora') ),
                            'TAB' => array( 'name' => self::_('Tabasco') ),
                            'TAM' => array( 'name' => self::_('Tamaulipas') ),
                            'TLA' => array( 'name' => self::_('Tlaxcala') ),
                            'VER' => array( 'name' => self::_('Veracruz') ),
                            'YUC' => array( 'name' => self::_('Yucatán') ),
                            'ZAC' => array( 'name' => self::_('Zacatecas') )
                        ) );
                    break;
                case 'MY':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            '14' => array( 'name' => self::_('Wilayah Persekutuan Kuala Lumpur') ),
                            '15' => array( 'name' => self::_('Wilayah Persekutuan Labuan') ),
                            '16' => array( 'name' => self::_('Wilayah Persekutuan Putrajaya') ),
                            '01' => array( 'name' => self::_('Johor') ),
                            '02' => array( 'name' => self::_('Kedah') ),
                            '03' => array( 'name' => self::_('Kelantan') ),
                            '04' => array( 'name' => self::_('Melaka') ),
                            '05' => array( 'name' => self::_('Negeri Sembilan') ),
                            '06' => array( 'name' => self::_('Pahang') ),
                            '08' => array( 'name' => self::_('Perak') ),
                            '09' => array( 'name' => self::_('Perlis') ),
                            '07' => array( 'name' => self::_('Pulau Pinang') ),
                            '12' => array( 'name' => self::_('Sabah') ),
                            '13' => array( 'name' => self::_('Sarawak') ),
                            '10' => array( 'name' => self::_('Selangor') ),
                            '11' => array( 'name' => self::_('Terengganu') )
                        ) );
                    break;
                case 'MZ':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'MPM' => array( 'name' => self::_('Maputo (city)') ),
                            'P'   => array( 'name' => self::_('Cabo Delgado') ),
                            'G'   => array( 'name' => self::_('Gaza') ),
                            'I'   => array( 'name' => self::_('Inhambane') ),
                            'B'   => array( 'name' => self::_('Manica') ),
                            'L'   => array( 'name' => self::_('Maputo') ),
                            'N'   => array( 'name' => self::_('Numpula') ),
                            'A'   => array( 'name' => self::_('Niassa') ),
                            'S'   => array( 'name' => self::_('Sofala') ),
                            'T'   => array( 'name' => self::_('Tete') ),
                            'Q'   => array( 'name' => self::_('Zambezia') )
                        ) );
                    break;
                case 'NA':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'CA' => array( 'name' => self::_('Caprivi') ),
                            'ER' => array( 'name' => self::_('Erongo') ),
                            'HA' => array( 'name' => self::_('Hardap') ),
                            'KA' => array( 'name' => self::_('Karas') ),
                            'KH' => array( 'name' => self::_('Khomas') ),
                            'KU' => array( 'name' => self::_('Kunene') ),
                            'OW' => array( 'name' => self::_('Ohangwena') ),
                            'OK' => array( 'name' => self::_('Okavango') ),
                            'OH' => array( 'name' => self::_('Omaheke') ),
                            'OS' => array( 'name' => self::_('Omusati') ),
                            'ON' => array( 'name' => self::_('Oshana') ),
                            'OT' => array( 'name' => self::_('Oshikoto') ),
                            'OD' => array( 'name' => self::_('Otjozondjupa') )
                        ) );
                    break;
                case 'NE':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            '8' => array( 'name' => self::_('Niamey') ),
                            '1' => array( 'name' => self::_('Agadez') ),
                            '2' => array( 'name' => self::_('Diffa') ),
                            '3' => array( 'name' => self::_('Dosso') ),
                            '4' => array( 'name' => self::_('Maradi') ),
                            '5' => array( 'name' => self::_('Tahoua') ),
                            '6' => array( 'name' => self::_('Tillabéri') ),
                            '7' => array( 'name' => self::_('Zinder') )
                        ) );
                    break;
                case 'NG':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            'FC' => array( 'name' => self::_('Abuja Capital Territory') ),
                            'AB' => array( 'name' => self::_('Abia') ),
                            'AD' => array( 'name' => self::_('Adamawa') ),
                            'AK' => array( 'name' => self::_('Akwa Ibom') ),
                            'AN' => array( 'name' => self::_('Anambra') ),
                            'BA' => array( 'name' => self::_('Bauchi') ),
                            'BY' => array( 'name' => self::_('Bayelsa') ),
                            'BE' => array( 'name' => self::_('Benue') ),
                            'BO' => array( 'name' => self::_('Borno') ),
                            'CR' => array( 'name' => self::_('Cross River') ),
                            'DE' => array( 'name' => self::_('Delta') ),
                            'EB' => array( 'name' => self::_('Ebonyi') ),
                            'ED' => array( 'name' => self::_('Edo') ),
                            'EK' => array( 'name' => self::_('Ekiti') ),
                            'EN' => array( 'name' => self::_('Enugu') ),
                            'GO' => array( 'name' => self::_('Gombe') ),
                            'IM' => array( 'name' => self::_('Imo') ),
                            'JI' => array( 'name' => self::_('Jigawa') ),
                            'KD' => array( 'name' => self::_('Kaduna') ),
                            'KN' => array( 'name' => self::_('Kano') ),
                            'KT' => array( 'name' => self::_('Katsina') ),
                            'KE' => array( 'name' => self::_('Kebbi') ),
                            'KO' => array( 'name' => self::_('Kogi') ),
                            'KW' => array( 'name' => self::_('Kwara') ),
                            'LA' => array( 'name' => self::_('Lagos') ),
                            'NA' => array( 'name' => self::_('Nassarawa') ),
                            'NI' => array( 'name' => self::_('Niger') ),
                            'OG' => array( 'name' => self::_('Ogun') ),
                            'ON' => array( 'name' => self::_('Ondo') ),
                            'OS' => array( 'name' => self::_('Osun') ),
                            'OY' => array( 'name' => self::_('Oyo') ),
                            'PL' => array( 'name' => self::_('Plateau') ),
                            'RI' => array( 'name' => self::_('Rivers') ),
                            'SO' => array( 'name' => self::_('Sokoto') ),
                            'TA' => array( 'name' => self::_('Taraba') ),
                            'YO' => array( 'name' => self::_('Yobe') ),
                            'ZA' => array( 'name' => self::_('Zamfara') )
                        ) );
                    break;
                case 'NI':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'BO' => array( 'name' => self::_('Boaco') ),
                            'CA' => array( 'name' => self::_('Carazo') ),
                            'CI' => array( 'name' => self::_('Chinandega') ),
                            'CO' => array( 'name' => self::_('Chontales') ),
                            'ES' => array( 'name' => self::_('Estelí') ),
                            'GR' => array( 'name' => self::_('Granada') ),
                            'JI' => array( 'name' => self::_('Jinotega') ),
                            'LE' => array( 'name' => self::_('León') ),
                            'MD' => array( 'name' => self::_('Madriz') ),
                            'MN' => array( 'name' => self::_('Managua') ),
                            'MS' => array( 'name' => self::_('Masaya') ),
                            'MT' => array( 'name' => self::_('Matagalpa') ),
                            'NS' => array( 'name' => self::_('Nueva Segovia') ),
                            'SJ' => array( 'name' => self::_('Río San Juan') ),
                            'RI' => array( 'name' => self::_('Rivas') ),
                            'AN' => array( 'name' => self::_('Atlántico Norte') ),
                            'AS' => array( 'name' => self::_('Atlántico Sur') )
                        ) );
                    break;
                case 'NL':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'DR' => array( 'name' => self::_('Drenthe') ),
                            'FL' => array( 'name' => self::_('Flevoland') ),
                            'FR' => array( 'name' => self::_('Friesland') ),
                            'GE' => array( 'name' => self::_('Gelderland') ),
                            'GR' => array( 'name' => self::_('Groningen') ),
                            'LI' => array( 'name' => self::_('Limburg') ),
                            'NB' => array( 'name' => self::_('Noord-Brabant') ),
                            'NH' => array( 'name' => self::_('Noord-Holland') ),
                            'OV' => array( 'name' => self::_('Overijssel') ),
                            'UT' => array( 'name' => self::_('Utrecht') ),
                            'ZE' => array( 'name' => self::_('Zeeland') ),
                            'ZH' => array( 'name' => self::_('Zuid-Holland') )
                        ) );
                    break;
                case 'NO':
                    return array(
                        'regions_label' => self::_('County'),
                        'regions'       => array(
                            '02' => array( 'name' => self::_('Akershus') ),
                            '09' => array( 'name' => self::_('Aust-Agder') ),
                            '06' => array( 'name' => self::_('Buskerud') ),
                            '20' => array( 'name' => self::_('Finnmark') ),
                            '04' => array( 'name' => self::_('Hedmark') ),
                            '12' => array( 'name' => self::_('Hordaland') ),
                            '15' => array( 'name' => self::_('Møre og Romsdal') ),
                            '18' => array( 'name' => self::_('Nordland') ),
                            '17' => array( 'name' => self::_('Nord-Trøndelag') ),
                            '05' => array( 'name' => self::_('Oppland') ),
                            '03' => array( 'name' => self::_('Oslo') ),
                            '11' => array( 'name' => self::_('Rogaland') ),
                            '14' => array( 'name' => self::_('Sogn og Fjordane') ),
                            '16' => array( 'name' => self::_('Sør-Trøndelag') ),
                            '08' => array( 'name' => self::_('Telemark') ),
                            '19' => array( 'name' => self::_('Troms') ),
                            '10' => array( 'name' => self::_('Vest-Agder') ),
                            '07' => array( 'name' => self::_('Vestfold') ),
                            '01' => array( 'name' => self::_('Østfold') ),
                            '22' => array( 'name' => self::_('Jan Mayen') ),
                            '21' => array( 'name' => self::_('Svalbard') )
                        ) );
                    break;
                case 'NP':
                    return array(
                        'regions_label'    => self::_('Development region'),
                        'subregions_label' => self::_('Zone'),
                        'regions'          => array(
                            '1' => array(
                                'name'       => self::_('Madhyamanchal'),
                                'subregions' => array(
                                    'BA' => array( 'name' => self::_('Bagmati') ),
                                    'JA' => array( 'name' => self::_('Janakpur') ),
                                    'NA' => array( 'name' => self::_('Narayani') )
                                ) ),
                            '2' => array(
                                'name'       => self::_('Madhya Pashchimanchal'),
                                'subregions' => array(
                                    'BH' => array( 'name' => self::_('Bheri') ),
                                    'KA' => array( 'name' => self::_('Karnali') ),
                                    'RA' => array( 'name' => self::_('Rapti') )
                                ) ),
                            '3' => array(
                                'name'       => self::_('Pashchimanchal'),
                                'subregions' => array(
                                    'DH' => array( 'name' => self::_('Dhawalagiri') ),
                                    'GA' => array( 'name' => self::_('Gandaki') ),
                                    'LU' => array( 'name' => self::_('Lumbini') )
                                ) ),
                            '4' => array(
                                'name'       => self::_('Purwanchal'),
                                'subregions' => array(
                                    'KO' => array( 'name' => self::_('Kosi') ),
                                    'ME' => array( 'name' => self::_('Mechi') ),
                                    'SA' => array( 'name' => self::_('Sagarmatha') )
                                ) ),
                            '5' => array(
                                'name'       => self::_('Sudur Pashchimanchal'),
                                'subregions' => array(
                                    'MA' => array( 'name' => self::_('Mahakali') ),
                                    'SE' => array( 'name' => self::_('Seti') )
                                ) ),
                        ) );
                    break;
                case 'NR':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Aiwo') ),
                            '02' => array( 'name' => self::_('Anabar') ),
                            '03' => array( 'name' => self::_('Anetan') ),
                            '04' => array( 'name' => self::_('Anibare') ),
                            '05' => array( 'name' => self::_('Baiti') ),
                            '06' => array( 'name' => self::_('Boe') ),
                            '07' => array( 'name' => self::_('Buada') ),
                            '08' => array( 'name' => self::_('Denigomodu') ),
                            '09' => array( 'name' => self::_('Ewa') ),
                            '10' => array( 'name' => self::_('Ijuw') ),
                            '11' => array( 'name' => self::_('Meneng') ),
                            '12' => array( 'name' => self::_('Nibok') ),
                            '13' => array( 'name' => self::_('Uaboe') ),
                            '14' => array( 'name' => self::_('Yaren') )
                        ) );
                    break;
                case 'NZ':
                    return array(
                        'regions_label'    => self::_('Island'),
                        'subregions_label' => self::_('Regional council'),
                        'regions'          => array(
                            'N'   => array(
                                'name'       => self::_('North Island'),
                                'subregions' => array(
                                    'AUK' => array( 'name' => self::_('Auckland') ),
                                    'BOP' => array( 'name' => self::_('Bay of Plenty') ),
                                    'HKB' => array( 'name' => self::_('Hawke\'s Bay') ),
                                    'MWT' => array( 'name' => self::_('Manawatu-Wanganui') ),
                                    'NTL' => array( 'name' => self::_('Northland') ),
                                    'TKI' => array( 'name' => self::_('Taranaki') ),
                                    'WKO' => array( 'name' => self::_('Waikato') ),
                                    'WGN' => array( 'name' => self::_('Wellington') ),
                                    'GIS' => array( 'name' => self::_('Gisborne District') )
                                ) ),
                            'S'   => array(
                                'name'       => self::_('South Island'),
                                'subregions' => array(
                                    'CAN' => array( 'name' => self::_('Canterbury') ),
                                    'OTA' => array( 'name' => self::_('Otago') ),
                                    'STL' => array( 'name' => self::_('Southland') ),
                                    'WTC' => array( 'name' => self::_('West Coast') ),
                                    'MBH' => array( 'name' => self::_('Marlborough District') ),
                                    'NSN' => array( 'name' => self::_('Nelson City') ),
                                    'TAS' => array( 'name' => self::_('Tasman District') )
                                ) ),
                            'CIT' => array( 'name' => self::_('Chatham Islands Territory') )
                        ) );
                    break;
                case 'OM':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'DA' => array( 'name' => self::_('Ad Dākhilīya') ),
                            'BA' => array( 'name' => self::_('Al Bāţinah') ),
                            'WU' => array( 'name' => self::_('Al Wusţá') ),
                            'SH' => array( 'name' => self::_('Ash Sharqīyah') ),
                            'ZA' => array( 'name' => self::_('Az̧ Z̧āhirah') ),
                            'BU' => array( 'name' => self::_('Al Buraymī') ),
                            'MA' => array( 'name' => self::_('Masqaţ') ),
                            'MU' => array( 'name' => self::_('Musandam') ),
                            'ZU' => array( 'name' => self::_('Z̧ufār') )
                        ) );
                    break;
                case 'PA':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '1'  => array( 'name' => self::_('Bocas del Toro') ),
                            '4'  => array( 'name' => self::_('Chiriquí') ),
                            '2'  => array( 'name' => self::_('Coclé') ),
                            '3'  => array( 'name' => self::_('Colón') ),
                            '5'  => array( 'name' => self::_('Darién') ),
                            '6'  => array( 'name' => self::_('Herrera') ),
                            '7'  => array( 'name' => self::_('Los Santos') ),
                            '8'  => array( 'name' => self::_('Panamá') ),
                            '9'  => array( 'name' => self::_('Veraguas') ),
                            'EM' => array( 'name' => self::_('Emberá') ),
                            'KY' => array( 'name' => self::_('Kuna Yala') ),
                            'NB' => array( 'name' => self::_('Ngöbe-Buglé') )
                        ) );
                    break;
                case 'PE':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'CAL' => array( 'name' => self::_('El Callao') ),
                            'LMA' => array( 'name' => self::_('Municipalidad Metropolitana de Lima') ),
                            'AMA' => array( 'name' => self::_('Amazonas') ),
                            'ANC' => array( 'name' => self::_('Ancash') ),
                            'APU' => array( 'name' => self::_('Apurímac') ),
                            'ARE' => array( 'name' => self::_('Arequipa') ),
                            'AYA' => array( 'name' => self::_('Ayacucho') ),
                            'CAJ' => array( 'name' => self::_('Cajamarca') ),
                            'CUS' => array( 'name' => self::_('Cusco [Cuzco]') ),
                            'HUV' => array( 'name' => self::_('Huancavelica') ),
                            'HUC' => array( 'name' => self::_('Huánuco') ),
                            'ICA' => array( 'name' => self::_('Ica') ),
                            'JUN' => array( 'name' => self::_('Junín') ),
                            'LAL' => array( 'name' => self::_('La Libertad') ),
                            'LAM' => array( 'name' => self::_('Lambayeque') ),
                            'LIM' => array( 'name' => self::_('Lima') ),
                            'LOR' => array( 'name' => self::_('Loreto') ),
                            'MDD' => array( 'name' => self::_('Madre de Dios') ),
                            'MOQ' => array( 'name' => self::_('Moquegua') ),
                            'PAS' => array( 'name' => self::_('Pasco') ),
                            'PIU' => array( 'name' => self::_('Piura') ),
                            'PUN' => array( 'name' => self::_('Puno') ),
                            'SAM' => array( 'name' => self::_('San Martín') ),
                            'TAC' => array( 'name' => self::_('Tacna') ),
                            'TUM' => array( 'name' => self::_('Tumbes') ),
                            'UCA' => array( 'name' => self::_('Ucayali') )
                        ) );
                    break;
                case 'PG':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'NCD' => array( 'name' => self::_('National Capital District (Port Moresby)') ),
                            'CPM' => array( 'name' => self::_('Central') ),
                            'CPK' => array( 'name' => self::_('Chimbu') ),
                            'EHG' => array( 'name' => self::_('Eastern Highlands') ),
                            'EBR' => array( 'name' => self::_('East New Britain') ),
                            'ESW' => array( 'name' => self::_('East Sepik') ),
                            'EPW' => array( 'name' => self::_('Enga') ),
                            'GPK' => array( 'name' => self::_('Gulf') ),
                            'MPM' => array( 'name' => self::_('Madang') ),
                            'MRL' => array( 'name' => self::_('Manus') ),
                            'MBA' => array( 'name' => self::_('Milne Bay') ),
                            'MPL' => array( 'name' => self::_('Morobe') ),
                            'NIK' => array( 'name' => self::_('New Ireland') ),
                            'NPP' => array( 'name' => self::_('Northern') ),
                            'NSA' => array( 'name' => self::_('North Solomons') ),
                            'SAN' => array( 'name' => self::_('Sandaun') ),
                            'SHM' => array( 'name' => self::_('Southern Highlands') ),
                            'WPD' => array( 'name' => self::_('Western') ),
                            'WHM' => array( 'name' => self::_('Western Highlands') ),
                            'WBK' => array( 'name' => self::_('West New Britain') )
                        ) );
                    break;
                case 'PH':
                    return array(
                        'regions_label'    => self::_('Region'),
                        'subregions_label' => self::_('Province'),
                        'regions'          => array(
                            '14' => array(
                                'name'       => self::_('Autonomous Region in Muslim Mindanao (ARMM)'),
                                'subregions' => array(
                                    'LAS' => array( 'name' => self::_('Lanao del Sur') ),
                                    'MAG' => array( 'name' => self::_('Maguindanao') ),
                                    'SLU' => array( 'name' => self::_('Sulu') ),
                                    'TAW' => array( 'name' => self::_('Tawi-Tawi') )
                                ) ),
                            '05' => array(
                                'name'       => self::_('Bicol (Region V)'),
                                'subregions' => array(
                                    'ALB' => array( 'name' => self::_('Albay') ),
                                    'CAN' => array( 'name' => self::_('Camarines Norte') ),
                                    'CAS' => array( 'name' => self::_('Camarines Sur') ),
                                    'CAT' => array( 'name' => self::_('Catanduanes') ),
                                    'MAS' => array( 'name' => self::_('Masbate') ),
                                    'SOR' => array( 'name' => self::_('Sorsogon') ),
                                ) ),
                            '02' => array(
                                'name'       => self::_('Cagayan Valley (Region II)'),
                                'subregions' => array(
                                    'BTN' => array( 'name' => self::_('Batanes') ),
                                    'CAG' => array( 'name' => self::_('Cagayan') ),
                                    'ISA' => array( 'name' => self::_('Isabela') ),
                                    'NUV' => array( 'name' => self::_('Nueva Vizcaya') ),
                                    'QUI' => array( 'name' => self::_('Quirino') )
                                ) ),
                            '40' => array(
                                'name'       => self::_('CALABARZON (Region IV-A)'),
                                'subregions' => array(
                                    'BTG' => array( 'name' => self::_('Batangas') ),
                                    'CAV' => array( 'name' => self::_('Cavite') ),
                                    'LAG' => array( 'name' => self::_('Laguna') ),
                                    'QUE' => array( 'name' => self::_('Quezon') ),
                                    'RIZ' => array( 'name' => self::_('Rizal') )
                                ) ),
                            '13' => array(
                                'name'       => self::_('Caraga (Region XIII)'),
                                'subregions' => array(
                                    'AGN' => array( 'name' => self::_('Agusan del Norte') ),
                                    'AGS' => array( 'name' => self::_('Agusan del Sur') ),
                                    'DIN' => array( 'name' => self::_('Dinagat Islands') ),
                                    'SUN' => array( 'name' => self::_('Surigao del Norte') ),
                                    'SUR' => array( 'name' => self::_('Surigao del Sur') )
                                ) ),
                            '03' => array(
                                'name'       => self::_('Central Luzon (Region III)'),
                                'subregions' => array(
                                    'AUR' => array( 'name' => self::_('Aurora') ),
                                    'BAN' => array( 'name' => self::_('Batasn') ),
                                    'BUL' => array( 'name' => self::_('Bulacan') ),
                                    'NUE' => array( 'name' => self::_('Nueva Ecija') ),
                                    'PAM' => array( 'name' => self::_('Pampanga') ),
                                    'TAR' => array( 'name' => self::_('Tarlac') ),
                                    'ZMB' => array( 'name' => self::_('Zambales') )
                                ) ),
                            '07' => array(
                                'name'       => self::_('Central Visayas (Region VII)'),
                                'subregions' => array(
                                    'BOH' => array( 'name' => self::_('Bohol') ),
                                    'CEB' => array( 'name' => self::_('Cebu') ),
                                    'NER' => array( 'name' => self::_('Negros Oriental') ),
                                    'SIG' => array( 'name' => self::_('Siquijor') )
                                ) ),
                            '15' => array(
                                'name'       => self::_('Cordillera Administrative Region (CAR)'),
                                'subregions' => array(
                                    'ABR' => array( 'name' => self::_('Abra') ),
                                    'APA' => array( 'name' => self::_('Apayao') ),
                                    'BEN' => array( 'name' => self::_('Benguet') ),
                                    'IFU' => array( 'name' => self::_('Ifugao') ),
                                    'KAL' => array( 'name' => self::_('Kalinga-Apayso') ),
                                    'MOU' => array( 'name' => self::_('Mountain Province') )
                                ) ),
                            '08' => array(
                                'name'       => self::_('Eastern Visayas (Region VIII)'),
                                'subregions' => array(
                                    'BIL' => array( 'name' => self::_('Biliran') ),
                                    'EAS' => array( 'name' => self::_('Eastern Samar') ),
                                    'LEY' => array( 'name' => self::_('Leyte') ),
                                    'NSA' => array( 'name' => self::_('Northern Samar') ),
                                    'SLE' => array( 'name' => self::_('Southern Leyte') ),
                                    'WSA' => array( 'name' => self::_('Western Samar') )
                                ) ),
                            '01' => array(
                                'name'       => self::_('Ilocos (Region I)'),
                                'subregions' => array(
                                    'ILN' => array( 'name' => self::_('Ilocos Norte') ),
                                    'ILS' => array( 'name' => self::_('Ilocos Sur') ),
                                    'LUN' => array( 'name' => self::_('La Union') ),
                                    'PAN' => array( 'name' => self::_('Pangasinan') ),
                                ) ),
                            '41' => array(
                                'name'       => self::_('MIMAROPA (Region IV-B)'),
                                'subregions' => array(
                                    'MAD' => array( 'name' => self::_('Marinduque') ),
                                    'MDC' => array( 'name' => self::_('Mindoro Occidental') ),
                                    'MDR' => array( 'name' => self::_('Mindoro Oriental') ),
                                    'PLW' => array( 'name' => self::_('Palawan') ),
                                    'ROM' => array( 'name' => self::_('Romblon') )
                                ) ),
                            '00' => array( 'name' => self::_('National Capital Region') ),
                            '10' => array(
                                'name'       => self::_('Northern Mindanao (Region X)'),
                                'subregions' => array(
                                    'BUK' => array( 'name' => self::_('Bukidnon') ),
                                    'CAM' => array( 'name' => self::_('Camiguin') ),
                                    'MSC' => array( 'name' => self::_('Misamis Occidental') ),
                                    'MSR' => array( 'name' => self::_('Misamis Oriental') )
                                ) ),
                            '12' => array(
                                'name'       => self::_('Soccsksargen (Region XII)'),
                                'subregions' => array(
                                    'LAN' => array( 'name' => self::_('Lanao del Norte') ),
                                    'NCO' => array( 'name' => self::_('North Cotabato') ),
                                    'SUK' => array( 'name' => self::_('Sultan Kudarat') )
                                ) ),
                            '06' => array(
                                'name'       => self::_('Western Visayas (Region VI)'),
                                'subregions' => array(
                                    'AKL' => array( 'name' => self::_('Aklan') ),
                                    'ANT' => array( 'name' => self::_('Antique') ),
                                    'CAP' => array( 'name' => self::_('Capiz') ),
                                    'GUI' => array( 'name' => self::_('Guimaras') ),
                                    'ILI' => array( 'name' => self::_('Iloilo') ),
                                    'NEC' => array( 'name' => self::_('Negroe Occidental') )
                                ) ),
                            '09' => array(
                                'name'       => self::_('Zamboanga Peninsula (Region IX)'),
                                'subregions' => array(
                                    'BAS' => array( 'name' => self::_('Basilan') ),
                                    'ZAN' => array( 'name' => self::_('Zamboanga del Norte') ),
                                    'ZAS' => array( 'name' => self::_('Zamboanga del Sur') ),
                                    'ZSI' => array( 'name' => self::_('Zamboanga Sibugay') )
                                ) ),
                            '11' => array(
                                'name'       => self::_('Davao'),
                                'subregions' => array(
                                    'COM' => array( 'name' => self::_('Compostela Valley') ),
                                    'DAV' => array( 'name' => self::_('Davao del Norte') ),
                                    'DAS' => array( 'name' => self::_('Davao del Sur') ),
                                    'DAO' => array( 'name' => self::_('Davao Oriental') ),
                                    'SAR' => array( 'name' => self::_('Sarangani') ),
                                    'SCO' => array( 'name' => self::_('South Cotabato') )
                                ) ),
                        ) );
                    break;
                case 'PK':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'IS' => array( 'name' => self::_('Islamabad') ),
                            'BA' => array( 'name' => self::_('Balochistan') ),
                            'NW' => array( 'name' => self::_('North-West Frontier') ),
                            'PB' => array( 'name' => self::_('Punjab') ),
                            'SD' => array( 'name' => self::_('Sindh') ),
                            'TA' => array( 'name' => self::_('Federally Administered Tribal Areas') ),
                            'JK' => array( 'name' => self::_('Azad Kashmir') ),
                            'NA' => array( 'name' => self::_('Northern Areas') )
                        ) );
                    break;
                case 'PL':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'DS' => array( 'name' => self::_('Dolnośląskie') ),
                            'KP' => array( 'name' => self::_('Kujawsko-pomorskie') ),
                            'LU' => array( 'name' => self::_('Lubelskie') ),
                            'LB' => array( 'name' => self::_('Lubuskie') ),
                            'LD' => array( 'name' => self::_('Łódzkie') ),
                            'MA' => array( 'name' => self::_('Małopolskie') ),
                            'MZ' => array( 'name' => self::_('Mazowieckie') ),
                            'OP' => array( 'name' => self::_('Opolskie') ),
                            'PK' => array( 'name' => self::_('Podkarpackie') ),
                            'PD' => array( 'name' => self::_('Podlaskie') ),
                            'PM' => array( 'name' => self::_('Pomorskie') ),
                            'SL' => array( 'name' => self::_('Śląskie') ),
                            'SK' => array( 'name' => self::_('Świętokrzyskie') ),
                            'WN' => array( 'name' => self::_('Warmińsko-mazurskie') ),
                            'WP' => array( 'name' => self::_('Wielkopolskie') ),
                            'ZP' => array( 'name' => self::_('Zachodniopomorskie') )
                        ) );
                    break;
                case 'PT':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Aveiro') ),
                            '02' => array( 'name' => self::_('Beja') ),
                            '03' => array( 'name' => self::_('Braga') ),
                            '04' => array( 'name' => self::_('Bragança') ),
                            '05' => array( 'name' => self::_('Castelo Branco') ),
                            '06' => array( 'name' => self::_('Coimbra') ),
                            '07' => array( 'name' => self::_('Évora') ),
                            '08' => array( 'name' => self::_('Faro') ),
                            '09' => array( 'name' => self::_('Guarda') ),
                            '10' => array( 'name' => self::_('Leiria') ),
                            '11' => array( 'name' => self::_('Lisboa') ),
                            '12' => array( 'name' => self::_('Portalegre') ),
                            '13' => array( 'name' => self::_('Porto') ),
                            '14' => array( 'name' => self::_('Santarém') ),
                            '15' => array( 'name' => self::_('Setúbal') ),
                            '16' => array( 'name' => self::_('Viana do Castelo') ),
                            '17' => array( 'name' => self::_('Vila Real') ),
                            '18' => array( 'name' => self::_('Viseu') ),
                            '20' => array( 'name' => self::_('Região Autónoma dos Açores') ),
                            '30' => array( 'name' => self::_('Região Autónoma da Madeira') )
                        ) );
                    break;
                case 'PW':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            '002' => array( 'name' => self::_('Aimeliik') ),
                            '004' => array( 'name' => self::_('Airai') ),
                            '010' => array( 'name' => self::_('Angaur') ),
                            '050' => array( 'name' => self::_('Hatobohei') ),
                            '100' => array( 'name' => self::_('Kayangel') ),
                            '150' => array( 'name' => self::_('Koror') ),
                            '212' => array( 'name' => self::_('Melekeok') ),
                            '214' => array( 'name' => self::_('Ngaraard') ),
                            '218' => array( 'name' => self::_('Ngarchelong') ),
                            '222' => array( 'name' => self::_('Ngardmau') ),
                            '224' => array( 'name' => self::_('Ngatpang') ),
                            '226' => array( 'name' => self::_('Ngchesar') ),
                            '227' => array( 'name' => self::_('Ngeremlengui') ),
                            '228' => array( 'name' => self::_('Ngiwal') ),
                            '350' => array( 'name' => self::_('Peleliu') ),
                            '370' => array( 'name' => self::_('Sonsorol') )
                        ) );
                    break;
                case 'PY':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'ASU' => array( 'name' => self::_('Asunción') ),
                            '16'  => array( 'name' => self::_('Alto Paraguay') ),
                            '10'  => array( 'name' => self::_('Alto Paraná') ),
                            '13'  => array( 'name' => self::_('Amambay') ),
                            '19'  => array( 'name' => self::_('Boquerón') ),
                            '5'   => array( 'name' => self::_('Caaguazú') ),
                            '6'   => array( 'name' => self::_('Caazapá') ),
                            '14'  => array( 'name' => self::_('Canindeyú') ),
                            '11'  => array( 'name' => self::_('Central') ),
                            '1'   => array( 'name' => self::_('Concepción') ),
                            '3'   => array( 'name' => self::_('Cordillera') ),
                            '4'   => array( 'name' => self::_('Guairá') ),
                            '7'   => array( 'name' => self::_('Itapúa') ),
                            '8'   => array( 'name' => self::_('Misiones') ),
                            '12'  => array( 'name' => self::_('Ñeembucú') ),
                            '9'   => array( 'name' => self::_('Paraguarí') ),
                            '15'  => array( 'name' => self::_('Presidente Hayes') ),
                            '2'   => array( 'name' => self::_('San Pedro') )
                        ) );
                    break;
                case 'QA':
                    return array(
                        'regions_label' => self::_('Municipality'),
                        'regions'       => array(
                            'DA' => array( 'name' => self::_('Ad Dawhah') ),
                            'GH' => array( 'name' => self::_('Al Ghuwayriyah') ),
                            'JU' => array( 'name' => self::_('Al Jumayliyah') ),
                            'KH' => array( 'name' => self::_('Al Khawr') ),
                            'WA' => array( 'name' => self::_('Al Wakrah') ),
                            'RA' => array( 'name' => self::_('Ar Rayyan') ),
                            'JB' => array( 'name' => self::_('Jariyan al Batnah') ),
                            'MS' => array( 'name' => self::_('Madinat ash Shamal') ),
                            'US' => array( 'name' => self::_('Umm Salal') )
                        ) );
                    break;
                case 'RO':
                    return array(
                        'regions_label'    => self::_('Department'),
                        'subregions_label' => self::_('Municipality'),
                        'regions'          => array(
                            'AB' => array( 'name' => self::_('Alba') ),
                            'AR' => array( 'name' => self::_('Arad') ),
                            'AG' => array( 'name' => self::_('Argeș') ),
                            'BC' => array( 'name' => self::_('Bacău') ),
                            'BH' => array( 'name' => self::_('Bihor') ),
                            'BN' => array( 'name' => self::_('Bistrița-Năsăud') ),
                            'BT' => array( 'name' => self::_('Botoșani') ),
                            'BV' => array( 'name' => self::_('Brașov') ),
                            'BR' => array( 'name' => self::_('Brăila') ),
                            'BZ' => array( 'name' => self::_('Buzău') ),
                            'CS' => array( 'name' => self::_('Caraș-Severin') ),
                            'CL' => array( 'name' => self::_('Călărași') ),
                            'CJ' => array( 'name' => self::_('Cluj') ),
                            'CT' => array( 'name' => self::_('Constanța') ),
                            'CV' => array( 'name' => self::_('Covasna') ),
                            'DB' => array( 'name' => self::_('Dâmbovița') ),
                            'DJ' => array( 'name' => self::_('Dolj') ),
                            'GL' => array( 'name' => self::_('Galați') ),
                            'GR' => array( 'name' => self::_('Giurgiu') ),
                            'GJ' => array( 'name' => self::_('Gorj') ),
                            'HR' => array( 'name' => self::_('Harghita') ),
                            'HD' => array( 'name' => self::_('Hunedoara') ),
                            'IL' => array( 'name' => self::_('Ialomița') ),
                            'IS' => array( 'name' => self::_('Iași') ),
                            'IF' => array( 'name' => self::_('Ilfov') ),
                            'MM' => array( 'name' => self::_('Maramureș') ),
                            'MH' => array( 'name' => self::_('Mehedinți') ),
                            'MS' => array( 'name' => self::_('Mureș') ),
                            'NT' => array( 'name' => self::_('Neamț') ),
                            'OT' => array( 'name' => self::_('Olt') ),
                            'PH' => array( 'name' => self::_('Prahova') ),
                            'SM' => array( 'name' => self::_('Satu Mare') ),
                            'SJ' => array( 'name' => self::_('Sălaj') ),
                            'SB' => array( 'name' => self::_('Sibiu') ),
                            'SV' => array( 'name' => self::_('Suceava') ),
                            'TR' => array( 'name' => self::_('Teleorman') ),
                            'TM' => array( 'name' => self::_('Timiș') ),
                            'TL' => array( 'name' => self::_('Tulcea') ),
                            'VS' => array( 'name' => self::_('Vaslui') ),
                            'VL' => array( 'name' => self::_('Vâlcea') ),
                            'VN' => array( 'name' => self::_('Vrancea') ),
                            'B'  => array( 'name' => self::_('București') )
                        ) );
                    break;
                case 'RS':
                    return array(
                        'regions_label'    => self::_('Province'),
                        'subregions_label' => self::_('District'),
                        'regions'          => array(
                            '00' => array( 'name' => self::_('Beograd') ),
                            'KM' => array(
                                'name'       => self::_('Kosovo-Metohija'),
                                'subregions' => array(
                                    '25' => array( 'name' => self::_('Kosovski okrug') ),
                                    '28' => array( 'name' => self::_('Kosovsko-Mitrovački okrug') ),
                                    '29' => array( 'name' => self::_('Kosovsko-Pomoravski okrug') ),
                                    '26' => array( 'name' => self::_('Pećki okrug') ),
                                    '27' => array( 'name' => self::_('Prizrenski okrug') )
                                ) ),
                            'VO' => array(
                                'name'       => self::_('Vojvodina'),
                                'subregions' => array(
                                    '06' => array( 'name' => self::_('Južnobački okrug') ),
                                    '04' => array( 'name' => self::_('Južnobanatski okrug') ),
                                    '01' => array( 'name' => self::_('Severnobački okrug') ),
                                    '03' => array( 'name' => self::_('Severnobanatski okrug') ),
                                    '02' => array( 'name' => self::_('Srednjebanatski okrug') ),
                                    '07' => array( 'name' => self::_('Sremski okrug') ),
                                    '05' => array( 'name' => self::_('Zapadnobački okrug') )
                                ) ),
                            '14' => array( 'name' => self::_('Borski okrug') ),
                            '11' => array( 'name' => self::_('Braničevski okrug') ),
                            '23' => array( 'name' => self::_('Jablanički okrug') ),
                            '09' => array( 'name' => self::_('Kolubarski okrug') ),
                            '08' => array( 'name' => self::_('Mačvanski okrug') ),
                            '17' => array( 'name' => self::_('Moravički okrug') ),
                            '20' => array( 'name' => self::_('Nišavski okrug') ),
                            '24' => array( 'name' => self::_('Pčinjski okrug') ),
                            '22' => array( 'name' => self::_('Pirotski okrug') ),
                            '10' => array( 'name' => self::_('Podunavski okrug') ),
                            '13' => array( 'name' => self::_('Pomoravski okrug') ),
                            '19' => array( 'name' => self::_('Rasinski okrug') ),
                            '18' => array( 'name' => self::_('Raški okrug') ),
                            '12' => array( 'name' => self::_('Šumadijski okrug') ),
                            '21' => array( 'name' => self::_('Toplički okrug') ),
                            '15' => array( 'name' => self::_('Zaječarski okrug') ),
                            '16' => array( 'name' => self::_('Zlatiborski okrug') )
                        ) );
                    break;
                case 'RU':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'AD'  => array( 'name' => self::_('Adygeya, Respublika') ),
                            'AL'  => array( 'name' => self::_('Altay, Respublika') ),
                            'BA'  => array( 'name' => self::_('Bashkortostan, Respublika') ),
                            'BU'  => array( 'name' => self::_('Buryatiya, Respublika') ),
                            'CE'  => array( 'name' => self::_('Chechenskaya Respublika') ),
                            'CU'  => array( 'name' => self::_('Chuvashskaya Respublika') ),
                            'DA'  => array( 'name' => self::_('Dagestan, Respublika') ),
                            'IN'  => array( 'name' => self::_('Respublika Ingushetiya') ),
                            'KB'  => array( 'name' => self::_('Kabardino-Balkarskaya Respublika') ),
                            'KL'  => array( 'name' => self::_('Kalmykiya, Respublika') ),
                            'KC'  => array( 'name' => self::_('Karachayevo-Cherkesskaya Respublika') ),
                            'KR'  => array( 'name' => self::_('Kareliya, Respublika') ),
                            'KK'  => array( 'name' => self::_('Khakasiya, Respublika') ),
                            'KO'  => array( 'name' => self::_('Komi, Respublika') ),
                            'ME'  => array( 'name' => self::_('Mariy El, Respublika') ),
                            'MO'  => array( 'name' => self::_('Mordoviya, Respublika') ),
                            'SA'  => array( 'name' => self::_('Sakha, Respublika [Yakutiya]') ),
                            'SE'  => array( 'name' => self::_('Severnaya Osetiya-Alaniya, Respublika') ),
                            'TA'  => array( 'name' => self::_('Tatarstan, Respublika') ),
                            'TY'  => array( 'name' => self::_('Tyva, Respublika [Tuva]') ),
                            'UD'  => array( 'name' => self::_('Udmurtskaya Respublika') ),
                            'ALT' => array( 'name' => self::_('Altayskiy kray') ),
                            'KAM' => array( 'name' => self::_('Kamchatskiy kray') ),
                            'KHA' => array( 'name' => self::_('Khabarovskiy kray') ),
                            'KDA' => array( 'name' => self::_('Krasnodarskiy kray') ),
                            'KYA' => array( 'name' => self::_('Krasnoyarskiy kray') ),
                            'PER' => array( 'name' => self::_('Permskiy kray') ),
                            'PRI' => array( 'name' => self::_('Primorskiy kray') ),
                            'STA' => array( 'name' => self::_('Stavropol\'skiy kray') ),
                            'ZAB' => array( 'name' => self::_('Zabajkal\'skij kraj') ),
                            'AMU' => array( 'name' => self::_('Amurskaya oblast\'') ),
                            'ARK' => array( 'name' => self::_('Arkhangel\'skaya oblast\'') ),
                            'AST' => array( 'name' => self::_('Astrakhanskaya oblast\'') ),
                            'BEL' => array( 'name' => self::_('Belgorodskaya oblast\'') ),
                            'BRY' => array( 'name' => self::_('Bryanskaya oblast\'') ),
                            'CHE' => array( 'name' => self::_('Chelyabinskaya oblast\'') ),
                            'IRK' => array( 'name' => self::_('Irkutiskaya oblast\'') ),
                            'IVA' => array( 'name' => self::_('Ivanovskaya oblast\'') ),
                            'KGD' => array( 'name' => self::_('Kaliningradskaya oblast\'') ),
                            'KLU' => array( 'name' => self::_('Kaluzhskaya oblast\'') ),
                            'KEM' => array( 'name' => self::_('Kemerovskaya oblast\'') ),
                            'KIR' => array( 'name' => self::_('Kirovskaya oblast\'') ),
                            'KOS' => array( 'name' => self::_('Kostromskaya oblast\'') ),
                            'KGN' => array( 'name' => self::_('Kurganskaya oblast\'') ),
                            'KRS' => array( 'name' => self::_('Kurskaya oblast\'') ),
                            'LEN' => array( 'name' => self::_('Leningradskaya oblast\'') ),
                            'LIP' => array( 'name' => self::_('Lipetskaya oblast\'') ),
                            'MAG' => array( 'name' => self::_('Magadanskaya oblast\'') ),
                            'MOS' => array( 'name' => self::_('Moskovskaya oblast\'') ),
                            'MUR' => array( 'name' => self::_('Murmanskaya oblast\'') ),
                            'NIZ' => array( 'name' => self::_('Nizhegorodskaya oblast\'') ),
                            'NGR' => array( 'name' => self::_('Novgorodskaya oblast\'') ),
                            'NVS' => array( 'name' => self::_('Novosibirskaya oblast\'') ),
                            'OMS' => array( 'name' => self::_('Omskaya oblast\'') ),
                            'ORE' => array( 'name' => self::_('Orenburgskaya oblast\'') ),
                            'ORL' => array( 'name' => self::_('Orlovskaya oblast\'') ),
                            'PNZ' => array( 'name' => self::_('Penzenskaya oblast\'') ),
                            'PSK' => array( 'name' => self::_('Pskovskaya oblast\'') ),
                            'ROS' => array( 'name' => self::_('Rostovskaya oblast\'') ),
                            'RYA' => array( 'name' => self::_('Ryazanskaya oblast\'') ),
                            'SAK' => array( 'name' => self::_('Sakhalinskaya oblast\'') ),
                            'SAM' => array( 'name' => self::_('Samaraskaya oblast\'') ),
                            'SAR' => array( 'name' => self::_('Saratovskaya oblast\'') ),
                            'SMO' => array( 'name' => self::_('Smolenskaya oblast\'') ),
                            'SVE' => array( 'name' => self::_('Sverdlovskaya oblast\'') ),
                            'TAM' => array( 'name' => self::_('Tambovskaya oblast\'') ),
                            'TOM' => array( 'name' => self::_('Tomskaya oblast\'') ),
                            'TUL' => array( 'name' => self::_('Tul\'skaya oblast\'') ),
                            'TVE' => array( 'name' => self::_('Tverskaya oblast\'') ),
                            'TYU' => array( 'name' => self::_('Tyumenskaya oblast\'') ),
                            'ULY' => array( 'name' => self::_('Ul\'yanovskaya oblast\'') ),
                            'VLA' => array( 'name' => self::_('Vladimirskaya oblast\'') ),
                            'VGG' => array( 'name' => self::_('Volgogradskaya oblast\'') ),
                            'VLG' => array( 'name' => self::_('Vologodskaya oblast\'') ),
                            'VOR' => array( 'name' => self::_('Voronezhskaya oblast\'') ),
                            'YAR' => array( 'name' => self::_('Yaroslavskaya oblast\'') ),
                            'MOW' => array( 'name' => self::_('Moskva') ),
                            'SPE' => array( 'name' => self::_('Sankt-Peterburg') ),
                            'YEV' => array( 'name' => self::_('Yevreyskaya avtonomnaya oblast\'') ),
                            'CHU' => array( 'name' => self::_('Chukotskiy avtonomnyy okrug') ),
                            'KHM' => array( 'name' => self::_('Khanty-Mansiysky avtonomnyy okrug-Yugra') ),
                            'NEN' => array( 'name' => self::_('Nenetskiy avtonomnyy okrug') ),
                            'YAN' => array( 'name' => self::_('Yamalo-Nenetskiy avtonomnyy okrug') )
                        ) );
                    break;
                case 'RW':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Ville de Kigali') ),
                            '02' => array( 'name' => self::_('Est') ),
                            '03' => array( 'name' => self::_('Nord') ),
                            '04' => array( 'name' => self::_('Ouest') ),
                            '05' => array( 'name' => self::_('Sud') )
                        ) );
                    break;
                case 'SA':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '11' => array( 'name' => self::_('Al Bāhah') ),
                            '08' => array( 'name' => self::_('Al Ḥudūd ash Shamāliyah') ),
                            '12' => array( 'name' => self::_('Al Jawf') ),
                            '03' => array( 'name' => self::_('Al Madīnah') ),
                            '05' => array( 'name' => self::_('Al Qaşīm') ),
                            '01' => array( 'name' => self::_('Ar Riyāḍ') ),
                            '04' => array( 'name' => self::_('Ash Sharqīyah') ),
                            '14' => array( 'name' => self::_('`Asīr') ),
                            '06' => array( 'name' => self::_('Ḥā\'il') ),
                            '09' => array( 'name' => self::_('Jīzan') ),
                            '02' => array( 'name' => self::_('Makkah') ),
                            '10' => array( 'name' => self::_('Najrān') ),
                            '07' => array( 'name' => self::_('Tabūk') )
                        ) );
                    break;
                case 'SB':
                    return array(
                        'regions_label'    => self::_('Capital territory'),
                        'subregions_label' => self::_('Province'),
                        'regions'          => array(
                            'CT' => array( 'name' => self::_('Capital Territory (Honiara)') ),
                            'CE' => array( 'name' => self::_('Central') ),
                            'CH' => array( 'name' => self::_('Choiseul') ),
                            'GU' => array( 'name' => self::_('Guadalcanal') ),
                            'IS' => array( 'name' => self::_('Isabel') ),
                            'MK' => array( 'name' => self::_('Makira') ),
                            'ML' => array( 'name' => self::_('Malaita') ),
                            'RB' => array( 'name' => self::_('Rennell and Bellona') ),
                            'TE' => array( 'name' => self::_('Temotu') ),
                            'WE' => array( 'name' => self::_('Western') )
                        ) );
                    break;
                case 'SC':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Anse aux Pins') ),
                            '02' => array( 'name' => self::_('Anse Boileau') ),
                            '03' => array( 'name' => self::_('Anse Etoile') ),
                            '04' => array( 'name' => self::_('Anse Louis') ),
                            '05' => array( 'name' => self::_('Anse Royale') ),
                            '06' => array( 'name' => self::_('Baie Lazare') ),
                            '07' => array( 'name' => self::_('Baie Sainte Anne') ),
                            '08' => array( 'name' => self::_('Beau Vallon') ),
                            '09' => array( 'name' => self::_('Bel Air') ),
                            '10' => array( 'name' => self::_('Bel Ombre') ),
                            '11' => array( 'name' => self::_('Cascade') ),
                            '12' => array( 'name' => self::_('Glacis') ),
                            '13' => array( 'name' => self::_('Grand Anse Mahe') ),
                            '14' => array( 'name' => self::_('Grand Anse Praslin') ),
                            '15' => array( 'name' => self::_('La Digue') ),
                            '16' => array( 'name' => self::_('English River') ),
                            '24' => array( 'name' => self::_('Les Mamelles') ),
                            '17' => array( 'name' => self::_('Mont Buxton') ),
                            '18' => array( 'name' => self::_('Mont Fleuri') ),
                            '19' => array( 'name' => self::_('Plaisance') ),
                            '20' => array( 'name' => self::_('Pointe Larue') ),
                            '21' => array( 'name' => self::_('Port Glaud') ),
                            '25' => array( 'name' => self::_('Roche Caiman') ),
                            '22' => array( 'name' => self::_('Saint Louis') ),
                            '23' => array( 'name' => self::_('Takamaka') )
                        ) );
                    break;
                case 'SD':
                    return array(
                        'regions_label' => self::_('state'),
                        'regions'       => array(
                            '26' => array( 'name' => self::_('Al Baḩr al Aḩmar') ),
                            '18' => array( 'name' => self::_('Al Buḩayrāt') ),
                            '07' => array( 'name' => self::_('Al Jazīrah') ),
                            '03' => array( 'name' => self::_('Al Kharţūm') ),
                            '06' => array( 'name' => self::_('Al Qaḑārif') ),
                            '22' => array( 'name' => self::_('Al Waḩdah') ),
                            '04' => array( 'name' => self::_('An Nīl') ),
                            '08' => array( 'name' => self::_('An Nīl al Abyaḑ') ),
                            '24' => array( 'name' => self::_('An Nīl al Azraq') ),
                            '01' => array( 'name' => self::_('Ash Shamālīyah') ),
                            '23' => array( 'name' => self::_('A\'ālī an Nīl') ),
                            '17' => array( 'name' => self::_('Baḩr al Jabal') ),
                            '16' => array( 'name' => self::_('Gharb al Istiwā\'īyah') ),
                            '14' => array( 'name' => self::_('Gharb Baḩr al Ghazāl') ),
                            '12' => array( 'name' => self::_('Gharb Dārfūr') ),
                            '11' => array( 'name' => self::_('Janūb Dārfūr') ),
                            '13' => array( 'name' => self::_('Janūb Kurdufān') ),
                            '20' => array( 'name' => self::_('Jūnqalī') ),
                            '05' => array( 'name' => self::_('Kassalā') ),
                            '15' => array( 'name' => self::_('Shamāl Baḩr al Ghazāl') ),
                            '02' => array( 'name' => self::_('Shamāl Dārfūr') ),
                            '09' => array( 'name' => self::_('Shamāl Kurdufān') ),
                            '19' => array( 'name' => self::_('Sharq al Istiwā\'īyah') ),
                            '25' => array( 'name' => self::_('Sinnār') ),
                            '21' => array( 'name' => self::_('Wārāb') )
                        ) );
                    break;
                case 'SE':
                    return array(
                        'regions_label' => self::_('County'),
                        'regions'       => array(
                            'K'  => array( 'name' => self::_('Blekinge län') ),
                            'W'  => array( 'name' => self::_('Dalarnas län') ),
                            'I'  => array( 'name' => self::_('Gotlands län') ),
                            'X'  => array( 'name' => self::_('Gävleborgs län') ),
                            'N'  => array( 'name' => self::_('Hallands län') ),
                            'Z'  => array( 'name' => self::_('Jämtlande län') ),
                            'F'  => array( 'name' => self::_('Jönköpings län') ),
                            'H'  => array( 'name' => self::_('Kalmar län') ),
                            'G'  => array( 'name' => self::_('Kronobergs län') ),
                            'BD' => array( 'name' => self::_('Norrbottens län') ),
                            'M'  => array( 'name' => self::_('Skåne län') ),
                            'AB' => array( 'name' => self::_('Stockholms län') ),
                            'D'  => array( 'name' => self::_('Södermanlands län') ),
                            'C'  => array( 'name' => self::_('Uppsala län') ),
                            'S'  => array( 'name' => self::_('Värmlands län') ),
                            'AC' => array( 'name' => self::_('Västerbottens län') ),
                            'Y'  => array( 'name' => self::_('Västernorrlands län') ),
                            'U'  => array( 'name' => self::_('Västmanlands län') ),
                            'Q'  => array( 'name' => self::_('Västra Götalands län') ),
                            'T'  => array( 'name' => self::_('Örebro län') ),
                            'E'  => array( 'name' => self::_('Östergötlands län') )
                        ) );
                    break;
                case 'SG':
                    return array(
                        'regions_label' => self::_('district'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Central Singapore') ),
                            '02' => array( 'name' => self::_('North East') ),
                            '03' => array( 'name' => self::_('North West') ),
                            '04' => array( 'name' => self::_('South East') ),
                            '05' => array( 'name' => self::_('South West') )
                        ) );
                    break;
                case 'SI':
                    return array(
                        'regions_label' => self::_('Municipalities'),
                        'regions'       => array(
                            '001' => array( 'name' => self::_('Ajdovščina') ),
                            '195' => array( 'name' => self::_('Apače') ),
                            '002' => array( 'name' => self::_('Beltinci') ),
                            '148' => array( 'name' => self::_('Benedikt') ),
                            '149' => array( 'name' => self::_('Bistrica ob Sotli') ),
                            '003' => array( 'name' => self::_('Bled') ),
                            '150' => array( 'name' => self::_('Bloke') ),
                            '004' => array( 'name' => self::_('Bohinj') ),
                            '005' => array( 'name' => self::_('Borovnica') ),
                            '006' => array( 'name' => self::_('Bovec') ),
                            '151' => array( 'name' => self::_('Braslovče') ),
                            '007' => array( 'name' => self::_('Brda') ),
                            '008' => array( 'name' => self::_('Brezovica') ),
                            '009' => array( 'name' => self::_('Brežice') ),
                            '152' => array( 'name' => self::_('Cankova') ),
                            '011' => array( 'name' => self::_('Celje') ),
                            '012' => array( 'name' => self::_('Cerklje na Gorenjskem') ),
                            '013' => array( 'name' => self::_('Cerknica') ),
                            '014' => array( 'name' => self::_('Cerkno') ),
                            '153' => array( 'name' => self::_('Cerkvenjak') ),
                            '196' => array( 'name' => self::_('Cirkulane') ),
                            '015' => array( 'name' => self::_('Črenšovci') ),
                            '016' => array( 'name' => self::_('Črna na Koroškem') ),
                            '017' => array( 'name' => self::_('Črnomelj') ),
                            '018' => array( 'name' => self::_('Destrnik') ),
                            '019' => array( 'name' => self::_('Divača') ),
                            '154' => array( 'name' => self::_('Dobje') ),
                            '020' => array( 'name' => self::_('Dobrepolje') ),
                            '155' => array( 'name' => self::_('Dobrna') ),
                            '021' => array( 'name' => self::_('Dobrova-Polhov Gradec') ),
                            '156' => array( 'name' => self::_('Dobrovnik/Dobronak') ),
                            '022' => array( 'name' => self::_('Dol pri Ljubljani') ),
                            '157' => array( 'name' => self::_('Dolenjske Toplice') ),
                            '023' => array( 'name' => self::_('Domžale') ),
                            '024' => array( 'name' => self::_('Dornava') ),
                            '025' => array( 'name' => self::_('Dravograd') ),
                            '026' => array( 'name' => self::_('Duplek') ),
                            '027' => array( 'name' => self::_('Gorenja vas-Poljane') ),
                            '028' => array( 'name' => self::_('Gorišnica') ),
                            '207' => array( 'name' => self::_('Gorje') ),
                            '029' => array( 'name' => self::_('Gornja Radgona') ),
                            '030' => array( 'name' => self::_('Gornji Grad') ),
                            '031' => array( 'name' => self::_('Gornji Petrovci') ),
                            '158' => array( 'name' => self::_('Grad') ),
                            '032' => array( 'name' => self::_('Grosuplje') ),
                            '159' => array( 'name' => self::_('Hajdina') ),
                            '160' => array( 'name' => self::_('Hoče-Slivnica') ),
                            '161' => array( 'name' => self::_('Hodoš/Hodos') ),
                            '162' => array( 'name' => self::_('Horjul') ),
                            '034' => array( 'name' => self::_('Hrastnik') ),
                            '035' => array( 'name' => self::_('Hrpelje-Kozina') ),
                            '036' => array( 'name' => self::_('Idrija') ),
                            '037' => array( 'name' => self::_('Ig') ),
                            '038' => array( 'name' => self::_('Ilirska Bistrica') ),
                            '039' => array( 'name' => self::_('Ivančna Gorica') ),
                            '040' => array( 'name' => self::_('Izola/Isola') ),
                            '041' => array( 'name' => self::_('Jesenice') ),
                            '163' => array( 'name' => self::_('Jezersko') ),
                            '042' => array( 'name' => self::_('Juršinci') ),
                            '043' => array( 'name' => self::_('Kamnik') ),
                            '044' => array( 'name' => self::_('Kanal') ),
                            '045' => array( 'name' => self::_('Kidričevo') ),
                            '046' => array( 'name' => self::_('Kobarid') ),
                            '047' => array( 'name' => self::_('Kobilje') ),
                            '048' => array( 'name' => self::_('Kočevje') ),
                            '049' => array( 'name' => self::_('Komen') ),
                            '164' => array( 'name' => self::_('Komenda') ),
                            '050' => array( 'name' => self::_('Koper/Capodistria') ),
                            '197' => array( 'name' => self::_('Kosanjevica na Krki') ),
                            '165' => array( 'name' => self::_('Kostel') ),
                            '051' => array( 'name' => self::_('Kozje') ),
                            '052' => array( 'name' => self::_('Kranj') ),
                            '053' => array( 'name' => self::_('Kranjska Gora') ),
                            '166' => array( 'name' => self::_('Križevci') ),
                            '054' => array( 'name' => self::_('Krško') ),
                            '055' => array( 'name' => self::_('Kungota') ),
                            '056' => array( 'name' => self::_('Kuzma') ),
                            '057' => array( 'name' => self::_('Laško') ),
                            '058' => array( 'name' => self::_('Lenart') ),
                            '059' => array( 'name' => self::_('Lendava/Lendva') ),
                            '060' => array( 'name' => self::_('Litija') ),
                            '061' => array( 'name' => self::_('Ljubljana') ),
                            '062' => array( 'name' => self::_('Ljubno') ),
                            '063' => array( 'name' => self::_('Ljutomer') ),
                            '208' => array( 'name' => self::_('Log-Dragomer') ),
                            '064' => array( 'name' => self::_('Logatec') ),
                            '065' => array( 'name' => self::_('Loška dolina') ),
                            '066' => array( 'name' => self::_('Loški Potok') ),
                            '167' => array( 'name' => self::_('Lovrenc na Pohorju') ),
                            '067' => array( 'name' => self::_('Luče') ),
                            '068' => array( 'name' => self::_('Lukovica') ),
                            '069' => array( 'name' => self::_('Majšperk') ),
                            '198' => array( 'name' => self::_('Makole') ),
                            '070' => array( 'name' => self::_('Maribor') ),
                            '168' => array( 'name' => self::_('Markovci') ),
                            '071' => array( 'name' => self::_('Medvode') ),
                            '072' => array( 'name' => self::_('Mengeš') ),
                            '073' => array( 'name' => self::_('Metlika') ),
                            '074' => array( 'name' => self::_('Mežica') ),
                            '169' => array( 'name' => self::_('Miklavž na Dravskem polju') ),
                            '075' => array( 'name' => self::_('Miren-Kostanjevica') ),
                            '170' => array( 'name' => self::_('Mirna Peč') ),
                            '076' => array( 'name' => self::_('Mislinja') ),
                            '199' => array( 'name' => self::_('Mokronog-Trebelno') ),
                            '077' => array( 'name' => self::_('Moravče') ),
                            '078' => array( 'name' => self::_('Moravske Toplice') ),
                            '079' => array( 'name' => self::_('Mozirje') ),
                            '080' => array( 'name' => self::_('Murska Sobota') ),
                            '081' => array( 'name' => self::_('Muta') ),
                            '082' => array( 'name' => self::_('Naklo') ),
                            '083' => array( 'name' => self::_('Nazarje') ),
                            '084' => array( 'name' => self::_('Nova Gorica') ),
                            '085' => array( 'name' => self::_('Novo mesto') ),
                            '086' => array( 'name' => self::_('Odranci') ),
                            '171' => array( 'name' => self::_('Oplotnica') ),
                            '087' => array( 'name' => self::_('Ormož') ),
                            '088' => array( 'name' => self::_('Osilnica') ),
                            '089' => array( 'name' => self::_('Pesnica') ),
                            '090' => array( 'name' => self::_('Piran/Pirano') ),
                            '091' => array( 'name' => self::_('Pivka') ),
                            '092' => array( 'name' => self::_('Podčetrtek') ),
                            '172' => array( 'name' => self::_('Podlehnik') ),
                            '093' => array( 'name' => self::_('Podvelka') ),
                            '200' => array( 'name' => self::_('Poljčane') ),
                            '173' => array( 'name' => self::_('Polzela') ),
                            '094' => array( 'name' => self::_('Postojna') ),
                            '174' => array( 'name' => self::_('Prebold') ),
                            '095' => array( 'name' => self::_('Preddvor') ),
                            '175' => array( 'name' => self::_('Prevalje') ),
                            '096' => array( 'name' => self::_('Ptuj') ),
                            '097' => array( 'name' => self::_('Puconci') ),
                            '098' => array( 'name' => self::_('Rače-Fram') ),
                            '099' => array( 'name' => self::_('Radeče') ),
                            '100' => array( 'name' => self::_('Radenci') ),
                            '101' => array( 'name' => self::_('Radlje ob Dravi') ),
                            '102' => array( 'name' => self::_('Radovljica') ),
                            '103' => array( 'name' => self::_('Ravne na Koroškem') ),
                            '176' => array( 'name' => self::_('Razkrižje') ),
                            '209' => array( 'name' => self::_('Rečica ob Savinji') ),
                            '201' => array( 'name' => self::_('Renče-Vogrsko') ),
                            '104' => array( 'name' => self::_('Ribnica') ),
                            '177' => array( 'name' => self::_('Ribnica na Pohorju') ),
                            '106' => array( 'name' => self::_('Rogaška Slatina') ),
                            '105' => array( 'name' => self::_('Rogašovci') ),
                            '107' => array( 'name' => self::_('Rogatec') ),
                            '108' => array( 'name' => self::_('Ruše') ),
                            '178' => array( 'name' => self::_('Selnica ob Dravi') ),
                            '109' => array( 'name' => self::_('Semič') ),
                            '110' => array( 'name' => self::_('Sevnica') ),
                            '111' => array( 'name' => self::_('Sežana') ),
                            '112' => array( 'name' => self::_('Slovenj Gradec') ),
                            '113' => array( 'name' => self::_('Slovenska Bistrica') ),
                            '114' => array( 'name' => self::_('Slovenske Konjice') ),
                            '179' => array( 'name' => self::_('Sodražica') ),
                            '180' => array( 'name' => self::_('Solčava') ),
                            '202' => array( 'name' => self::_('Središče ob Dravi') ),
                            '115' => array( 'name' => self::_('Starče') ),
                            '203' => array( 'name' => self::_('Straža') ),
                            '181' => array( 'name' => self::_('Sveta Ana') ),
                            '204' => array( 'name' => self::_('Sveta Trojica v Slovenskih Goricah') ),
                            '182' => array( 'name' => self::_('Sveta Andraž v Slovenskih Goricah') ),
                            '116' => array( 'name' => self::_('Sveti Jurij') ),
                            '210' => array( 'name' => self::_('Sveti Jurij v Slovenskih Goricah') ),
                            '205' => array( 'name' => self::_('Sveti Tomaž') ),
                            '033' => array( 'name' => self::_('Šalovci') ),
                            '183' => array( 'name' => self::_('Šempeter-Vrtojba') ),
                            '117' => array( 'name' => self::_('Šenčur') ),
                            '118' => array( 'name' => self::_('Šentilj') ),
                            '119' => array( 'name' => self::_('Šentjernej') ),
                            '120' => array( 'name' => self::_('Šentjur') ),
                            '211' => array( 'name' => self::_('Šentrupert') ),
                            '121' => array( 'name' => self::_('Škocjan') ),
                            '122' => array( 'name' => self::_('Škofja Loka') ),
                            '123' => array( 'name' => self::_('Škofljica') ),
                            '124' => array( 'name' => self::_('Šmarje pri Jelšah') ),
                            '206' => array( 'name' => self::_('Šmarjeske Topliče') ),
                            '125' => array( 'name' => self::_('Šmartno ob Paki') ),
                            '194' => array( 'name' => self::_('Šmartno pri Litiji') ),
                            '126' => array( 'name' => self::_('Šoštanj') ),
                            '127' => array( 'name' => self::_('Štore') ),
                            '184' => array( 'name' => self::_('Tabor') ),
                            '010' => array( 'name' => self::_('Tišina') ),
                            '128' => array( 'name' => self::_('Tolmin') ),
                            '129' => array( 'name' => self::_('Trbovlje') ),
                            '130' => array( 'name' => self::_('Trebnje') ),
                            '185' => array( 'name' => self::_('Trnovska vas') ),
                            '186' => array( 'name' => self::_('Trzin') ),
                            '131' => array( 'name' => self::_('Tržič') ),
                            '132' => array( 'name' => self::_('Turnišče') ),
                            '133' => array( 'name' => self::_('Velenje') ),
                            '187' => array( 'name' => self::_('Velika Polana') ),
                            '134' => array( 'name' => self::_('Velike Lašče') ),
                            '188' => array( 'name' => self::_('Veržej') ),
                            '135' => array( 'name' => self::_('Videm') ),
                            '136' => array( 'name' => self::_('Vipava') ),
                            '137' => array( 'name' => self::_('Vitanje') ),
                            '138' => array( 'name' => self::_('Vodice') ),
                            '139' => array( 'name' => self::_('Vojnik') ),
                            '189' => array( 'name' => self::_('Vransko') ),
                            '140' => array( 'name' => self::_('Vrhnika') ),
                            '141' => array( 'name' => self::_('Vuzenica') ),
                            '142' => array( 'name' => self::_('Zagorje ob Savi') ),
                            '143' => array( 'name' => self::_('Zavrč') ),
                            '144' => array( 'name' => self::_('Zreče') ),
                            '190' => array( 'name' => self::_('Žalec') ),
                            '146' => array( 'name' => self::_('Železniki') ),
                            '191' => array( 'name' => self::_('Žetale') ),
                            '147' => array( 'name' => self::_('Žiri') ),
                            '192' => array( 'name' => self::_('Žirovnica') ),
                            '193' => array( 'name' => self::_('Žužemberk') )
                        ) );
                    break;
                case 'SK':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'BC' => array( 'name' => self::_('Banskobystrický kraj') ),
                            'BL' => array( 'name' => self::_('Bratislavský kraj') ),
                            'KI' => array( 'name' => self::_('Košický kraj') ),
                            'NJ' => array( 'name' => self::_('Nitriansky kraj') ),
                            'PV' => array( 'name' => self::_('Prešovský kraj') ),
                            'TC' => array( 'name' => self::_('Trenčiansky kraj') ),
                            'TA' => array( 'name' => self::_('Trnavský kraj') ),
                            'ZI' => array( 'name' => self::_('Žilinský kraj') )
                        ) );
                    break;
                case 'SL':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'W' => array( 'name' => self::_('Western Area (Freetown)') ),
                            'E' => array( 'name' => self::_('Eastern') ),
                            'N' => array( 'name' => self::_('Northern') ),
                            'S' => array( 'name' => self::_('Southern (Sierra Leone)') )
                        ) );
                    break;
                case 'SM':
                    return array(
                        'regions_label' => self::_('Municipalities'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Acquaviva') ),
                            '06' => array( 'name' => self::_('Borgo Maggiore') ),
                            '02' => array( 'name' => self::_('Chiesanuova') ),
                            '03' => array( 'name' => self::_('Domagnano') ),
                            '04' => array( 'name' => self::_('Faetano') ),
                            '05' => array( 'name' => self::_('Fiorentino') ),
                            '08' => array( 'name' => self::_('Montegiardino') ),
                            '07' => array( 'name' => self::_('San Marino') ),
                            '09' => array( 'name' => self::_('Serravalle') )
                        ) );
                    break;
                case 'SN':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'DK' => array( 'name' => self::_('Dakar') ),
                            'DB' => array( 'name' => self::_('Diourbel') ),
                            'FK' => array( 'name' => self::_('Fatick') ),
                            'KA' => array( 'name' => self::_('Kaffrine') ),
                            'KL' => array( 'name' => self::_('Kaolack') ),
                            'KE' => array( 'name' => self::_('Kédougou') ),
                            'KD' => array( 'name' => self::_('Kolda') ),
                            'LG' => array( 'name' => self::_('Louga') ),
                            'MT' => array( 'name' => self::_('Matam') ),
                            'SL' => array( 'name' => self::_('Saint-Louis') ),
                            'SE' => array( 'name' => self::_('Sédhiou') ),
                            'TC' => array( 'name' => self::_('Tambacounda') ),
                            'TH' => array( 'name' => self::_('Thiès') ),
                            'ZG' => array( 'name' => self::_('Ziguinchor') )
                        ) );
                    break;
                case 'SO':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'AW' => array( 'name' => self::_('Awdal') ),
                            'BK' => array( 'name' => self::_('Bakool') ),
                            'BN' => array( 'name' => self::_('Banaadir') ),
                            'BR' => array( 'name' => self::_('Bari') ),
                            'BY' => array( 'name' => self::_('Bay') ),
                            'GA' => array( 'name' => self::_('Galguduud') ),
                            'GE' => array( 'name' => self::_('Gedo') ),
                            'HI' => array( 'name' => self::_('Hiirsan') ),
                            'JD' => array( 'name' => self::_('Jubbada Dhexe') ),
                            'JH' => array( 'name' => self::_('Jubbada Hoose') ),
                            'MU' => array( 'name' => self::_('Mudug') ),
                            'NU' => array( 'name' => self::_('Nugaal') ),
                            'SA' => array( 'name' => self::_('Saneag') ),
                            'SD' => array( 'name' => self::_('Shabeellaha Dhexe') ),
                            'SH' => array( 'name' => self::_('Shabeellaha Hoose') ),
                            'SO' => array( 'name' => self::_('Sool') ),
                            'TO' => array( 'name' => self::_('Togdheer') ),
                            'WO' => array( 'name' => self::_('Woqooyi Galbeed') )
                        ) );
                    break;
                case 'SR':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'BR' => array( 'name' => self::_('Brokopondo') ),
                            'CM' => array( 'name' => self::_('Commewijne') ),
                            'CR' => array( 'name' => self::_('Coronie') ),
                            'MA' => array( 'name' => self::_('Marowijne') ),
                            'NI' => array( 'name' => self::_('Nickerie') ),
                            'PR' => array( 'name' => self::_('Para') ),
                            'PM' => array( 'name' => self::_('Paramaribo') ),
                            'SA' => array( 'name' => self::_('Saramacca') ),
                            'SI' => array( 'name' => self::_('Sipaliwini') ),
                            'WA' => array( 'name' => self::_('Wanica') )
                        ) );
                    break;
                case 'ST':
                    return array(
                        'regions_label' => self::_('Municipality'),
                        'regions'       => array(
                            'P' => array( 'name' => self::_('Príncipe') ),
                            'S' => array( 'name' => self::_('São Tomé') )
                        ) );
                    break;
                case 'SV':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'AH' => array( 'name' => self::_('Ahuachapán') ),
                            'CA' => array( 'name' => self::_('Cabañas') ),
                            'CU' => array( 'name' => self::_('Cuscatlán') ),
                            'CH' => array( 'name' => self::_('Chalatenango') ),
                            'LI' => array( 'name' => self::_('La Libertad') ),
                            'PA' => array( 'name' => self::_('La Paz') ),
                            'UN' => array( 'name' => self::_('La Unión') ),
                            'MO' => array( 'name' => self::_('Morazán') ),
                            'SM' => array( 'name' => self::_('San Miguel') ),
                            'SS' => array( 'name' => self::_('San Salvador') ),
                            'SA' => array( 'name' => self::_('Santa Ana') ),
                            'SV' => array( 'name' => self::_('San Vicente') ),
                            'SO' => array( 'name' => self::_('Sonsonate') ),
                            'US' => array( 'name' => self::_('Usulután') )
                        ) );
                    break;
                case 'SY':
                    return array(
                        'regions_label' => self::_('Governorate'),
                        'regions'       => array(
                            'HA' => array( 'name' => self::_('Al Hasakah') ),
                            'LA' => array( 'name' => self::_('Al Ladhiqiyah') ),
                            'QU' => array( 'name' => self::_('Al Qunaytirah') ),
                            'RA' => array( 'name' => self::_('Ar Raqqah') ),
                            'SU' => array( 'name' => self::_('As Suwayda\'') ),
                            'DR' => array( 'name' => self::_('Dar\'a') ),
                            'DY' => array( 'name' => self::_('Dayr az Zawr') ),
                            'DI' => array( 'name' => self::_('Dimashq') ),
                            'HL' => array( 'name' => self::_('Halab') ),
                            'HM' => array( 'name' => self::_('Hamah') ),
                            'HI' => array( 'name' => self::_('Homs') ),
                            'ID' => array( 'name' => self::_('Idlib') ),
                            'RD' => array( 'name' => self::_('Rif Dimashq') ),
                            'TA' => array( 'name' => self::_('Tartus') )
                        ) );
                    break;
                case 'SZ':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'HH' => array( 'name' => self::_('Hhohho') ),
                            'LU' => array( 'name' => self::_('Lubombo') ),
                            'MA' => array( 'name' => self::_('Manzini') ),
                            'SH' => array( 'name' => self::_('Shiselweni') )
                        ) );
                    break;
                case 'TD':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'BA' => array( 'name' => self::_('Al Baṭḩah') ),
                            'LC' => array( 'name' => self::_('Al Buḩayrah') ),
                            'BG' => array( 'name' => self::_('Baḩr al Ghazāl') ),
                            'BO' => array( 'name' => self::_('Būrkū') ),
                            'HL' => array( 'name' => self::_('Ḥajjar Lamīs') ),
                            'EN' => array( 'name' => self::_('Innīdī') ),
                            'KA' => array( 'name' => self::_('Kānim') ),
                            'LO' => array( 'name' => self::_('Lūqūn al Gharbī') ),
                            'LR' => array( 'name' => self::_('Lūqūn ash Sharqī') ),
                            'ND' => array( 'name' => self::_('Madīnat Injamīnā') ),
                            'MA' => array( 'name' => self::_('Māndūl') ),
                            'MO' => array( 'name' => self::_('Māyū Kībbī al Gharbī') ),
                            'ME' => array( 'name' => self::_('Māyū Kībbī ash Sharqī') ),
                            'GR' => array( 'name' => self::_('Qīrā') ),
                            'SA' => array( 'name' => self::_('Salāmāt') ),
                            'MC' => array( 'name' => self::_('Shārī al Awsaṭ') ),
                            'CB' => array( 'name' => self::_('Shārī Bāqirmī') ),
                            'SI' => array( 'name' => self::_('Sīlā') ),
                            'TA' => array( 'name' => self::_('Tānjilī') ),
                            'TI' => array( 'name' => self::_('Tibastī') ),
                            'OD' => array( 'name' => self::_('Waddāy') ),
                            'WF' => array( 'name' => self::_('Wādī Fīrā') )
                        ) );
                    break;
                case 'TG':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'C' => array( 'name' => self::_('Région du Centre') ),
                            'K' => array( 'name' => self::_('Région de la Kara') ),
                            'M' => array( 'name' => self::_('Région Maritime') ),
                            'P' => array( 'name' => self::_('Région des Plateaux') ),
                            'S' => array( 'name' => self::_('Région des Savannes') )
                        ) );
                    break;
                case 'TH':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '10' => array( 'name' => self::_('Krung Thep Maha Nakhon Bangkok') ),
                            'S'  => array( 'name' => self::_('Phatthaya') ),
                            '37' => array( 'name' => self::_('Amnat Charoen') ),
                            '15' => array( 'name' => self::_('Ang Thong') ),
                            '31' => array( 'name' => self::_('Buri Ram') ),
                            '24' => array( 'name' => self::_('Chachoengsao') ),
                            '18' => array( 'name' => self::_('Chai Nat') ),
                            '36' => array( 'name' => self::_('Chaiyaphum') ),
                            '22' => array( 'name' => self::_('Chanthaburi') ),
                            '50' => array( 'name' => self::_('Chiang Mai') ),
                            '57' => array( 'name' => self::_('Chiang Rai') ),
                            '20' => array( 'name' => self::_('Chon Buri') ),
                            '86' => array( 'name' => self::_('Chumphon') ),
                            '46' => array( 'name' => self::_('Kalasin') ),
                            '62' => array( 'name' => self::_('Kamphaeng Phet') ),
                            '71' => array( 'name' => self::_('Kanchanaburi') ),
                            '40' => array( 'name' => self::_('Khon Kaen') ),
                            '81' => array( 'name' => self::_('Krabi') ),
                            '52' => array( 'name' => self::_('Lampang') ),
                            '51' => array( 'name' => self::_('Lamphun') ),
                            '42' => array( 'name' => self::_('Loei') ),
                            '16' => array( 'name' => self::_('Lop Buri') ),
                            '58' => array( 'name' => self::_('Mae Hong Son') ),
                            '44' => array( 'name' => self::_('Maha Sarakham') ),
                            '49' => array( 'name' => self::_('Mukdahan') ),
                            '26' => array( 'name' => self::_('Nakhon Nayok') ),
                            '73' => array( 'name' => self::_('Nakhon Pathom') ),
                            '48' => array( 'name' => self::_('Nakhon Phanom') ),
                            '30' => array( 'name' => self::_('Nakhon Ratchasima') ),
                            '60' => array( 'name' => self::_('Nakhon Sawan') ),
                            '80' => array( 'name' => self::_('Nakhon Si Thammarat') ),
                            '55' => array( 'name' => self::_('Nan') ),
                            '96' => array( 'name' => self::_('Narathiwat') ),
                            '39' => array( 'name' => self::_('Nong Bua Lam Phu') ),
                            '43' => array( 'name' => self::_('Nong Khai') ),
                            '12' => array( 'name' => self::_('Nonthaburi') ),
                            '13' => array( 'name' => self::_('Pathum Thani') ),
                            '94' => array( 'name' => self::_('Pattani') ),
                            '82' => array( 'name' => self::_('Phangnga') ),
                            '93' => array( 'name' => self::_('Phatthalung') ),
                            '56' => array( 'name' => self::_('Phayao') ),
                            '67' => array( 'name' => self::_('Phetchabun') ),
                            '76' => array( 'name' => self::_('Phetchaburi') ),
                            '66' => array( 'name' => self::_('Phichit') ),
                            '65' => array( 'name' => self::_('Phitsanulok') ),
                            '54' => array( 'name' => self::_('Phrae') ),
                            '14' => array( 'name' => self::_('Phra Nakhon Si Ayutthaya') ),
                            '83' => array( 'name' => self::_('Phuket') ),
                            '25' => array( 'name' => self::_('Prachin Buri') ),
                            '77' => array( 'name' => self::_('Prachuap Khiri Khan') ),
                            '85' => array( 'name' => self::_('Ranong') ),
                            '70' => array( 'name' => self::_('Ratchaburi') ),
                            '21' => array( 'name' => self::_('Rayong') ),
                            '45' => array( 'name' => self::_('Roi Et') ),
                            '27' => array( 'name' => self::_('Sa Kaeo') ),
                            '47' => array( 'name' => self::_('Sakon Nakhon') ),
                            '11' => array( 'name' => self::_('Samut Prakan') ),
                            '74' => array( 'name' => self::_('Samut Sakhon') ),
                            '75' => array( 'name' => self::_('Samut Songkhram') ),
                            '19' => array( 'name' => self::_('Saraburi') ),
                            '91' => array( 'name' => self::_('Satun') ),
                            '17' => array( 'name' => self::_('Sing Buri') ),
                            '33' => array( 'name' => self::_('Si Sa Ket') ),
                            '90' => array( 'name' => self::_('Songkhla') ),
                            '64' => array( 'name' => self::_('Sukhothai') ),
                            '72' => array( 'name' => self::_('Suphan Buri') ),
                            '84' => array( 'name' => self::_('Surat Thani') ),
                            '32' => array( 'name' => self::_('Surin') ),
                            '63' => array( 'name' => self::_('Tak') ),
                            '92' => array( 'name' => self::_('Trang') ),
                            '23' => array( 'name' => self::_('Trat') ),
                            '34' => array( 'name' => self::_('Ubon Ratchathani') ),
                            '41' => array( 'name' => self::_('Udon Thani') ),
                            '61' => array( 'name' => self::_('Uthai Thani') ),
                            '53' => array( 'name' => self::_('Uttaradit') ),
                            '95' => array( 'name' => self::_('Yala') ),
                            '35' => array( 'name' => self::_('Yasothon') )
                        ) );
                    break;
                case 'TJ':
                    return array(
                        'regions_label'    => self::_('Autonomous region'),
                        'subregions_label' => self::_('Region'),
                        'regions'          => array(
                            'GB' => array( 'name' => self::_('Gorno-Badakhshan') ),
                            'KT' => array( 'name' => self::_('Khatlon') ),
                            'SU' => array( 'name' => self::_('Sughd') )
                        ) );
                    break;
                case 'TL':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'AL' => array( 'name' => self::_('Aileu') ),
                            'AN' => array( 'name' => self::_('Ainaro') ),
                            'BA' => array( 'name' => self::_('Baucau') ),
                            'BO' => array( 'name' => self::_('Bobonaro') ),
                            'CO' => array( 'name' => self::_('Cova Lima') ),
                            'DI' => array( 'name' => self::_('Dili') ),
                            'ER' => array( 'name' => self::_('Ermera') ),
                            'LA' => array( 'name' => self::_('Lautem') ),
                            'LI' => array( 'name' => self::_('Liquiça') ),
                            'MT' => array( 'name' => self::_('Manatuto') ),
                            'MF' => array( 'name' => self::_('Manufahi') ),
                            'OE' => array( 'name' => self::_('Oecussi') ),
                            'VI' => array( 'name' => self::_('Viqueque') )
                        ) );
                    break;
                case 'TM':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'A' => array( 'name' => self::_('Ahal') ),
                            'B' => array( 'name' => self::_('Balkan') ),
                            'D' => array( 'name' => self::_('Daşoguz') ),
                            'L' => array( 'name' => self::_('Lebap') ),
                            'M' => array( 'name' => self::_('Mary') ),
                            'S' => array( 'name' => self::_('Aşgabat') )
                        ) );
                    break;
                case 'TN':
                    return array(
                        'regions_label' => self::_('Governorate'),
                        'regions'       => array(
                            '31' => array( 'name' => self::_('Béja') ),
                            '13' => array( 'name' => self::_('Ben Arous') ),
                            '23' => array( 'name' => self::_('Bizerte') ),
                            '81' => array( 'name' => self::_('Gabès') ),
                            '71' => array( 'name' => self::_('Gafsa') ),
                            '32' => array( 'name' => self::_('Jendouba') ),
                            '41' => array( 'name' => self::_('Kairouan') ),
                            '42' => array( 'name' => self::_('Kasserine') ),
                            '73' => array( 'name' => self::_('Kebili') ),
                            '12' => array( 'name' => self::_('L\'Ariana') ),
                            '33' => array( 'name' => self::_('Le Kef') ),
                            '53' => array( 'name' => self::_('Mahdia') ),
                            '14' => array( 'name' => self::_('La Manouba') ),
                            '82' => array( 'name' => self::_('Medenine') ),
                            '52' => array( 'name' => self::_('Monastir') ),
                            '21' => array( 'name' => self::_('Nabeul') ),
                            '61' => array( 'name' => self::_('Sfax') ),
                            '43' => array( 'name' => self::_('Sidi Bouzid') ),
                            '34' => array( 'name' => self::_('Siliana') ),
                            '51' => array( 'name' => self::_('Sousse') ),
                            '83' => array( 'name' => self::_('Tataouine') ),
                            '72' => array( 'name' => self::_('Tozeur') ),
                            '11' => array( 'name' => self::_('Tunis') ),
                            '22' => array( 'name' => self::_('Zaghouan') )
                        ) );
                    break;
                case 'TO':
                    return array(
                        'regions_label' => self::_('Division'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('\'Eua') ),
                            '02' => array( 'name' => self::_('Ha\'apai') ),
                            '03' => array( 'name' => self::_('Niuas') ),
                            '04' => array( 'name' => self::_('Tongatapu') ),
                            '05' => array( 'name' => self::_('Vava\'u') )
                        ) );
                    break;
                case 'TR':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Adana') ),
                            '02' => array( 'name' => self::_('Adıyaman') ),
                            '03' => array( 'name' => self::_('Afyon') ),
                            '04' => array( 'name' => self::_('Ağrı') ),
                            '68' => array( 'name' => self::_('Aksaray') ),
                            '05' => array( 'name' => self::_('Amasya') ),
                            '06' => array( 'name' => self::_('Ankara') ),
                            '07' => array( 'name' => self::_('Antalya') ),
                            '75' => array( 'name' => self::_('Ardahan') ),
                            '08' => array( 'name' => self::_('Artvin') ),
                            '09' => array( 'name' => self::_('Aydın') ),
                            '10' => array( 'name' => self::_('Balıkesir') ),
                            '74' => array( 'name' => self::_('Bartın') ),
                            '72' => array( 'name' => self::_('Batman') ),
                            '69' => array( 'name' => self::_('Bayburt') ),
                            '11' => array( 'name' => self::_('Bilecik') ),
                            '12' => array( 'name' => self::_('Bingöl') ),
                            '13' => array( 'name' => self::_('Bitlis') ),
                            '14' => array( 'name' => self::_('Bolu') ),
                            '15' => array( 'name' => self::_('Burdur') ),
                            '16' => array( 'name' => self::_('Bursa') ),
                            '17' => array( 'name' => self::_('Çanakkale') ),
                            '18' => array( 'name' => self::_('Çankırı') ),
                            '19' => array( 'name' => self::_('Çorum') ),
                            '20' => array( 'name' => self::_('Denizli') ),
                            '21' => array( 'name' => self::_('Diyarbakır') ),
                            '81' => array( 'name' => self::_('Düzce') ),
                            '22' => array( 'name' => self::_('Edirne') ),
                            '23' => array( 'name' => self::_('Elazığ') ),
                            '24' => array( 'name' => self::_('Erzincan') ),
                            '25' => array( 'name' => self::_('Erzurum') ),
                            '26' => array( 'name' => self::_('Eskişehir') ),
                            '27' => array( 'name' => self::_('Gaziantep') ),
                            '28' => array( 'name' => self::_('Giresun') ),
                            '29' => array( 'name' => self::_('Gümüşhane') ),
                            '30' => array( 'name' => self::_('Hakkâri') ),
                            '31' => array( 'name' => self::_('Hatay') ),
                            '76' => array( 'name' => self::_('Iğdır') ),
                            '32' => array( 'name' => self::_('Isparta') ),
                            '33' => array( 'name' => self::_('İçel') ),
                            '34' => array( 'name' => self::_('İstanbul') ),
                            '35' => array( 'name' => self::_('İzmir') ),
                            '46' => array( 'name' => self::_('Kahramanmaraş') ),
                            '78' => array( 'name' => self::_('Karabük') ),
                            '70' => array( 'name' => self::_('Karaman') ),
                            '36' => array( 'name' => self::_('Kars') ),
                            '37' => array( 'name' => self::_('Kastamonu') ),
                            '38' => array( 'name' => self::_('Kayseri') ),
                            '71' => array( 'name' => self::_('Kırıkkale') ),
                            '39' => array( 'name' => self::_('Kırklareli') ),
                            '40' => array( 'name' => self::_('Kırşehir') ),
                            '79' => array( 'name' => self::_('Kilis') ),
                            '41' => array( 'name' => self::_('Kocaeli') ),
                            '42' => array( 'name' => self::_('Konya') ),
                            '43' => array( 'name' => self::_('Kütahya') ),
                            '44' => array( 'name' => self::_('Malatya') ),
                            '45' => array( 'name' => self::_('Manisa') ),
                            '47' => array( 'name' => self::_('Mardin') ),
                            '48' => array( 'name' => self::_('Muğla') ),
                            '49' => array( 'name' => self::_('Muş') ),
                            '50' => array( 'name' => self::_('Nevşehir') ),
                            '51' => array( 'name' => self::_('Niğde') ),
                            '52' => array( 'name' => self::_('Ordu') ),
                            '80' => array( 'name' => self::_('Osmaniye') ),
                            '53' => array( 'name' => self::_('Rize') ),
                            '54' => array( 'name' => self::_('Sakarya') ),
                            '55' => array( 'name' => self::_('Samsun') ),
                            '56' => array( 'name' => self::_('Siirt') ),
                            '57' => array( 'name' => self::_('Sinop') ),
                            '58' => array( 'name' => self::_('Sivas') ),
                            '63' => array( 'name' => self::_('Şanlıurfa') ),
                            '73' => array( 'name' => self::_('Şırnak') ),
                            '59' => array( 'name' => self::_('Tekirdağ') ),
                            '60' => array( 'name' => self::_('Tokat') ),
                            '61' => array( 'name' => self::_('Trabzon') ),
                            '62' => array( 'name' => self::_('Tunceli') ),
                            '64' => array( 'name' => self::_('Uşak') ),
                            '65' => array( 'name' => self::_('Van') ),
                            '77' => array( 'name' => self::_('Yalova') ),
                            '66' => array( 'name' => self::_('Yozgat') ),
                            '67' => array( 'name' => self::_('Zonguldak') )
                        ) );
                    break;
                case 'TT':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'CTT' => array( 'name' => self::_('Couva-Tabaquite-Talparo') ),
                            'DMN' => array( 'name' => self::_('Diego Martin') ),
                            'ETO' => array( 'name' => self::_('Eastern Tobago') ),
                            'PED' => array( 'name' => self::_('Penal-Debe') ),
                            'PRT' => array( 'name' => self::_('Princes Town') ),
                            'RCM' => array( 'name' => self::_('Rio Claro-Mayaro') ),
                            'SGE' => array( 'name' => self::_('Sangre Grande') ),
                            'SJL' => array( 'name' => self::_('San Juan-Laventille') ),
                            'SIP' => array( 'name' => self::_('Siparia') ),
                            'TUP' => array( 'name' => self::_('Tunapuna-Piarco') ),
                            'WTO' => array( 'name' => self::_('Western Tobago') ),
                            'ARI' => array( 'name' => self::_('Arima') ),
                            'CHA' => array( 'name' => self::_('Chaguanas') ),
                            'PTF' => array( 'name' => self::_('Point Fortin') ),
                            'POS' => array( 'name' => self::_('Port of Spain') ),
                            'SFO' => array( 'name' => self::_('San Fernando') )
                        ) );
                    break;
                case 'TV':
                    return array(
                        'regions_label' => self::_('Island council'),
                        'regions'       => array(
                            'FUN' => array( 'name' => self::_('Funafuti') ),
                            'NMG' => array( 'name' => self::_('Nanumanga') ),
                            'NMA' => array( 'name' => self::_('Nanumea') ),
                            'NIT' => array( 'name' => self::_('Niutao') ),
                            'NIU' => array( 'name' => self::_('Nui') ),
                            'NKF' => array( 'name' => self::_('Nukufetau') ),
                            'NKL' => array( 'name' => self::_('Nukulaelae') ),
                            'VAI' => array( 'name' => self::_('Vaitupu') )
                        ) );
                    break;
                case 'TW':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'CHA' => array( 'name' => self::_('Changhua') ),
                            'CYQ' => array( 'name' => self::_('Chiayi') ),
                            'HSQ' => array( 'name' => self::_('Hsinchu') ),
                            'HUA' => array( 'name' => self::_('Hualien') ),
                            'ILA' => array( 'name' => self::_('Ilan') ),
                            'KHQ' => array( 'name' => self::_('Kaohsiung') ),
                            'MIA' => array( 'name' => self::_('Miaoli') ),
                            'NAN' => array( 'name' => self::_('Nantou') ),
                            'PEN' => array( 'name' => self::_('Penghu') ),
                            'PIF' => array( 'name' => self::_('Pingtung') ),
                            'TXQ' => array( 'name' => self::_('Taichung') ),
                            'TNQ' => array( 'name' => self::_('Tainan') ),
                            'TPQ' => array( 'name' => self::_('Taipei') ),
                            'TTT' => array( 'name' => self::_('Taitung') ),
                            'TAO' => array( 'name' => self::_('Taoyuan') ),
                            'YUN' => array( 'name' => self::_('Yunlin') ),
                            'CYI' => array( 'name' => self::_('Chiay City') ),
                            'HSZ' => array( 'name' => self::_('Hsinchui City') ),
                            'KEE' => array( 'name' => self::_('Keelung City') ),
                            'TXG' => array( 'name' => self::_('Taichung City') ),
                            'TNN' => array( 'name' => self::_('Tainan City') ),
                            'KHH' => array( 'name' => self::_('Kaohsiung City') ),
                            'TPE' => array( 'name' => self::_('Taipei City') )
                        ) );
                    break;
                case 'TZ':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Arusha') ),
                            '02' => array( 'name' => self::_('Dar-es-Salaam') ),
                            '03' => array( 'name' => self::_('Dodoma') ),
                            '04' => array( 'name' => self::_('Iringa') ),
                            '05' => array( 'name' => self::_('Kagera') ),
                            '06' => array( 'name' => self::_('Kaskazini Pemba') ),
                            '07' => array( 'name' => self::_('Kaskazini Unguja') ),
                            '08' => array( 'name' => self::_('Kigoma') ),
                            '09' => array( 'name' => self::_('Kilimanjaro') ),
                            '10' => array( 'name' => self::_('Kusini Pemba') ),
                            '11' => array( 'name' => self::_('Kusini Unguja') ),
                            '12' => array( 'name' => self::_('Lindi') ),
                            '26' => array( 'name' => self::_('Manyara') ),
                            '13' => array( 'name' => self::_('Mara') ),
                            '14' => array( 'name' => self::_('Mbeya') ),
                            '15' => array( 'name' => self::_('Mjini Magharibi') ),
                            '16' => array( 'name' => self::_('Morogoro') ),
                            '17' => array( 'name' => self::_('Mtwara') ),
                            '18' => array( 'name' => self::_('Mwanza') ),
                            '19' => array( 'name' => self::_('Pwani') ),
                            '20' => array( 'name' => self::_('Rukwa') ),
                            '21' => array( 'name' => self::_('Ruvuma') ),
                            '22' => array( 'name' => self::_('Shinyanga') ),
                            '23' => array( 'name' => self::_('Singida') ),
                            '24' => array( 'name' => self::_('Tabora') ),
                            '25' => array( 'name' => self::_('Tanga') )
                        ) );
                    break;
                case 'UA':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            '71' => array( 'name' => self::_('Cherkas\'ka Oblast\'') ),
                            '74' => array( 'name' => self::_('Chernihivs\'ka Oblast\'') ),
                            '77' => array( 'name' => self::_('Chernivets\'ka Oblast\'') ),
                            '12' => array( 'name' => self::_('Dnipropetrovs\'ka Oblast\'') ),
                            '14' => array( 'name' => self::_('Donets\'ka Oblast\'') ),
                            '26' => array( 'name' => self::_('Ivano-Frankivs\'ka Oblast\'') ),
                            '63' => array( 'name' => self::_('Kharkivs\'ka Oblast\'') ),
                            '65' => array( 'name' => self::_('Khersons\'ka Oblast\'') ),
                            '68' => array( 'name' => self::_('Khmel\'nyts\'ka Oblast\'') ),
                            '35' => array( 'name' => self::_('Kirovohrads\'ka Oblast\'') ),
                            '32' => array( 'name' => self::_('Kyïvs\'ka Oblast\'') ),
                            '09' => array( 'name' => self::_('Luhans\'ka Oblast\'') ),
                            '46' => array( 'name' => self::_('L\'vivs\'ka Oblast\'') ),
                            '48' => array( 'name' => self::_('Mykolaïvs\'ka Oblast\'') ),
                            '51' => array( 'name' => self::_('Odes\'ka Oblast\'') ),
                            '53' => array( 'name' => self::_('Poltavs\'ka Oblast\'') ),
                            '56' => array( 'name' => self::_('Rivnens\'ka Oblast\'') ),
                            '59' => array( 'name' => self::_('Sums \'ka Oblast\'') ),
                            '61' => array( 'name' => self::_('Ternopil\'s\'ka Oblast\'') ),
                            '05' => array( 'name' => self::_('Vinnyts\'ka Oblast\'') ),
                            '07' => array( 'name' => self::_('Volyns\'ka Oblast\'') ),
                            '21' => array( 'name' => self::_('Zakarpats\'ka Oblast\'') ),
                            '23' => array( 'name' => self::_('Zaporiz\'ka Oblast\'') ),
                            '18' => array( 'name' => self::_('Zhytomyrs\'ka Oblast\'') ),
                            '43' => array( 'name' => self::_('Respublika Krym') ),
                            '30' => array( 'name' => self::_('Kyïvs\'ka mis\'ka rada') ),
                            '40' => array( 'name' => self::_('Sevastopol') )
                        ) );
                    break;
                case 'UG':
                    return array(
                        'regions_label'    => self::_('Geographical region'),
                        'subregions_label' => self::_('District'),
                        'regions'          => array(
                            'C' => array(
                                'name'       => self::_('Central'),
                                'subregions' => array(
                                    '101' => array( 'name' => self::_('Kalangala') ),
                                    '102' => array( 'name' => self::_('Kampala') ),
                                    '112' => array( 'name' => self::_('Kayunga') ),
                                    '103' => array( 'name' => self::_('Kiboga') ),
                                    '104' => array( 'name' => self::_('Luwero') ),
                                    '116' => array( 'name' => self::_('Lyantonde') ),
                                    '105' => array( 'name' => self::_('Masaka') ),
                                    '114' => array( 'name' => self::_('Mityana') ),
                                    '106' => array( 'name' => self::_('Mpigi') ),
                                    '107' => array( 'name' => self::_('Mubende') ),
                                    '108' => array( 'name' => self::_('Mukono') ),
                                    '115' => array( 'name' => self::_('Nakaseke') ),
                                    '109' => array( 'name' => self::_('Nakasongola') ),
                                    '110' => array( 'name' => self::_('Rakai') ),
                                    '111' => array( 'name' => self::_('Sembabule') ),
                                    '113' => array( 'name' => self::_('Wakiso') )
                                ) ),
                            'E' => array(
                                'name'       => self::_('Eastern'),
                                'subregions' => array(
                                    '216' => array( 'name' => self::_('Amuria') ),
                                    '217' => array( 'name' => self::_('Budaka') ),
                                    '223' => array( 'name' => self::_('Bududa') ),
                                    '201' => array( 'name' => self::_('Bugiri') ),
                                    '224' => array( 'name' => self::_('Bukedea') ),
                                    '218' => array( 'name' => self::_('Bukwa') ),
                                    '202' => array( 'name' => self::_('Busia') ),
                                    '219' => array( 'name' => self::_('Butaleja') ),
                                    '203' => array( 'name' => self::_('Iganga') ),
                                    '204' => array( 'name' => self::_('Jinja') ),
                                    '213' => array( 'name' => self::_('Kaberamaido') ),
                                    '220' => array( 'name' => self::_('Kaliro') ),
                                    '205' => array( 'name' => self::_('Kamuli') ),
                                    '206' => array( 'name' => self::_('Kapchorwa') ),
                                    '207' => array( 'name' => self::_('Katakwi') ),
                                    '208' => array( 'name' => self::_('Kumi') ),
                                    '221' => array( 'name' => self::_('Manafwa') ),
                                    '214' => array( 'name' => self::_('Mayuge') ),
                                    '209' => array( 'name' => self::_('Mbale') ),
                                    '222' => array( 'name' => self::_('Namutumba') ),
                                    '210' => array( 'name' => self::_('Pallisa') ),
                                    '215' => array( 'name' => self::_('Sironko') ),
                                    '211' => array( 'name' => self::_('Soroti') ),
                                    '212' => array( 'name' => self::_('Tororo') )
                                ) ),
                            'N' => array(
                                'name'       => self::_('Northern'),
                                'subregions' => array(
                                    '317' => array( 'name' => self::_('Abim') ),
                                    '301' => array( 'name' => self::_('Adjumani') ),
                                    '314' => array( 'name' => self::_('Amolatar') ),
                                    '319' => array( 'name' => self::_('Amuru') ),
                                    '302' => array( 'name' => self::_('Apac') ),
                                    '303' => array( 'name' => self::_('Arua') ),
                                    '318' => array( 'name' => self::_('Dokolo') ),
                                    '304' => array( 'name' => self::_('Gulu') ),
                                    '315' => array( 'name' => self::_('Kaabong') ),
                                    '305' => array( 'name' => self::_('Kitgum') ),
                                    '316' => array( 'name' => self::_('Koboko') ),
                                    '306' => array( 'name' => self::_('Kotido') ),
                                    '307' => array( 'name' => self::_('Lira') ),
                                    '320' => array( 'name' => self::_('Maracha') ),
                                    '308' => array( 'name' => self::_('Moroto') ),
                                    '309' => array( 'name' => self::_('Moyo') ),
                                    '311' => array( 'name' => self::_('Nakapiripirit') ),
                                    '310' => array( 'name' => self::_('Nebbi') ),
                                    '321' => array( 'name' => self::_('Oyam') ),
                                    '312' => array( 'name' => self::_('Pader') ),
                                    '313' => array( 'name' => self::_('Yumbe') )
                                ) ),
                            'W' => array(
                                'name'       => self::_('Western'),
                                'subregions' => array(
                                    '419' => array( 'name' => self::_('Buliisa') ),
                                    '401' => array( 'name' => self::_('Bundibugyo') ),
                                    '402' => array( 'name' => self::_('Bushenyi') ),
                                    '403' => array( 'name' => self::_('Hoima') ),
                                    '416' => array( 'name' => self::_('Ibanda') ),
                                    '417' => array( 'name' => self::_('Isingiro') ),
                                    '404' => array( 'name' => self::_('Kabale') ),
                                    '405' => array( 'name' => self::_('Kabarole') ),
                                    '413' => array( 'name' => self::_('Kamwenge') ),
                                    '414' => array( 'name' => self::_('Kanungu') ),
                                    '406' => array( 'name' => self::_('Kasese') ),
                                    '407' => array( 'name' => self::_('Kibaale') ),
                                    '418' => array( 'name' => self::_('Kiruhura') ),
                                    '408' => array( 'name' => self::_('Kisoro') ),
                                    '415' => array( 'name' => self::_('Kyenjojo') ),
                                    '409' => array( 'name' => self::_('Masindi') ),
                                    '410' => array( 'name' => self::_('Mbarara') ),
                                    '411' => array( 'name' => self::_('Ntungamo') ),
                                    '412' => array( 'name' => self::_('Rukungiri') )
                                ) ),
                        ) );
                    break;
                case 'UM':
                    return array(
                        'regions_label' => self::_('Territory'),
                        'regions'       => array(
                            81 => array( 'name' => self::_('Baker Island') ),
                            84 => array( 'name' => self::_('Howland Island') ),
                            86 => array( 'name' => self::_('Jarvis Island') ),
                            67 => array( 'name' => self::_('Johnston Atoll') ),
                            89 => array( 'name' => self::_('Kingman Reef') ),
                            71 => array( 'name' => self::_('Midway Islands') ),
                            76 => array( 'name' => self::_('Navassa Island') ),
                            95 => array( 'name' => self::_('Palmyra Atoll') ),
                            79 => array( 'name' => self::_('Wake Island') )
                        ) );
                    break;
                case 'US':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            'AL' => array( 'name' => self::_('Alabama') ),
                            'AK' => array( 'name' => self::_('Alaska') ),
                            'AZ' => array( 'name' => self::_('Arizona') ),
                            'AR' => array( 'name' => self::_('Arkansas') ),
                            'CA' => array( 'name' => self::_('California') ),
                            'CO' => array( 'name' => self::_('Colorado') ),
                            'CT' => array( 'name' => self::_('Connecticut') ),
                            'DE' => array( 'name' => self::_('Delaware') ),
                            'FL' => array( 'name' => self::_('Florida') ),
                            'GA' => array( 'name' => self::_('Georgia') ),
                            'HI' => array( 'name' => self::_('Hawaii') ),
                            'ID' => array( 'name' => self::_('Idaho') ),
                            'IL' => array( 'name' => self::_('Illinois') ),
                            'IN' => array( 'name' => self::_('Indiana') ),
                            'IA' => array( 'name' => self::_('Iowa') ),
                            'KS' => array( 'name' => self::_('Kansas') ),
                            'KY' => array( 'name' => self::_('Kentucky') ),
                            'LA' => array( 'name' => self::_('Louisiana') ),
                            'ME' => array( 'name' => self::_('Maine') ),
                            'MD' => array( 'name' => self::_('Maryland') ),
                            'MA' => array( 'name' => self::_('Massachusetts') ),
                            'MI' => array( 'name' => self::_('Michigan') ),
                            'MN' => array( 'name' => self::_('Minnesota') ),
                            'MS' => array( 'name' => self::_('Mississippi') ),
                            'MO' => array( 'name' => self::_('Missouri') ),
                            'MT' => array( 'name' => self::_('Montana') ),
                            'NE' => array( 'name' => self::_('Nebraska') ),
                            'NV' => array( 'name' => self::_('Nevada') ),
                            'NH' => array( 'name' => self::_('New Hampshire') ),
                            'NJ' => array( 'name' => self::_('New Jersey') ),
                            'NM' => array( 'name' => self::_('New Mexico') ),
                            'NY' => array( 'name' => self::_('New York') ),
                            'NC' => array( 'name' => self::_('North Carolina') ),
                            'ND' => array( 'name' => self::_('North Dakota') ),
                            'OH' => array( 'name' => self::_('Ohio') ),
                            'OK' => array( 'name' => self::_('Oklahoma') ),
                            'OR' => array( 'name' => self::_('Oregon') ),
                            'PA' => array( 'name' => self::_('Pennsylvania') ),
                            'RI' => array( 'name' => self::_('Rhode Island') ),
                            'SC' => array( 'name' => self::_('South Carolina') ),
                            'SD' => array( 'name' => self::_('South Dakota') ),
                            'TN' => array( 'name' => self::_('Tennessee') ),
                            'TX' => array( 'name' => self::_('Texas') ),
                            'UT' => array( 'name' => self::_('Utah') ),
                            'VT' => array( 'name' => self::_('Vermont') ),
                            'VA' => array( 'name' => self::_('Virginia') ),
                            'WA' => array( 'name' => self::_('Washington') ),
                            'WV' => array( 'name' => self::_('West Virginia') ),
                            'WI' => array( 'name' => self::_('Wisconsin') ),
                            'WY' => array( 'name' => self::_('Wyoming') ),
                            'DC' => array( 'name' => self::_('District of Columbia') ),
                            'AS' => array( 'name' => self::_('American Samoa') ),
                            'GU' => array( 'name' => self::_('Guam') ),
                            'MP' => array( 'name' => self::_('Northern Mariana Islands') ),
                            'PR' => array( 'name' => self::_('Puerto Rico') ),
                            'UM' => array( 'name' => self::_('United States Minor Outlying Islands') ),
                            'VI' => array( 'name' => self::_('Virgin Islands') )
                        ) );
                    break;
                case 'UY':
                    return array(
                        'regions_label' => self::_('Department'),
                        'regions'       => array(
                            'AR' => array( 'name' => self::_('Artigas') ),
                            'CA' => array( 'name' => self::_('Canelones') ),
                            'CL' => array( 'name' => self::_('Cerro Largo') ),
                            'CO' => array( 'name' => self::_('Colonia') ),
                            'DU' => array( 'name' => self::_('Durazno') ),
                            'FS' => array( 'name' => self::_('Flores') ),
                            'FD' => array( 'name' => self::_('Florida') ),
                            'LA' => array( 'name' => self::_('Lavalleja') ),
                            'MA' => array( 'name' => self::_('Maldonado') ),
                            'MO' => array( 'name' => self::_('Montevideo') ),
                            'PA' => array( 'name' => self::_('Paysandú') ),
                            'RN' => array( 'name' => self::_('Río Negro') ),
                            'RV' => array( 'name' => self::_('Rivera') ),
                            'RO' => array( 'name' => self::_('Rocha') ),
                            'SA' => array( 'name' => self::_('Salto') ),
                            'SJ' => array( 'name' => self::_('San José') ),
                            'SO' => array( 'name' => self::_('Soriano') ),
                            'TA' => array( 'name' => self::_('Tacuarembó') ),
                            'TT' => array( 'name' => self::_('Treinta y Tres') )
                        ) );
                    break;
                case 'UZ':
                    return array(
                        'regions_label' => self::_('Region'),
                        'regions'       => array(
                            'TK' => array( 'name' => self::_('Toshkent') ),
                            'AN' => array( 'name' => self::_('Andijon') ),
                            'BU' => array( 'name' => self::_('Buxoro') ),
                            'FA' => array( 'name' => self::_('Farg\'ona') ),
                            'JI' => array( 'name' => self::_('Jizzax') ),
                            'NG' => array( 'name' => self::_('Namangan') ),
                            'NW' => array( 'name' => self::_('Navoiy') ),
                            'QA' => array( 'name' => self::_('Qashqadaryo') ),
                            'SA' => array( 'name' => self::_('Samarqand') ),
                            'SI' => array( 'name' => self::_('Sirdaryo') ),
                            'SU' => array( 'name' => self::_('Surxondaryo') ),
                            'TO' => array( 'name' => self::_('Toshkent') ),
                            'XO' => array( 'name' => self::_('Xorazm') ),
                            'QR' => array( 'name' => self::_('Qoraqalpog\'iston Respublikasi') )
                        ) );
                    break;
                case 'VC':
                    return array(
                        'regions_label' => self::_('Parish'),
                        'regions'       => array(
                            '01' => array( 'name' => self::_('Charlotte') ),
                            '06' => array( 'name' => self::_('Grenadines') ),
                            '02' => array( 'name' => self::_('Saint Andrew') ),
                            '03' => array( 'name' => self::_('Saint David') ),
                            '04' => array( 'name' => self::_('Saint George') ),
                            '05' => array( 'name' => self::_('Saint Patrick') )
                        ) );
                    break;
                case 'VE':
                    return array(
                        'regions_label' => self::_('State'),
                        'regions'       => array(
                            'W' => array( 'name' => self::_('Dependencias Federales') ),
                            'A' => array( 'name' => self::_('Distrito Federal') ),
                            'Z' => array( 'name' => self::_('Amazonas') ),
                            'B' => array( 'name' => self::_('Anzoátegui') ),
                            'C' => array( 'name' => self::_('Apure') ),
                            'D' => array( 'name' => self::_('Aragua') ),
                            'E' => array( 'name' => self::_('Barinas') ),
                            'F' => array( 'name' => self::_('Bolívar') ),
                            'G' => array( 'name' => self::_('Carabobo') ),
                            'H' => array( 'name' => self::_('Cojedes') ),
                            'Y' => array( 'name' => self::_('Delta Amacuro') ),
                            'I' => array( 'name' => self::_('Falcón') ),
                            'J' => array( 'name' => self::_('Guárico') ),
                            'K' => array( 'name' => self::_('Lara') ),
                            'L' => array( 'name' => self::_('Mérida') ),
                            'M' => array( 'name' => self::_('Miranda') ),
                            'N' => array( 'name' => self::_('Monagas') ),
                            'O' => array( 'name' => self::_('Nueva Esparta') ),
                            'P' => array( 'name' => self::_('Portuguesa') ),
                            'R' => array( 'name' => self::_('Sucre') ),
                            'S' => array( 'name' => self::_('Táchira') ),
                            'T' => array( 'name' => self::_('Trujillo') ),
                            'X' => array( 'name' => self::_('Vargas') ),
                            'U' => array( 'name' => self::_('Yaracuy') ),
                            'V' => array( 'name' => self::_('Zulia') )
                        ) );
                    break;
                case 'VN':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '44' => array( 'name' => self::_('An Giang') ),
                            '43' => array( 'name' => self::_('Bà Rịa - Vũng Tàu') ),
                            '53' => array( 'name' => self::_('Bắc Kạn') ),
                            '54' => array( 'name' => self::_('Bắc Giang') ),
                            '55' => array( 'name' => self::_('Bạc Liêu') ),
                            '56' => array( 'name' => self::_('Bắc Ninh') ),
                            '50' => array( 'name' => self::_('Bến Tre') ),
                            '31' => array( 'name' => self::_('Bình Định') ),
                            '57' => array( 'name' => self::_('Bình Dương') ),
                            '58' => array( 'name' => self::_('Bình Phước') ),
                            '40' => array( 'name' => self::_('Bình Thuận') ),
                            '59' => array( 'name' => self::_('Cà Mau') ),
                            '48' => array( 'name' => self::_('Cần Thơ') ),
                            '04' => array( 'name' => self::_('Cao Bằng') ),
                            '60' => array( 'name' => self::_('Đà Nẵng, thành phố') ),
                            '33' => array( 'name' => self::_('Đắc Lắk') ),
                            '72' => array( 'name' => self::_('Đắk Nông') ),
                            '71' => array( 'name' => self::_('Điện Biên') ),
                            '39' => array( 'name' => self::_('Đồng Nai') ),
                            '45' => array( 'name' => self::_('Đồng Tháp') ),
                            '30' => array( 'name' => self::_('Gia Lai') ),
                            '03' => array( 'name' => self::_('Hà Giang') ),
                            '63' => array( 'name' => self::_('Hà Nam') ),
                            '64' => array( 'name' => self::_('Hà Nội, thủ đô') ),
                            '15' => array( 'name' => self::_('Hà Tây') ),
                            '23' => array( 'name' => self::_('Hà Tỉnh') ),
                            '61' => array( 'name' => self::_('Hải Duong') ),
                            '62' => array( 'name' => self::_('Hải Phòng, thành phố') ),
                            '73' => array( 'name' => self::_('Hậu Giang') ),
                            '14' => array( 'name' => self::_('Hoà Bình') ),
                            '65' => array( 'name' => self::_('Hồ Chí Minh, thành phố [Sài Gòn]') ),
                            '66' => array( 'name' => self::_('Hưng Yên') ),
                            '34' => array( 'name' => self::_('Khánh Hòa') ),
                            '47' => array( 'name' => self::_('Kiên Giang') ),
                            '28' => array( 'name' => self::_('Kon Tum') ),
                            '01' => array( 'name' => self::_('Lai Châu') ),
                            '35' => array( 'name' => self::_('Lâm Đồng') ),
                            '09' => array( 'name' => self::_('Lạng Sơn') ),
                            '02' => array( 'name' => self::_('Lào Cai') ),
                            '41' => array( 'name' => self::_('Long An') ),
                            '67' => array( 'name' => self::_('Nam Định') ),
                            '22' => array( 'name' => self::_('Nghệ An') ),
                            '18' => array( 'name' => self::_('Ninh Bình') ),
                            '36' => array( 'name' => self::_('Ninh Thuận') ),
                            '68' => array( 'name' => self::_('Phú Thọ') ),
                            '32' => array( 'name' => self::_('Phú Yên') ),
                            '24' => array( 'name' => self::_('Quảng Bình') ),
                            '27' => array( 'name' => self::_('Quảng Nam') ),
                            '29' => array( 'name' => self::_('Quảng Ngãi') ),
                            '13' => array( 'name' => self::_('Quảng Ninh') ),
                            '25' => array( 'name' => self::_('Quảng Trị') ),
                            '52' => array( 'name' => self::_('Sóc Trăng') ),
                            '05' => array( 'name' => self::_('Sơn La') ),
                            '37' => array( 'name' => self::_('Tây Ninh') ),
                            '20' => array( 'name' => self::_('Thái Bình') ),
                            '69' => array( 'name' => self::_('Thái Nguyên') ),
                            '21' => array( 'name' => self::_('Thanh Hóa') ),
                            '26' => array( 'name' => self::_('Thừa Thiên-Huế') ),
                            '46' => array( 'name' => self::_('Tiền Giang') ),
                            '51' => array( 'name' => self::_('Trà Vinh') ),
                            '07' => array( 'name' => self::_('Tuyên Quang') ),
                            '49' => array( 'name' => self::_('Vĩnh Long') ),
                            '70' => array( 'name' => self::_('Vĩnh Phúc') ),
                            '06' => array( 'name' => self::_('Yên Bái') )
                        ) );
                    break;
                case 'VU':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'MAP' => array( 'name' => self::_('Malampa') ),
                            'PAM' => array( 'name' => self::_('Pénama') ),
                            'SAM' => array( 'name' => self::_('Sanma') ),
                            'SEE' => array( 'name' => self::_('Shéfa') ),
                            'TAE' => array( 'name' => self::_('Taféa') ),
                            'TOB' => array( 'name' => self::_('Torba') )
                        ) );
                    break;
                case 'WS':
                    return array(
                        'regions_label' => self::_('District'),
                        'regions'       => array(
                            'AA' => array( 'name' => self::_('A\'ana') ),
                            'AL' => array( 'name' => self::_('Aiga-i-le-Tai') ),
                            'AT' => array( 'name' => self::_('Atua') ),
                            'FA' => array( 'name' => self::_('Fa\'asaleleaga') ),
                            'GE' => array( 'name' => self::_('Gaga\'emauga') ),
                            'GI' => array( 'name' => self::_('Gagaifomauga') ),
                            'PA' => array( 'name' => self::_('Palauli') ),
                            'SA' => array( 'name' => self::_('Satupa\'itea') ),
                            'TU' => array( 'name' => self::_('Tuamasaga') ),
                            'VF' => array( 'name' => self::_('Va\'a-o-Fonoti') ),
                            'VS' => array( 'name' => self::_('Vaisigano') )
                        ) );
                    break;
                case 'YE':
                    return array(
                        'regions_label' => self::_('Governorate'),
                        'regions'       => array(
                            'AB' => array( 'name' => self::_('Abyān') ),
                            'AD' => array( 'name' => self::_('\'Adan') ),
                            'DA' => array( 'name' => self::_('Aḑ Ḑāli\'') ),
                            'BA' => array( 'name' => self::_('Al Bayḑā\'') ),
                            'MU' => array( 'name' => self::_('Al Ḩudaydah') ),
                            'JA' => array( 'name' => self::_('Al Jawf') ),
                            'MR' => array( 'name' => self::_('Al Mahrah') ),
                            'MW' => array( 'name' => self::_('Al Maḩwīt') ),
                            'AM' => array( 'name' => self::_('\'Amrān') ),
                            'DH' => array( 'name' => self::_('Dhamār') ),
                            'HD' => array( 'name' => self::_('Ḩaḑramawt') ),
                            'HJ' => array( 'name' => self::_('Ḩajjah') ),
                            'IB' => array( 'name' => self::_('Ibb') ),
                            'LA' => array( 'name' => self::_('Laḩij') ),
                            'MA' => array( 'name' => self::_('Ma\'rib') ),
                            'RA' => array( 'name' => self::_('Raymah') ),
                            'SD' => array( 'name' => self::_('Şa\'dah') ),
                            'SN' => array( 'name' => self::_('Şan\'ā\'') ),
                            'SH' => array( 'name' => self::_('Shabwah') ),
                            'TA' => array( 'name' => self::_('Tā\'izz') )
                        ) );
                    break;
                case 'ZA':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'EC' => array( 'name' => self::_('Eastern Cape') ),
                            'FS' => array( 'name' => self::_('Free State') ),
                            'GT' => array( 'name' => self::_('Gauteng') ),
                            'NL' => array( 'name' => self::_('Kwazulu-Natal') ),
                            'LP' => array( 'name' => self::_('Limpopo') ),
                            'MP' => array( 'name' => self::_('Mpumalanga') ),
                            'NC' => array( 'name' => self::_('Northern Cape') ),
                            'NW' => array( 'name' => self::_('North-West (South Africa)') ),
                            'WC' => array( 'name' => self::_('Western Cape') )
                        ) );
                    break;
                case 'ZM':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            '02' => array( 'name' => self::_('Central') ),
                            '08' => array( 'name' => self::_('Copperbelt') ),
                            '03' => array( 'name' => self::_('Eastern') ),
                            '04' => array( 'name' => self::_('Luapula') ),
                            '09' => array( 'name' => self::_('Lusaka') ),
                            '05' => array( 'name' => self::_('Northern') ),
                            '06' => array( 'name' => self::_('North-Western') ),
                            '07' => array( 'name' => self::_('Southern (Zambia)') ),
                            '01' => array( 'name' => self::_('Western') )
                        ) );
                    break;
                case 'ZW':
                    return array(
                        'regions_label' => self::_('Province'),
                        'regions'       => array(
                            'BU' => array( 'name' => self::_('Bulawayo') ),
                            'HA' => array( 'name' => self::_('Harare') ),
                            'MA' => array( 'name' => self::_('Manicaland') ),
                            'MC' => array( 'name' => self::_('Mashonaland Central') ),
                            'ME' => array( 'name' => self::_('Mashonaland East') ),
                            'MW' => array( 'name' => self::_('Mashonaland West') ),
                            'MV' => array( 'name' => self::_('Masvingo') ),
                            'MN' => array( 'name' => self::_('Matabeleland North') ),
                            'MS' => array( 'name' => self::_('Matabeleland South') ),
                            'MI' => array( 'name' => self::_('Midlands') )
                        ) );
                    break;
            }

            return;
        }
    }