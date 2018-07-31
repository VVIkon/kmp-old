<div class="service-avia-loyalty-program js-service-avia-loyalty-program">
  <div class="col-xs-5 service-avia-loyalty-program__provider">
    <select class="js-service-avia-loyalty-program--provider" {{^isSavingAllowed}}disabled{{/isSavingAllowed}} placeholder="Программа лояльности">
      {{#loyalityProgram}}
        <option value="{{.}}" selected>Загрузка списка...</option>
      {{/loyalityProgram}}
    </select>
  </div>
  <div class="col-xs-5 service-avia-loyalty-program__number">
    <input type="text" class="js-service-avia-loyalty-program--number" readonly {{^isSavingAllowed}}disabled{{/isSavingAllowed}} placeholder="Номер карты" maxlength="30" value="{{#loyalityCardNumber}}{{.}}{{/loyalityCardNumber}}">
  </div>
</div>