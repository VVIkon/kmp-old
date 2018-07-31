<div>
  <table class="additional-service__block pretty-table pretty-table--striped">
    <tr>
      <td class="checker">
        <label class="juicy-checkbox">
          <input type="checkbox" id="additional-service--required" hidden class="js-additional-service--required">
          <i class="control"></i>
        </label>
      </td>
      <td>
        <label for="additional-service--required">
          Отметить как обязательную для бронирования <br>
          <i>(если дополнительная услуга не забронируется,<br>
            основная тоже не будет забронирована</i>
        </label>
      </td>
    </tr>
  </table>
</div>
<div>
  <table class="additional-service__block pretty-table pretty-table--striped">
    <thead>
      <tr>
        <th colspan="2">Время заезда</th>
      </tr>
    </thead> 
    <tbody>
      {{#arrivalTimes}}
        <tr>
          <td class="checker">
            <label class="juicy-radio">
              <input type="radio" hidden 
                id="additional-service--arrival-{{id}}" 
                value="{{id}}" 
                name="additional-service--arrival" 
                class="js-early-arrival--time"
              >
              <i class="control"></i>
            </label>
          </td>
          <td>
            <label for="additional-service--arrival-{{id}}">
              Заезд с {{time}}
            </label>
          </td>
        </tr>
      {{/arrivalTimes}}
      </tbody>
  </table>
</div>
