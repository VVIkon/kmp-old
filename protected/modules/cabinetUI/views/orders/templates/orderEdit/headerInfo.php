<div class="ore-header-info__wrapper-status">
    <i class="ore-header-info__status-icon order-status order-status-{{orderStatusIcon}}" title="{{orderStatusTitle}}"></i>
    <div 
      class="ore-header-info__orderid {{#allowOrderChat}}js-open-chat{{/allowOrderChat}}" 
      {{#allowOrderChat}}
        data-orderid="{{orderId}}" 
      {{/allowOrderChat}}
      {{#gatewayOrderIds}}
        data-tooltip="{{#gp}}ID заявки в GPTS: {{.}}{{/gp}}<br>{{#utk}}ID заявки в УТК: {{.}}{{/utk}}"
      {{/gatewayOrderIds}}
    >
      {{#gatewayOrderIds}}
        <i class="ore-header-info__gateway-ids kmpicon kmpicon-info"></i>{{#orderNumber}}{{.}}{{/orderNumber}}{{^orderNumber}}Новая{{/orderNumber}}<i class="ore-header-info__icon-spacer"></i>
      {{/gatewayOrderIds}}
      {{^gatewayOrderIds}}
        {{#orderNumber}}{{.}}{{/orderNumber}}{{^orderNumber}}Новая{{/orderNumber}}
      {{/gatewayOrderIds}}
    </div>
</div>
<div class="ore-header-info__wrapper-brief">
  <div class="ore-header-info__topbar">
    {{#vip}}<span class="ore-header-info__vip-flag">VIP</span>{{/vip}}
    <span class="ore-header-info__creation-info">
      дата создания: {{creationDate}}{{#creator}},{{/creator}}
      {{#creator}}
        {{#allowChat}}
          <span class="ore-header-info__chat-link js-open-chat" 
            data-userid="{{id}}" 
            data-tooltip="Открыть чат"
          >
            создатель заявки: {{fullName}}
          </span>
        {{/allowChat}}
        {{^allowChat}}
          создатель заявки: {{fullName}}
        {{/allowChat}}
      {{/creator}}
    </span>
  </div>
  <div class="ore-header-info__wrapper-details">
    <div class="ore-header-info__tourleader-name {{^touristSet}}ore-header-info__tourleader-name--empty{{/touristSet}}">
      {{touristName}}
      {{touristCount}}
    </div>
    <div class="ore-header-info__tourleader-contacts">
      {{#touristTel}}<span><i class="kmpicon kmpicon-call" title="{{.}}"></i>{{.}}</span>{{/touristTel}}
      {{#touristEmail}}<span><i class="kmpicon kmpicon-envelope" title="{{.}}"></i>{{.}}</span>{{/touristEmail}}
    </div>
    <div class="ore-header-info__wrapper-client-info">
      {{#kmpManager}}
        <div class="ore-header-info__kmp-manager">
          {{#allowChat}}
            <span class="ore-header-info__chat-link js-open-chat" data-userid="{{id}}" data-tooltip="Открыть чат">
          {{/allowChat}}
            Менеджер КМП: {{fullName}}
          {{#allowChat}}</span>{{/allowChat}}
        </div>
      {{/kmpManager}}
      {{#clientManager}}
        <div class="ore-header-info__client-manager">
          {{#allowChat}}
            <span class="ore-header-info__chat-link js-open-chat" data-userid="{{id}}" data-tooltip="Открыть чат">
          {{/allowChat}}
            Менеджер клиента: {{fullName}}
          {{#allowChat}}</span>{{/allowChat}}
        </div>
      {{/clientManager}}
      {{#clientCompany}}
        <div class="ore-header-info__client-company">
          Клиент: {{.}}
        </div>
      {{/clientCompany}}
      {{#holdingCompany}}
        <div class="ore-header-info__client-company">
          [{{.}}]
        </div>
      {{/holdingCompany}}
    </div>
  </div>
</div>
<div class="ore-header-info__delim"></div>
<div class="ore-header-info__wrapper-cost">
  <div class="ore-header-info__cost-local">{{localSum}} ₽</div>
  <div class="ore-header-info__cost-requested">({{requestedSum}} {{requestedCurrency}})</div>
</div>
