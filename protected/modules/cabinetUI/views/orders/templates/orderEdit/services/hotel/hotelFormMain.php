<!-- Route -->
<div class="row">
  <div class="col-xs-2">
    <span class="content-tab__row-label">Номер</span>
  </div>
  <div class="col-xs-10 service-form-section">
    {{#offerSupplier}}
      <div class="service-form-info-field">
        поставщик: <span class="service-form-info-field__value">{{.}}</span>
      </div>
    {{/offerSupplier}}
    <div class="service-hotel-offer">
      <div class="service-hotel-offer__wrapper">
        <table class="service-hotel-offer__info">
          <tbody>
            <tr>
              <td class="service-hotel-offer__info-icon"><i class="kmpicon kmpicon-clockwatch"></i></td>
              <td class="service-hotel-offer__info-type">Продолжительность:</td>
              <td class="service-hotel-offer__info-data">{{daysCount}} / {{nightsCount}}</td>
            </tr>
            <tr>
              <td class="service-hotel-offer__info-icon"><i class="kmpicon kmpicon-room"></i></td>
              <td class="service-hotel-offer__info-type">Тип номера:</td>
              <td class="service-hotel-offer__info-data">{{roomType}}</td>
            </tr>
            <tr>
              <td class="service-hotel-offer__info-icon"><i class="kmpicon kmpicon-room"></i></td>
              <td class="service-hotel-offer__info-type">Описание номера:</td>
              <td class="service-hotel-offer__info-data">
                {{#roomTypeDescription}}
                  {{.}}
                {{/roomTypeDescription}}
                {{^roomTypeDescription}}
                  <span class="service-hotel-offer__no-info-data">[отсутствует]</span>
                {{/roomTypeDescription}}
              </td>
            </tr>
            <tr>
              <td class="service-hotel-offer__info-icon"><i class="kmpicon kmpicon-service-meal"></i></td>
              <td class="service-hotel-offer__info-type">Тип питания:</td>
              <td class="service-hotel-offer__info-data">{{mealType}}</td>
            </tr>
            <tr>
              <td class="service-hotel-offer__info-icon"><i class="kmpicon kmpicon-fares"></i></td>
              <td class="service-hotel-offer__info-type">Тариф:</td>
              <td class="service-hotel-offer__info-data">
                {{#fareName}}
                  {{.}}
                {{/fareName}}
                {{^fareName}}
                  <span class="service-hotel-offer__no-info-data">[отсутствует]</span>
                {{/fareName}}
              </td>
            </tr>
            <tr>
              <td class="service-hotel-offer__info-icon"><i class="kmpicon kmpicon-fares"></i></td>
              <td class="service-hotel-offer__info-type">Описание тарифа:</td>
              <td class="service-hotel-offer__info-data">
                {{#fareDescription}}
                  {{.}}
                {{/fareDescription}}
                {{^fareDescription}}
                  <span class="service-hotel-offer__no-info-data">[отсутствует]</span>
                {{/fareDescription}}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="clearfix"></div>
  </div>
</div>
{{#voucherLink}}
<div class="row">
  <div class="col-xs-2">
    <span class="content-tab__row-label">Ваучер</span>
  </div>
  <div class="col-xs-10 service-form-section service-hotel-voucher">
    <a href="{{.}}" class="service-hotel-voucher__download" target="_blank">
      <i class="kmpicon kmpicon-hotel-voucher"></i>
      Скачать ваучер
    </a>
  </div>
</div>
{{/voucherLink}}