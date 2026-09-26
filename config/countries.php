<?php

$lines = <<<'LIST'
AR|Argentina
AU|Australia
AT|Austria
BD|Bangladesh
BE|Belgium
BR|Brazil
CA|Canada
CL|Chile
CN|China
CO|Colombia
CZ|Czechia
DK|Denmark
EG|Egypt
FI|Finland
FR|France
DE|Germany
GH|Ghana
GR|Greece
HK|Hong Kong
HU|Hungary
IN|India
ID|Indonesia
IE|Ireland
IL|Israel
IT|Italy
JP|Japan
KE|Kenya
KR|South Korea
MY|Malaysia
MX|Mexico
MA|Morocco
NP|Nepal
NL|Netherlands
NZ|New Zealand
NG|Nigeria
NO|Norway
PK|Pakistan
PE|Peru
PH|Philippines
PL|Poland
PT|Portugal
QA|Qatar
RO|Romania
RU|Russia
SA|Saudi Arabia
SG|Singapore
ZA|South Africa
ES|Spain
LK|Sri Lanka
SE|Sweden
CH|Switzerland
TW|Taiwan
TH|Thailand
TR|Turkey
UA|Ukraine
AE|United Arab Emirates
GB|United Kingdom
US|United States
VN|Vietnam
LIST;

$countries = [];
foreach (preg_split('/\r\n|\n|\r/', trim($lines)) as $line) {
    [$code, $name] = explode('|', $line, 2);
    $countries[$code] = $name;
}

return $countries;
