<?php
/**
 * Вывод информации в шапке сайта
 */

class HeaderInfo extends CWidget {

  public function run()
  {
      /*
      if (Yii::app()->session->contains('userData')) {
        $userData = Yii::app()->session->get('userData');

        $viewCurrency = $userData['viewCurrency'];
        $prices = $userData['prices'];
        $user = $userData;

      } else {
        $viewCurrency = 978;
        $prices = 'gross';
        $user = [
          'role' => 'guest'
        ];
      } */

      $currates = [
        [
          'code' => e(CurrencyRate::CURRENCY_CODE_USD),
          'rate' => CurrencyRate::rateById(CurrencyRate::CURRENCY_ID_USD),
          'isocode' => 'USD'
        ],
        [
          'code' => e(CurrencyRate::CURRENCY_CODE_EUR),
          'rate' => CurrencyRate::rateById(CurrencyRate::CURRENCY_ID_EUR),
          'isocode' => 'EUR'
        ]
      ];

      $this->render('HeaderInfoClient',[
        'currates' => $currates
      ]);
  }

}

?>
