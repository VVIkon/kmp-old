<div id="order-edit-payment--tos-agreement" class="modal-accept-book-terms">
  <h2 class="modal-accept-book-terms__header">Подтвердите согласие с условиями брони</h2>
  {{#hasNonRefundableServices}}
    <div class="modal-accept-book-terms__non-refundable-warning">
      <i class="kmpicon kmpicon-warning"></i>
      В выбранном наборе услуг присутствуют невозвратные
    </div>
  {{/hasNonRefundableServices}}
  <table class="modal-accept-book-terms__service-list">
    <tr>
      <td>
        <span class="modal-accept-book-terms__tos-link js-modal-accept-book-terms--tos-link" data-tosdoc="companyTOS">Условия компании</span>
      </td>
    </tr>
    {{#services}}
      <tr>
        <td>
          <span class="modal-accept-book-terms__service-name">
            <i class="kmpicon kmpicon-{{icon}}" title="{{type}}"></i> {{name}}
          </span> - 
          <span class="modal-accept-book-terms__tos-link js-modal-accept-book-terms--tos-link" data-tosdoc="{{tosdoc}}">
            Условия отмены брони
          </span>
        </td>
      </tr>
    {{/services}}
  </table>
  <p class="modal-accept-book-terms__question">Вы согласны c условиями?</p>
</div>