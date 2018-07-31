<?php
/* 
* Правила парсинга авиатарифов.
* Функции определения флагов принимают массив результатов проверки паттернов 
* и возвращают значение флага.
*/
return [
  'flightFaresParsingRules' => [
    // парсинг блока Penalties
    'penalties' => [
      // паттерны, которые нужно удалить перед анализом блока
      'clear' => [
        'cd.1' => 'voluntary change is not permitted in case of surname change',
        'cd.2' => 'then([^.]{1,50})voluntary change is permitted',
        'cd.3' => 'else([^.]{1,50})voluntary change is not permitted',
        'cd.4' => 'involuntary change is permitted'
      ],
      // вычисляемые значения
      'flags' => [
        // возврат до вылета
        'refund_before_rule' => function($p) {
            if ( 
              $p['r.1'] || $p['r.2'] || $p['r.3'] || $p['r.4'] ||
              $p['r.9'] || $p['r.10'] || $p['r.11']
            ) { return true; }
            elseif (
              $p['r.5'] || $p['r.7']
            ) { return false; }
            else { return null; }
        },
        // возврат после вылета
        'refund_after_rule' => function($p) {
            if ( 
              $p['r.4']
            ) { return true; }
            elseif (
              $p['r.5'] || $p['r.6'] || $p['r.7'] || (!$p['rd.2'] && $p['r.8'])
            ) { return false; }
            else { return null; }
        },
        // обмен до вылета
        'change_before_rule' => function($p) {
          if (
            $p['c.1'] || $p['c.2'] || $p['c.3'] || $p['c.4'] ||
            $p['c.5'] || $p['c.6'] || $p['c.7'] || $p['c.10']
          ) { return true; }
          else { return null; }
        },
        // обмен после вылета
        'change_after_rule' => function($p) {
          if (
            $p['c.2'] || $p['c.5'] || $p['c.6'] || $p['c.7']
          ) { return true; }
          elseif (
            $p['c.8'] || $p['c.9']
          ) { return false; }
          else { return null; }
        }
      ],
      // искомые паттерны
      'patterns' => [
        // паттерны определения возможности возврата
        'rd.2' => 'fare is non-refundable if upgraded ticket is subsequently cancelled',
        'r.1' => 'voluntary refund is permitted from([^.]{1,50})before departure',
        'r.2' => 'voluntary refund is permitted up to([^.]{1,50})before departure',
        'r.3' => 'voluntary refund is permitted',
        'r.4' => 'voluntary refund is permitted after departure',
        'r.5' => 'ticket is non-refundable in case of cancel\/no-show\/ refund',
        'r.6' => 'any time ticket is non-refundable',
        'r.7' => 'after departure ticket is non-refundable',
        'r.8' => 'fare is non-refundable',
        'r.9' => 'refund of unused fees and taxes permitted',
        'r.10' => 'voluntary refund of unused fees and taxes permitted',
        'r.11' => 'refund must be made within 12 months from ticketing date',
        // паттерны определения возможности обмена
        'c.1' => 'voluntary change is permitted([^.]{0,10}(from|up to)[^.]{1,50})before departure',
        'c.2' => 'voluntary change is permitted after departure',
        'c.3' => 'voluntary change is permitted',
        'c.4' => 'changes before outbound departure([^.]{1,50})when the first fare component is changed',
        'c.5' => 'changes after departure([^.]{1,50})the entire ticket must be re-priced',
        'c.6' => 'changes any time per ticket charge([^.]{1,50})for reissue\/revalidation',
        'c.7' => 'any changes of travel type are permitted',
        'c.8' => 'changes for no-show segment([^.]{1,20})not permitted',
        'c.9' => 'changes not permitted in case of no-show',
        'c.10' => 'changes before departure charge'
      ]
    ],
    // парсинг блока Reissue
    'reissue' => [
      // вычисляемые значения
      'flags' => [
        'online_change' => function($p) {
            if ($p['o.1']) { return true; }
            else { return null; }
        }
      ],
      // искомые паттерны
      'patterns' => [
        'o.1' => 'voluntary changes conditions may apply for automated reissue'
      ]
    ]
  ]
];