<div class="lightbox-block__title">Условия отмены бронирования отеля</div>
<div class="lightbox-block__content lightbox-block__content--staticdoc">
  <div class="service-hotel-book-terms">
    <h2>отель: {{hotelName}}</h2>
    <h3>номер: {{roomType}}</h3>
    <div class="pretty-table pretty-table--3-row-striped">
      <table>
        <thead>
          <tr>
            <th colspan="2">Штрафы за отмену бронирования</th>
          </tr>
        </thead>
        {{#penalties}}
          <tbody>
            <tr>
              <td>Штраф действует:</td>
              <td>с {{dateFrom}} по {{dateTo}}</td>
            </tr>
            <tr>
              <td>Сумма штрафа:</td>
              <td>{{localAmount}} {{localCurrency}} ({{viewAmount}} {{viewCurrency}})</td>
            </tr>
            <tr>
              <td>Описание:</td>
              <td>
                {{#description}}{{.}}{{/description}}
                {{^description}}отсутствует{{/description}}
              </td>
            </tr>
          </tbody>
        {{/penalties}}
        {{^penalties}}
          <tbody>
            <tr>
              <td colspan="2">Штрафов нет</td>
            </tr>
          </tbody>
        {{/penalties}}
      </table>
    </div>
    <p>* Сумма штрафа выставляется по курсу ЦБ на день выставления штрафа</p>
  </div>
</div>
