<tr>
  <td class="ore-history-list__event-time">
    {{eventTime}}
  </td>
  <td class="ore-history-list__event-name">
    {{event}}
  </td>
  <td class="ore-history-list__order-status" title="{{orderStatusTitle}}">
    <i class="order-status-small order-sm-status-{{orderStatusIcon}}"></i>
    <b class="ore-history-list__delim"></b>
    {{orderStatusTitle}}
  </td>
  <td class="ore-history-list__user-name">
    {{userName}}
  </td>
  <td class="ore-history-list__service-name">
    {{#serviceName}}
      <i class="service-status-small service-sm-status-{{serviceStatusIcon}}" title="{{serviceStatusTitle}}"></i>
      &nbsp;
      {{.}}
    {{/serviceName}}
    {{^serviceName}}---{{/serviceName}}
  </td>
</tr>
<tr>
  <td class="ore-history-list__comment-title">Комментарий:</td>
  <td colspan="4" class="ore-history-list__comment">
    <i class="kmpicon {{#success}}kmpicon-success{{/success}}{{^success}}kmpicon-error{{/success}}"></i>
    {{eventComment}}
  </td>
</tr>
<tr><td colspan="5" class="ore-history-list__spacer"></td></tr>
