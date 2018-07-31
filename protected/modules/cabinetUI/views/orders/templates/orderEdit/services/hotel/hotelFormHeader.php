<div class="service-form-shortinfo">
  <div class="service-form-shortinfo__about">
    {{roomType}}
  </div>
  <div class="service-form-shortinfo__amend">
    &nbsp;
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
<table class="service-form-headerinfo service-hotel-header">
  <tr>
    <td class="service-form-headerinfo__status">
      <span class="service-status service-status-{{statusIcon}}" title="{{statusTitle}}"></span>
    </td>
    <td class="service-hotel-header-dates">
      {{#dateFrom}}
        <div class="service-hotel-header-dates__date-from">{{wd}}, {{dm}}</div>
      {{/dateFrom}}
      {{#dateTo}}
        <div class="service-hotel-header-dates__date-to">{{wd}}, {{dm}}</div>
      {{/dateTo}}
    </td>
    <td class="service-hotel-header-days">
      {{#days}}
        <div class="service-hotel-header-days__count">{{count}}</div>
        <div class="service-hotel-header-days__label">{{label}}</div>
      {{/days}}
    </td>
    <td class="service-hotel-header-hotelinfo">
      <div class="service-hotel-header-hotelinfo__title {{^isPartial}}js-service-hotel--hotel-link{{/isPartial}}" data-hotelid="{{hotelId}}" data-tooltip="Нажмите для просмотра полной информации">
        <span class="service-hotel-header-hotelinfo__name">{{hotelName}}</span>
        <span class="service-hotel-header-hotelinfo__stars">
          {{#category}}
          <i class="kmpicon kmpicon-star"></i>
          {{/category}}
        </span>
      </div>
      <div class="service-hotel-header-hotelinfo__address">
        {{hotelAddress}}
      </div>
    </td>
    <td class="service-hotel-header-residents">
      <i class="kmpicon kmpicon-adult" title="Взрослые"></i> {{residents.adults}}
      <i class="kmpicon kmpicon-child" title="Дети"></i> {{residents.children}}
    </td>
    <td class="service-hotel-header-extra">
      {{#reservationData}}
        <div class="service-hotel-header-extra__label">Номер брони</div>
        <div class="service-hotel-header-extra__reservation">
          {{number}}
          {{#voucherLink}}
            <a href="{{.}}" class="service-hotel-header-extra__voucher-download" target="_blank">
              <i class="kmpicon kmpicon-hotel-voucher"></i>
            </a>
          {{/voucherLink}}
        </div>
      {{/reservationData}}
    </td>
  </tr>
</table>
