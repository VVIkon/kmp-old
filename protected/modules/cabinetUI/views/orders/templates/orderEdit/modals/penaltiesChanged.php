<div class="modal-prices-changed">
  <h2 class="modal-prices-changed__header">Штрафы за отмену брони изменились</h2>
  <table class="modal-prices-changed__pricing-table">
    <tr>
      <th>&nbsp;</th>
      <th>старый штраф</th>
      <th class="modal-prices-changed__arrow">&nbsp;</th>
      <th>новый штраф</th>
    </tr>
    {{#client}}
      <tr>
        <td>Штраф к оплате</td>
        <td>{{localOldPenalty}} {{localCurrency}}</td>
        <td class="modal-prices-changed__arrow"><i class="kmpicon kmpicon-arrow-right"></i></td>
        <td>{{localNewPenalty}} {{localCurrency}}</td>
      </tr>
    {{/client}}
    {{#supplier}}
      <tr>
        <td>Штраф поставщика</td>
        <td>{{localOldPenalty}} {{localCurrency}}</td>
        <td class="modal-prices-changed__arrow"><i class="kmpicon kmpicon-arrow-right"></i></td>
        <td>{{localNewPenalty}} {{localCurrency}}</td>
      </tr>
    {{/supplier}}
  </table>
</div>