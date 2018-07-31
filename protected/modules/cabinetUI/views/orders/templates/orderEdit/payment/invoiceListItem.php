<div class="ore-invoice js-ore-invoice" data-invoiceid="{{invoiceId}}">
  <div  class="ore-invoice__header js-ore-invoice--header">
    <span class="ore-invoice__status">
      <i class="invoice-status invoice-{{statusIcon}}" data-tooltip="{{statusTitle}}"></i>
      <i class="ore-invoice__delim"></i>
    </span>
    <span class="ore-invoice__number" title="Счет № {{number}}">№{{number}}</span>
    <span class="ore-invoice__date">от {{creationDate}}</span>
    <span class="ore-invoice__name" title="{{description}}">{{description}}</span>
    <span class="ore-invoice__sum">{{sum}} {{{currencySign}}}</span>
    <div class="toggler"></div>
  </div>
  <div class="ore-invoice__description js-ore-invoice--description">
    <div class="ore-invoice-services">
      <table>
        {{{serviceDetails}}}
      </table>
      <div class="ore-invoice-services__delim"></div>
    </div>
    {{#actions}}
      <div class="ore-invoice-actions">
        {{#cancel}}
          <button type="button" class="btn btn-medium btn-lightred js-ore-invoice-actions--cancel">
            <i class="kmpicon kmpicon-cancel"></i>
            Отменить счет
          </button>
        {{/cancel}}
      </div>
    {{/actions}}
  </div>
</div>
