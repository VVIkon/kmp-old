<?php

class FareRule {
  /** @var string название сегмента перелета */
  private $segment;
  /** @var array правила тарифа (как пришли от поставщика) */
  private $rules;
  /** @var array маппинг блоков праил */
  private $rulesMap;
  /** @var array вычислаемые при анализе правил тарифа флаги */
  private $flags;

  /**
  * @param string $segment название сегмента перелета
  * @param array $rules правила тарифа для сегмента
  */
  public function __construct($segment, $rules) {
    $this->segment = $segment;
    $this->rules = $rules;

    $this->mapRules();

    $this->flags = [
      'refund_before_rule' => null,
      'refund_before_penalty' => null,
      'refund_before_penalty_perc' => null,
      'refund_after_rule' => null,
      'refund_after_penalty' => null,
      'refund_after_penalty_perc' => null,
      'change_before_rule' => null,
      'change_before_penalty' => null,
      'change_before_penalty_perc' => null,
      'change_after_rule' => null,
      'change_after_penalty' => null,
      'change_after_penalty_perc' => null,
      'online_change' => null,
      'penalty_currency' => null
    ];
  }

  /**
  * Возвращает текст блока правил
  * @param string $block название блока согласно карте правил (NB: может отличаться от оригинального названия)
  * @return string|false текст блока или false в случае отсутствия такового
  */
  public function getBlockText($block) {
    if (!isset($this->rulesMap[$block])) {
      return false;
    } else {
      return $this->rules[$this->rulesMap[$block]]['text'];
    }
  }

  /**
  * Установка значения флага
  * @param string $flag название флага
  * @param mixed $value значение флага
  */
  public function setFlag($flag, $value) {
    $this->flags[$flag] = $value;
  }

  /**
  * Возвращает правила в виде, определенном ss_aviaFareRule
  * @return array правила тарифа
  */
  public function toArray() {
    return [
      'segment' => [
        'flightSegmentName' => $this->segment
      ],
      'aviaFareRule' => [
        'shortRules' => $this->flags,
        'rules' => $this->rules
      ]
    ];
  }

  /**
  * Создает карту массива правил, с переименованием блоков для удобства
  */
  private function mapRules() {
    $this->rulesMap = [];

    for ($i = 0, $len = count($this->rules); $i < $len; $i++) {
      $name = '';

      switch ($this->rules[$i]['name']) {
        case 'Penalties':
          $name = 'penalties';
          break;
        case 'Reissue (voluntary changes)':
          $name = 'reissue';
          break;
        default:
          $name = $this->rules[$i]['name'];
      }

      $this->rulesMap[$name] = $i;
    }
  }
}