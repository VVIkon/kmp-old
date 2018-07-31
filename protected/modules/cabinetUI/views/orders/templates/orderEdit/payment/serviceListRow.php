<tr>
  <td class="ore-payment-services-list__check">
    <label class="juicy-checkbox" title="Выбрать">
        <input class="js-ore-payment-services--check-service" type="checkbox" hidden {{#offline}}disabled data-offline="true"{{/offline}} name="check-{{serviceId}}" data-serviceid="{{serviceId}}">
        <i class="control"></i>
    </label>
  </td>
  <td class="ore-payment-services-list__status">
    <i class="service-status-small service-sm-status-{{statusIcon}}" title="{{statusTitle}}"></i>
  </td>
  <td class="ore-payment-services-list__service">
    {{#travelPolicyViolations}}
      <i class="kmpicon kmpicon-important ore-payment-services-list__tp-violations" data-tooltip="Нарушения трэвел-политик:<br><ul class='travel-policy-violations'>{{#list}}<li>{{.}}</li>{{/list}}</ul>"></i> 
    {{/travelPolicyViolations}}
    <i class="kmpicon kmpicon-{{serviceIcon}} {{#offline}}offline{{/offline}}" title="{{serviceTypeName}}"></i> 
    {{serviceName}}
  </td>
  <td class="ore-payment-services-list__info">
    {{^offline}}
      <span class="ore-payment-services-list__tos js-ore-payment-services--tos" data-serviceid="{{serviceId}}">
        условия отмены
      </span>
    {{/offline}}
    {{#offline}}
      <span class="ore-payment-services-list__offline">оффлайн услуга</span>
    {{/offline}}
  </td>
  {{#net}}<td class="ore-payment-services-list__net">{{.}} ₽</td>{{/net}}
  <td class="ore-payment-services-list__gross">{{gross}} ₽</td>
</tr>
