<form 
  class="service-form service-{{serviceTypeName}} {{#isCancelled}}is-cancelled{{/isCancelled}} js-service-form" 
  data-sid="{{serviceId}}" 
  data-serviceType="{{serviceType}}"
>
  <div class="row">
    <div class="col-xs-2">
      <span class="content-tab__row-label"><i class="kmpicon kmpicon-{{serviceIcon}}"></i> {{serviceName}}</span>
    </div>
    <div class="col-xs-10 service-form-header js-service-form-header">
    <!-- form header -->
      {{#isCancelled}}
      <div class="service-form-shortinfo">
        <div class="service-form-shortinfo__cancel-notification">
          Услуга отменена
        </div>
        <div class="service-form-shortinfo__penalty">
          {{#penalty}}
            {{#supplier}}
              Штраф поставщика: <span class="service-form-shortinfo__penalty-local">{{inLocal}} ₽</span>
              <span class="service-form-shortinfo__penalty-requested">({{inView}} {{viewCurrencyIcon}})</span>
              ,&nbsp;
            {{/supplier}}
            {{#client}}
              Штраф к оплате: <span class="service-form-shortinfo__penalty-local">{{inLocal}} ₽</span>
              <span class="service-form-shortinfo__penalty-requested">({{inView}} {{viewCurrencyIcon}})</span>
            {{/client}}
          {{/penalty}}
        </div>
      </div>
      {{/isCancelled}}
      {{{header}}}
      <div class="toggler"></div>
    </div>
  </div>
  <div class="service-form-content js-service-form-content">
    <!-- Offer info -->
    {{{main}}}
    <!-- Additional services -->
    {{#additionalServices}}
      <div class="row">
        <div class="col-xs-2">
          <span class="content-tab__row-label">Дополнительные услуги</span>
        </div>
        <div class="col-xs-10 service-form-section service-form-add-services-list js-service-form--add-services">
          {{{.}}}
        </div>
      </div>
    {{/additionalServices}}
    <!-- TP violations -->
    {{#travelPolicyViolations}}
      <div class="row">
        <div class="col-xs-2"></div>
        <div class="col-xs-10 service-form-section service-form-tp-violations">
          <div class="service-form-tp-violations__header">
            <i class="kmpicon kmpicon-warning"></i>
            Нарушения тревел-политики:
          </div>
          <ul class="service-form-tp-violations__list">
            {{#list}}
              <li class="service-form-tp-violations__list-item">{{.}}</li>
            {{/list}}
          </ul>
        </div>
      </div>
    {{/travelPolicyViolations}}
    <!-- minimal price -->
    {{{minimalPrice}}}
    <!-- Custom fields -->
    {{#hasCustomFields}}
      <div class="row">
        <div class="col-xs-2">
          <span class="content-tab__row-label">Дополнительно</span>
        </div>
        <div class="col-xs-10 service-form-section service-form-custom-fields">
          <div class="js-service-form-custom-fields">
            <!-- custom fields here -->
          </div>
          <!-- TODO: rework
          <div class="clearfix"></div>
          <div class="service-form-custom-fields__noty">
            Знаком (<i class="service-form-custom-fields__required-mark kmpicon kmpicon-star"></i>) отмечены обязательные для заполнения поля
          </div>
          -->
        </div>
      </div>
    {{/hasCustomFields}}
    <!-- Tourists -->
    <div class="row">
      <div class="col-xs-2">
        <span class="content-tab__row-label">Туристы</span>
      </div>
      <div class="col-xs-10 service-form-section service-form-touristlist">
        <div class="js-service-form-tourists">
          <!-- tourists here -->
          {{{tourists}}}
        </div>
        {{#allowTouristAdd}}
          <div class="service-form-touristlist__empty">
            <span class="iconed-link plus js-service-form--add-tourist">Добавить туриста</span>
          </div>
          <div class="clearfix"></div>
        {{/allowTouristAdd}}
      </div>
    </div>
    <!-- Buttons -->
    <div class="row">
      <div class="col-xs-2">
        &nbsp;
      </div>
      <div class="col-xs-10 service-form-section service-form-actions js-service-form-actions">
        <!-- service actions here -->
        <span class="service-form-actions__loading">Проверка доступных действий...</span>
      </div>
    </div>
  </div>
</form>
