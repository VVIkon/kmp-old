<tr data-serviceid="{{serviceId}}" class="js-ore-set-invoice--service {{#disabled}}disabled{{/disabled}}">
  <td class="sl-check">
    <label class="juicy-checkbox">
      <input type="checkbox" hidden class="js-ore-set-invoice--service-check" {{#disabled}}disabled{{/disabled}} id="setinv-check-{{serviceId}}">
      <i class="control"></i>
    </label>
  </td>
  <td class="sl-service"><label for="setinv-check-{{serviceId}}" title="{{serviceName}}">{{serviceName}}</label></td>
  <td class="sl-status"><i class="service-status-small service-sm-status-{{statusIcon}}"></i><i class="delim"></i>{{statusTitle}}</td>
  <td class="sl-price">{{price}} {{currencyIcon}}</td>
  <td class="sl-topay"><input type="text" name="bill-{{serviceId}}" class="js-ore-set-invoice--service-sum" {{#disabled}}disabled{{/disabled}} placeholder="{{leftToPay}}"> {{currencyIcon}}</td>
</tr>
