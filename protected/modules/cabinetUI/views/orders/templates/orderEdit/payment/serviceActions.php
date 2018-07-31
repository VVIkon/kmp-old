{{#BookStart}}
  <button type="button" class="btn btn-medium btn-green js-ore-payment-services--book" {{^validated}}disabled{{/validated}}>
    <i class="kmpicon kmpicon-confirm"></i>
    Забронировать
  </button>
{{/BookStart}}
{{#BookCancel}}
  <button type="button" class="btn btn-medium btn-lightred js-ore-payment-services--book-cancel" {{^validated}}disabled{{/validated}}>
    <i class="kmpicon kmpicon-cancel"></i>
    Отменить бронь
  </button>
{{/BookCancel}}
{{#IssueTickets}}
  <button type="button" class="btn btn-medium btn-lemon js-ore-payment-services--issue" {{^validated}}disabled{{/validated}}>
    <i class="kmpicon kmpicon-upload"></i>
    Выписать ваучер
  </button>
{{/IssueTickets}}
{{#PayStart}}
  <button type="button" class="btn btn-medium btn-blue js-ore-payment-services--set-invoice" {{^validated}}disabled{{/validated}}>
    <i class="kmpicon kmpicon-ruble-coin"></i>
    Выставить счет
  </button>
{{/PayStart}}
{{#Manual}}
  <button type="button" class="btn btn-medium btn-brown js-ore-payment-services--to-manual" {{^validated}}disabled{{/validated}}>
    <i class="kmpicon kmpicon-gear"></i>
    Перевести в ручной режим
  </button>
{{/Manual}}
{{#ToManager}}
  <button type="button" class="btn btn-medium btn-brown js-ore-payment-services--to-manual" {{^validated}}disabled{{/validated}}>
    <i class="kmpicon kmpicon-gear"></i>
    Передать менеджеру
  </button>
{{/ToManager}}
{{#ServiceChange}}
  <button type="button" class="btn btn-medium btn-blue js-ore-payment-services--change" {{^validated}}disabled{{/validated}}>
    <i class="kmpicon kmpicon-change"></i>
    Изменить
  </button>
{{/ServiceChange}}
<!--
{{#ServiceCancel}}
  <button type="button" class="btn btn-medium btn-reset js-ore-payment-services--cancel" {{^validated}}disabled{{/validated}}>
    <i class="kmpicon kmpicon-roundarrow"></i>
    Отменить услугу
  </button>
{{/ServiceCancel}}
-->