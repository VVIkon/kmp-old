<div class="service-form-add-services-list__issued js-service-form-add-service--issued-list">
  {{#issued}}
    <div 
      class="service-hotel-add-service service-hotel-add-service--issued js-service-form-add-service--issued-service" 
      data-add-service-id="{{id}}"
    >
      <div class="service-hotel-add-service__info">
        <i class="kmpicon kmpicon-{{icon}}"></i>
        <span class="service-hotel-add-service__type">
          {{typeName}}: 
        </span>
        <span class="service-hotel-add-service__name">
          {{name}}
        </span>
      </div>
      <div class="service-hotel-add-service__prices">
        <span class="service-hotel-add-service__local-price">
          {{localPrice}} {{localCurrency}} 
        </span>
        <span class="service-hotel-add-service__view-price">
          ({{viewPrice}} {{viewCurrency}})
        </span>
      </div>
      <div class="service-hotel-add-service__actions">
        {{#removeAllowed}}
          <button 
            type="button" 
            class="service-hotel-add-service__action service-hotel-add-service__action--remove js-service-form-add-service--action-remove"
            data-tooltip="Убрать дополнительную услугу"
          >
            <i class="kmpicon kmpicon-close"></i>
          </button>
        {{/removeAllowed}}
        {{^status}}
          {{#bookedWithService}}
            <span class="service-hotel-add-service__notice service-hotel-add-service__notice--book-with-service">
              Бронируется<br>
              вместе с услугой
            </span>
          {{/bookedWithService}}
          {{^bookedWithService}}
            <button type="button" class="service-hotel-add-service__action service-hotel-add-service__action--book js-service-form-add-service--action-book">
              Забронировать
            </button>
          {{/bookedWithService}}
        {{/status}}
        {{#status}}
          {{#waitingBook}}
            <span class="service-hotel-add-service__notice service-hotel-add-service__notice--waiting-book">
              Ожидает<br>
              бронирования
            </span>
          {{/waitingBook}}
          {{#isBooked}}
            <span class="service-hotel-add-service__notice service-hotel-add-service__notice--booked">
              Услуга<br>
              забронирована
            </span>
          {{/isBooked}}
          {{#isCancelled}}
            <span class="service-hotel-add-service__notice service-hotel-add-service__notice--cancelled">
              Услуга<br>
              отменена
            </span>
          {{/isCancelled}}
          {{#isVoided}}
            <span class="service-hotel-add-service__notice service-hotel-add-service__notice--cancelled">
              Услуга<br>
              отменена
            </span>
          {{/isVoided}}
        {{/status}}
      </div>
    </div>
  {{/issued}}
  {{^issued}}
    <div class="service-form-add-services-list__empty"> 
      Нет добавленных дополнительных услуг
    </div>
  {{/issued}}
</div>
{{#addingServicesAllowed}}
  <div class="service-form-add-services-list__actions">
    <span class="service-form-add-services-list__action service-form-add-services-list__action--show js-service-form--show-add-services-list active">
      Добавить дополнительную услугу
    </span>
    <span class="service-form-add-services-list__action service-form-add-services-list__action--hide js-service-form--hide-add-services-list">
      Скрыть список
    </span>
  </div>
{{/addingServicesAllowed}}
<div class="service-form-add-services-list__available js-service-form--available-add-services-list">
  {{#available}}
    <div 
      class="service-hotel-add-service service-hotel-add-service--available js-service-form-add-service--available-service" 
      data-add-service-id="{{id}}"
    >
      <div class="service-hotel-add-service__info">
        <i class="kmpicon kmpicon-{{icon}}"></i>
        <span class="service-hotel-add-service__type">
          {{typeName}}: 
        </span>
        <span class="service-hotel-add-service__name">
          {{name}}
        </span>
      </div>
      <div class="service-hotel-add-service__prices">
        <span class="service-hotel-add-service__local-price">
          {{localPrice}} {{localCurrency}} 
        </span>
        <span class="service-hotel-add-service__view-price">
          ({{viewPrice}} {{viewCurrency}})
        </span>
      </div>
      <div class="service-hotel-add-service__actions">
        {{#addingAllowed}}
          <button type="button" class="service-hotel-add-service__action service-hotel-add-service__action--add js-service-form-add-service--action-add">
            Добавить
          </button>
        {{/addingAllowed}}
        {{^addingAllowed}}
          <span class="service-hotel-add-service__notice service-hotel-add-service__notice--add">
            Добавлено
          </span>
        {{/addingAllowed}}
      </div>
    </div>
  {{/available}}
</div>
