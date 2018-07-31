<div class="lightbox-block__title">Редактирование услуги</div>
<div class="lightbox-block__content ore-service-manualedit js-ore-service-manualedit" data-serviceid="{{serviceId}}">
  <div class="tab-headers js-ore-service-manualedit--tab-header">
    <button type="button" class="tab-headers__link js-ore-service-manualedit--tab-link active" data-tab="common">
      Общие
    </button>
    <button type="button" class="tab-headers__link js-ore-service-manualedit--tab-link" data-tab="booking">
      Бронь
    </button>
    <button type="button" class="tab-headers__link js-ore-service-manualedit--tab-link" data-tab="tickets">
      Билеты
    </button>
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
    <div class="clearfix"></div>
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
  <div class="ore-service-manualedit-avia ore-service-manualedit-tab content-tab js-ore-service-manualedit--tab" data-tab="booking">
    <div class="notabene-label">
      При изменении брони услуга будет переведена в оффлайн
    </div>
    <table class="ore-service-manualedit-tab__form">
      <tr>
        <td class="ore-service-manualedit-tab__label">
          Текущий номер брони
        </td>
        <td class="ore-service-manualedit-tab__field">
          <span class="ore-service-manualedit-avia__pnr">{{#pnr}}{{.}}{{/pnr}}</span>
        </td>
      </tr>
      <tr>
        <td class="ore-service-manualedit-tab__label">
          Новый номер брони
        </td>
        <td class="ore-service-manualedit-tab__field">
          <input type="text" {{^pnr}}disabled{{/pnr}} class="js-ore-service-manualedit--new-pnr-number" {{#pnr}}placeholder="{{.}}"{{/pnr}}>
        </td>
      </tr>
      <tr>
        <td class="ore-service-manualedit-tab__label">
          Поставщик
        </td>
        <td class="ore-service-manualedit-tab__field">
          <select {{^pnr}}disabled{{/pnr}} class="ore-service-manualedit-avia__supplier js-ore-service-manualedit--supplier"></select>
        </td>
      </tr>
      <tr>
        <td class="ore-service-manualedit-tab__label">
          Тариф
        </td>
        <td class="ore-service-manualedit-tab__field">
          <input type="text" {{^pnr}}disabled{{/pnr}} class="js-ore-service-manualedit--tariff" value="{{flightTariff}}">
        </td>
      </tr>
      <tr>
        <td class="ore-service-manualedit-tab__label">
          Выписать до
        </td>
        <td class="ore-service-manualedit-tab__field">
          <input type="text" {{^pnr}}disabled{{/pnr}} class="js-ore-service-manualedit--last-ticketing-date">
        </td>
      </tr>
    </table>
    <div class="ore-service-manualedit-tab__actions">
      <button type="button" class="btn btn-medium btn-confirm js-ore-service-manualedit--save-book-info">
        <i class="kmpicon kmpicon-success"></i>
        Сохранить
      </button>
    </div>
  </div>

  <!-- Билеты -->
  <div class="ore-service-manualedit-avia ore-service-manualedit-tab content-tab js-ore-service-manualedit--tab" data-tab="tickets">
    <div class="notabene-label">
      При изменении брони услуга будет переведена в оффлайн
    </div>
    <div class="ore-service-manualedit-avia-tickets pretty-table pretty-table--striped js-ore-service-manualedit--avia-tickets-list">
      <table>
        <thead>
          <tr>
            <th class="ore-service-manualedit-tab__label">№ билета</th>
            <th class="ore-service-manualedit-tab__label">Турист</th>
            <th class="ore-service-manualedit-tab__label">Статус</th>
            <th class="ore-service-manualedit-tab__label">Новый билет</th>
          </tr>
          </thead>
        <tbody>
        {{#tickets}}
          <tr class="ore-service-manualedit-avia-tickets__item {{#disabled}}disabled{{/disabled}} js-ore-service-manualedit--avia-ticket" data-ticket="{{number}}" data-pnr="{{pnr}}" data-touristid="{{tourist.id}}">
            <td>{{number}}</td>
            <td>
              {{tourist.fullname}} 
              ( <span class="ore-service-manualedit-avia-tickets__tourist-document-number" data-tooltip="{{tourist.document.docname}}">
                документ № {{tourist.document.number}}
              </span> )
            </td>
            <td>{{status}}</td>
            <td>{{#newNumber}}{{.}}{{/newNumber}}</td>
          </tr>
        {{/tickets}}
        </tbody>
      </table>
    </div>
    <div class="clearfix"></div>
    <div class="ore-service-manualedit-avia-change-ticket active js-ore-service-manualedit--change-avia-ticket">
      <div class="ore-service-manualedit-avia-change-ticket__label">
        Изменить билет
      </div>
      <table class="ore-service-manualedit-tab__form">
        <tr>
          <td class="ore-service-manualedit-tab__label">Билет №</td>
          <td class="ore-service-manualedit-tab__field">
            <select class="ore-service-manualedit-avia__current-ticket-number js-ore-service-manualedit--current-ticket-number"></select>
          </td>
        </tr>
        <tr>
          <td class="ore-service-manualedit-tab__label">Новый номер билета</td>
          <td class="ore-service-manualedit-tab__field">
            <input type="text" class="js-ore-service-manualedit--new-ticket-number">
          </td>
        </tr>
      </table>
      <div class="ore-service-manualedit-tab__actions">
        <button type="button" class="btn btn-medium btn-confirm js-ore-service-manualedit--save-changed-avia-ticket">
          <i class="kmpicon kmpicon-success"></i>
          Сохранить
        </button>
      </div>
    </div>
    <div class="ore-service-manualedit-avia-add-ticket js-ore-service-manualedit--add-avia-ticket">
      <div class="ore-service-manualedit-avia-add-ticket__label">
        Добавить билет
      </div>
      <table class="ore-service-manualedit-tab__form">
        <tr>
          <td class="ore-service-manualedit-tab__label">Номер билета</td>
          <td class="ore-service-manualedit-tab__field">
            <input type="text" class="js-ore-service-manualedit--new-ticket-number">
          </td>
        </tr>
        <tr>
          <td class="ore-service-manualedit-tab__label">Турист</td>
          <td class="ore-service-manualedit-tab__field">
            <select class="js-ore-service-manualedit--new-ticket-tourist"></select>
          </td>
        </tr>
      </table>
      <div class="ore-service-manualedit-tab__actions">
        <button type="button" class="btn btn-medium btn-confirm js-ore-service-manualedit--save-new-avia-ticket">
          <i class="kmpicon kmpicon-success"></i>
          Сохранить
        </button>
      </div>
    </div>
    <div class="clearfix"></div>
    <div class="ore-service-manualedit-tab__actions">
      <span class="ore-service-manualedit-avia__add-ticket-button js-ore-service-manualedit--add-avia-ticket-button">
        Добавить билет
      </span>
    </div>
  </div>
</div>

