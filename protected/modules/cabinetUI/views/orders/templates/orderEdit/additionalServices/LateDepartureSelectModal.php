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
        <th colspan="2">Время выезда</th>
      </tr>
    </thead> 
    <tbody>
      {{#departureTimes}}
        <tr>
          <td class="checker">
            <label class="juicy-radio">
              <input 
                type="radio" hidden 
                id="additional-service--departure-{{id}}" 
                name="additional-service--departure" 
                value="{{id}}" 
                class="js-late-departure--time"
              >
              <i class="control"></i>
            </label>
          </td>
          <td>
            <label for="additional-service--departure-{{id}}">
              Выезд до {{time}}
            </label>
          </td>
        </tr>
      {{/departureTimes}}
      </tbody>
  </table>
</div>
