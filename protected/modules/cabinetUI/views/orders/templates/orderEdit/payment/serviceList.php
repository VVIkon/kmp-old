<div class="ore-payment-services-list pretty-table-right pretty-table--striped">
  <table>
    <thead>
      <tr>
        <th class="ore-payment-services-list__check-header">&nbsp;</th>
        <th class="ore-payment-services-list__status-header">Статус</th>
        <th class="ore-payment-services-list__service-header" colspan="2">Услуга</th>
        {{#showNetPrice}}<th class="ore-payment-services-list__net-header">Нетто</th>{{/showNetPrice}}
        <th class="ore-payment-services-list__gross-header">Брутто</th>
      </tr>
    </thead>
    <tbody>
      {{{serviceList}}}
    </tbody>
    <tfoot>
      <tr class="ore-payment-services-list__actions">
        <td class="ore-payment-services-list__service-actions js-ore-payment-services--service-actions" colspan="{{#discount}}3{{/discount}}{{^discount}}6{{/discount}}">
         {{{serviceActions}}}
        </td>
        {{#discount}}
          <td class="ore-payment-services-list__discount-label">Скидка клиенту:</td>
          <td class="ore-payment-services-list__discount">
            <input class="js-ore-payment-services--discount" type="text" name="discount" value="{{.}}" class="number">
            ₽
          </td>
        {{/discount}}
      </tr>
      <tr>
        <th colspan="{{#showNetPrice}}4{{/showNetPrice}}{{^showNetPrice}}3{{/showNetPrice}}">Итого</th>
        <th class="ore-payment-services-list__total">{{#showNetPrice}}{{totalNet}} ₽{{/showNetPrice}}{{^showNetPrice}}&nbsp;{{/showNetPrice}}</th>
        <th class="ore-payment-services-list__total">{{totalGross}} ₽</th>
      </tr>
    </tfoot>
  </table>
</div>
<div class="ore-payment-services__commission">
  <table>
    <tr>
      <td>Комиссия агента:</td>
      <td>{{totalCommission}} ₽</td>
    </tr>
    {{#profit}}
      <tr>
        <td>Прибыль:</td>
        <td>{{.}} ₽</td>
      </tr>
    {{/profit}}
  </table>
</div>
