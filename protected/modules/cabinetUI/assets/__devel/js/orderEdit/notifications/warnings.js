$.extend(KT.notifications,{
  'bookingFailedNoTourists': {
    type:'warning',
    title:'Не удалось забронировать услугу',
    msg:'Вы еще не добавили туристов в заявку!',
    killtarget:'body',
    timeout:10000
  },
  'serviceSwitchedToManual': {
    type:'warning',
    title:'Услуга переведена в обработку',
    msg:'Заявка обрабатывается менеджером КМП',
    killtarget:'body'
  },
  'saveServiceFailedNoTourists': {
    type:'warning',
    title:'Не удалось сохранить услугу',
    msg:'Вы еще не добавили туристов в заявку!',
    killtarget:'body',
    timeout:10000
  },
  'bookingTermsNotAccepted': {
    type:'warning',
    title:'Вы не можете начать бронирование',
    msg:'Необходимо подтвердить согласие с условиями бронирования',
    killtarget:'body',
    timeout:10000
  },
  'bookingChangeNotSupported': {
    type:'warning',
    title:'Невозможно изменить бронь',
    msg:'Поставщик не поддерживает изменение брони'
  },
  'bookingChangeProhibited': {
    type:'warning',
    title:'Невозможно изменить бронь',
    msg:'Поставщик отказал в изменении брони для данной услуги'
  },
  'notAllTouristsLinked': {
    type:'warning',
    title:'Вы не можете выполнить операцию',
    msg:'Необходимо прикрепить к услуге заявленное число туристов',
    killtarget:'body',
    timeout:10000
  },
  'touristLinkageNotAllowedByDocument': {
    type:'warning',
    title:'Туриста нельзя привязать к услуге',
    msg:'У туриста заканчивается срок действия документа',
    killtarget:'body',
    timeout:10000
  },
  'touristLinkageNotAllowedByAge': {
    type:'warning',
    title:'Данного туриста нельзя привязать к услуге',
    msg:'В эту услугу нельзя добавить еще одного туриста данной возрастной группы',
    killtarget:'body',
    timeout:10000
  },
  'incorrectTouristData': {
    type:'warning',
    title:'Некорретные данные!',
    msg:'Проверьте форму на правильность заполнения',
    killtarget:'body'
  },
  'uploadDocumentFailed': {
    type:'warning',
    title:'Не удалось загрузить документ',
    msg:'Сбой загрузки, попробуйте еще раз',
    ontop:true,
    killtarget:'body'
  },
  'uploadDocumentNotAllowedByFilesize': {
    type:'warning',
    title:'Не удалось загрузить документ',
    msg:'Размер файла превышает допустимое значение',
    ontop:true,
    killtarget:'body'
  },
  'pricesChanged': {
    type:'warning',
    title:'Изменились цены предложения',
    msg:'Для продолжения операции требуется подтверждение согласия с новыми ценами',
    ontop:true,
    killtarget:'body'
  },
  'waitTOSLoading': {
    type:'warning',
    title:'Документ еще загружается',
    msg:'Попробуйте открыть снова через несколько секунд',
    ontop:true,
    killtarget:'body'
  },
  'noManualEditForm': {
    type:'warning',
    title:'Нет формы для редактирования услуги',
    msg:'Для данной услуги не поддерживается ручное редактирование',
    ontop:true,
    killtarget:'body'
  },
  'reservationNumberNotSet': {
    type:'warning',
    title:'Не указан номер брони',
    msg:'Для создания брони необходимо указать номер',
    ontop:true,
    killtarget:'body'
  },
  'ticketNumberNotEntered': {
    type:'warning',
    title:'Не указан номер билета',
    msg:'Введите номер билета',
    ontop:true,
    killtarget:'body'
  },
  'ticketTouristNotSet': {
    type:'warning',
    title:'Не указан турист для создания билета',
    msg:'Укажите туриста',
    ontop:true,
    killtarget:'body'
  },
  'changingTicketNotSelected': {
    type:'warning',
    title:'Не выбран билет для редактирования',
    msg:'Выберите билет',
    ontop:true,
    killtarget:'body'
  },
  'notAllCustomFieldsSet': {
    type:'warning',
    title:'Не все обязательные поля заполнены',
    msg:'',
    ontop:true,
    killtarget:'body'
  },
  'customFieldsRevealed': {
    type:'warning',
    title:'Проверьте данные',
    msg:'У туриста есть незаполненные дополнительные поля',
    ontop:true,
    killtarget:'body'
  },
  'noDocumentsSelected': {
    type:'warning',
    title:'Документы не выбраны',
    msg:'Вы не выбрали ни одного документа для совершения действия',
    killtarget:'body'
  }
});
