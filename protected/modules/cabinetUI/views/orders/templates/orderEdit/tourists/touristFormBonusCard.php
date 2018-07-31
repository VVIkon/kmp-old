<div class="ore-tourist-bonus-cards__item js-ore-tourist--bonus-card" {{#id}}data-cardid="{{.}}"{{/id}}>
  <div class="col-xs-3 ore-tourist__control">
    <select class="js-ore-tourist--bonus-card-program" placeholder="Программа лояльности">
      {{#aviaLoyaltyProgramId}}
        <option value="{{.}}" selected>Загрузка списка...</option>
      {{/aviaLoyaltyProgramId}}
    </select>
  </div>
  <div class="col-xs-3 ore-tourist__control">
    <input type="text" class="js-ore-tourist--bonus-card-number" placeholder="Номер карты" maxlength="30" value="{{bonuscardNumber}}">
  </div>
  {{#remove}}
  <div class="col-xs-3 ore-tourist-control">
    <span class="ore-tourist-bonus-cards__delete-card js-ore-tourist--bonus-card-delete" data-tooltip="Удалить карту"></span>
  </div>
  {{/remove}}
</div>