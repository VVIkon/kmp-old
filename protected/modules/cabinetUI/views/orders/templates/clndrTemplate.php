<div class="clndr-controls">
    <div class="clndr-previous-button"></div>
    <div class="month">
      <span class="sel-month"><span>{{month}}</span><div class="sel-month-list"></div></span> ' 
      <span class="sel-year">{{year}}</span>
    </div>
    <div class="clndr-next-button"></div>
</div>
<div class="clndr-grid">
  <div class="days-of-the-week">
    {{#daysOfTheWeek}}
      <div class="header-day">{{.}}</div>
    {{/daysOfTheWeek}}
    <div class="days">
      {{#days}}
        <div class="{{classes}}">{{day}}</div>
      {{/days}}
    </div>
  </div>
</div>
