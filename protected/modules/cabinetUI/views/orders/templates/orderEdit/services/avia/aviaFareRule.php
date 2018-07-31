<div class="lightbox-block__title">Правила тарифа</div>
<div class="lightbox-block__content avs-fare-rule js-avs-fare-rule">
  <div class="tab-headers js-avs-fare-rule--tab-header">
    {{#rules}}
      <button type="button" class="tab-headers__link js-avs-fare-rule--tab-link {{#active}}active{{/active}}" data-tab="{{segment}}">
        {{segment}}
      </button>
    {{/rules}}
  </div>
  {{#rules}}
  <div class="avs-fare-rule-tab content-tab content-tab--padded js-avs-fare-rule--tab {{#active}}active{{/active}}" data-tab="{{segment}}">
    <div class="avs-fare-rule__full-segment-name">
      {{fullSegmentName}}
    </div>
    {{#shortRules}}
      <table class="avs-fare-rule-flags">
        <tr>
          <td class="avs-fare-rule-flags__label">Возврат до вылета</td>
          <td class="avs-fare-rule-flags__value">
            {{#refund_before_rule}}
              {{#allowed}}<i class="kmpicon kmpicon-success" title="возможен"></i>{{/allowed}}
              {{#forbidden}}<i class="kmpicon kmpicon-close" title="невозможен"></i>{{/forbidden}}
              {{#undefined}}<i class="kmpicon kmpicon-unknown" title="не определено"></i>{{/undefined}}
            {{/refund_before_rule}}
          </td>
          <td class="avs-fare-rule-flags__label">Обмен до вылета</td>
          <td class="avs-fare-rule-flags__value">
            {{#change_before_rule}}
              {{#allowed}}<i class="kmpicon kmpicon-success" title="возможен"></i>{{/allowed}}
              {{#forbidden}}<i class="kmpicon kmpicon-close" title="невозможен"></i>{{/forbidden}}
              {{#undefined}}<i class="kmpicon kmpicon-unknown" title="не определено"></i>{{/undefined}}
            {{/change_before_rule}}
          </td>
        </tr>
        <tr>
          <td class="avs-fare-rule-flags__label">Возврат после вылета</td>
          <td class="avs-fare-rule-flags__value">
            {{#refund_after_rule}}
              {{#allowed}}<i class="kmpicon kmpicon-success" title="возможен"></i>{{/allowed}}
              {{#forbidden}}<i class="kmpicon kmpicon-close" title="невозможен"></i>{{/forbidden}}
              {{#undefined}}<i class="kmpicon kmpicon-unknown" title="не определено"></i>{{/undefined}}
            {{/refund_after_rule}}
          </td>
          <td class="avs-fare-rule-flags__label">Обмен после вылета</td>
          <td class="avs-fare-rule-flags__value">
            {{#change_after_rule}}
              {{#allowed}}<i class="kmpicon kmpicon-success" title="возможен"></i>{{/allowed}}
              {{#forbidden}}<i class="kmpicon kmpicon-close" title="невозможен"></i>{{/forbidden}}
              {{#undefined}}<i class="kmpicon kmpicon-unknown" title="не определено"></i>{{/undefined}}
            {{/change_after_rule}}
          </td>
        </tr>
        <tr>
          <td class="avs-fare-rule-flags__label">Онлайн обмен</td>
          <td class="avs-fare-rule-flags__value">
            {{#online_change}}
              {{#allowed}}<i class="kmpicon kmpicon-success" title="возможен"></i>{{/allowed}}
              {{#forbidden}}<i class="kmpicon kmpicon-close" title="невозможен"></i>{{/forbidden}}
              {{#undefined}}<i class="kmpicon kmpicon-unknown" title="не определено"></i>{{/undefined}}
            {{/online_change}}
          </td>
          <td class="avs-fare-rule-flags__label"></td>
          <td class="avs-fare-rule-flags__value"></td>
        </tr>
      </table>
    {{/shortRules}}
    <div class="avs-fare-rule-text">
      {{#rulesText}}
      <h2 class="avs-fare-rule-text__label">{{name}}</h2>
      <div class="avs-fare-rule-text__block">
        {{{text}}}
      </div>
      {{/rulesText}}
    </div>
  </div>
  {{/rules}}
  <div class="clearfix"></div>
</div>