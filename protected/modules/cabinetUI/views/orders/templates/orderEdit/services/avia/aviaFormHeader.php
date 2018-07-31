<div class="service-form-shortinfo">
  <div class="service-form-shortinfo__about">
    {{#firstFlightNum}}
      № рейса: {{.}}
    {{/firstFlightNum}}
  </div>
  <div class="service-form-shortinfo__amend">
    {{#amendDate}}
      Оплатить до: {{.}}
    {{/amendDate}}
  </div>
  <div class="service-form-shortinfo__price {{#priorityOffer}}service-form-shortinfo__price--priority-offer{{/priorityOffer}}">
    Стоимость: 
    {{#priorityOffer}}
      <i class="kmpicon kmpicon-star" data-tooltip="Предпочтительное предложение"></i>
    {{/priorityOffer}}
    <span 
      class="service-form-shortinfo__price-local" 
      {{#priorityOffer}}data-tooltip="Предпочтительное предложение"{{/priorityOffer}}
    >
      {{priceLocal}} ₽
    </span>
    <span class="service-form-shortinfo__price-requested">({{priceInView}} {{viewCurrencyIcon}})</span>
    {{#priceFactors}}
      <span 
        class="service-form-shortinfo__tax" 
        data-tooltip="
          <div class='price-factors-tooltip'>
            {{#taxes}}
              <table class='pretty-table pretty-table--striped'>
                <tr><td colspan='2'><b>Налоги и сборы:</b></td></tr>
                {{#list}}
                  <tr>
                    <td>{{description}}</td>
                    <td class='price-factors-tooltip__price'>{{amount}} {{currencyIcon}}</td>
                  </tr>
                {{/list}}
              </table>
            {{/taxes}}
            {{#supplierCommission}}
              <table class='pretty-table pretty-table--striped'>
                <tr>
                  <td><b>Комиссия поставщика:</b></td>
                  <td class='price-factors-tooltip__price'>{{amount}} {{currencyIcon}}</td>
                </tr>
              </table>
            {{/supplierCommission}}
          </div>
        "
      >
        <i class="kmpicon kmpicon-tax"></i>
      </span>
    {{/priceFactors}}
  </div>
</div>
<table class="service-form-headerinfo service-avia-header">
  <tr>
    <td class="service-form-headerinfo__status">
      <span class="service-status service-status-{{statusIcon}}" title="{{statusTitle}}"></span>
    </td>
    <td class="service-avia-header-dates">
      {{#dates}}
        <div class="service-avia-header-dates__date-to">{{wd}}, {{dm}}</div>
      {{/dates}}
    </td>
    {{^serviceName}}
      <td class="service-avia-header-flights">
        {{#flights}}
          <div class="service-avia-header-flights__flight">
          {{dep.city}}
          (<i class="service-avia-header-flights__iata">{{dep.iata}}</i>) -
          {{arr.city}}
          (<i class="service-avia-header-flights__iata">{{arr.iata}}</i>)</div>
        {{/flights}}
      </td>
    {{/serviceName}}
    {{#serviceName}}
      <td class="service-avia-header-name">
        {{.}}
      </td>
    {{/serviceName}}
    <td class="service-avia-header-passengers">
      <i class="kmpicon kmpicon-adult" title="Взрослые"></i> {{passengers.adult}}
      <i class="kmpicon kmpicon-child" title="Дети"></i> {{passengers.child}}
      <i class="kmpicon kmpicon-infant" title="Младенцы"></i> {{passengers.infant}}
    </td>
    <td class="service-avia-header-extra">
      <div class="service-avia-header-extra__pnr">
        {{#pnrNumber}}PNR: {{.}}{{/pnrNumber}}
        {{#ticketLink}}
        <a href="{{.}}" class="service-avia-header-extra__ticket-download" target="_blank" title="Скачать маршрутные квитанции">
          <i class="kmpicon kmpicon-avia-ticket"></i>
        </a>
        {{/ticketLink}}
      </div>
    </td>
  </tr>
</table>
