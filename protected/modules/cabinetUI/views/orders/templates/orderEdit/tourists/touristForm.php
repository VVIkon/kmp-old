<form class="ore-tourist js-ore-tourist" data-touristid="{{touristId}}" {{#userId}}data-userid="{{.}}"{{/userId}} data-leader="{{isTourleader}}">
  <!-- header -->
  <div class="row">
    <div class="col-xs-2">
      <span class="content-tab__row-label">
        {{#isNewTourist}}
          Новый турист
        {{/isNewTourist}}
        {{^isNewTourist}}
          {{#isTourleader}}Заказчик{{/isTourleader}}
          {{^isTourleader}}Турист{{/isTourleader}}
        {{/isNewTourist}}
      </span>
    </div>
    <div class="col-xs-10 ore-tourist__header ore-tourist-info js-ore-tourist--header">
      <div class="ore-tourist__header-lock js-ore-tourist--header-lock">
        <div class="spinner-medium" style="display:block;">
          <img src="/app/img/common/loading.gif" alt="Загрузка">
        </div>
      </div>
      <div class="ore-tourist-info__main">
        {{#isNewTourist}}
          {{#allowedTouristSelect}}
            <div class="ore-tourist-info__suggest ore-tourist-suggest">
              <div class="col-xs-2 ore-tourist-suggest__field-label js-ore-tourist--suggest-field">
                Выбор сотрудника
              </div>
              <div class="col-xs-4 ore-tourist-suggest__field js-ore-tourist--suggest-field">
                <select class="ore-tourist-suggest__field-control js-ore-tourist--suggest" placeholder="начните ввод для поиска"></select>
              </div>
            </div>
          {{/allowedTouristSelect}}
          {{^allowedTouristSelect}}
            <div class="ore-tourist-info__new">Новый турист</div>
          {{/allowedTouristSelect}}
        {{/isNewTourist}}
        {{^isNewTourist}}
          <div class="ore-tourist-info__name">{{lastname}} {{firstname}}</div>
          <div class="ore-tourist-info__contacts ore-tourist-contacts">
            {{#phone}}<span class="ore-tourist-contacts__item"><i class="kmpicon kmpicon-call"></i> {{.}}</span>{{/phone}}
            {{#email}}<span class="ore-tourist-contacts__item"><i class="kmpicon kmpicon-envelope"></i> {{.}}</span>{{/email}}
          </div>
        {{/isNewTourist}}
      </div>
      <div class="ore-tourist__toggler"></div>
    </div>
  </div>
  <!-- form body -->
  <div class="ore-tourist__form-wrapper js-ore-tourist--form-wrapper">
    {{^isTourleaderSet}}
    <input type="checkbox" class="js-ore-tourist--tourleader-toggler" {{#isTourleader}}checked{{/isTourleader}} hidden>
    <!-- DEPRECATED
      <div class="row js-ore-tourist--tourleader-block" data-optgroup="tourleader">
        <div class="col-xs-2">
          <span class="content-tab__row-label">&nbsp;</span>
        </div>
        <div class="col-xs-10 ore-tourist__form-part" data-part="tourleader">
          <div class="col-xs-4 ore-tourist__tourleader-control">
            <label>
              <span>Заказчик</span>
              <div class="simpletoggler {{#isTourleader}}active{{/isTourleader}}">
                <input type="checkbox" class="js-ore-tourist--tourleader-toggler" {{#isTourleader}}checked{{/isTourleader}} hidden>
                <i class="toggler"></i>
                <div class="on-text">да</div>
                <div class="off-text">нет</div>
              </div>
            </label>
          </div>
        </div>
      </div>
      <div class="row js-ore-tourist--tourleader-block" data-optgroup="tourleader">
        <div class="ore-tourist__block-delim col-xs-10 col-xs-offset-2">
          <hr>
        </div>
      </div> -->
    {{/isTourleaderSet}}
    <!-- main data -->
    <div class="row">
      <div class="col-xs-2">
        <span class="content-tab__row-label">Данные</span>
      </div>
      <div class="col-xs-10 ore-tourist__form-part" data-part="person">
        <div class="col-xs-1 ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Пол</span>
            <div class="simpletoggler toggler-sex">
              <input type="checkbox" {{^isEditable}}disabled{{/isEditable}} hidden {{#isMale}}checked{{/isMale}} name="sex">
              <i class="toggler"></i>
              <div class="on-text"><i class="kmpicon kmpicon-male"></i></div>
              <div class="off-text"><i class="kmpicon kmpicon-female"></i></div>
            </div>
          </label>
        </div>
        <div class="col-xs-3 ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Фамилия</span>
            <input type="text" {{^isEditable}}disabled{{/isEditable}} name="lastname" value="{{lastname}}" placeholder="Фамилия">
          </label>
        </div>
        <div class="col-xs-3 ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Имя</span>
            <input type="text" {{^isEditable}}disabled{{/isEditable}} name="firstname" value="{{firstname}}" placeholder="Имя">
          </label>
        </div>
        <div class="col-xs-3 ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Отчество</span>
            <input type="text" {{^isEditable}}disabled{{/isEditable}} name="middlename" value="{{middlename}}" placeholder="Отчество">
          </label>
        </div>
        <div class="col-xs-2 ore-tourist__control">
          <label for="birthdate-{{touristId}}">
            <span class="ore-tourist__control-label">Дата рождения</span>
          </label>
          <input type="text" {{^isEditable}}disabled{{/isEditable}} name="birthdate" id="birthdate-{{touristId}}" value="{{birthdate}}" placeholder="дд.мм.гггг">
        </div>
        <div class="col-xs-3 ore-tourist__control">
          <div>
            <span class="ore-tourist__control-label">Телефон</span>
            <div class="phone-input js-ore-tourist--phone-input">
              <div class="prefix">
                + <input type="text" {{^isEditable}}disabled{{/isEditable}} name="country-prefix" maxlength="6" value="{{phoneCountryCode}}">
                (<input type="text" {{^isEditable}}disabled{{/isEditable}} name="city-prefix" maxlength="4" value="{{phoneCityCode}}">)
              </div>
              <input type="text" {{^isEditable}}disabled{{/isEditable}} name="phone" value="{{phoneNumber}}" maxlength="13" placeholder="Номер">
            </div>
          </div>
        </div>
        <div class="col-xs-4 ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">E-Mail</span>
            <input type="text" {{^isEditable}}disabled{{/isEditable}} name="email" value="{{email}}" placeholder="E-Mail">
          </label>
        </div>
      </div>
    </div>
    <!-- Delim -->
    <div class="row">
      <div class="ore-tourist__block-delim col-xs-10 col-xs-offset-2">
        <hr>
      </div>
    </div>
    <!-- document -->
    <div class="row">
      <div class="col-xs-2">
        <span class="content-tab__row-label">Документ</span>
      </div>
      <div class="col-xs-10 ore-tourist__form-part js-ore-tourist--document" {{#document.documentId}}data-documentid="{{.}}"{{/document.documentId}} data-part="document">
        <div class="ore-tourist__notify">
          <i class="kmpicon kmpicon-info"></i>
          Введите ФИО по паспорту, латинскими буквами
        </div>
        <div class="col-xs-5  ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Документ</span>
            <select name="doctype" {{^isEditable}}disabled{{/isEditable}}>
              <option value="{{document.type}}" selected></option>
            </select>
          </label>
        </div>
        <div class="col-xs-4  ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Страна выдачи документа</span>
            <select name="citizenship" {{^isEditable}}disabled{{/isEditable}}>
              <option value="{{document.citizenship}}"></option>
            </select>
          </label>
        </div>
        <div class="clearfix"></div>
        <div class="col-xs-3  ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Фамилия</span>
            <input type="text" {{^isEditable}}disabled{{/isEditable}} name="doclastname" value="{{document.lastname}}" placeholder="Фамилия">
          </label>
        </div>
        <div class="col-xs-3  ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Имя</span>
            <input type="text" {{^isEditable}}disabled{{/isEditable}} name="docfirstname" value="{{document.firstname}}" placeholder="Имя">
          </label>
        </div>
        <div class="col-xs-3  ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Отчество</span>
            <input type="text" {{^isEditable}}disabled{{/isEditable}} name="docmiddlename" value="{{document.middlename}}" placeholder="Отчество">
          </label>
        </div>
        <div class="col-xs-2  ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">&nbsp;</span>
            {{#isEditable}}
              <button type="button" class="ore-tourist__copy-name js-ore-tourist--copy-name">
                <i class="kmpicon kmpicon-copy" title="Скопировать из данных"></i>
              </button>
            {{/isEditable}}
          </label>
        </div>
        <div class="clearfix"></div>
        <div class="col-xs-2  ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Серия</span>
            <input type="text" {{^isEditable}}disabled{{/isEditable}} name="docseries" value="{{document.series}}" maxlength="4" placeholder="Серия">
          </label>
        </div>
        <div class="col-xs-3  ore-tourist__control">
          <label>
            <span class="ore-tourist__control-label">Номер</span>
            <input type="text" {{^isEditable}}disabled{{/isEditable}} name="docnumber" value="{{document.number}}" maxlength="9" placeholder="Номер">
          </label>
        </div>
        <!--
        <div class="col-xs-2  ore-tourist__control">
          <label for="docgetdate-{{touristId}}">
            <span>Дата выдачи</span>
          </label>
          <input type="text" {{^isEditable}}disabled{{/isEditable}} name="docgetdate" id="docgetdate-{{touristId}}" value="{{document.issueDate}}" placeholder="дд.мм.гггг">
        </div>-->
        <div class="col-xs-2  ore-tourist__control">
          <label for="docenddate-{{touristId}}">
            <span class="ore-tourist__control-label">Дата окончания</span>
          </label>
          <input type="text" {{^isEditable}}disabled{{/isEditable}} name="docenddate" id="docenddate-{{touristId}}" value="{{document.expiryDate}}" placeholder="дд.мм.гггг">
        </div>
        <div class="clearfix"></div>
      </div>
    </div>
    <!-- Delim -->
    <div class="row">
      <div class="ore-tourist__block-delim col-xs-10 col-xs-offset-2">
        <hr>
      </div>
    </div>
    <!-- Bonus cards -->
    <div class="row">
      <div class="col-xs-2">
        <span class="content-tab__row-label">Карты программ лояльности</span>
      </div>
      <div class="col-xs-10 ore-tourist__form-part ore-tourist-bonus-cards">
        <div class="col-xs-3 ore-tourist__control">
          <span class="ore-tourist__control-label">Программа лояльности</span>
        </div>
        <div class="col-xs-3 ore-tourist__control">
          <span class="ore-tourist__control-label">Номер карты</span>
        </div>
        <div class="clearfix"></div>
        <div class="ore-tourist-bonus-cards__list js-ore-tourist--bonus-cards">
          {{{bonusCards}}}
        </div>
        <div class="ore-tourist-bonus-cards__actions js-ore-tourist--bonus-cards-actions">
          {{{bonusCardsActions}}}
        </div>
      </div>
    </div>
    <!-- Delim -->
    <div class="row">
      <div class="ore-tourist__block-delim col-xs-10 col-xs-offset-2">
        <hr>
      </div>
    </div>
    <!-- Custom fields -->
    {{#hasCustomFields}}
      <div class="row">
        <div class="col-xs-2">
          <span class="content-tab__row-label">Дополнительно</span>
        </div>
        <div class="col-xs-10 ore-tourist__form-part ore-tourist-custom-fields">
          <div class="js-ore-tourist-custom-fields">
            <!-- custom fields here -->
          </div>
        </div>
      </div>
      <!-- Delim -->
      <div class="row">
        <div class="ore-tourist__block-delim col-xs-10 col-xs-offset-2">
          <hr>
        </div>
      </div>
    {{/hasCustomFields}}
    <!-- actions -->
    <div class="row">
      <div class="col-xs-2">
        <span class="content-tab__row-label">&nbsp;</span>
      </div>
      <div class="col-xs-10 ore-tourist__form-part" data-part="options">
        <div class="ore-tourist__notify">
          <i class="kmpicon kmpicon-info"></i>
          Для бронирования авиаперелета необходимо указать номер телефона и данные документа
        </div>
        <div class="ore-tourist__actions js-ore-tourist--actions">
          {{{actions}}}
        </div>
      </div>
    </div>
  </div>
</form>
