<div class="row">
  <div class="col-xs-2"></div>
  <div class="col-xs-10 service-form-section service-avia-minimal-price">
    <i class="service-avia-minimal-price__facet"></i>
    <div class="service-avia-minimal-price__header">
      <i class="kmpicon kmpicon-warning"></i>
      В момент поиска было найдено предложение с минимальной ценой
    </div>
    <div class="service-avia-minimal-price__offer-wrapper">
      <table class="service-avia-minimal-price__offer">
        <tr>
          <td class="service-avia-minimal-price__point">{{from}}</td>
          <td class="service-avia-minimal-price__arrow">
            <i class="kmpicon kmpicon-arrow-right"></i>
          </td>
          <td class="service-avia-minimal-price__point">{{to}}</td>
          <td rowspan="2" class="service-avia-minimal-price__changes">
            {{changes}}
          </td>
          <td rowspan="2" class="service-avia-minimal-price__price">
            <i class="kmpicon kmpicon-best-price"></i>
            {{price}} {{currency}}
          </td>
        </tr>
        <tr>
          <td class="service-avia-minimal-price__date">{{dateStart}}</td>
          <td class="service-avia-minimal-price__duration">
            <i class="kmpicon kmpicon-clockwatch"></i>
            {{#duration}}
              {{hours}} ч.
              {{minutes}} м.
            {{/duration}}
          </td>
          <td class="service-avia-minimal-price__date">{{dateFinish}}</td>
        </tr>
      </table>
    </div>
  </div>
</div>