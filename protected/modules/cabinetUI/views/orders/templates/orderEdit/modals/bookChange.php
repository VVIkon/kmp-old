<div class="modal-book-change js-modal-book-change" data-serviceid="{{serviceId}}">
  <div class="modal-book-change__header">
    <i class="kmpicon kmpicon-{{serviceIcon}}"></i> {{serviceName}}
  </div>
  <div class="modal-book-change-dates modal-book-change__panel">
    <div class="modal-book-change__label">Даты</div>
    <div class="modal-book-change-dates__date-in">
      <span>Дата заезда</span>
      <div class="modal-book-change-dates__control">
        <input type="text" class="js-modal-book-change--date-in" name="date-in" value="{{dateIn}}" placeholder="дд.мм.гггг" data-default="{{dateIn}}">
      </div>
    </div>
    <div class="modal-book-change-dates__date-out">
      <span>Дата выезда</span>
      <div class="modal-book-change-dates__control">
        <input type="text" class="js-modal-book-change--date-out" name="date-out" value="{{dateOut}}" placeholder="дд.мм.гггг" data-default="{{dateOut}}">
      </div>
    </div>
  </div>
  <div class="modal-book-change-tourists modal-book-change__panel js-modal-book-change--tourists">
    <div class="modal-book-change__label">Туристы</div>
    {{#tourists}}
    <div class="modal-book-change-tourists__tourist">
      <label>
        <div class="simpletoggler toggler-small {{#attached}}active{{/attached}}">
          <input class="js-modal-book-change--tourist-bound" type="checkbox" {{#attached}}checked{{/attached}} hidden name="tourist-{{touristId}}" data-touristid="{{touristId}}">
          <i class="toggler"></i>
          <div class="on-text"><i class="kmpicon kmpicon-success"></i></div>
          <div class="off-text"><i class="kmpicon kmpicon-close"></i></div>
        </div>
        {{firstName}} {{surName}}
      </label>
    </div>
    {{/tourists}}
  </div>
  <p class="modal-book-change__question">Вы действительно хотите изменить бронь?</p>
</div>