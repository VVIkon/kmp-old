/* Набор общих правил корпоративных политик */
(function(global,factory) {
  
      KT.crates.ClientAdmin.aviaTPRuleEffectSet = factory(KT.crates.ClientAdmin);
      
  }(this, function(crate) {
    'use strict';
    
    // var TPRuleEffect = crate.TPRuleEffect;
    var TPRuleCommonEffects = crate.TPRuleCommonEffects;

    /*==========Классы действий корпоративных политик===========*/

    // Пометка предложения при поиске
    var markOfferEffectSearch = TPRuleCommonEffects.markOfferEffect.extend(function() {
      TPRuleCommonEffects.markOfferEffect.apply(this, arguments);
    });

    markOfferEffectSearch.prototype.fieldsList = { // список доступных для пометки полей
      'priorityOffer': {
        name: 'Приоритетное предложение',
        valueType: 'bool'
      },
      'travelPolicyFailCode': {
        name: 'Нарушение корпоративной политики',
        placeholder: 'Код нарушения',
        valueType: 'string'
      },
      'overnightFlight': {
        name: 'Ночной перелет',
        valueType: 'bool'
      },
      'nightTransfer': {
        name: 'Ночная пересадка',
        valueType: 'bool'
      }
    };

    // Пометка предложения при оформлении
    var markOfferEffectIssue = TPRuleCommonEffects.markOfferEffect.extend(function() {
      TPRuleCommonEffects.markOfferEffect.apply(this, arguments);
    });

    markOfferEffectIssue.prototype.fieldsList = { // список доступных для пометки полей
      'travelPolicyFailCode': {
        name: 'Нарушение корпоративной политики',
        placeholder: 'Код нарушения',
        valueType: 'string'
      }
    };

    /*================Набор действий==================*/

    var aviaTPRuleEffectSet = {
      '0': { // поиск
        'deleteNonConditionOffers' : TPRuleCommonEffects.deleteNonConditionOffersEffect,
        'markOffer': markOfferEffectSearch
      },
      '1': { // оформление
        'markOffer': markOfferEffectIssue,
        // 'SetOrderValue': TPRuleCommonEffects.SetOrderValueEffect,
      },
      '2': { // создание услуги
        'SetMinimalPriceValue': TPRuleCommonEffects.SetMinimalPriceValueEffect
      }
    };

    return aviaTPRuleEffectSet;
  }));