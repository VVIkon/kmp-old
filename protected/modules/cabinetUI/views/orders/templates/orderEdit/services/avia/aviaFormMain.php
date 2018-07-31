<!-- Route -->
<div class="row">
  <div class="col-xs-2">
    <span class="content-tab__row-label">Маршрут</span>
  </div>
  <div class="col-xs-10 service-form-section">
    {{#offerSupplier}}
      <div class="service-form-info-field">
        Поставщик: <span class="service-form-info-field__value">{{.}}</span>
      </div>
    {{/offerSupplier}}
    <div class="service-form-info-field">
      Выписать билет до: 
      <span class="service-form-info-field__value">
        {{#lastTicketingDate}}{{.}}{{/lastTicketingDate}}
        {{^lastTicketingDate}}не указано{{/lastTicketingDate}}
      </span>
    </div>
    <!-- flight trips here -->
    <div>
      {{#flightTrips}}
        {{{.}}}
      {{/flightTrips}}
    </div>
    <div class="service-form-flags service-avia-flags">
      {{#overNightFlight}}
        <span class="service-form-flags__flag">
          <i class="kmpicon kmpicon-night"></i>
          Ночной перелет
        </span>
      {{/overNightFlight}}
      {{#nightTransfer}}
        <span class="service-form-flags__flag">
          <i class="kmpicon kmpicon-eye"></i>
          Ночные пересадки
        </span>
      {{/nightTransfer}}
    </div>
  </div>
</div>
<!-- PNR Baggage -->
{{#pnrBaggageInfo}}
<div class="row">
  <div class="col-xs-2">
    <span class="content-tab__row-label">Багаж</span>
  </div>
  <div class="col-xs-10 service-form-section service-avia-pnr-baggage">
    <i class="service-avia-pnr-baggage__facet"></i>
    <span class="service-avia-pnr-baggage__label">
      <i clasl="kmpicon kmpicon-baggage"></i>
      Нормы провоза багажа: 
    </span>
    {{#info}}
      <span class="service-avia-pnr-baggage__info-item">
        {{measureQuantity}} {{measureCode}}
      </span>
    {{/info}}
    {{^info}}
      <span class="service-avia-pnr-baggage__no-info">
        Нет информации
      </span>
    {{/info}}
  </div>
</div>
{{/pnrBaggageInfo}}
<!-- Ticket -->
{{#ticketLink}}
<div class="row">
  <div class="col-xs-2">
    <span class="content-tab__row-label">Билеты</span>
  </div>
  <div class="col-xs-10 service-form-section service-avia-ticket">
    <a href="{{.}}" class="service-avia-ticket__download" target="_blank">
      <i class="kmpicon kmpicon-avia-ticket"></i>
      Скачать маршрутные квитанции
    </a>
  </div>
</div>
{{/ticketLink}}