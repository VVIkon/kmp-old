<div class="service-avia-triproute">
  <div class="service-avia-triproute__logo">
    <div class="service-avia-triproute__logo-wrapper">
      <i class="service-avia-triproute__logo-cell">
        <img src="/app/img/airlinelogos/{{#alLogo}}{{.}}{{/alLogo}}{{^alLogo}}nologo{{/alLogo}}.png" title="{{alName}}">
      </i>
    </div>
  </div>
  <table class="service-avia-tripsegments">
  {{#segments}}
<!-- TODO: add also stops - tr ? -->
  <tbody class="service-avia-segment">
    <tr>
      <td rowspan="{{inforows}}" class="service-avia-segment__number">
        {{flightNum}}
        <span class="service-avia-segment__plane-type">{{aircraft}}</span>
      </td>
      <td rowspan="{{inforows}}" class="service-avia-segment__class">
        <i title="класс обслуживания: {{#className}}{{.}}{{/className}}{{^className}}не определен{{/className}}">{{class}}</i>
      </td>
      <td class="service-avia-segment__point">
        {{startpoint.city}}
        (<span class="service-avia-segment__point-airport" title="{{startpoint.airport}}">{{startpoint.iata}}</span>)
        {{startpoint.terminal}}
      </td>
      <td class="service-avia-segment__arrow">
        <i class="kmpicon kmpicon-arrow-right"></i>
      </td>
      <td class="service-avia-segment__point">
        {{endpoint.city}}
        (<span class="service-avia-segment__point-airport" title="{{endpoint.airport}}">{{endpoint.iata}}</span>)
        {{endpoint.terminal}}
      </td>
    </tr>
    <tr>
      <td class="service-avia-segment__date">
        <span class="service-avia-segment__date-time">{{startpoint.time}}</span> {{startpoint.date}}
      </td>
      <td class="service-avia-segment__time">
        <span><i class="kmpicon kmpicon-clockwatch"></i> {{#dhours}}{{.}}ч.{{/dhours}} {{#dminutes}}{{.}}м.{{/dminutes}}</span>
      </td>
      <td class="service-avia-segment__date">
        <span class="service-avia-segment__date-time">{{endpoint.time}}</span> {{endpoint.date}}
      </td>
    </tr>
    {{#transporter}}
    <tr>
      <td colspan="3" class="service-avia-segment__transporter">
        <i class="kmpicon kmpicon-info"></i>
        Перевозку осуществляет {{name}}
      </td>
    </tr>
    {{/transporter}}
    {{#stops}}
    <tr>
      <td colspan="3" class="service-avia-segment__stops">
        <i class="kmpicon kmpicon-hourglass"></i>
        Остановки:
        {{#points}}
          {{location}} - {{hours}}ч. {{minutes}}м.;
        {{/points}}
      </td>
    </tr>
    {{/stops}}
    {{#waiting}}
    <tr>
      <td colspan="6" class="service-avia-segment__waiting">
        <i class="kmpicon kmpicon-hourglass"></i>
        Время на пересадку: {{hours}}ч. {{minutes}}м.
      </td>
    </tr>
    {{/waiting}}
  </tbody>
  {{/segments}}
  </table>
</div>
