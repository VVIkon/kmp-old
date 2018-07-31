$.extend(KT.notifications,{
  'bookingFailedNoTourists': {
    type:'warning',
    title:'Не удалось забронировать услугу',
    msg:'Вы еще не добавили туристов в заявку!',
    killtarget:'body'
  },
  'saveServiceFailedNoTourists': {
    type:'warning',
    title:'Не удалось сохранить услугу',
    msg:'Вы еще не добавили туристов в заявку!',
    killtarget:'body'
  },
  'saveServiceFailedIncorrectLoyality': {
    type:'warning',
    title:'Не удалось сохранить услугу',
    msg:'Некорректные данные программы лояльности',
    killtarget:'body'
  },
  'linkingTouristFailed': {
    type:'error',
    title:'Не удалось сохранить привязку!',
    msg:'',
    killtarget:'body'
  },
  'removingTouristFailed': {
    type:'error',
    title:'Не удалось удалить туриста!',
    msg:'',
    killtarget:'body'
  },
  'bookingFailed': {
    type:'error',
    title:'Бронирование не запустилось',
    msg:'Не удалось запустить процесс бронирования'
  },
  'bookingChangeFailed': {
    type:'error',
    title:'Не удалось изменить данные брони',
    msg:'Не удалось изменить данные брони, обратитесь к менеджеру КМП для уточнения причин'
  },
  'bookingCancelFailed': {
    type:'error',
    title:'Бронь не отменена',
    msg:'Не удалось отменить бронирование, обратитесь к менеджеру КМП для уточнения причин'
  },
  'issuingTicketsFailed': {
    type:'error',
    title:'Выписка билетов не удалась!',
    msg:'',
    killtarget:'body'
  },
  'saveTouristFailed': {
    type:'error',
    title:'Не удалось сохранить туриста',
    msg:'',
    killtarget:'body'
  },
  'loadingDocumentsFailed': {
    type:'error',
    title:'Не удалось загрузить документы',
    msg:'',
    killtarget:'body'
  },
  'loadingHistoryFailed': {
    type:'error',
    title:'Не удалось загрузить историю заявки',
    msg:'',
    killtarget:'body'
  },
  'settingDicountFailed': {
    type:'error',
    title:'Не удалось сохранить скидку',
    msg:''
  },
  'changingServiceDataFailed': {
    type:'error',
    title:'Не удалось изменить данные услуги',
    msg:''
  },
  'changingReservationDataFailed': {
    type:'error',
    title:'Не удалось изменить данные брони',
    msg:''
  },
  'changingTicketsDataFailed': {
    type:'error',
    title:'Не удалось изменить данные билетов',
    msg:''
  },
  'cancellingInvoiceFailed': {
    type:'error',
    title:'Не удалось отменить счет',
    msg:''
  },
  'savingUserFailed': {
    type:'error',
    title:'Не удалось сохранить сотрудника',
    msg:''
  },
  'savingCustomFieldsFailed': {
    type:'error',
    title:'Не удалось сохранить данные услуги',
    msg:''
  },
  'settingServiceToManualFailed': {
    type:'error',
    title:'Не удалось отправить запрос',
    msg:''
  },
  'AddingAdditionalServiceFailed': {
    type:'error',
    title:'Не удалось добавить дополнительную услугу',
    msg:''
  },
  'RemovingAdditionalServiceFailed': {
    type:'error',
    title:'Не удалось удалить дополнительную услугу',
    msg:''
  },
  'checkingServiceStatusFailed': {
    type:'error',
    title:'Не удалось проверить статус услуги',
    msg:''
  },
  'sendingHistoryReportFailed': {
    type:'error',
    title:'Не удалось отправить отчет',
    msg:''
  },
  'sendingDocumentFailed': {
    type:'error',
    title:'Не удалось отправить документ',
    msg:'Документ: {{document}} <br> Причина: {{error}}'
  },
  'offerExpired': {
    type:'error',
    title:'Предложение недоступно',
    msg:'Предложение недоступно, повторите пожалуйста поиск'
  }
});
