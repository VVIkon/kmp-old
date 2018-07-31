<div class="orl-list-item js-orl-list-item {{#isArchived}}orl-list-item--acrhived{{/isArchived}}" data-id="{{orderId}}">
  <div class="orl-list-item__pin"><i class="kmpicon kmpicon-pin-inactive"></i></div>
  <a class="orl-list-item__link" href="/cabinetUI/orders/order/{{orderId}}">
    <div class="orl-list-item__status {{#manualMode}}orl-list-item__status--manual{{/manualMode}}">
        <div class="orl-list-item__status-bg js-orl-list-item--status-bg"></div>
        <span class="js-orl-list-item--status-icon order-status order-status-{{orderStatusIcon}}" data-tooltip="{{orderStatusText}}"></span>
        <div class="orl-list-item__order-id">{{orderNumber}}</div>
    </div>
    <div class="orl-list-item__block orl-list-item__order-info">
        <div class="orl-list-item__infobar">
          {{#vip}}<span class="orl-list-item__vip-flag">VIP</span>{{/vip}}
          {{#offline}}<span class="orl-list-item__offline-flag" title="В заявке присутствуют оффлайн услуги">оффлайн</span>{{/offline}}
          <span class="orl-list-item__modification-date" title="дата изменения">{{dolc}}</span></div>
        <div class="orl-list-item__tourist-info">
          {{touristName}}
          {{touristCount}}
        </div>
        {{#dateAmend}}<div class="orl-list-item__date-amend">оплатить до: {{.}}</div>{{/dateAmend}}
        <!--<div class="history"><span class="tel"> </span><span class="email"> </span></div>-->
    </div>
    <div class="orl-list-item__block orl-list-item__trip-target">
      <div class="orl-list-item__dates">
        <span class="orl-list-item__date-in">{{startDate}}</span> - <span class="orl-list-item__date-out">{{endDate}}</span>
      </div>
      <div class="orl-list-item__location">
        <i class="country-flag country-{{countryCode}}"></i>{{country}}
      </div>
    </div>
    <div class="orl-list-item__block orl-list-item__services orl-list-item-services">
      {{{orderServices}}}
    </div>
    <div class="orl-list-item__block orl-list-item__payment-info">
      <div class="orl-list-item__kmp-info">
        <span class="orl-list-item__kmp-manager" data-tooltip="Менеджер КМП">{{KmpManagerName}}</span>
      </div>
      <div class="orl-list-item__prices">
        <span class="orl-list-item__price-local">
          {{localSum}} ₽
        </span><span class="orl-list-item__price-requested">
          ({{requestedSum}} {{{requestedCurrency}}})
        </span>
      </div>
      <div class="orl-list-item__client-info">
        {{#creator}}
          <span class="orl-list-item__client-manager">создал: {{.}}</span>
        {{/creator}}
        {{#clientCompany}}
          <span class="orl-list-item__client-company" 
            data-tooltip="Компания клиента: {{.}}{{#holdingCompany}} [{{.}}]{{/holdingCompany}}"
          >
          {{#holdingCompany}}<i class="kmpicon kmpicon-business-center"></i>{{/holdingCompany}}
          {{.}}
          </span>
        {{/clientCompany}}
      </div>
    </div>
  </a>
</div>
