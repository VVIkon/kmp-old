<div class="service-form-tos-agreement js-service-form-tos-agreement {{#agreementDisabled}}disabled{{/agreementDisabled}}">
  <label class="juicy-checkbox">
    <input type="checkbox" hidden name="agreement-{{serviceId}}" {{#agreementDisabled}}disabled{{/agreementDisabled}} {{#agreementSet}}checked{{/agreementSet}}>
    <i class="control"></i>
  </label>
  <span class="service-form-tos-agreement__text">
    С <span class="service-form-tos-agreement__tos-link js-service-form-tos-agreement--tos-link" data-link="companyTOS">условиями компании</span>, <span class="service-form-tos-agreement__tos-link js-service-form-tos-agreement--tos-link" data-link="hotelBookTerms-{{serviceId}}">правилами отмены брони</span> ознакомлен и согласен
  </span>
</div>
{{#save}}
  <button type="button" class="btn btn-medium btn-confirm js-service-form-actions--save">
    <i class="kmpicon kmpicon-success"></i>
    Сохранить
  </button>
{{/save}}
{{#OrderSync}}
  <button type="button" class="btn btn-medium btn-lemon js-service-form-actions--check-status">
    <i class="kmpicon kmpicon-change"></i>
    Проверить статус
  </button>
{{/OrderSync}}
{{#BookStart}}
  <button type="button" class="btn btn-medium btn-green js-service-form-actions--book">
    <i class="kmpicon kmpicon-confirm"></i>
    Забронировать
  </button>
{{/BookStart}}
{{#BookChange}}
  <button type="button" class="btn btn-medium btn-lime js-service-form-actions--book-change">
    <i class="kmpicon kmpicon-gear"></i>
    Изменить бронь
  </button>
{{/BookChange}}
{{#BookCancel}}
  <button type="button" class="btn btn-medium btn-lightred js-service-form-actions--book-cancel">
    <i class="kmpicon kmpicon-cancel"></i>
    Отменить бронь
  </button>
{{/BookCancel}}
{{#IssueTickets}}
  <button type="button" class="btn btn-medium btn-lemon js-service-form-actions--issue">
    <i class="kmpicon kmpicon-upload"></i>
    Выписать ваучер
  </button>
{{/IssueTickets}}
{{#PayStart}}
  <button type="button" class="btn btn-medium btn-blue js-service-form-actions--set-invoice">
    <i class="kmpicon kmpicon-ruble-coin"></i>
    Выставить счет
  </button>
{{/PayStart}}
{{#Manual}}
  <button type="button" class="btn btn-medium btn-brown js-service-form-actions--to-manual">
    <i class="kmpicon kmpicon-gear"></i>
    Перевести в ручной режим
  </button>
{{/Manual}}
{{#ToManager}}
  <button type="button" class="btn btn-medium btn-brown js-service-form-actions--to-manual">
    <i class="kmpicon kmpicon-gear"></i>
    Передать менеджеру
  </button>
{{/ToManager}}
{{#ManualEdit}}
  <button type="button" class="btn btn-medium btn-violet js-service-form-actions--manual-edit">
    <i class="kmpicon kmpicon-gear"></i>
    Редактировать услугу
  </button>
{{/ManualEdit}}
{{#ServiceChange}}
  <button type="button" class="btn btn-medium btn-blue js-service-form-actions--change">
    <i class="kmpicon kmpicon-change"></i>
    Изменить
  </button>
{{/ServiceChange}}
<!--
{{#ServiceCancel}}
  <button type="button" class="btn btn-medium btn-reset js-service-form-actions--cancel">
    <i class="kmpicon kmpicon-roundarrow"></i>
    Отменить услугу
  </button>
{{/ServiceCancel}}
-->