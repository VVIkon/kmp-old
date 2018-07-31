<div class="modal-multi-book-cancel">
  <div class="modal-multi-book-cancel__info">
    {{#hasPenalty}}
      При отмене брони будут начислены штрафы:
    {{/hasPenalty}}
    {{^hasPenalty}}
      За отмену брони не будут начислены штрафы
    {{/hasPenalty}}
    <table class="modal-multi-book-cancel__service-list">
      <tr>
        <th>Услуга</th>
        <th>Штраф</th>
        <th>&nbsp;</th>
      </tr>
    {{#services}}
      <tr>
        <td>{{name}}</td>
        <td>{{#penalty}} {{localAmount}} {{localCurrency}}{{/penalty}}{{^penalty}}нет{{/penalty}}</td>
        <td>
          {{#isInvoiceOptional}}
            <label class="juicy-checkbox modal-multi-book-cancel__label">
              <input type="checkbox" hidden name="book-cancel" class="js-modal-multi-book-cancel--set-invoice" data-serviceid="{{serviceId}}">
              <i class="control"></i>
              <span class="modal-multi-book-cancel__label-text">выставить счет</span>
            </label>
          {{/isInvoiceOptional}}
        </td>
      </tr>
    {{/services}}
    </table>
  </div>
  <p class="modal-multi-book-cancel__question">Вы согласны продолжить операцию?</p>
</div>