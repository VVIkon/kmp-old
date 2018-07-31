<div class="row">
  <div class="col-xs-2"></div>
  <div class="col-xs-10 service-form-section service-hotel-minimal-price">
    <i class="service-hotel-minimal-price__facet"></i>
    <div class="service-hotel-minimal-price__header">
      <i class="kmpicon kmpicon-warning"></i>
      В момент поиска было найдено предложение с минимальной ценой
    </div>
    <div class="service-hotel-minimal-price__offer-wrapper">
      <table class="service-hotel-minimal-price__offer">
        <tr>
          <td class="service-hotel-minimal-price__hotel">{{hotelName}}</td>
          <td rowspan="2" class="service-hotel-minimal-price__meal">
            <i class="kmpicon kmpicon-service-meal"></i>
            {{mealType}}
          </td>
          <td rowspan="2" class="service-hotel-minimal-price__price">
            <i class="kmpicon kmpicon-best-price"></i>
            {{price}} {{currency}}
          </td>
        </tr>
        <tr>
          <td class="service-hotel-minimal-price__room">{{room}}</td>
        </tr>
      </table>
    </div>
  </div>
</div>