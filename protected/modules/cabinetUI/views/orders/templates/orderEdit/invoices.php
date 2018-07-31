<div class="lightbox-block__title">Получить счет за услуги</div>
<div class="lightbox-block__content js-ore-set-invoice--content">
  <div class="service-list pretty-table-right pretty-table--striped">
    <table>
      <thead>
        <tr>
          <th class="sl-check">&nbsp;</th>
          <th class="sl-service">Услуга</th>
          <th class="sl-status">Статус</th>
          <th class="sl-price">Цена</th>
          <th class="sl-topay">К оплате</th>
        </tr>
      </thead>
      <tbody>
        {{{services}}}
      </tbody>
      <tfoot>
        <tr>
          <th class="sl-check">&nbsp;</th>
          <th class="sl-totaltext" colspan="3">Итого</th>
          <th class="sl-total"><span class="js-ore-set-invoice--total-invoice">0</span> {{{currencyCode}}}</th>
        </tr>
      </tfoot>
    </table>
  </div>
  <div class="payform-select">
    <label for="SetInv-SelectPayment">Форма оплаты</label>
    <div class="select-control">
      <select id="SetInv-SelectPayment">
          <option value="banktransfer">Банковский перевод</option>
      </select>
    </div>
  </div>
  <div class="set-inv-buttons">
    <button data-action="cancel" class="btn btn-medium btn-reset js-ore-set-invoice--action-cancel">
      <i class="kmpicon kmpicon-roundarrow"></i>
      Отмена
    </button>
    <button data-action="setinvoice" class="btn btn-medium btn-yellow js-ore-set-invoice--action-set">
      <i class="kmpicon kmpicon-ruble-coin"></i>
      Выставить счёт
    </button>
  </div>
</div>