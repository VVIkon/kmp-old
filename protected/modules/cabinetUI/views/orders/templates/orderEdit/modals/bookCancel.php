<div class="modal-book-cancel">
  <div class="modal-book-cancel__info">
    услуга: {{serviceName}}<br>
    {{#penalty}}
      При отмене брони будет начислен штраф в размере <b>{{localAmount}} {{localCurrency}}</b>
    {{/penalty}}
    {{^penalty}}
      За отмену брони не будут начислены штрафы
    {{/penalty}}
    {{#isInvoiceOptional}}
      <label class="juicy-checkbox modal-book-cancel__label">
        <input type="checkbox" hidden name="book-cancel" class="js-modal-book-cancel--set-invoice">
        <i class="control"></i>
        <span class="modal-book-cancel__label-text">Выставить счет на штраф</span>
      </label>
    {{/isInvoiceOptional}}
  </div>
  <p class="modal-book-cancel__question">Вы согласны продолжить операцию?</p>
</div>