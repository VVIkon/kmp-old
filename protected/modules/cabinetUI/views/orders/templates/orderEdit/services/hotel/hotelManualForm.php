<div class="lightbox-block__title">Редактирование услуги</div>
<div class="lightbox-block__content ore-service-manualedit js-ore-service-manualedit" data-serviceid="{{serviceId}}">
  <div class="tab-headers js-ore-service-manualedit--tab-header">
    <span class="tab-headers__link js-ore-service-manualedit--tab-link active" data-tab="common">
      Общие
    </span>
    <span class="tab-headers__link js-ore-service-manualedit--tab-link" data-tab="booking">
      Бронь
    </span>
  </div>
  <!-- Общие -->
  <div class="ore-service-manualedit-common ore-service-manualedit-tab content-tab js-ore-service-manualedit--tab active" data-tab="common">
    <div class="row">
      <div class="col-xs-6">
        <table class="ore-service-manualedit-tab__form">
          <tr>
            <td class="ore-service-manualedit-tab__label">Дата начала услуги</td>
            <td class="ore-service-manualedit-tab__field">
              <input type="text" class="js-ore-service-manualedit--start-date">
            </td>
          </tr>
          <tr>
            <td class="ore-service-manualedit-tab__label">Дата окончания услуги</td>
            <td class="ore-service-manualedit-tab__field">
              <input type="text" class="js-ore-service-manualedit--end-date">
            </td>
          </tr>
          <tr>
            <td class="ore-service-manualedit-tab__label">Цена поставщика</td>
            <td class="ore-service-manualedit-tab__field">
              {{supplierGrossPrice}} {{supplierCurrency}}
            </td>
          </tr>
          <tr>
            <td class="ore-service-manualedit-tab__label">Валюта</td>
            <td class="ore-service-manualedit-tab__field">
              <select class="js-ore-service-manualedit--currency">
                <option selected value="{{clientCurrency}}">{{clientCurrency}}</option>
              </select>
            </td>
          </tr>
          <tr>
            <td class="ore-service-manualedit-tab__label">Брутто КМП</td>
            <td class="ore-service-manualedit-tab__field">
              <div class="price-and-currency-selector">
                <input type="text" class="price-and-currency-selector__price js-ore-service-manualedit--client-gross-price" value="{{clientGrossPrice}}">
                <div class="price-and-currency-selector__currency-wrapper">
                  <span class="js-ore-service-manualedit--selected-currency">{{clientCurrency}}</span>
                </div>
              </div>
            </td>
          </tr>
          <tr>
            <td class="ore-service-manualedit-tab__label">Комиссия агента</td>
            <td class="ore-service-manualedit-tab__field">
              <div class="price-and-currency-selector">
                <input type="text" class="price-and-currency-selector__price js-ore-service-manualedit--agent-commission" value="{{agentCommission}}">
                <div class="price-and-currency-selector__currency-wrapper">
                  <span>{{clientCurrency}}</span>
                </div>
              </div>
            </td>
          </tr>
        </table>
      </div>
      <div class="col-xs-6">
        <div class="ore-service-manualedit-tab__label">
          Штрафы
        </div>
        <div>
          <table class="ore-service-manualedit-tab__form">
            {{^clientCancelPenalties}}
              <tr><td class="ore-service-manualedit-tab__field">Штрафов нет</td></tr>
            {{/clientCancelPenalties}}
            {{#clientCancelPenalties}}
              <tr class="js-ore-service-manualedit--client-cancel-penalty" data-id="{{penaltyIndex}}" data-currency="{{currency}}">
                <td class="ore-service-manualedit-tab__cell">с {{dateFrom}} по {{dateTo}}: </td>
                <td class="ore-service-manualedit-tab__field">
                  <div class="price-and-currency-selector">
                    <input type="text" class="price-and-currency-selector__price js-ore-service-manualedit--client-cancel-penalty-amount" value="{{amount}}" data-penalty="{{amount}}">
                    <div class="price-and-currency-selector__currency-wrapper">
                      <span>{{currencyIcon}}</span>
                    </div>
                  </div>
                </td>
              </tr>
            {{/clientCancelPenalties}}
          </table>
        </div>
      </div>
    </div>
    <div class="ore-service-manualedit-tab__actions">
      <button type="button" class="btn btn-medium btn-confirm js-ore-service-manualedit--save-common-data">
        <i class="kmpicon kmpicon-success"></i>
        Сохранить
      </button>
    </div>

    <div class="ore-service-manualedit-tab__spacer"></div>
    <table class="ore-service-manualedit-tab__form">
      <tr>
        <td class="ore-service-manualedit-tab__label">Изменить статус</td>
        <td class="ore-service-manualedit-tab__field">
          <select class="ore-service-manualedit-tab__change-status js-ore-service-manualedit--change-status"></select>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="ore-service-manualedit-tab__label">
          <label class="juicy-checkbox">
            <span>Перевести в оффлайн</span>&nbsp;
            <input type="checkbox" hidden class="ore-service-manualedit-tab__set-offline js-ore-service-manualedit--set-offline">
            <i class="control"></i>
          </label>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="ore-service-manualedit-tab__label">Комментарий</td>
      </tr>
      <tr>
        <td colspan="2" class="ore-service-manualedit-tab__field">
          <textarea class="ore-service-manualedit-tab__status-comment js-ore-service-manualedit--status-comment"></textarea>
        </td>
      </tr>
    </table>
    <div class="ore-service-manualedit-tab__actions">
      <button type="button" class="btn btn-medium btn-confirm js-ore-service-manualedit--save-service-status">
        <i class="kmpicon kmpicon-gear"></i>
        Изменить
      </button>
    </div>
  </div>

  <!-- Бронь -->
  <div class="ore-service-manualedit-hotel ore-service-manualedit-tab content-tab js-ore-service-manualedit--tab" data-tab="booking">
    <div class="notabene-label">
      При изменении брони услуга будет переведена в оффлайн
    </div>
    <table class="ore-service-manualedit-tab__form">
      <tr>
        <td class="ore-service-manualedit-tab__label">
          Текущий номер брони
        </td>
        <td class="ore-service-manualedit-tab__field">
          <span class="ore-service-manualedit-hotel__reservation-number">
           {{#reservation}}
             {{.}}
           {{/reservation}}
          </span>
        </td>
      </tr>
      <tr>
        <td class="ore-service-manualedit-tab__label">
          Новый номер брони
        </td>
        <td class="ore-service-manualedit-tab__field">
          <input {{^reservation}}disabled{{/reservation}} type="text" class="js-ore-service-manualedit--new-reservation-number" {{#reservation}}placeholder="{{.}}"{{/reservation}}>
        </td>
      </tr>
      <tr>
        <td class="ore-service-manualedit-tab__label">
          Поставщик
        </td>
        <td class="ore-service-manualedit-tab__field">
          <select {{^reservation}}disabled{{/reservation}} class="ore-service-manualedit-hotel__supplier js-ore-service-manualedit--supplier"></select>
        </td>
      </tr>
    </table>
    <div class="ore-service-manualedit-tab__actions">
      <button type="button" {{^reservation}}disabled{{/reservation}} class="btn btn-medium btn-confirm js-ore-service-manualedit--save-book-info">
        <i class="kmpicon kmpicon-success"></i>
        Сохранить
      </button>
    </div>
  </div>
</div>

