<div class="lightbox-block__title">Информация об отеле</div>
<div class="lightbox-block__content hotel-info">
  <div class="hotel-info__main-wrapper">
    <div class="hotel-info__photo-wrapper">
      <div class="hotel-info__main-photo js-hotel-info__main-photo" style="background-image:url('{{mainImage}}')"></div>
      <div class="hotel-info-gallery js-hotel-info__gallery">
        {{#photos}}
          <div class="hotel-info-gallery__item js-hotel-info__gallery-item" style="background-image:url('{{.}}')" data-url="{{.}}"></div>
        {{/photos}}
        {{^photos}}
          <div class="hotel-info-gallery__no-items">
            нет фотографий
          </div>
        {{/photos}}
      </div>
    </div>
    <div class="hotel-info__essentials-wrapper">
      <h1 class="hotel-info__name">{{name}}
        <span class="hotel-info__category">
          {{#category}}
            <i class="kmpicon kmpicon-star"></i>
          {{/category}}
        </span>
      </h1>
      {{#hotelChain}}
        <h2 class="hotel-info__chain">{{.}}</h2>
      {{/hotelChain}}
      <div class="hotel-info__location">
        {{city}}, {{country}}
      </div>
      <div class="hotel-info__timings">
        Время заезда: 
        {{#checkIn}}
          <span class="hotel-info__checkin">{{.}}</span>
        {{/checkIn}}
        {{^checkIn}}
          <span class="hotel-info__no-timing">не указано</span>
        {{/checkIn}}
        <br>
        Время выезда: 
        {{#checkOut}}
          <span class="hotel-info__checkout">{{.}}</span>
        {{/checkOut}}
        {{^checkOut}}
          <span class="hotel-info__no-timing">не указано</span>
        {{/checkOut}}
      </div>
      <div class="hotel-info__address">
        <span class="hotel-info__label">Адрес:</span> {{address}}<br>
      </div>
      {{#distance}}
        <div class="hotel-info__distance">
          <span class="hotel-info__label">Расстояние от центра города:</span> {{.}} км<br>
        </div>
      {{/distance}}
      <div class="hotel-info__contacts">
        <span class="hotel-info__label">Контакты:</span><br>
        <div class="hotel-info__contacts-list">
          <table>
            {{#phone}}<tr><td>Телефон:</td><td>{{.}}</td></tr>{{/phone}}
            {{#fax}}<tr><td>Факс:</td><td>{{.}}</td></tr>{{/fax}}
            {{#email}}<tr><td>E-mail:</td><td>{{.}}</td></tr>{{/email}}
            {{#siteurl}}<tr><td>Сайт:</td><td>{{.}}</td></tr>{{/siteurl}}
          </table>
        </div>
      </div>
    </div>
  </div>
   
  <div class="clearfix"></div>

  <div class="hotel-info__block-wrapper">
    <div class="hotel-info__block-label">Услуги</div>
    <div class="hotel-info-services">
      {{#services}}
        <div class="col-xs-3 hotel-info-services__group">
          <h3 class="hotel-info-services__label">
            <i class="kmpicon kmpicon-ota-hac-{{icon}}"></i>
            {{group}}
          </h3>
          <ul class="hotel-info-services__list">
            {{#list}}
              <li>{{name}}</li>
            {{/list}}
          </ul>
        </div>
      {{/services}}
    </div>
  </div>

  <div class="hotel-info__block-wrapper hotel-info-description__wrapper">
    <div class="hotel-info__block-label">Описание отеля</div>
    {{#descriptions}}
      <div class="hotel-info-description">
        <h3 class="hotel-info-description__label">
          {{descriptionType}}
        </h3>
        {{{description}}}
      </div>
    {{/descriptions}}
    {{^descriptions}}
      <div class="hotel-info-description__empty">
        нет описания
      </div>
    {{/descriptions}}
  </div>
   
  <div class="clearfix"></div>
</div>