<div class="service-avia-trip">
  <div class="service-avia-trip__wrapper">
    {{{route}}}
    <div class="service-avia-tripinfo">
      <div class="service-avia-tripinfo-baggage col-xs-8">
        {{^hasPnr}}
          <i class="kmpicon kmpicon-baggage"></i> 
          <span class="service-avia-tripinfo-baggage__label">багаж:</span>
          {{#baggageInfo}}
            <span class="service-avia-tripinfo-baggage__item">{{.}}</span>
          {{/baggageInfo}}
          {{^baggageInfo}}
            <span class="service-avia-tripinfo-baggage__no-info">нет информации<span>
          {{/baggageInfo}}
        {{/hasPnr}}
      </div>
      <div class="service-avia-tripinfo__hops col-xs-4">
        <i class="kmpicon kmpicon-plane"></i> <span>{{transfersNum}}</span>
      </div>
    </div>
  </div>
</div>
